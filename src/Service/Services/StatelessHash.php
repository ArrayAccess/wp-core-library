<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Service\Services;

use ArrayAccess\WP\Libraries\Core\Service\Abstracts\AbstractService;
use ArrayAccess\WP\Libraries\Core\Util\Consolidator;
use ArrayAccess\WP\Libraries\Core\Util\Header;
use function array_shift;
use function hash_hmac;
use function max;
use function md5;
use function preg_match;
use function str_contains;

/**
 * Object to help create hashed value, suitable for stateless cookie value
 *
 * This also suitable to create custom hash with id verification
 *
 * Class StatelessSession to create encrypted session based on cookie
 * The cookie value based on hash_hmac() function with sha256 algorithm
 * User id based on: wp logged-in user id or 0 if not logged in
 *
 * Random bytes ($randomString) based on:
 * - random_bytes(16)
 * Hashed value based on:
 * - random hex is md5($randomString) ($random) - 32 chars
 * - Using dechex(user id + hexdec(substr($random, -4))) ($userId)
 * - Using dechex()  of Start time ($startTime) (unix timestamp)
 * - Cookie using session or not ($forever) (longer session time).
 * (1) if longer session time, (0) if not
 * - Using hash_hmac(
 *      'md5',
 *      $userAgent,
 *      AUTH_SALT . SECURE_AUTH_SALT . LOGGED_IN_SALT . NONCE_SALT . $random
 * ) of User agent browser ($agentHash) name type (e.g.: chrome, firefox, safari, etc.)
 *
 * Secret key based on:
 *  hash_hmac(
 *      'sha256',
 *      $random . '-' . $userId . '-' . $agentHash . '-' . $startTime . '-' . $forever,
 *      AUTH_KEY . SECURE_AUTH_KEY . LOGGED_IN_KEY . NONCE_KEY . $random
 * )
 *
 * And the content of cookie is:
 * $secretKey. '-' . $random. '-' . $userId .'-' .$agentHash .'-' .$startTime .'-' .$forever
 */
class StatelessHash extends AbstractService
{
    /**
     * @var string The service name.
     */
    protected string $serviceName = 'statelessHash';

    /**
     * Browser type
     *
     * @var string The browser type
     * @uses Consolidator::getBrowserType
     */
    protected string $browserType;

    /**
     * The cached data
     *
     * @var array<int, array{0: string, 1: string>>
     */
    protected array $cachedData = [];

    /**
     * @inheritdoc
     */
    protected function onConstruct(): void
    {
        $this->description = __(
            'Object to help create hashed value, suitable for stateless cookie value',
            'arrayaccess'
        );
    }

    /**
     * Generate hashed value
     *
     * @param ?int $userId user id (null if not logged in) if null uses current user id
     * @param bool $forever longer session time
     * @param bool $regenerate regenerate cookie value
     * @return string The cookie value
     */
    public function generate(
        ?int $userId = null,
        bool $forever = true,
        bool $regenerate = false
    ): string {
        if ($userId === null) {
            $userId = get_current_user_id();
        }
        $userId = max(0, $userId);
        $foreverInt = $forever ? 1 : 0;
        if (!$regenerate && $userId > 0 && isset($this->cachedData[$userId][$foreverInt])) {
            return $this->cachedData[$userId][$foreverInt];
        }
        $cookieValue = $this->generateHashedValue($userId, $forever);
        if ($userId > 0) {
            $this->cachedData[$userId][$foreverInt] = $cookieValue;
        }
        return $cookieValue;
    }

    /**
     * Generate hashed value from string (e.g.: cookie)
     *
     * @param ?int $userId user id (null if not logged in)
     * @param bool $forever longer session time
     * @return string
     */
    public function generateHashedValue(?int $userId = null, bool $forever = true) : string
    {
        if ($userId === null) {
            $userId = get_current_user_id();
        }
        // minimum user id is 0
        $userId = max(0, $userId);

        // generate random string
        $randomString = Consolidator::randomBytes(16);
        // random hex is md5($random)
        $random = md5($randomString);
        $userAgent = $this->getBrowserType();
        // agent hash is
        // hash_hmac('md5', $userAgent, AUTH_SALT . SECURE_AUTH_SALT . LOGGED_IN_SALT . NONCE_SALT . $random)
        $agentHash = hash_hmac(
            'md5',
            $userAgent,
            AUTH_SALT . SECURE_AUTH_SALT . LOGGED_IN_SALT . NONCE_SALT . $random
        );
        // start time in hex
        $startTimeHex = dechex(time());
        // user id + hexdec(substr($randomHex, -4))
        $userIdCalcHex = dechex($userId + hexdec(substr($random, -4)));
        // if forever, set forever to 1, else 0
        $forever = $forever ? 1 : 0;
        // generate secret key
        $secretKey = hash_hmac(
            'sha256',
            $random . '-' . $userIdCalcHex . '-' . $agentHash . '-' . $startTimeHex . '-' . $forever,
            AUTH_KEY . SECURE_AUTH_KEY . LOGGED_IN_KEY . NONCE_KEY . $random
        );

        // return cookie value
        return $secretKey
            . '-'
            . $random
            . '-'
            . $userIdCalcHex
            . '-'
            . $agentHash
            . '-'
            . $startTimeHex
            . '-'
            . $forever;
    }

    /**
     * Check if cookie value is valid
     *
     * @param string $hashedValue
     * @param string|null $userAgent
     * @return bool
     */
    public function isValid(string $hashedValue, ?string $userAgent = null): bool
    {
        return $this->parse($hashedValue, $userAgent) !== null;
    }

    /**
     * Parse from cookie-hashed value
     * - if expired return null
     * - if invalid validation from secret key & random hex return null
     * - user id (integer) based on $userIdCalcHex - hexdec(substr($randomHex, -4))
     *
     * @param string $hashedValue
     * @param string|null $userAgent if null, will use $_SERVER['HTTP_USER_AGENT']
     * @return ?array{
     *     secretKey: string,
     *     random: string,
     *     agentHash: string,
     *     startTime: int,
     *     forever: bool,
     *     userId: int,
     *     userAgent: string,
     *     user: ?\WP_User
     * }
     * @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public function parse(
        string $hashedValue,
        ?string $userAgent = null
    ) : ?array {
        // parse cookie value
        preg_match(
            '~^([a-f0-9]{64})-([a-f0-9]{32})-([a-f0-9]{1,8})-([a-f0-9]{32})-([a-f0-9]{1,8})-([01])$~i',
            $hashedValue,
            $matches
        );
        // if not match, it's invalid
        if (empty($matches)) {
            return null;
        }
        array_shift($matches);
        // list of matches
        [
            $secretKey,
            $random,
            $userIdCalcHex,
            $agentHash,
            $startTimeHex,
            $forever,
        ] = $matches;
        // get user id from hexdec($userIdCalcHex) - hexdec(substr($randomHex, -4))
        $userId = hexdec($userIdCalcHex) - hexdec(substr($random, -4));
        // user id below zero is invalid
        if ($userId < 0) {
            return null;
        }
        $time = time();
        $startTime = hexdec($startTimeHex);
        // if start time is more than current time, it's invalid
        if ($startTime > $time) {
            return null;
        }
        $sevenDaysSecond = 7 * 24 * 60 * 60;
        $isForever = $forever === '1';
        // expired if not forever and start time + 7 days is less than current time
        if (!$isForever && ($startTime + $sevenDaysSecond) < $time) {
            return null;
        }
        $userAgent = $this->getBrowserTypeFromUserAgent($userAgent);
        $agent = hash_hmac(
            'md5',
            $userAgent,
            AUTH_SALT . SECURE_AUTH_SALT . LOGGED_IN_SALT . NONCE_SALT . $random
        );
        // if agent is not same, it's invalid
        if ($agent !== $agentHash) {
            return null;
        }
        // generate secret key
        $secretKeyCheck = hash_hmac(
            'sha256',
            $random . '-' . $userIdCalcHex . '-' . $agentHash . '-' . $startTimeHex . '-' . $forever,
            AUTH_KEY . SECURE_AUTH_KEY . LOGGED_IN_KEY . NONCE_KEY . $random
        );
        // if secret key is not identical, it's invalid
        if ($secretKey !== $secretKeyCheck) {
            return null;
        }
        $user = null;
        // if user id is more than zero, get user by id
        if ($userId > 0) {
            /**
             * Get WordPress user by id if user id more than zero
             */
            $user = get_user_by('id', $userId);
            // if a user is not found, it's invalid
            if (!$user) {
                return null;
            }
        }
        // return parsed data
        return [
            'secretKey' => $secretKey,
            'random' => $random,
            'agentHash' => $agentHash,
            'startTime' => $startTime,
            'forever' => $isForever,
            'userId' => $userId,
            'userAgent' => $userAgent,
            'user' => $user,
        ];
    }

    /**
     * Get a browser type from user agent
     *
     * @param ?string $userAgent if null, will use $_SERVER['HTTP_USER_AGENT']
     * @return string The browser type
     */
    public function getBrowserTypeFromUserAgent(?string $userAgent = null): string
    {
        $userAgent = $userAgent?:Header::line('User-Agent');
        if (str_contains($userAgent, 'MSIE') || str_contains($userAgent, 'Trident/')) {
            return 'ie';
        }
        if (str_contains($userAgent, 'Firefox')) {
            return 'firefox';
        }
        if (str_contains($userAgent, 'Chrome')) {
            return 'chrome';
        }
        if (str_contains($userAgent, 'Opera')) {
            return 'opera';
        }
        if (str_contains($userAgent, 'Safari')) {
            return 'safari';
        }
        return Consolidator::getBrowserType($userAgent);
    }

    /**
     * Get a browser type
     *
     * @return string The browser type
     */
    public function getBrowserType(): string
    {
        return $this->browserType ??= $this->getBrowserTypeFromUserAgent();
    }
}

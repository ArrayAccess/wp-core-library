<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Service\Services;

use ArrayAccess\WP\Libraries\Core\Service\Abstracts\AbstractService;
use ArrayAccess\WP\Libraries\Core\Util\Consolidator;
use Exception;
use function array_shift;
use function max;
use function preg_match;
use function str_contains;

/**
 * Object to help create stateless cookie value with hashed value
 *
 * This also suitable to create custom hash with id verification
 *
 * Class StatelessSession to create encrypted session based on cookie
 * The cookie value based on hash_hmac() function with sha256 algorithm
 * User id based on: wp logged-in user id or 0 if not logged in
 *
 * Random bytes ($random) based on:
 * - random_bytes(16)
 * Hashed value based on:
 * - random hex is md5($random) ($randomHex) - 32 chars
 * - Using dechex(user id + hexdec(substr($randomHex, -4))) ($userId)
 * - Using md5() of User agent browser ($agent) name type (eg: chrome, firefox, safari, etc.)
 * - Using dechex()  of Start time ($startTime) (unix timestamp)
 * - Cookie using session or not ($forever) (longer session time). 1 if longer session time, 0 if not
 *
 * Secret key based on:
 *  hash_hmac(
 *      'sha256',
 *      $randomHex . '-' . $userId . '-' . $agent . '-' . $startTime . '-' . $forever,
 *      AUTH_SALT . SECURE_AUTH_SALT . LOGGED_IN_SALT . NONCE_SALT . $random
 * )
 *
 * And the content of cookie is:
 * $secretKey. '-' . $randomHex. '-' . $userId .'-' .$agent .'-' .$startTime .'-' .$forever
 */
class StatelessCookie extends AbstractService
{
    /**
     * @var string The service name.
     */
    protected string $serviceName = 'statelessCookie';

    /**
     * Browser type
     *
     * @var string The browser type
     * @uses Consolidator::getBrowserType
     */
    protected string $browserType;

    /**
     * The cached cookie data
     *
     * @var array<int, array{0: string, 1: string>>
     */
    protected array $cookieData = [];

    /**
     * @inheritdoc
     */
    protected function onConstruct(): void
    {
        $this->description = __(
            'Object to help create stateless cookie value with hashed value',
            'arrayaccess'
        );
    }

    /**
     * Generate cookie value
     *
     * @param int $userId user id (0 if not logged in)
     * @param bool $forever longer session time
     * @param bool $regenerate regenerate cookie value
     * @return string The cookie value
     */
    public function generate(
        int $userId = 0,
        bool $forever = true,
        bool $regenerate = false
    ): string {
        $userId = max(0, $userId);
        $foreverInt = $forever ? 1 : 0;
        if (!$regenerate && $userId > 0 && isset($this->cookieData[$userId][$foreverInt])) {
            return $this->cookieData[$userId][$foreverInt];
        }
        $cookieValue = $this->generateHashedValue($userId, $forever);
        if ($userId > 0) {
            $this->cookieData[$userId][$foreverInt] = $cookieValue;
        }
        return $cookieValue;
    }

    /**
     * Generate hashed value from cookie
     *
     * @param ?int $userId user id (null if not logged in)
     * @param bool $forever
     * @return string
     */
    public function generateHashedValue(?int $userId = null, bool $forever = true) : string
    {
        if ($userId === null) {
            $userId = get_current_user_id();
        }
        // minimum user id is 0
        $userId = max(0, $userId);

        // generate hashed cookie
        try {
            $random = random_bytes(16);
        } catch (Exception) {
            // if random bytes thrown an error, generate 16 chars from random string
            $random = '';
            for ($i = 0; $i < 16; $i++) {
                $random .= chr(mt_rand(0, 255));
            }
        }
        // agent hex is md5($agent)
        $agentHex = md5($this->getBrowserType());
        // random hex is md5($random)
        $randomHex = md5($random);
        // start time in hex
        $startTimeHex = dechex(time());
        // user id + hexdec(substr($randomHex, -4))
        $userIdCalcHex = dechex($userId + hexdec(substr($randomHex, -4)));
        // if forever, set forever to 1, else 0
        $forever = $forever ? 1 : 0;
        // generate secret key
        $secretKey = hash_hmac(
            'sha256',
            $randomHex . '-' . $userIdCalcHex . '-' . $agentHex . '-' . $startTimeHex . '-' . $forever,
            AUTH_SALT . SECURE_AUTH_SALT . LOGGED_IN_SALT . NONCE_SALT . $random
        );

        // return cookie value
        return $secretKey
            . '-'
            . $randomHex
            . '-'
            . $userIdCalcHex
            . '-'
            . $agentHex
            . '-'
            . $startTimeHex
            . '-'
            . $forever;
    }

    /**
     * Check if cookie value is valid
     *
     * @param string $cookieValue
     * @param string|null $userAgent
     * @return bool
     */
    public function isValid(string $cookieValue, ?string $userAgent = null): bool
    {
        return $this->parseCookieHashedValue($cookieValue, $userAgent) !== null;
    }

    /**
     * Parse from cookie-hashed value
     * - if expired return null
     * - if invalid validation from secret key & random hex return null
     * - user id (integer) based on $userIdCalcHex - hexdec(substr($randomHex, -4))
     *
     * @param string $cookieValue
     * @param string|null $userAgent if null, will use $_SERVER['HTTP_USER_AGENT']
     * @return ?array{
     *     secretKey: string,
     *     randomHex: string,
     *     userId: int,
     *     user: ?\WP_User,
     *     agentHex: string,
     *     startTime: int,
     *     forever: bool
     * }
     * @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public function parseCookieHashedValue(
        string $cookieValue,
        ?string $userAgent = null
    ) : ?array {
        // parse cookie value
        preg_match(
            '~^([a-f0-9]{64})-([a-f0-9]{32})-([a-f0-9]{1,8})-([a-f0-9]{32})-([a-f0-9]{1,8})-([01])$~i',
            $cookieValue,
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
            $randomHex,
            $userIdCalcHex,
            $agentHex,
            $startTimeHex,
            $forever
        ] = $matches;
        // get user id from hexdec($userIdCalcHex) - hexdec(substr($randomHex, -4))
        $userId = hexdec($userIdCalcHex) - hexdec(substr($randomHex, -4));
        // user id below zero is invalid
        if ($userId < 0) {
            return null;
        }
        $startTime = hexdec($startTimeHex);
        // if start time is more than 1 hour, it's invalid
        if ($startTime < time() - 3600) {
            return null;
        }
        $agent = md5($this->getBrowserTypeFromUserAgent($userAgent));
        // if agent is not same, it's invalid
        if ($agent !== $agentHex) {
            return null;
        }
        $secretKeyCheck = hash_hmac(
            'sha256',
            $randomHex . '-' . $userIdCalcHex . '-' . $agentHex . '-' . $startTimeHex . '-' . $forever,
            AUTH_SALT . SECURE_AUTH_SALT . LOGGED_IN_SALT . NONCE_SALT . $randomHex
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
        return [
            'secretKey' => $secretKey,
            'randomHex' => $randomHex,
            'userId' => $userId,
            'user' => $user,
            'agentHex' => $agentHex,
            'startTime' => $startTime,
            'forever' => $forever === '1',
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
        $userAgent = $userAgent?:($_SERVER['HTTP_USER_AGENT'] ?? '');
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

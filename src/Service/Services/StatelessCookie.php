<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Service\Services;

use ArrayAccess\WP\Libraries\Core\Service\Abstracts\AbstractService;
use ArrayAccess\WP\Libraries\Core\Util\Consolidator;
use Exception;
use function preg_match;
use function str_contains;

/**
 * Copilot completion always use standard of php 8.0
 * Class StatelessSession to create encrypted session based on cookie
 * The cookie value based on hash_hmac() function with sha256 algorithm
 * User id based on: wp logged-in user id or 0 if not logged in
 *
 * Random bytes ($random) based on:
 * - random_bytes(16)
 *
 * Secret key based on:
 * hash_hmac('sha256', $random, AUTH_SALT . SECURE_AUTH_SALT . LOGGED_IN_SALT . NONCE_SALT)
 *
 * Hashed value based on:
 * - random hex is md5($random) ($randomHex) - 32 chars
 * - Using dechex(user id + hexdec(substr($randomHex, -4))) ($userId)
 * - Using md5() of User agent browser ($agent) name type (eg: chrome, firefox, safari, etc.)
 * - Using dechex()  of Start time ($startTime) (unix timestamp)
 * - Cookie using session or not ($forever) (longer session time). 1 if longer session time, 0 if not
 *
 * And the content of cookie is:
 * $secretKey. '-' . $randomHex. '-' . $userId .'-' .$agent .'-' .$startTime .'-' .$forever
 */
class StatelessCookie extends AbstractService
{
    protected string $serviceName = 'statelessCookie';

    protected string $browserType;

    /**
     * @var array<int, array{0: string, 1: string>>
     */
    protected array $cookieData = [];

    /**
     * See Class doc comment for creating cookie value
     */
    public function generateCookieValue(int $userId = 0, bool $forever = true): string
    {
        $foreverInt = $forever ? 1 : 0;
        if ($userId > 0 && isset($this->cookieData[$userId][$foreverInt])) {
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
     * @param int $userId
     * @param bool $forever
     * @return string
     */
    public function generateHashedValue(int $userId = 0, bool $forever = true) : string
    {
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
        $secretKey = hash_hmac(
            'sha256',
            $random,
            AUTH_SALT . SECURE_AUTH_SALT . LOGGED_IN_SALT . NONCE_SALT
        );
        $randomHex = md5($random);
        $agentHex = md5($this->getBrowserType());
        $startTimeHex = dechex(time());
        $userIdCalcHex = dechex($userId + hexdec(substr($randomHex, -4)));
        $forever = $forever ? 1 : 0;
        return $secretKey . '-' . $randomHex
            . '-' . $userIdCalcHex . '-'
            . $agentHex . '-'
            . $startTimeHex . '-' . $forever;
    }

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
     *     agentHex: string,
     *     startTime: int,
     *     forever: bool
     * }
     */
    public function parseCookieHashedValue(
        string $cookieValue,
        ?string $userAgent = null
    ) : ?array {
        preg_match(
            '~^([a-f0-9]{64})-([a-f0-9]{32})-([a-f0-9]{1,8})-([a-f0-9]{32})-([a-f0-9]{1,8})-([01])$~i',
            $cookieValue,
            $matches
        );
        if (empty($matches)) {
            return null;
        }
        [$secretKey, $randomHex, $userIdCalcHex, $agentHex, $startTimeHex, $forever] = $matches;
        $userId = hexdec($userIdCalcHex) - hexdec(substr($randomHex, -4));
        $startTime = hexdec($startTimeHex);
        if ($startTime < time() - 3600) {
            return null;
        }
        $agent = md5($this->getBrowserTypeFromUserAgent($userAgent));
        if ($agent !== $agentHex) {
            return null;
        }
        $secretKeyCheck = hash_hmac(
            'sha256',
            hex2bin($randomHex),
            AUTH_SALT . SECURE_AUTH_SALT . LOGGED_IN_SALT . NONCE_SALT
        );
        if ($secretKey !== $secretKeyCheck) {
            return null;
        }
        return [
            'secretKey' => $secretKey,
            'randomHex' => $randomHex,
            'userId' => $userId,
            'agentHex' => $agentHex,
            'startTime' => $startTime,
            'forever' => $forever === '1',
        ];
    }

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

    public function getBrowserType(): string
    {
        return $this->browserType ??= $this->getBrowserTypeFromUserAgent();
    }
}

<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Util;

use function array_key_exists;

/**
 * Class handles various global variables of
 * $_REQUEST, $_POST, $_GET, $_SERVER, $_COOKIE, $_FILES, $_ENV, $_SESSION, $GLOBALS
 * With getting default value if not exists
 *
 * @link https://www.php.net/manual/en/reserved.variables.php
 */
class Variables
{
    /**
     * Check if key exists in an array
     *
     * @param string $key key to check
     * @param array $array
     * @return bool
     */
    public static function containKey(string $key, array $array): bool
    {
        return array_key_exists($key, $array);
    }

    /**
     * Get $_GET
     *
     * @return array
     */
    public static function gets(): array
    {
        return $_GET??[];
    }

    /**
     * Get $_REQUEST
     *
     * @return array
     */
    public static function requests(): array
    {
        return $_REQUEST??[];
    }

    /**
     * Get $_POST
     *
     * @return array
     */
    public static function posts(): array
    {
        return $_POST??[];
    }

    /**
     * Get $_SERVER
     *
     * @return array
     */
    public static function servers(): array
    {
        return $_SERVER??[];
    }

    /**
     * Get $_COOKIE
     *
     * @return array
     */
    public static function cookies(): array
    {
        return $_COOKIE??[];
    }

    /**
     * Get $_FILES
     *
     * @return array
     */
    public static function files(): array
    {
        return $_FILES??[];
    }

    /**
     * Get $_ENV
     *
     * @return array
     */
    public static function envs(): array
    {
        return $_ENV??[];
    }

    /**
     * Get $_SESSION
     *
     * @return array
     */
    public static function sessions(): array
    {
        return $_SESSION??[];
    }

    /**
     * Get $GLOBALS
     *
     * @return array
     */
    public static function globals(): array
    {
        return $GLOBALS;
    }

    /**
     * Get value from $_GET
     *
     * @param string $key $_GET key
     * @param null $default
     * @param null $found
     * @return mixed
     */
    public static function get(string $key, $default = null, &$found = null): mixed
    {
        $found = self::containKey($key, self::gets());
        return $found ? self::gets()[$key] : $default;
    }

    /**
     * Get value from $_REQUEST
     *
     * @param string $key $_REQUEST key
     * @param $default
     * @param $found
     * @return mixed
     */
    public static function request(string $key, $default = null, &$found = null): mixed
    {
        $found = self::containKey($key, self::requests());
        return $found ? self::requests()[$key] : $default;
    }

    /**
     * Get value from $_POST
     *
     * @param string $key $_POST key
     * @param $default
     * @param $found
     * @return mixed
     */
    public static function post(string $key, $default = null, &$found = null) : mixed
    {
        $found = self::containKey($key, self::posts());
        return $found ? self::posts()[$key] : $default;
    }

    /**
     * Get value from $_SERVER
     *
     * @param string $key $_SERVER key
     * @param $default
     * @param $found
     * @return mixed
     */
    public static function server(string $key, $default = null, &$found = null): mixed
    {
        $found = self::containKey($key, self::servers());
        return $found ? self::servers()[$key] : $default;
    }

    /**
     * Get value from $_COOKIE
     *
     * @param string $key $_COOKIE key
     * @param $default
     * @param $found
     * @return mixed
     */
    public static function cookie(string $key, $default = null, &$found = null): mixed
    {
        $found = self::containKey($key, self::cookies());
        return $found ? self::cookies()[$key] : $default;
    }

    /**
     * @param string $key $_FILES key
     * @param $default
     * @param $found
     * @return mixed
     */
    public static function file(string $key, $default = null, &$found = null): mixed
    {
        $found = self::containKey($key, self::files());
        return $found ? self::files()[$key] : $default;
    }

    /**
     * Get value from $_ENV
     *
     * @param string $key
     * @param $default
     * @param $found
     * @return mixed
     */
    public static function env(string $key, $default = null, &$found = null): mixed
    {
        $found = self::containKey($key, self::envs());
        return $found ? self::envs()[$key] : $default;
    }

    /**
     * Get value from $_SESSION
     *
     * @param string $key $_SESSION key
     * @param $default
     * @param $found
     * @return mixed|null
     */
    public static function session(string $key, $default = null, &$found = null): mixed
    {
        $found = self::containKey($key, self::sessions());
        return $found ? self::sessions()[$key] : $default;
    }

    /**
     * Get value from $GLOBALS
     *
     * @param string $key
     * @param $default
     * @param $found
     * @return mixed|null
     */
    public static function global(string $key, $default = null, &$found = null): mixed
    {
        $found = self::containKey($key, self::globals());
        return $found ? self::globals()[$key] : $default;
    }
}

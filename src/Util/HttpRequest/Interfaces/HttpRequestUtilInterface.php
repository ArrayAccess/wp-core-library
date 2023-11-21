<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Util\HttpRequest\Interfaces;

interface HttpRequestUtilInterface
{
    /**
     * Get all value
     *
     * @return array<string, mixed>
     */
    public static function all() : array;

    /**
     * Check if key exists
     *
     * @param string $key
     * @return bool
     */
    public static function has(string $key) : bool;

    /**
     * Check if value contains, using strict comparison
     *
     * @param string $value
     * @return bool
     */
    public static function contains(string $value) : bool;

    /**
     * Get value by key
     *
     * @param string $key
     * @param $default
     * @param $found
     * @return mixed
     */
    public static function get(string $key, $default = null, &$found = null): mixed;

    /**
     * Get int value, if default numeric will convert into int
     *
     * @param string $key
     * @param $default
     * @param $found
     * @return int|null
     */
    public static function int(string $key, $default = null, &$found = null) : ?int;

    /**
     * Get float value, if default numeric will convert into float
     *
     * @param string $key
     * @param $default
     * @param $found
     * @return float|null
     */
    public static function float(string $key, $default = null, &$found = null) : ?float;

    /**
     * Get numeric value
     *
     * @param string $key
     * @param $default
     * @param $found
     * @return int|string|float|null
     */
    public static function numeric(string $key, $default = null, &$found = null) : int|string|float|null;

    /**
     * Get array value, if value is iterable will return an array
     *
     * @param string $key
     * @param $default
     * @param $found
     * @return array|null
     */
    public static function array(string $key, $default = null, &$found = null) : ?array;

    /**
     * Get string value, if value is scalar (not boolean) will convert into string
     *
     * @param string $key
     * @param $default
     * @param $found
     * @return string|null
     */
    public static function string(string $key, $default = null, &$found = null) : ?string;

    /**
     * Get boolean value
     *
     * @param string $key
     * @param $default
     * @param $found
     * @return bool|null
     */
    public static function bool(string $key, $default = null, &$found = null) : ?bool;

    /**
     * Get object value, if value is array will convert into object
     *
     * @param string $key
     * @param $default
     * @param $found
     * @return object|null
     */
    public static function object(string $key, $default = null, &$found = null) : ?object;

    /**
     * Get json value, if value is json-string will convert into object|array
     *
     * @param string $key
     * @param $default
     * @param $found
     * @param bool $assoc
     * @return object|array|null
     */
    public static function json(string $key, $default = null, &$found = null, bool $assoc = true) : object|array|null;
}

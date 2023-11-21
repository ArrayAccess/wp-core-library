<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Util\HttpRequest\Abstracts;

use ArrayAccess\WP\Libraries\Core\Util\HttpRequest\Interfaces\HttpRequestUtilInterface;
use ArrayAccess\WP\Libraries\Core\Util\Variables;
use function in_array;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_numeric;
use function is_object;
use function is_scalar;
use function is_string;
use function iterator_to_array;
use function json_decode;
use function strtolower;

abstract class AbstractHttpRequestUtil implements HttpRequestUtilInterface
{
    /**
     * @inheritdoc
     */
    public static function get(string $key, $default = null, &$found = null) : mixed
    {
        return Variables::containKey($key, static::all())
            ? static::all()[$key]
            : $default;
    }

    /**
     * @inheritdoc
     */
    public static function has(string $key) : bool
    {
        return Variables::containKey($key, static::all());
    }

    /**
     * @inheritdoc
     */
    public static function contains(string $value) : bool
    {
        return in_array($value, static::all(), true);
    }

    /**
     * @inheritdoc
     */
    public static function int(string $key, $default = null, &$found = null) : ?int
    {
        $found = false;
        $data = static::get($key, null, $founds);
        if ($founds) {
            if (is_string($data)
                && is_numeric($data)
                && !str_contains($data, '.')
            ) {
                $data = (int)$data;
            }
            if (is_int($data)) {
                $found = true;
                return $data;
            }
        }
        return is_int($default) ? $default : null;
    }

    /**
     * @inheritdoc
     */
    public static function float(string $key, $default = null, &$found = null) : ?float
    {
        $found = false;
        $data = static::get($key, null, $founds);
        if ($founds) {
            if (is_string($data) && is_numeric($data)) {
                $data = (float)$data;
            }
            if (is_float($data)) {
                $found = true;
                return $data;
            }
        }
        if (!is_numeric($default)) {
            return null;
        }
        return (float)$default;
    }

    /**
     * @inheritdoc
     */
    public static function numeric(string $key, $default = null, &$found = null) : int|string|float|null
    {
        $found = false;
        $data = static::get($key, null, $founds);
        if ($founds && is_numeric($data)) {
            if (is_string($data)) {
                $data = str_contains($data, '.') ? (float)$data : (int)$data;
            }
            $found = true;
            return $data;
        }
        if (!is_numeric($default)) {
            return null;
        }
        return $default;
    }

    /**
     * @inheritdoc
     */
    public static function array(string $key, $default = null, &$found = null): ?array
    {
        $found = false;
        $data = static::get($key, null, $founds);
        if ($founds && (is_array($data) || is_iterable($data))) {
            $data = !is_array($data) ? iterator_to_array($data) : $data;
            $found = true;
            return $data;
        }
        if (!is_array($default) || !is_iterable($default)) {
            return null;
        }

        /** @noinspection PhpConditionAlreadyCheckedInspection */
        return ! is_array($default) ? iterator_to_array($default) : $default;
    }

    /**
     * @inheritdoc
     */
    public static function string(string $key, $default = null, &$found = null): ?string
    {
        $found = false;
        $data = static::get($key, null, $founds);
        if ($founds && !is_bool($data) && is_scalar($data)) {
            $data = (string)$data;
            $found = true;
            return $data;
        }
        if (!is_string($default) && !is_scalar($default)) {
            return null;
        }
        return (string) $default;
    }

    /**
     * @inheritdoc
     */
    public static function bool(string $key, $default = null, &$found = null): ?bool
    {
        $found = false;
        $data = static::get($key, null, $founds);
        if (is_numeric($data)
            && ($data === 0 || $data === 1 || $data === '0' || $data === '1')
        ) {
            $data = (bool)$data;
        }
        if (is_string($data)
            && ($data = strtolower($data))
            && ($data === 'true' || $data === 'false' || $data === 'yes' || $data === 'no')
        ) {
            $data = $data === 'true';
        }
        if ($founds && is_bool($data)) {
            $found = true;
            return $data;
        }
        if (!is_bool($default)) {
            return null;
        }
        return $default;
    }

    /**
     * @inheritdoc
     */
    public static function object(string $key, $default = null, &$found = null): ?object
    {
        $found = false;
        $data = static::get($key, null, $founds);
        if ($founds && is_object($data)) {
            $found = true;
            return $data;
        }
        if (!is_object($default) && !is_array($default)) {
            return null;
        }
        return (object) $default;
    }

    /**
     * @inheritdoc
     */
    public static function json(string $key, $default = null, &$found = null, bool $assoc = true): object|array|null
    {
        $found = false;
        $data = static::get($key, null, $founds);
        if ($founds && is_string($data)) {
            $data = json_decode($data, $assoc);
            if (is_object($data) || is_array($data)) {
                $found = true;
                return $data;
            }
        }
        if (is_string($default)) {
            $default = json_decode($default, $assoc);
        }
        if (!is_object($default) && !is_array($default)) {
            return null;
        }
        return $default;
    }
}

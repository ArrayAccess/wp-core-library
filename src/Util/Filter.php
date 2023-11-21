<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Util;

use ReflectionClass;
use function array_filter;
use function bccomp;
use function bcdiv;
use function bcmul;
use function checkdnsrr;
use function class_exists;
use function dirname;
use function explode;
use function filter_var;
use function in_array;
use function ini_get;
use function intval;
use function is_array;
use function is_int;
use function is_numeric;
use function is_object;
use function min;
use function number_format;
use function preg_match;
use function preg_replace;
use function str_replace;
use function str_starts_with;
use function strrchr;
use function strtolower;
use function strtoupper;
use function substr;
use function trim;
use const DIRECTORY_SEPARATOR;
use const FILTER_VALIDATE_EMAIL;
use const PHP_SAPI;

/**
 * Class collection for various filtering
 * All methods using static method
 */
class Filter
{
    /**
     * @var array<class-string, class-string> class name cache
     */
    private static array $cachedClasses = [];

    /**
     * Check is valid function name
     *
     * @param string $name
     * @return bool
     */
    public static function isValidFunctionName(string $name): bool
    {
        return (bool)preg_match('~[_a-zA-Z\x80-\xff]+[a-zA-Z0-9_\x80-\xff]*$~', $name);
    }

    /**
     * Check is valid variable name
     *
     * @param string $name
     * @return bool
     */
    public static function isValidVariableName(string $name): bool
    {
        return (bool)preg_match('~[_a-zA-Z\x80-\xff]+[a-zA-Z0-9_\x80-\xff]*$~', $name);
    }

    /**
     * Check is cli
     *
     * @return bool
     */
    public static function isCli(): bool
    {
        return in_array(PHP_SAPI, ['cli', 'phpdbg', 'embed'], true);
    }

    /**
     * Check is windows
     *
     * @return bool
     */
    public static function isWindows(): bool
    {
        return DIRECTORY_SEPARATOR === '\\';
    }

    /**
     * Check is unix
     *
     * @return bool
     */
    public static function isUnix(): bool
    {
        return DIRECTORY_SEPARATOR === '/';
    }

    /**
     * Check if data is (or contains) Binary
     *
     * @param string $str
     * @return bool
     */
    public static function isBinary(string $str): bool
    {
        return (bool) preg_match('~[^\x20-\x7E]~', $str);
    }

    /**
     * Check if data is Base 64
     *
     * @param string $str
     * @return bool
     */
    public static function isBase64(string $str): bool
    {
        return (bool) preg_match(
            '~^(?:[A-Za-z0-9+/]{4})*(?:[A-Za-z0-9+/]{2}==|[A-Za-z0-9+/]{3}=)?$~',
            $str
        );
    }

    /**
     * Check if data is Hex
     *
     * @param string $str
     * @return bool
     */
    public static function isHex(string $str): bool
    {
        return !preg_match('~[^a-fA-F0-9]+~', $str);
    }

    /**
     * Check if data is UUID, uuid checked the version from 1 to 5
     *
     * @param string $str UUID
     * @return bool true if valid UUID
     */
    public static function isUUID(string $str): bool
    {
        return UUID::isValid($str);
    }

    /**
     * Filter the UUID
     *
     * @param string $str
     * @return ?string string if valid UUID
     */
    public static function uuid(string $str): ?string
    {
        return self::isUUID($str) ? $str : null;
    }

    /**
     * Filter the relative path
     *
     * @param string $path
     * @return ?string string if valid relative path
     */
    public static function relativePath(string $path): ?string
    {
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        if (self::isWindows()) {
            preg_match('~^([A-Za-z]+)(:\\\.*)$~', $path, $match);
            if (empty($match)) {
                return null;
            }
            return strtoupper($match[1]) . $match[2];
        }
        return str_starts_with($path, '/') ? $path : null;
    }

    /**
     * Filter the class name
     *
     * @param class-string|object $className
     * @return ?string string if valid class name
     */
    public static function className(string|object $className): ?string
    {
        if (is_object($className)) {
            return $className::class;
        }
        preg_match(
            '~^\\\?([A-Z-a-z_\x80-\xff]+[A-Z-a-z_0-9\x80-\xff]*(?:\\\[A-Z-a-z_\x80-\xff]+[A-Z-a-z_0-9\x80-\xff]*)*)$~',
            $className,
            $match
        );
        if (empty($match)) {
            return null;
        }
        $lowerClassName = strtolower($match[1]);
        if (isset(self::$cachedClasses[$lowerClassName])) {
            return self::$cachedClasses[$lowerClassName];
        }
        if (class_exists($match[1])) {
            return self::$cachedClasses[$lowerClassName] = (new ReflectionClass($match[1]))->getName();
        }

        return $match[1];
    }

    /**
     * Get class short name
     *
     * @param string|object $fullClassName
     * @return ?string string if valid class name
     */
    public static function classShortName(
        string|object $fullClassName
    ): ?string {
        $fullClassName = self::className($fullClassName);
        if (!$fullClassName) {
            return '';
        }
        return str_contains($fullClassName, '\\')
            ? substr(
                strrchr($fullClassName, '\\'),
                1
            ) : $fullClassName;
    }

    /**
     * Filter the namespace
     *
     * @param string|object $fullClassName Full Class Name
     * @return ?string string if valid namespace
     */
    public static function namespace(string|object $fullClassName): ?string
    {
        $className = Filter::className($fullClassName);
        if (!$className) {
            return null;
        }
        $className = str_replace('\\', '/', $className);
        return str_replace('/', '\\', dirname($className));
    }

    /**
     * Filter email address
     *
     * @param string $email Email Address
     * @param bool $allowIP Allow IP Address
     * @param bool $validateDNSSR Validate DNS SR
     * @return string|false Return false if invalid email address
     */
    public static function email(
        string $email,
        bool $allowIP = true,
        bool $validateDNSSR = false
    ): false|string {
        $email = trim(strtolower($email));
        $explode = explode('@', $email);
        // validate email address & domain
        if (count($explode) !== 2
            // Domain must be contained Period, and it will be a real email address
            || !str_contains($explode[1], '.')
            // could not use email with double-period and hyphens
            || preg_match('~[.]{2,}|[\-_]{3,}~', $explode[0])
            // check validate email
            || !preg_match('~^[a-zA-Z0-9]+(?:[a-zA-Z0-9._\-]?[a-zA-Z0-9]+)?$~', $explode[0])
        ) {
            return false;
        }

        // filtering Email Address
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $isIP = IP::version($explode[1]) !== null;
        if ($isIP) {
            return $allowIP ? $email : false;
        }

        // if validate DNS
        if ($validateDNSSR === true && !@checkdnsrr($explode[1])) {
            return false;
        }

        return $email;
    }

    /**
     * Convert number of bytes the largest unit bytes will fit into.
     *
     * It is easier to read 1 kB than 1024 bytes and 1 MB than 1048576 bytes.
     * Convert the number of bytes to human-readable number by taking the number of that unit
     * that the bytes will go into it. Supports TB value.
     *
     * Please note that integers in PHP are limited to 32 bits, unless they are on
     * 64-bit architecture, then they have 64-bit size. If you need to place the
     * larger size, then what PHP integer type will hold, then use a string. It will
     * be converted to a double, which should always have 64-bit length.
     *
     * Technically the correct unit names for powers of 1024 are KiB, MiB etc.
     *
     * @param int|float|numeric-string $bytes Number of bytes. Note max integer size for integers.
     * @param int $decimals Optional. Precision of number of decimal places. Default 0.
     * @param string $decimalPoint Optional decimal point
     * @param string $thousandSeparator Optional a thousand separator
     * @param bool $removeZero if decimal contain zero, remove it
     *
     * @return string size unit
     */
    public static function formatSize(
        int|float|string $bytes,
        int $decimals = 0,
        string $decimalPoint = '.',
        string $thousandSeparator = ',',
        bool $removeZero = true
    ): string {
        // if not numeric return 0 B
        if (!is_numeric($bytes)) {
            return '0 B';
        }
        $quanta = [
            // ========================= Origin ====
            'YB' => '1208925819614629174706176',  // pow( 1024, 8)
            'ZB' => '1180591620717411303424',  // pow( 1024, 7) << bigger than PHP_INT_MAX is 9223372036854775807
            'EB' => '1152921504606846976',  // pow( 1024, 6)
            'PB' => 1125899906842624,  // pow( 1024, 5)
            'TB' => 1099511627776,  // pow( 1024, 4)
            'GB' => 1073741824,     // pow( 1024, 3)
            'MB' => 1048576,        // pow( 1024, 2)
            'KB' => 1024,           // pow( 1024, 1)
            'B' => 1,              // 1
        ];

        /**
         * Check and did
         */
        $currentUnit = 'B';
        foreach ($quanta as $unit => $mag) {
            $real = bccomp((string) $mag, (string) $bytes);
            if ($real === 1) {
                $result = number_format(
                    (float)bcdiv((string)$bytes, (string)$mag),
                    $decimals,
                    $decimalPoint,
                    $thousandSeparator
                );
                $currentUnit = $unit;
                break;
            }
        }

        $result = $result ?? number_format(
            $bytes,
            $decimals,
            $decimalPoint,
            $thousandSeparator
        );
        if ($removeZero) {
            $result = preg_replace('~\.0+$~', '', $result);
        }
        return "$result $currentUnit";
    }

    /**
     * Convert number of unit just for Ki(not kilo) metric based on 1024 (binary unit)
     *
     * @param string $size number with unit name 10M or 10MB
     * @return int|numeric-string integer if less than PHP_INT_MAX, string if bigger than PHP_INT_MAX
     */
    public static function byteSize(string $size): int|string
    {
        $size = trim($size) ?: 0;
        if (!$size) {
            return 0;
        }

        $intMax = (string) PHP_INT_MAX;
        $size = (string) intval($size);

        // get size unit (MB = MiB = MIB = mib) case-insensitive
        // invalid format will return exponent of 1
        preg_match(
            '~[0-9]\s*([yzeptgmk]i?b|[yzeptgmkb])$~',
            strtolower($size),
            $match
        );
        // patch tolerant
        $multiplication = (match ($match[1] ?? null) {
                'y', 'yb' => '1208925819614629174706176', // yottabyte
                'z', 'zb' => '1180591620717411303424', // zettabyte << bigger than PHP_INT_MAX is 9223372036854775807
                'e', 'eb' => '1152921504606846976', // exabyte
                'p', 'pb' => '1125899906842624', // petabyte
                't', 'tb' => '1099511627776', // terabyte
                'g', 'gb' => '1073741824', // gigabyte
                'm', 'mb' => '1048576', // megabyte
                'k', 'kb' => '1024', // kilobyte
                default => '1' // byte
        });
        // if size is bigger than PHP_INT_MAX, return string
        $realSize = bcmul($size, $multiplication);
        $compare = bccomp($realSize, $intMax);
        return $compare === 1 ? $realSize : intval($realSize);
    }

    /**
     * Get max upload size from php.ini setting (init_set())
     *
     * @return int max upload size in bytes
     */
    public static function maxUploadSize(): int
    {
        $data = [
            self::byteSize(ini_get('post_max_size')), // post_max_size must be bigger than upload_max_filesize
            self::byteSize(ini_get('upload_max_filesize')), // upload_max_filesize
            (self::byteSize(ini_get('memory_limit')) - 2048), // memory_limit - 2048
        ];
        // remove if data less or equals 0
        $data = array_filter($data, fn ($v) => $v > 0);
        // getting lowest value
        return min($data);
    }

    /*!
     * ------------------------------------------------------------
     * SECTION FOR METHODS THAT FILTERING VALUE WITH DATA TYPE & ADD DEFAULT
     * The DataType / dataType is dataType
     * Method prefix with should(DataType)(mixed $arg, datatype $default = default, &$valid = null): datatype
     * ------------------------------------------------------------
     */

    /**
     * Filter the callable value
     *
     * @param mixed $arg the argument
     * @param callable|null $default the default callable value (null)
     * @param $valid
     * @return ?callable the callable value or null
     */
    public static function shouldCallable(mixed $arg, callable $default = null, &$valid = null): ?callable
    {
        $valid = is_callable($arg);
        return $valid ? $arg : $default;
    }

    /**
     * Filter the string value
     *
     * @param mixed $arg the argument
     * @param string|null $default the default value (null)
     * @param $valid
     * @return string|null the value
     */
    public static function shouldStringOrNull(mixed $arg, ?string $default = null, &$valid = null): ?string
    {
        $valid = is_string($arg) || $arg === null;
        return $valid ? $arg : $default;
    }

    /**
     * Filter the boolean value
     *
     * @param mixed $arg the argument
     * @param string $default the default value ('')
     * @param $valid
     *
     * @return string the string value
     */
    public static function shouldString(mixed $arg, string $default = '', &$valid = null): string
    {
        $valid = is_string($arg);
        return $valid ? $arg : $default;
    }

    /**
     * Filter the boolean value
     *
     * @param mixed $arg the argument
     * @param bool $default the default value (false)
     * @param $valid
     *
     * @return bool the boolean value
     */
    public static function shouldBoolean(mixed $arg, bool $default = false, &$valid = null): bool
    {
        $valid = is_bool($arg);
        return $valid ? $arg : $default;
    }

    /**
     * Filter the integer value
     *
     * @param mixed $arg the argument
     * @param int $default the default value (0)
     * @param $valid
     * @return int
     */
    public static function shouldInteger(mixed $arg, int $default = 0, &$valid = null): int
    {
        $valid = is_int($arg);
        return $valid ? $arg : $default;
    }

    /**
     * Filter the array value
     *
     * @param mixed $arg
     * @param array $default
     * @param $valid
     * @return array
     */
    public static function shouldArray(mixed $arg, array $default = [], &$valid = null): array
    {
        $valid = is_array($arg);
        return $valid ? $arg : $default;
    }

    /**
     * Filter the iterable value
     *
     * @param mixed $arg the argument
     * @param iterable $default the default value ([])
     * @param $valid
     * @return iterable the iterable value
     */
    public static function shouldIterable(mixed $arg, iterable $default = [], &$valid = null): iterable
    {
        $valid = is_array($arg) || is_iterable($arg);
        return $valid ? $arg : $default;
    }

    /**
     * Filter the float value
     *
     * @param mixed $arg the argument
     * @param float $default the default value (0.0)
     * @param $valid
     * @return float the float value
     */
    public static function shouldFloat(mixed $arg, float $default = 0.0, &$valid = null): float
    {
        $valid = is_float($arg);
        return $valid ? $arg : $default;
    }

    /**
     * Filter the numeric value
     *
     * @param mixed $arg the argument
     * @param float|int $default the default value (0)
     * @param $valid
     * @return float|int|numeric-string the numeric value
     */
    public static function shouldNumeric(mixed $arg, float|int $default = 0, &$valid = null): float|int|string
    {
        $valid = is_numeric($arg);
        return $valid ? $arg : $default;
    }

    /**
     * Filter the object value
     *
     * @template T of object
     * @psalm-param T $arg the argument
     * @param mixed $arg
     * @param class-string $class the class name to check
     * @param $valid
     * @psalm-return ?T
     * @return ?object
     */
    public static function shouldInstanceOf(mixed $arg, string $class, &$valid = null): ?object
    {
        $valid = $arg instanceof $class;
        return $valid ? $arg : null;
    }

    /**
     * Filter should same with given value
     *
     * @param mixed $arg the argument
     * @param mixed $value the expected value
     * @param $valid
     * @return mixed
     */
    public static function shouldSame(mixed $arg, mixed $value, &$valid = null): mixed
    {
        $valid = $arg === $value;
        return $valid ? $arg : $value;
    }

    /**
     * Filter should equal with given value
     *
     * @param mixed $arg the argument
     * @param mixed $value the expected value
     * @param $valid
     * @return mixed
     */
    public static function shouldEquals(mixed $arg, mixed $value, &$valid = null): mixed
    {
        // phpcs:ignore SlevomatCodingStandard.Operators.DisallowEqualOperators.DisallowedEqualOperator
        $valid = $arg == $value;
        return $valid ? $arg : $value;
    }

    /**
     * Filter the argument should string
     *
     * @param ...$args
     * @return bool|array false if not string
     */
    public static function shouldStrings(...$args) : array|false
    {
        if ([] === $args) {
            return false;
        }
        foreach ($args as $arg) {
            if (!is_string($arg)) {
                return false;
            }
        }
        return $args;
    }

    /**
     * Filter the argument should boolean
     *
     * @param ...$args
     * @return bool|array false if not boolean
     */
    public static function shouldIntegers(...$args) : array|false
    {
        if ([] === $args) {
            return false;
        }
        foreach ($args as $arg) {
            if (!is_int($arg)) {
                return false;
            }
        }
        return $args;
    }

    /**
     * Filter the argument should float
     *
     * @param ...$args
     * @return bool|array false if not boolean
     */
    public static function shouldFloats(...$args) : array|false
    {
        if ([] === $args) {
            return false;
        }
        foreach ($args as $arg) {
            if (!is_float($arg)) {
                return false;
            }
        }
        return $args;
    }

    /**
     * Filter the argument should numeric
     *
     * @param ...$args
     * @return bool|array false if not numeric
     */
    public static function shouldNumerics(...$args) : array|false
    {
        if ([] === $args) {
            return false;
        }
        foreach ($args as $arg) {
            if (!is_numeric($arg)) {
                return false;
            }
        }
        return $args;
    }

    /**
     * Filter the argument should boolean
     *
     * @param ...$args
     * @return bool|array false if not boolean
     */
    public static function shouldBooleans(...$args) : array|false
    {
        if ([] === $args) {
            return false;
        }
        foreach ($args as $arg) {
            if (!is_bool($arg)) {
                return false;
            }
        }
        return $args;
    }

    /**
     * Filter the argument should object
     *
     * @param ...$args
     * @return bool|array false if not object
     */
    public static function shouldArrays(...$args) : array|false
    {
        if ([] === $args) {
            return false;
        }
        foreach ($args as $arg) {
            if (!is_array($arg)) {
                return false;
            }
        }
        return $args;
    }

    /**
     * Filter the argument should iterable
     *
     * @param ...$args
     * @return bool|array false if not iterable
     */
    public static function shouldIterables(...$args) : array|false
    {
        if ([] === $args) {
            return false;
        }
        foreach ($args as $arg) {
            if (is_array($arg) || is_iterable($arg)) {
                continue;
            }
            return false;
        }
        return $args;
    }

    /**
     * Filter the argument should object
     *
     * @param ...$args
     * @return bool|array false if not object
     */
    public static function shouldObjects(...$args) : array|false
    {
        if ([] === $args) {
            return false;
        }
        foreach ($args as $arg) {
            if (!is_object($arg)) {
                return false;
            }
        }
        return $args;
    }

    /**
     * Filter the argument should object instance of the given class
     *
     * @param class-string $class the class name to check
     * @param ...$args
     * @return bool|array false if not object
     */
    public static function shouldInstanceOfs(string $class, ...$args) : array|false
    {
        if ([] === $args) {
            return false;
        }
        foreach ($args as $arg) {
            if (!$arg instanceof $class) {
                return false;
            }
        }
        return $args;
    }

    /**
     * Filter the argument should string or null
     *
     * @param ...$args
     * @return bool|array false if not string or null
     */
    public static function shouldStringOrNulls(...$args) : array|false
    {
        if ([] === $args) {
            return false;
        }

        foreach ($args as $arg) {
            if (!is_string($arg) && $arg !== null) {
                return false;
            }
        }
        return $args;
    }

    /**
     * The arguments should callable
     *
     * @param ...$args
     * @return bool|array false if not callable
     */
    public static function shouldCallables(...$args) : array|false
    {
        if ([] === $args) {
            return false;
        }
        foreach ($args as $arg) {
            if (!is_callable($arg)) {
                return false;
            }
        }
        return $args;
    }
}

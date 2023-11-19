<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Util;

use Closure;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionObject;
use RuntimeException;
use function array_key_exists;
use function class_exists;
use function doubleval;
use function explode;
use function file_exists;
use function get_class;
use function implode;
use function in_array;
use function ini_get;
use function intval;
use function is_array;
use function is_callable;
use function is_dir;
use function is_file;
use function is_int;
use function is_numeric;
use function is_object;
use function is_readable;
use function is_string;
use function ltrim;
use function min;
use function number_format;
use function preg_match;
use function preg_replace;
use function realpath;
use function restore_error_handler;
use function set_error_handler;
use function spl_autoload_register;
use function spl_autoload_unregister;
use function str_contains;
use function str_replace;
use function str_starts_with;
use function strlen;
use function strrchr;
use function strtolower;
use function substr;
use function trim;
use function urlencode;
use const DIRECTORY_SEPARATOR;
use const PHP_SAPI;

class Consolidator
{
    public const BLACKLISTED_NAME = [
        'array',
        'iterable',
        'string',
        'false',
        'bool',
        'boolean',
        'object',
        'default',
        'switch',
        'case',
        'if',
        'elseif',
        'else',
        'while',
        'for',
        'foreach',
        'match',
        'fn',
        'function',
        'const',
        'class',
        'float',
        'and',
        'null',
        'or',
        'private',
        'public',
        'protected',
        'final',
        'abstract',
        'as',
        'break',
        'continue',

        'abstract',
        'and',
        'array',
        'as',
        'break',
        'callable',
        'case',
        'catch',
        'class',
        'clone',
        'const',
        'continue',
        'declare',
        'default',
        'die',
        'do',
        'echo',
        'else',
        'elseif',
        'empty',
        'enddeclare	',
        'endfor	',
        'endforeach	',
        'endif',
        'endswitch',
        'endwhile',
        'eval',
        'exit',
        'extends',
        'final',
        'finally',
        'fn',
        'for',
        'foreach',
        'function',
        'global',
        'goto',
        'if',
        'implements',
        'include',
        'include_once',
        'instanceof',
        'insteadof',
        'interface',
        'isset',
        'list',
        'match',
        'namespace',
        'new',
        'or',
        'print',
        'private',
        'protected',
        'public',
        'readonly',
        'require',
        'require_once',
        'return',
        'static',
        'self',
        'parent',
        // reserved
        '__halt_compiler',
        'switch',
        'throw',
        'trait',
        'try',
        'unset',
        'use',
        'var',
        'while',
        'xor',
        'yield',
        'list',
    ];

    public static function allowedClassName(string $className): bool
    {
        if (!self::isValidClassName($className)) {
            return false;
        }
        foreach (explode('\\', $className) as $className) {
            if (in_array(strtolower($className), self::BLACKLISTED_NAME)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Build query string
     *
     * @param $data
     * @param string|null $prefix
     * @param string|null $sep
     * @param string $key
     * @param bool $urlEncode
     * @return string
     */
    public static function buildQuery(
        $data,
        string $prefix = null,
        string $sep = null,
        string $key = '',
        bool $urlEncode = true
    ): string {
        $ret = [];

        foreach ((array)$data as $k => $v) {
            if ($urlEncode) {
                $k = urlencode((string)$k);
            }
            if (is_int($k) && null !== $prefix) {
                $k = $prefix . $k;
            }
            if (!empty($key)) {
                $k = $key . '%5B' . $k . '%5D';
            }
            if (null === $v) {
                continue;
            } elseif (false === $v) {
                $v = '0';
            }

            if (is_array($v) || is_object($v)) {
                $ret[] = self::buildQuery($v, '', $sep, $k, $urlEncode);
            } elseif ($urlEncode) {
                $ret[] = $k . '=' . urlencode((string)$v);
            } else {
                $ret[] = $k . '=' . $v;
            }
        }

        if (null === $sep) {
            $sep = ini_get('arg_separator.output');
        }

        return implode($sep, $ret);
    }

    /**
     * Call the callable with reduction of error
     *
     * @param callable $callback
     * @param $errNo
     * @param $errStr
     * @param $errFile
     * @param $errLine
     * @param $errContext
     *
     * @return mixed
     */
    public static function callbackReduceError(
        callable $callback,
        &$errNo = null,
        &$errStr = null,
        &$errFile = null,
        &$errLine = null,
        &$errContext = null
    ): mixed {
        set_error_handler(static function (
            $no,
            $str,
            $file,
            $line,
            $c = null
        ) use (
            &$errNo,
            &$errStr,
            &$errFile,
            &$errLine,
            &$errContext
        ) {
            $errNo = $no;
            $errStr = $str;
            $errFile = $file;
            $errLine = $line;
            $errContext = $c;
        });
        $result = $callback();
        restore_error_handler();

        return $result;
    }

    /**
     * Call the callable with hide the error
     *
     * @param callable $callback
     * @param ...$args
     * @return mixed
     */
    public static function callNoError(callable $callback, ...$args): mixed
    {
        set_error_handler(static fn() => null);
        try {
            return $callback(...$args);
        } finally {
            restore_error_handler();
        }
    }

    public static function namespace(string|object $fullClassName): string|false
    {
        if (is_object($fullClassName)) {
            return (new ReflectionObject($fullClassName))->getNamespaceName();
        }
        if (!self::isValidClassName($fullClassName)) {
            return false;
        }
        $className = ltrim($fullClassName, '\\');
        return preg_replace('~^(.+)?\\\[^\\\]+$~', '$1', $className);
    }

    /**
     * Check is valid class name
     *
     * @param string $className
     * @return bool
     */
    public static function isValidClassName(string $className): bool
    {
        return (bool)preg_match(
            '~^\\\?[A-Z-a-z_\x80-\xff]+[A-Z-a-z_0-9\x80-\xff]*(?:\\\[A-Z-a-z_\x80-\xff]+[A-Z-a-z_0-9\x80-\xff]*)*$~',
            $className
        );
    }

    /**
     * Get class short name
     *
     * @param string|object $fullClassName
     * @param bool $real
     * @return string
     */
    public static function classShortName(
        string|object $fullClassName,
        bool $real = false
    ): string {
        if (is_object($fullClassName)) {
            if ($real) {
                return (new ReflectionObject($fullClassName))->getShortName();
            }
            $fullClassName = $fullClassName::class;
        } elseif ($real && class_exists($fullClassName)) {
            return (new ReflectionClass($fullClassName))->getShortName();
        }

        return str_contains($fullClassName, '\\')
            ? substr(
                strrchr($fullClassName, '\\'),
                1
            ) : $fullClassName;
    }

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
     * Is relative path
     *
     * @param string $path
     * @return bool
     */
    public static function isRelativePath(string $path): bool
    {
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        return (bool)(self::isWindows() ? preg_match('~^[A-Za-z]+:\\\~', $path) : preg_match('~^/~', $path));
    }

    /**
     * Doing require file with no $this object
     *
     * @param string $file
     * @param bool $once
     * @param array $arguments
     * @param null $found
     *
     * @return mixed
     */
    public static function requireNull(
        string $file,
        bool $once = false,
        array $arguments = [],
        &$found = null
    ): mixed {
        $found = is_file($file) && is_readable($file);

        return $found
            ? (static fn($arguments) => $once ? require_once $file : require $file)->bindTo(null)($arguments)
            : false;
    }

    /**
     * Doing includes file with no $this object
     *
     * @param string $file
     * @param bool $once
     * @param $found
     *
     * @return mixed
     */
    public static function includeNull(string $file, bool $once = false, &$found = null): mixed
    {
        $found = is_file($file) && is_readable($file);
        return $found
            ? (static fn() => $once ? include_once $file : include $file)->bindTo(null)()
            : false;
    }

    /**
     * Convert notation value
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public static function convertNotationValue(mixed $value): mixed
    {
        $annotator = [
            'true' => true,
            'TRUE' => true,
            'false' => false,
            'FALSE' => false,
            'NULL' => null,
            'null' => null,
        ];
        if (is_string($value)) {
            if (is_numeric($value)) {
                return str_contains($value, '.') ? (float)$value : (int)$value;
            }
            return array_key_exists($value, $annotator) ? $annotator[$value] : $value;
        }

        if (is_array($value)) {
            foreach ($value as $key => $val) {
                $value[$key] = self::convertNotationValue($val);
            }
        }

        return $value;
    }

    /**
     * Object binding suitable to call private method
     *
     * @param Closure $closure
     * @param object $object
     *
     * @return Closure
     * @throws RuntimeException|ReflectionException
     */
    public static function objectBinding(
        Closure $closure,
        object $object
    ): Closure {
        $reflectedClosure = new ReflectionFunction($closure);
        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $isBindable = (
            !$reflectedClosure->isStatic()
            || !$reflectedClosure->getClosureScopeClass()
            || $reflectedClosure->getClosureThis() !== null
        );
        if (!$isBindable) {
            throw new RuntimeException(
                'Cannot bind an instance to a static closure.'
            );
        }

        return $closure->bindTo($object, get_class($object));
    }

    /**
     * Call object binding
     *
     * @param Closure $closure
     * @param object $object
     * @param ...$args
     *
     * @return mixed
     * @throws RuntimeException|ReflectionException
     */
    public static function callObjectBinding(Closure $closure, object $object, ...$args): mixed
    {
        return self::objectBinding($closure, $object)(...$args);
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
     * @param int|float $bytes Number of bytes. Note max integer size for integers.
     * @param int $decimals Optional. Precision of number of decimal places. Default 0.
     * @param string $decimalPoint Optional decimal point
     * @param string $thousandSeparator Optional a thousand separator
     * @param bool $removeZero if decimal contain zero, remove it
     *
     * @return string size unit
     */
    public static function sizeFormat(
        int|float $bytes,
        int $decimals = 0,
        string $decimalPoint = '.',
        string $thousandSeparator = ',',
        bool $removeZero = true
    ): string {
        $quanta = [
            // ========================= Origin ====
            // 'YB' => 1208925819614629174706176,  // pow( 1024, 8)
            // 'ZB' => 1180591620717411303424,  // pow( 1024, 7) << bigger than PHP_INT_MAX is 9223372036854775807
            'EB' => 1152921504606846976,  // pow( 1024, 6)
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
            if (doubleval($bytes) >= $mag) {
                $result = number_format(
                    ($bytes / $mag),
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
     * @return int
     */
    public static function returnBytes(string $size): int
    {
        $size = trim($size) ?: 0;
        if (!$size) {
            return 0;
        }
        // get size unit (MB = MiB = MIB = mib) case-insensitive
        // invalid format will return exponent of 1
        preg_match(
            '~[0-9]\s*([yzeptgmk]i?b|[yzeptgmkb])$~',
            strtolower($size),
            $match
        );
        // patch tolerant
        return intval($size) * (match ($match[1] ?? null) {
                'y', 'yb' => 1208925819614629174706176, // yottabyte
                'z', 'zb' => 1180591620717411303424, // zettabyte << bigger than PHP_INT_MAX is 9223372036854775807
                'e', 'eb' => 1152921504606846976, // exabyte
                'p', 'pb' => 1125899906842624, // petabyte
                't', 'tb' => 1099511627776, // terabyte
                'g', 'gb' => 1073741824, // gigabyte
                'm', 'mb' => 1048576, // megabyte
                'k', 'kb' => 1024, // kilobyte
                default => 1 // byte
        });
    }

    /**
     * @return int
     */
    public static function getMaxUploadSize(): int
    {
        $data = [
            self::returnBytes(ini_get('post_max_size')),
            self::returnBytes(ini_get('upload_max_filesize')),
            (self::returnBytes(ini_get('memory_limit')) - 2048),
        ];
        foreach ($data as $key => $v) {
            if ($v <= 0) {
                unset($data[$key]);
            }
        }

        return min($data);
    }

    /**
     * Check if data is (or contains) Binary
     *
     * @param string $str
     * @return bool
     */
    public static function isBinary(string $str): bool
    {
        return preg_match('~[^\x20-\x7E]~', $str) > 0;
    }

    /**
     * Check if data is Base 64
     *
     * @param string $str
     * @return bool
     */
    public static function isBase64(string $str): bool
    {
        return preg_match(
            '~^(?:[A-Za-z0-9+/]{4})*(?:[A-Za-z0-9+/]{2}==|[A-Za-z0-9+/]{3}=)?$~',
            $str
        ) > 0;
    }

    /**
     * Filter email address
     *
     * @param string $email Email Address
     * @param bool $allowIP Allow IP Address
     * @param bool $validateDNSSR Validate DNS SR
     * @return string|false Return false if invalid email address
     */
    public static function filterEmail(
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

    public static function getBrowserType(string $userAgent): string
    {
        $agent = strtolower($userAgent);
        $browser = 'unknown';
        if (str_contains($agent, 'msie')) {
            $browser = 'ie';
        } elseif (str_contains($agent, 'wget')) {
            $browser = 'wget';
        } elseif (str_contains($agent, 'curl')) {
            $browser = 'curl';
        } elseif (str_contains($agent, 'lynx')) {
            $browser = 'lynx';
        } elseif (str_contains($agent, 'links')) {
            $browser = 'links';
        } elseif (str_contains($agent, 'bot')) {
            $browser = 'bot';
        } elseif (str_contains($agent, 'spider')) {
            $browser = 'spider';
        } elseif (str_contains($agent, 'crawler')) {
            $browser = 'crawler';
        } elseif (str_contains($agent, 'rss')) {
            $browser = 'rss';
        } elseif (str_contains($agent, 'feed')) {
            $browser = 'feed';
        } elseif (str_contains($agent, 'archive')) {
            $browser = 'archive';
        } elseif (str_contains($agent, 'appengine-google')) {
            $browser = 'appengine-google';
        } elseif (str_contains($agent, 'mediapartners-google')) {
            $browser = 'mediapartners-google';
        } elseif (str_contains($agent, 'googlebot')) {
            $browser = 'googlebot';
        } elseif (str_contains($agent, 'yahoo')) {
            $browser = 'yahoo';
        } elseif (str_contains($agent, 'msn')) {
            $browser = 'msn';
        } elseif (str_contains($agent, 'baidu')) {
            $browser = 'baidu';
        } elseif (str_contains($agent, 'bing')) {
            $browser = 'bing';
        } elseif (str_contains($agent, 'ask')) {
            $browser = 'ask';
        } elseif (str_contains($agent, 'duckduckgo')) {
            $browser = 'duckduckgo';
        } elseif (str_contains($agent, 'yandex')) {
            $browser = 'yandex';
        } elseif (str_contains($agent, 'aol')) {
            $browser = 'aol';
        } elseif (str_contains($agent, 'lycos')) {
            $browser = 'lycos';
        } elseif (str_contains($agent, 'excite')) {
            $browser = 'excite';
        } elseif (str_contains($agent, 'altavista')) {
            $browser = 'altavista';
        } elseif (str_contains($agent, 'edge')) {
            $browser = 'edge';
        } elseif (str_contains($agent, 'firefox')) {
            $browser = 'firefox';
        } elseif (str_contains($agent, 'chrome')) {
            $browser = 'chrome';
        } elseif (str_contains($agent, 'safari')) {
            $browser = 'safari';
        } elseif (str_contains($agent, 'opera')) {
            $browser = 'opera';
        } elseif (str_contains($agent, 'netscape')) {
            $browser = 'netscape';
        } elseif (str_contains($agent, 'maxthon')) {
            $browser = 'maxthon';
        } elseif (str_contains($agent, 'konqueror')) {
            $browser = 'konqueror';
        } elseif (str_contains($agent, 'iphone')) {
            $browser = 'iphone';
        } elseif (str_contains($agent, 'ipod')) {
            $browser = 'ipod';
        } elseif (str_contains($agent, 'ipad')) {
            $browser = 'ipad';
        } elseif (str_contains($agent, 'android')) {
            $browser = 'android';
        } elseif (str_contains($agent, 'blackberry')) {
            $browser = 'blackberry';
        } elseif (str_contains($agent, 'webos')) {
            $browser = 'webos';
        } elseif (str_contains($agent, 'mobile')) {
            $browser = 'mobile';
        }
        return $browser;
    }

    /*! AUTOLOADER */
    /**
     * @var array<string, array<string, callable>>
     */
    private static array $registeredLoaderAutoloader = [];

    /**
     * @var array<string, bool>
     */
    private static array $registeredDirectoriesAutoloader = [];

    /**
     * De-Register autoloader with namespace
     *
     * @param string $namespace
     * @param string $directory
     * @return bool
     */
    public static function deRegisterAutoloader(
        string $namespace,
        string $directory
    ): bool {
        $namespace = trim($namespace, '\\');
        $namespace = $namespace . '\\';
        $directory = realpath($directory) ?: $directory;
        if (!isset(self::$registeredLoaderAutoloader[$namespace][$directory])) {
            return false;
        }
        $callback = self::$registeredLoaderAutoloader[$namespace][$directory];
        unset(self::$registeredLoaderAutoloader[$namespace][$directory]);
        if (!is_callable($callback)) {
            return false;
        }
        return spl_autoload_unregister($callback);
    }

    /**
     * Register autoloader with namespace
     *
     * @param string $namespace
     * @param string $directory
     * @param bool $prepend
     * @return bool
     */
    public static function registerAutoloader(
        string $namespace,
        string $directory,
        bool $prepend = false
    ): bool {
        static $include = null;
        $include ??= Closure::bind(static function ($file) {
            include_once $file;
        }, null, null);
        $namespace = trim($namespace, '\\');
        if (!$namespace
            || !is_dir($directory)
            || !self::isValidClassName($namespace)
        ) {
            return false;
        }

        $namespace = $namespace . '\\';
        $directory = realpath($directory) ?: $directory;
        if (!empty(self::$registeredLoaderAutoloader[$namespace][$directory])) {
            return false;
        }

        self::$registeredLoaderAutoloader[$namespace][$directory] = static function (
            $className
        ) use (
            $namespace,
            $directory,
            $include
        ) {
            if (!str_starts_with($className, $namespace)) {
                return;
            }
            $file = substr($className, strlen($namespace));
            $file = str_replace('\\', DIRECTORY_SEPARATOR, $file);
            $fileName = $directory . DIRECTORY_SEPARATOR . $file . ".php";
            if (isset(self::$registeredDirectoriesAutoloader[$fileName])) {
                return;
            }
            self::$registeredDirectoriesAutoloader[$fileName] = true;
            if (file_exists($fileName)) {
                $include($fileName);
            }
        };

        if (!spl_autoload_register(
            self::$registeredLoaderAutoloader[$namespace][$directory],
            true,
            $prepend
        )) {
            unset(self::$registeredLoaderAutoloader[$namespace][$directory]);
        }

        return is_string(self::$registeredLoaderAutoloader[$namespace][$directory]);
    }
}

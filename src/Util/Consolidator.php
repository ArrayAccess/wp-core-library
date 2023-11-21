<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Util;

use ArrayAccess\WP\Libraries\Core\Exceptions\RuntimeException;
use Closure;
use ReflectionFunction;
use Throwable;
use function array_key_exists;
use function explode;
use function file_exists;
use function get_class;
use function implode;
use function in_array;
use function ini_get;
use function is_array;
use function is_callable;
use function is_dir;
use function is_file;
use function is_int;
use function is_numeric;
use function is_object;
use function is_readable;
use function is_string;
use function realpath;
use function restore_error_handler;
use function set_error_handler;
use function spl_autoload_register;
use function spl_autoload_unregister;
use function str_contains;
use function str_replace;
use function stripos;
use function strlen;
use function strtolower;
use function substr;
use function trim;
use function urlencode;
use const DIRECTORY_SEPARATOR;

/**
 * Class Consolidator - A collection of static methods
 */
class Consolidator
{
    /**
     * Blacklisted class name
     */
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

    /**
     * Check if the string is a valid class name & allowed
     *
     * @param string $className Class name
     * @return bool
     */
    public static function isClassNameAllowed(string $className): bool
    {
        $className = Filter::className($className);
        if (!$className) {
            return false;
        }
        $lowerClassName = strtolower($className);
        // loop through the blacklisted name
        foreach (explode('\\', $lowerClassName) as $className) {
            if (in_array($className, self::BLACKLISTED_NAME)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Generate a random bytes string
     *
     * @param int $length
     * @return string
     */
    public static function randomBytes(int $length = 32): string
    {
        try {
            return random_bytes($length);
        } catch (Throwable) {
            $bytes = '';
            for ($i = 0; $i < $length; $i++) {
                $bytes .= chr(mt_rand(0, 255));
            }
            return $bytes;
        }
    }

    /**
     * Generate a random int
     *
     * @param int $min
     * @param int $max
     * @return int
     */
    public static function randomInt(int $min, int $max): int
    {
        try {
            return random_int($min, $max);
        } catch (Throwable) {
            return mt_rand($min, $max);
        }
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
     * @throws RuntimeException|\ReflectionException
     * @noinspection PhpFullyQualifiedNameUsageInspection
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
     * @throws RuntimeException|\ReflectionException
     * @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public static function callObjectBinding(Closure $closure, object $object, ...$args): mixed
    {
        return self::objectBinding($closure, $object)(...$args);
    }

    /**
     * Get the browser type
     * @param string $userAgent User Agent
     * @return string browser type
     */
    public static function getBrowserType(string $userAgent): string
    {
        $agent = strtolower($userAgent);
        $default = 'unknown';
        $browserLists = [
            'msie' => 'ie',
            'wget' => 'wget',
            'curl' => 'curl',
            'lynx' => 'lynx',
            'links' => 'links',
            'bot' => 'bot',
            'spider' => 'spider',
            'crawler' => 'crawler',
            'rss' => 'rss',
            'feed' => 'feed',
            'archive' => 'archive',
            'appengine-google' => 'appengine-google',
            'mediapartners-google' => 'mediapartners-google',
            'googlebot' => 'googlebot',
            'yahoo' => 'yahoo',
            'msn' => 'msn',
            'baidu' => 'baidu',
            'bing' => 'bing',
            'ask' => 'ask',
            'duckduckgo' => 'duckduckgo',
            'yandex' => 'yandex',
            'aol' => 'aol',
            'lycos' => 'lycos',
            'excite' => 'excite',
            'altavista' => 'altavista',
            'edge' => 'edge',
            'firefox' => 'firefox',
            'chrome' => 'chrome',
            'safari' => 'safari',
            'opera' => 'opera',
            'netscape' => 'netscape',
            'maxthon' => 'maxthon',
            'konqueror' => 'konqueror',
            'iphone' => 'iphone',
            'ipod' => 'ipod',
            'ipad' => 'ipad',
            'android' => 'android',
            'blackberry' => 'blackberry',
            'webos' => 'webos',
            'mobile' => 'mobile',
        ];
        foreach ($browserLists as $key => $val) {
            if (str_contains($agent, $key)) {
                return $val;
            }
        }
        return $default;
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
        $lowerNamespace = strtolower($namespace);
        if (!isset(self::$registeredLoaderAutoloader[$lowerNamespace][$directory])) {
            return false;
        }
        $callback = self::$registeredLoaderAutoloader[$lowerNamespace][$directory];
        unset(self::$registeredLoaderAutoloader[$lowerNamespace][$directory]);
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
            || !($namespace = Filter::className($namespace))
        ) {
            return false;
        }

        $namespace = $namespace . '\\';
        $directory = realpath($directory) ?: $directory;
        $lowerNamespace = strtolower($namespace);
        if (!empty(self::$registeredLoaderAutoloader[$lowerNamespace][$directory])) {
            return false;
        }
        self::$registeredLoaderAutoloader[$lowerNamespace][$directory] = static function (
            $className
        ) use (
            $namespace,
            $directory,
            $include
        ) {
            if (stripos($className, $namespace) !== 0) {
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
            self::$registeredLoaderAutoloader[$lowerNamespace][$directory],
            true,
            $prepend
        )) {
            unset(self::$registeredLoaderAutoloader[$lowerNamespace][$directory]);
        }

        return is_string(self::$registeredLoaderAutoloader[$lowerNamespace][$directory]);
    }
}

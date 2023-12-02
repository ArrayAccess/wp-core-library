<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Util;

use function array_key_exists;
use function function_exists;
use function getallheaders;
use function implode;
use function is_array;
use function is_string;
use function str_replace;
use function strtolower;
use function strtoupper;
use function substr;
use function trim;
use function ucwords;

/**
 * Header resolver that get header from $_SERVER['HTTP_']
 */
class Header
{
    /**
     * @var array<string, string[]>
     */
    protected array $headers;

    /**
     * @var Header $instance
     */
    private static Header $instance;

    /**
     * Constructor of Header
     *
     * @param array $headers
     */
    public function __construct(array $headers = [])
    {
        if (empty($headers)) {
            if (isset(self::$instance)) {
                $this->headers = self::$instance->headers;
                return;
            }
            $servers = $_SERVER;
            if (function_exists('getallheaders')) {
                foreach (getallheaders() as $key => $value) {
                    $servers['HTTP_' . str_replace('-', '_', strtoupper($key))] = $value;
                }
            }
            $headers = $this->getHeadersFromServer($servers);
        }
        $this->setHeaderProperty($headers);
    }

    /**
     * Get instance
     *
     * @return self
     */
    public static function getInstance(): self
    {
        return self::$instance??= new Header();
    }

    /**
     * Get header line
     *
     * @param string $key
     * @return string
     */
    public static function line(string $key): string
    {
        return self::getInstance()->getHeaderLine($key);
    }

    /**
     * Get header
     *
     * @param string $key
     * @return string[]
     */
    public static function get(string $key): array
    {
        return self::getInstance()->getHeader($key);
    }

    /**
     * Set header property
     *
     * @param array<string|string[]> $headers
     * @return void
     */
    private function setHeaderProperty(array $headers): void
    {
        $this->headers = [];
        foreach ($headers as $key => $value) {
            if (!is_string($key)) {
                continue;
            }
            $key = $this->normalizeKey($key);
            if (is_string($value)) {
                $value = [$value];
                $this->headers[$key][] = $value;
                continue;
            }
            if (is_array($value)) {
                foreach ($value as $v) {
                    if (!is_string($v)) {
                        continue;
                    }
                    $this->headers[$key][] = $v;
                }
            }
        }
    }

    /**
     * Normalize key
     *
     * @param string $key
     * @return string
     */
    public function normalizeKey(string $key): string
    {
        $key = strtolower(trim($key));
        if ($key === '') {
            return '';
        }
        $key = str_replace('_', '-', $key);
        if (str_starts_with($key, 'http-')) {
            $key = substr($key, 5);
        }
        return $key;
    }

    /**
     * Normalize header key name
     *
     * @param string $key
     * @return string
     */
    public function normalizeHeaderKeyName(string $key): string
    {
        return ucwords($this->normalizeKey($key), '-');
    }

    /**
     * Get headers from $_SERVER
     *
     * @param array $server
     * @return array
     */
    public function getHeadersFromServer(array $server): array
    {
        $headers = [];
        foreach ($server as $key => $value) {
            if (!is_string($value) || !str_starts_with($key, 'HTTP_')) {
                continue;
            }
            $key = $this->normalizeKey($key);
            $headers[$key][] = $value;
        }
        return $headers;
    }

    /**
     * Get all headers
     *
     * @return array<string, string[]>
     */
    public function getHeaders(): array
    {
        $headers = [];
        foreach ($this->headers as $key => $value) {
            $key = $this->normalizeHeaderKeyName($key);
            $headers[$key] = $value;
        }
        return $headers;
    }

    /**
     * Get header
     *
     * @param string $key
     * @return array|string[]
     */
    public function getHeader(string $key): array
    {
        $key = $this->normalizeKey($key);
        return $this->headers[$key] ?? [];
    }

    /**
     * Get header line
     *
     * @param string $key
     * @return string
     */
    public function getHeaderLine(string $key): string
    {
        $headers = $this->getHeader($key);
        return implode(', ', $headers);
    }

    /**
     * Check if the header exists
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        $key = $this->normalizeKey($key);
        return array_key_exists($key, $this->headers);
    }

    /**
     * With added header, clone object
     *
     * @param string $key
     * @param string $value
     * @return $this
     */
    public function withAddedHeader(string $key, string $value): self
    {
        $obj = clone $this;
        $key = $this->normalizeKey($key);
        $obj->headers[$key][] = $value;
        return $obj;
    }

    /**
     * With added headers, clone object
     *
     * @param string $key
     * @param string|array<string> $value
     * @return $this
     */
    public function withHeader(string $key, string|array $value): self
    {
        $obj = clone $this;
        $key = $this->normalizeKey($key);
        if (is_string($value)) {
            $value = [$value];
        }
        $values = [];
        foreach ($value as $v) {
            if (!is_string($v)) {
                continue;
            }
            $values[] = $v;
        }
        $obj->headers[$key] = $values;
        return $obj;
    }

    /**
     * Without header, clone object
     *
     * @param string $key Header name to remove
     * @return $this Header
     */
    public function withoutHeader(string $key) : self
    {
        $obj = clone $this;
        $key = $this->normalizeKey($key);
        unset($obj->headers[$key]);
        return $obj;
    }
}

<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Ajax\Interfaces;

use ArrayAccess\WP\Libraries\Core\Service\Interfaces\HookInterface;

interface JsonSenderInterface
{
    public const DEFAULT_DEPTH = 512;

    public const DEFAULT_FLAGS = JSON_UNESCAPED_SLASHES;

    /**
     * @param JsonFormatterInterface $formatter
     */
    public function __construct(JsonFormatterInterface $formatter);

    /**
     * Get JSON formatter
     *
     * @return JsonFormatterInterface
     */
    public function getFormatter(): JsonFormatterInterface;

    /**
     * Set JSON formatter
     *
     * @param JsonFormatterInterface $formatter
     * @return $this
     */
    public function setFormatter(JsonFormatterInterface $formatter): static;

    /**
     * Set JSON encode depth
     * @link https://www.php.net/manual/en/function.json-encode.php
     *
     * @param int $depth unsigned integer
     * @return $this
     */
    public function setDepth(int $depth): static;

    /**
     * Get JSON encode depth
     * @link https://www.php.net/manual/en/function.json-encode.php
     *
     * @return int JSON encode depth
     */
    public function getDepth(): int;

    /**
     * Get JSON encode options
     * @link https://www.php.net/manual/en/function.json-encode.php
     *
     * @return int JSON encode options
     */
    public function getFlags() : int;

    /**
     * Set JSON encode options
     * @param int $flags JSON encode options
     *
     * @return $this
     */
    public function setFlags(int $flags) : static;

    /**
     * Encode data to json string
     * @link https://www.php.net/manual/en/function.json-encode.php
     *
     * @param mixed $data
     * @return string
     * @uses \wp_json_encode()
     * @throws \ArrayAccess\WP\Libraries\Core\Ajax\Exceptions\JsonException
     * @noinspection PhpFullyQualifiedNameUsageInspection
     */
    public function encode(mixed $data) : string;

    /**
     * Send Json response
     *
     * @param JsonResponseInterface $response
     * @return never-return
     */
    public function send(JsonResponseInterface $response);

    /**
     * Get hook
     * @return ?HookInterface
     */
    public function getHook(): ?HookInterface;

    /**
     * Set hook
     * @param HookInterface|null $hook
     * @return $this
     */
    public function setHook(?HookInterface $hook): static;
}

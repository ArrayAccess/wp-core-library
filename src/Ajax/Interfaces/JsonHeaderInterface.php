<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Ajax\Interfaces;

interface JsonHeaderInterface
{
    /**
     * Status code
     * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status
     *
     * @return int HTTP status code
     */
    public function getStatusCode() : int;

    /**
     * Set the status code
     * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status
     *
     * @param int $statusCode HTTP status code
     * @param ?string $message HTTP status message
     *
     * @return $this JsonHeaderInterface
     */
    public function setStatus(int $statusCode, ?string $message = null): static;

    /**
     * Status Message
     * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status
     *
     * @return string HTTP status message
     */
    public function getStatusMessage() : string;

    /**
     * Set the charset
     * @link https://www.iana.org/assignments/character-sets/character-sets.xhtml
     *
     * @param ?string $charset Charset
     * @return $this JsonHeaderInterface
     * @uses \ArrayAccess\WP\Libraries\Core\Util\CharacterEncoding::filterEncoding($charset)
     */
    public function setCharset(?string $charset): static;

    /**
     * Get the charset
     * @link https://www.iana.org/assignments/character-sets/character-sets.xhtml
     *
     * @return ?string Charset
     */
    public function getCharset(): ?string;

    /**
     * @param string $name
     * @param string $value
     * @param string ...$additionalValues
     * @return $this
     */
    public function set(string $name, string $value, string ...$additionalValues): static;

    /**
     * Check if the header exists
     *
     * @param string $name
     * @return bool Whether the header exists
     */
    public function has(string $name): bool;

    /**
     * @param string $name Header name
     *
     * @return $this JsonHeaderInterface
     */
    public function remove(string $name): static;

    /**
     * Get header value
     *
     * @param string $name
     * @return string[] Header value
     */
    public function get(string $name): array;

    /**
     * Get content type header value
     * @return string Content type header value
     */
    public function getContentTypeHeader() : string;

    /**
     * Get header value
     *
     * @param string $name
     * @return string Header value
     */
    public function line(string $name): string;

    /**
     * Get all headers to serve to the client
     *
     * @return array<string, string> Array of normalize header key & headers values to render for http header
     */
    public function toHeaders(): array;
}

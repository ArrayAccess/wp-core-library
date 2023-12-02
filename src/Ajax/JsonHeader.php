<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Ajax;

use ArrayAccess\WP\Libraries\Core\Ajax\Interfaces\JsonHeaderInterface;
use ArrayAccess\WP\Libraries\Core\Util\CharacterEncoding;
use ArrayAccess\WP\Libraries\Core\Util\HttpStatusCode;
use function is_string;
use function str_replace;
use function strtolower;
use function substr;
use function trim;
use function ucwords;

class JsonHeader implements JsonHeaderInterface
{
    /**
     * @var array<string, string[]> The headers.
     */
    private array $headers = [];

    /**
     * @var ?string The charset.
     */
    private ?string $charset = null;

    /**
     * @var int The status code.
     * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status
     */
    private int $statusCode = HttpStatusCode::OK;

    /**
     * @var string The status message.
     * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status
     */
    private string $statusMessage = HttpStatusCode::REASON_PHRASES[HttpStatusCode::OK];

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
     * @inheritdoc
     * @uses HttpStatusCode::getMessage()
     */
    public function setStatus(int $statusCode, ?string $message = null): static
    {
        if (!$message || trim($message) === '') {
            $message = HttpStatusCode::getMessage($statusCode);
        }
        if (!$message) {
            return $this;
        }
        $this->statusCode = $statusCode;
        $this->statusMessage = $message;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @inheritdoc
     */
    public function getStatusMessage(): string
    {
        return $this->statusMessage;
    }

    /**
     * @inheritdoc
     */
    public function setCharset(?string $charset): static
    {
        $charset = is_string($charset) ? trim($charset) : null;
        if (!$charset) {
            $this->charset = null;
            return $this;
        }
        $charset = CharacterEncoding::filterEncoding($charset);
        if ($charset) {
            $this->charset = $charset;
        }
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCharset(): ?string
    {
        return $this->charset;
    }

    /**
     * @inheritdoc
     */
    public function set(string $name, string $value, string ...$additionalValues): static
    {
        $name = $this->normalizeKey($name);
        if ($name === '') {
            return $this;
        }
        // prevent set content type
        if ($name === 'content-type') {
            return $this;
        }

        $values = [trim($value)];
        foreach ($additionalValues as $v) {
            $v = trim($v);
            if ($v === '') {
                continue;
            }
            $values[] = $v;
        }
        // remove empty values
        if (empty($values)) {
            unset($this->headers[$name]);
            return $this;
        }
        $this->headers[$name] = $values;
        return $this;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool
    {
        $name = $this->normalizeKey($name);
        if ($name === 'content-type') {
            return true;
        }
        return array_key_exists($name, $this->headers);
    }

    /**
     * @inheritdoc
     */
    public function remove(string $name): static
    {
        $name = $this->normalizeKey($name);
        unset($this->headers[$name]);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function get(string $name): array
    {
        $name = $this->normalizeKey($name);
        if ($name === 'content-type') {
            return [$this->getContentTypeHeader()];
        }
        return $this->headers[$name] ?? [];
    }

    /**
     * @inheritdoc
     */
    public function line(string $name): string
    {
        $name = $this->normalizeKey($name);
        if ($name === 'content-type') {
            return $this->getContentTypeHeader();
        }
        $values = $this->headers[$name] ?? [];
        if (empty($values)) {
            return '';
        }
        return implode(', ', $values);
    }

    /**
     * @inheritdoc
     */
    public function getContentTypeHeader() : string
    {
        $contentType = 'application/json';
        $charset = $this->getCharset();
        if ($charset) {
            $contentType .= '; charset=' . $charset;
        }
        return $contentType;
    }

    /**
     * @inheritdoc
     */
    public function toHeaders(): array
    {
        $headers = [
            'Content-Type' => $this->getContentTypeHeader(),
        ];
        foreach ($this->headers as $name => $values) {
            $name = $this->normalizeHeaderKeyName($name);
            $headers[$name] = $this->line($name);
        }
        return $headers;
    }
}

<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Ajax;

use ArrayAccess\WP\Libraries\Core\Ajax\Exceptions\JsonException;
use ArrayAccess\WP\Libraries\Core\Ajax\Interfaces\JsonFormatterInterface;
use ArrayAccess\WP\Libraries\Core\Ajax\Interfaces\JsonResponseInterface;
use ArrayAccess\WP\Libraries\Core\Ajax\Interfaces\JsonSenderInterface;
use ArrayAccess\WP\Libraries\Core\Exceptions\RuntimeException;
use ArrayAccess\WP\Libraries\Core\Service\Interfaces\HookInterface;
use Throwable;
use function header;
use function headers_sent;
use function is_string;
use function json_last_error;
use function json_last_error_msg;
use function ob_end_clean;
use function ob_get_level;
use function ob_start;
use function sprintf;
use function wp_die;
use function wp_json_encode;
use const JSON_ERROR_NONE;
use const JSON_THROW_ON_ERROR;

class JsonSender implements JsonSenderInterface
{
    /**
     * @var int JSON encode options
     */
    private int $flags = self::DEFAULT_FLAGS;

    /**
     * @var int JSON encode depth
     */
    private int $depth = self::DEFAULT_DEPTH;

    /**
     * @var ?HookInterface
     */
    private ?HookInterface $hook = null;

    /**
     * @var JsonFormatterInterface Formatter
     */
    private JsonFormatterInterface $formatter;

    /**
     * Json Sender constructor.
     * @param JsonFormatterInterface $formatter
     * @param ?HookInterface $hook
     */
    public function __construct(JsonFormatterInterface $formatter, ?HookInterface $hook = null)
    {
        $this->setFormatter($formatter);
        $this->setHook($hook);
    }

    /**
     * @inheritdoc
     */
    public function getFormatter(): JsonFormatterInterface
    {
        return $this->formatter;
    }

    /**
     * @inheritdoc
     */
    public function setFormatter(JsonFormatterInterface $formatter) : static
    {
        $this->formatter = $formatter;
        return $this;
    }

    /**
     * Get hook
     * @return ?HookInterface
     */
    public function getHook(): ?HookInterface
    {
        return $this->hook;
    }

    /**
     * Set hook
     * @param HookInterface|null $hook
     * @return $this
     */
    public function setHook(?HookInterface $hook): static
    {
        $this->hook = $hook;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setDepth(int $depth): static
    {
        if ($depth > 0) {
            $this->depth = $depth;
        }
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getDepth(): int
    {
        return $this->depth;
    }

    /**
     * @inheritdoc
     */
    public function getFlags(): int
    {
        return $this->flags;
    }

    /**
     * @inheritdoc
     */
    public function setFlags(int $flags): static
    {
        $this->flags = $flags;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function encode(mixed $data): string
    {
        $hook = $this->getHook();
        if ($hook && $hook->has('jsonSender.data.encode')) {
            $data = $hook->apply('jsonSender.data.encode', $data, $this);
        }

        // save original data
        $originalData = $data;
        // add JSON_THROW_ON_ERROR flag
        $flags = $this->flags | JSON_THROW_ON_ERROR;
        try {
            /**
             * Encode
             */
            $data = wp_json_encode($data, $flags, $this->depth);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new RuntimeException(
                    sprintf('wp_json_encode() error. %s', json_last_error_msg()),
                    json_last_error(),
                    null
                );
            } elseif ($data === false) {
                throw new RuntimeException(
                    'wp_json_encode() returned false.',
                    0,
                    null
                );
            }
        } catch (Throwable $e) {
            throw new JsonException(
                $originalData,
                $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
        unset($originalData);
        return $data; // return empty string if error
    }

    /**
     * Send Json response
     *
     * @param JsonResponseInterface $response
     * @return never-return
     * @noinspection PhpNoReturnAttributeCanBeAddedInspection
     */
    public function send(JsonResponseInterface $response): void
    {
        $header = $response->getHandler()->getHeader();
        $statusCode = $header->getStatusCode();
        $statusMessage = $header->getStatusMessage();
        $headers = $header->toHeaders();
        unset($headers['Content-Type']);
        $ob_level = ob_get_level();
        $hasLevel = $ob_level > 0;
        while ($ob_level > 0) {
            ob_end_clean();
            $ob_level--;
        }
        if ($hasLevel) {
            ob_start();
        }
        if (!headers_sent()) {
            header(sprintf('HTTP/1.1 %d %s', $statusCode, $statusMessage), true, $statusCode);
            header(sprintf('Content-Type: %s', $header->getContentTypeHeader()), true, $statusCode);
            foreach ($headers as $name => $values) {
                if (!is_string($values)) {
                    continue;
                }
                header(sprintf('%s: %s', $name, $values), true, $statusCode);
            }
        }
        echo $this->encode($this->getFormatter()->format($response));
        wp_die();
    }
}

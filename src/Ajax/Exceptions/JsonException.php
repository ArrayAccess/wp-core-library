<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Ajax\Exceptions;

use ArrayAccess\WP\Libraries\Core\Exceptions\RuntimeException;
use Throwable;

class JsonException extends RuntimeException
{
    /**
     * @var mixed The data.
     */
    protected mixed $data;

    /**
     * @param mixed $data The data.
     * @param string $message The message.
     * @param int $code The code.
     * @param ?Throwable $previous The previous exception.
     */
    public function __construct(
        mixed $data,
        string $message = "",
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return mixed The data.
     */
    public function getData(): mixed
    {
        return $this->data;
    }
}

<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Ajax;

use ArrayAccess\WP\Libraries\Core\Ajax\Interfaces\HandlerInterface;
use ArrayAccess\WP\Libraries\Core\Ajax\Interfaces\JsonResponseInterface;

class JsonResponse implements JsonResponseInterface
{
    /**
     * @var HandlerInterface Handler
     */
    private HandlerInterface $handler;

    /**
     * @var mixed Data to be sent
     */
    private mixed $data;

    /**
     * Json constructor.
     * @param HandlerInterface $handler
     * @param mixed|null $data
     */
    public function __construct(HandlerInterface $handler, mixed $data = null)
    {
        $this->handler = $handler;
        $this->setData($data);
    }

    /**
     * @inheritdoc
     */
    public function getHandler(): HandlerInterface
    {
        return $this->handler;
    }

    /**
     * @inheritdoc
     */
    public function getData(): mixed
    {
        return $this->data;
    }

    /**
     * @inheritdoc
     */
    public function setData(mixed $data): static
    {
        $this->data = $data;
        return $this;
    }
}

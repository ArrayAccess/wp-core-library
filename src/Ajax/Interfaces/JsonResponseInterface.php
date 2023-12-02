<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Ajax\Interfaces;

interface JsonResponseInterface
{
    /**
     * @param HandlerInterface $handler
     * @param mixed|null $data
     */
    public function __construct(HandlerInterface $handler, mixed $data = null);

    /**
     * @return HandlerInterface
     */
    public function getHandler(): HandlerInterface;

    /**
     * Get data
     * @return mixed
     */
    public function getData(): mixed;

    /**
     * Set data
     *
     * @param mixed $data
     * @return $this
     */
    public function setData(mixed $data): static;
}

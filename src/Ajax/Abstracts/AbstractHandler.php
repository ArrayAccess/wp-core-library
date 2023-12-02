<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Ajax\Abstracts;

use ArrayAccess\WP\Libraries\Core\Ajax\Interfaces\HandlerInterface;
use ArrayAccess\WP\Libraries\Core\Ajax\Interfaces\JsonHeaderInterface;
use ArrayAccess\WP\Libraries\Core\Ajax\JsonHeader;

abstract class AbstractHandler implements HandlerInterface
{
    /**
     * @var string The name of the action to be called.
     */
    protected string $action;

    /**
     * @var bool Whether the user must be logged in to access this handler.
     */
    protected bool $requireLoggedIn = false;

    /**
     * @var JsonHeaderInterface The header object.
     */
    protected JsonHeaderInterface $header;

    /**
     * @var int The priority of the action.
     */
    protected int $priority = 10;

    /**
     * @var ?array<string> Accepted methods.
     */
    protected ?array $acceptedMethods = null;

    /**
     * @inheritdoc
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * @inheritdoc
     */
    public function setStatus(int $statusCode, ?string $message = null): static
    {
        $this->getHeader()->setStatus($statusCode);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getHeader(): JsonHeaderInterface
    {
        return $this->header ??= new JsonHeader();
    }

    /**
     * @inheritdoc
     */
    public function getAction(): string
    {
        return $this->action ??= '';
    }

    /**
     * @inheritdoc
     */
    public function getAcceptedMethods(): ?array
    {
        return $this->acceptedMethods;
    }

    /**
     * @inheritdoc
     */
    public function requireLoggedIn(): bool
    {
        return $this->requireLoggedIn;
    }

    /**
     * @inheritdoc
     */
    public function enqueueScript()
    {
        // no enqueue
    }
}

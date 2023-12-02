<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Ajax\Interfaces;

interface HandlerInterface
{
    /**
     * Set the status code.
     *
     * @param int $statusCode The status code to set.
     * @param ?string $message Optional message to send with the status code.
     * @return $this
     * @uses getHeader()
     * @uses JsonHeaderInterface::setStatus()
     */
    public function setStatus(int $statusCode, ?string $message = null): static;

    /**
     * Get the header object.
     *
     * @return JsonHeaderInterface Header object
     */
    public function getHeader(): JsonHeaderInterface;

    /**
     * Get the name of the action to be called.
     *
     * @return string action name, if empty does not process
     */
    public function getAction(): string;

    /**
     * Get accepted methods.
     * If empty, all methods are accepted.
     *
     * @return ?array
     */
    public function getAcceptedMethods() : ?array;

    /**
     * Whether the user must be logged in to access this handler.
     *
     * @return bool
     */
    public function requireLoggedIn() : bool;

    /**
     * Render the script for this handler.
     */
    public function enqueueScript();

    /**
     * Get the priority of the action.
     *
     * @return int The priority.
     */
    public function getPriority() : int;

    /**
     * Handle the request.
     * Add JSON sender to make it easier to send direct mutable responses.
     *
     * @param JsonSenderInterface $jsonSender
     * @return JsonResponseInterface
     */
    public function handle(JsonSenderInterface $jsonSender) : JsonResponseInterface;
}

<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\API\Interfaces;

use ArrayAccess\WP\Libraries\Core\Service\Interfaces\HookInterface;
use Countable;

interface EndpointCollectionInterface extends Countable
{
    /**
     * Get hook.
     *
     * @return HookInterface
     */
    public function getHook(): HookInterface;

    /**
     * Set hook.
     *
     * @param HookInterface $hook
     * @return void
     */
    public function setHook(HookInterface $hook): void;

    /**
     * Get namespace.
     *
     * @return string
     */
    public function getNamespace(): string;

    /**
     * Set namespace.
     * If collections already registered, do nothing.
     *
     * @param string $namespace
     * @return ?string The namespace or null if not valid.
     */
    public function setNamespace(string $namespace) : ?string;

    /**
     * Add endpoint.
     * If collections already registered, do nothing.
     *
     * @param RootEndpointInterface $endpoint
     */
    public function addEndpoint(RootEndpointInterface $endpoint): void;

    /**
     * Remove endpoint.
     * If collections already registered, do nothing.
     *
     * @param string|RootEndpointInterface $endpoint
     * @return ?RootEndpointInterface Removed endpoint or null if not found.
     */
    public function removeEndpoint(string|RootEndpointInterface $endpoint): ?RootEndpointInterface;

    /**
     * Get endpoint by namespace.
     *
     * @param string|RootEndpointInterface $endpoint
     * @return ?EndpointInterface Endpoint instance or null if not found.
     */
    public function getEndpoint(string|RootEndpointInterface $endpoint): ?RootEndpointInterface;

    /**
     * Check if endpoint is registered.
     *
     * @param string|RootEndpointInterface $endpoint
     * @return bool
     */
    public function hasEndpoint(string|RootEndpointInterface $endpoint): bool;

    /**
     * Clear all endpoints.
     * @throws ApiEndpointExceptionInterface if endpoint already registered.
     */
    public function clearEndpoints();

    /**
     * Get All endpoints.
     *
     * @return array<string, RootEndpointInterface> List of endpoints.
     */
    public function getEndpoints(): array;

    /**
     * @return bool Check if endpoint is registered.
     */
    public function isRegistered(): bool;

    /**
     * Register endpoints.
     */
    public function register();
}

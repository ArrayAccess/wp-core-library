<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\API\Interfaces;

use Countable;

/**
 * Interface to help create WordPress REST API endpoints.
 * Determine by slug & contain sub endpoints.
 */
interface EndpointInterface extends Countable
{
    /**
     * The endpoint method.
     *
     * @return string[] Endpoint methods list.
     */
    public function getMethods() : array;

    /**
     * Check if endpoint is allowed.
     * This just like permission callback.
     *
     * @return bool Check if endpoint is allowed.
     */
    public function isAllowed(): bool;

    /**
     * Get endpoint namespace.
     *
     * @return string Endpoint namespace.
     */
    public function getNamespace(): string;

    /**
     * Get endpoint route.
     *
     * @return string
     */
    public function getRoute(): string;

    /**
     * Add sub endpoint to current endpoint.
     *
     * @param SubEndpointInterface $endpoint
     * @return bool True if success, false if failed.
     * @throws ApiEndpointExceptionInterface if nested parent endpoint contains current object.
     */
    public function addEndpoint(SubEndpointInterface $endpoint) : bool;

    /**
     * Check if endpoint has sub endpoint.
     *
     * @param SubEndpointInterface|string $endpoint
     * @return bool
     */
    public function hasEndpoint(SubEndpointInterface|string $endpoint) : bool;

    /**
     * Remove sub endpoint from current endpoint.
     *
     * @param SubEndpointInterface|string $endpoint
     * @return ?SubEndpointInterface Removed endpoint or null if not found.
     * @throws ApiEndpointExceptionInterface if endpoint already registered.
     */
    public function removeEndpoint(SubEndpointInterface|string $endpoint) : ?SubEndpointInterface;

    /**
     * Get sub endpoint by slug.
     *
     * @param SubEndpointInterface|string $endpoint
     * @return ?SubEndpointInterface Endpoint instance or null if not found.
     */
    public function getEndpoint(SubEndpointInterface|string $endpoint): ?SubEndpointInterface;

    /**
     * @var array<string, SubEndpointInterface> $endpoints List of sub endpoints.
     */
    public function getEndpoints(): array;

    /**
     * @return bool Check if endpoint is registered.
     */
    public function isRegistered(): bool;

    /**
     * Clear all endpoints.
     *
     * @throws ApiEndpointExceptionInterface if endpoint already registered.
     */
    public function clearEndpoints();

    /**
     * Get endpoint processor.
     * Default
     * @uses \ArrayAccess\WP\Libraries\Core\API\Processors\NotFoundResourceProcessor
     * @return ProcessorInterface
     */
    public function getProcessor(): ProcessorInterface;
}

<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\API\Interfaces;

/**
 * Interface RootEndpointInterface to be used as a root endpoint.
 */
interface RootEndpointInterface extends EndpointInterface
{
    /**
     * Set endpoint collection.
     *
     * @param EndpointCollectionInterface $endpoints
     */
    public function setEndpointCollection(EndpointCollectionInterface $endpoints);

    /**
     * Get an endpoint collection.
     * If not set, return null.
     * @return ?EndpointCollectionInterface
     */
    public function getEndpointCollection(): ?EndpointCollectionInterface;

    /**
     * Register endpoint to WordPress.
     * Register if the hook doing or did 'rest_api_init'.
     *
     * @param EndpointCollectionInterface $endpoints
     * @return bool
     */
    public function register(EndpointCollectionInterface $endpoints): bool;
}

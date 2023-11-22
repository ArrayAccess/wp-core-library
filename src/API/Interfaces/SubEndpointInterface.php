<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\API\Interfaces;

/**
 * Interface SubEndpointInterface
 */
interface SubEndpointInterface extends EndpointInterface
{
    /**
     * @param RootEndpointInterface|SubEndpointInterface $endpoint
     */
    public function setParentEndpoint(RootEndpointInterface|SubEndpointInterface $endpoint);

    /**
     * Register endpoint to WordPress.
     *
     * @param RootEndpointInterface|SubEndpointInterface $endpoint
     * @return bool
     */
    public function register(RootEndpointInterface|SubEndpointInterface $endpoint) : bool;

    /**
     * Get parent endpoint.
     *
     * @return RootEndpointInterface|SubEndpointInterface|null Parent endpoint or null if not set.
     */
    public function getParentEndpoint(): RootEndpointInterface|SubEndpointInterface|null;
}

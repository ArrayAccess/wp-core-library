<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\API\Interfaces;

use WP_REST_Request;

interface ProcessorInterface
{
    /**
     * Check if endpoint is processable.
     * If not processable, the endpoint will not be processed.
     */
    public function processable(EndpointInterface $endpoint): bool;

    /**
     * Set the current endpoint instance
     *
     * @param EndpointInterface $endpoint
     */
    public function setEndpoint(EndpointInterface $endpoint);

    /**
     * Get the current endpoint instance
     *
     * @return ?EndpointInterface Endpoint instance or null set.
     */
    public function getEndpoint(): ?EndpointInterface;

    /**
     * Process endpoint request.
     *
     * @param EndpointInterface $endpoint
     * @param WP_REST_Request $request
     */
    public function process(EndpointInterface $endpoint, WP_REST_Request $request);
}

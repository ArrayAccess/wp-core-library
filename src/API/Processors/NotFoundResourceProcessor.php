<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\API\Processors;

use ArrayAccess\WP\Libraries\Core\API\Interfaces\EndpointInterface;
use ArrayAccess\WP\Libraries\Core\API\Interfaces\ProcessorInterface;
use WP_Error;
use WP_REST_Request;

class NotFoundResourceProcessor implements ProcessorInterface
{
    protected ?EndpointInterface $endpoint;

    /**
     * Check if endpoint is processable.
     * If not processable, the endpoint will not be processed.
     */
    public function processable(EndpointInterface $endpoint): bool
    {
        return true;
    }

    /**
     * Set the current endpoint instance
     *
     * @param EndpointInterface $endpoint
     */
    public function setEndpoint(EndpointInterface $endpoint): void
    {
        $this->endpoint = $endpoint;
    }

    /**
     * Get the current endpoint instance
     *
     * @return ?EndpointInterface Endpoint instance or null set.
     */
    public function getEndpoint(): ?EndpointInterface
    {
        return $this->endpoint;
    }

    /**
     * Process endpoint request.
     *
     * @param EndpointInterface $endpoint
     * @param WP_REST_Request $request
     * @return WP_Error
     */
    public function process(EndpointInterface $endpoint, WP_REST_Request $request): WP_Error
    {
        return new WP_Error(
            'rest_no_route',
            sprintf(
                __('No route was found matching the URL and request method %s.', 'arrayaccess'),
                '<code>' . $request->get_method() . '</code>'
            ),
            [
                'status' => 404,
            ]
        );
    }
}

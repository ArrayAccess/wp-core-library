<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Service\Services;

use ArrayAccess\WP\Libraries\Core\API\Endpoints;
use ArrayAccess\WP\Libraries\Core\API\Interfaces\RootEndpointInterface;
use ArrayAccess\WP\Libraries\Core\Service\Abstracts\AbstractService;

/**
 * Service to handle REST API.
 */
class Rest extends AbstractService
{
    /**
     * @var string $serviceName Service name.
     */
    protected string $serviceName = 'rest';

    /**
     * @var Endpoints $endpoints
     */
    protected Endpoints $endpoints;

    protected function onConstruct(): void
    {
        $this->description = __('Service to handle REST API.', 'arrayaccess');
        // set the endpoints
        $this->endpoints ??= new Endpoints($this->services->get(Hooks::class));
    }

    /**
     * @return Endpoints
     */
    public function getEndpoints(): Endpoints
    {
        return $this->endpoints;
    }

    /**
     * Add endpoint to the service.
     *
     * @param RootEndpointInterface $endpoint
     * @return void
     */
    public function addEndpoint(RootEndpointInterface $endpoint): void
    {
        $this->endpoints->addEndpoint($endpoint);
    }

    /**
     * Remove endpoint from the service.
     *
     * @param string|RootEndpointInterface $endpoint
     * @return RootEndpointInterface|null
     */
    public function removeEndpoint(string|RootEndpointInterface $endpoint): ?RootEndpointInterface
    {
        return $this->endpoints->removeEndpoint($endpoint);
    }

    /**
     * Get endpoint from the service.
     *
     * @param string|RootEndpointInterface $endpoint
     * @return RootEndpointInterface|null
     */
    public function getEndpoint(string|RootEndpointInterface $endpoint): ?RootEndpointInterface
    {
        return $this->endpoints->getEndpoint($endpoint);
    }

    /**
     * @return array<string, RootEndpointInterface> List of endpoints.
     */
    public function getEndpointList(): array
    {
        return $this->endpoints->getEndpoints();
    }

    /**
     * @return void Register the service of endpoints.
     */
    public function register(): void
    {
        $this->endpoints->register();
    }
}

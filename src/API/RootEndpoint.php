<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\API;

use ArrayAccess\WP\Libraries\Core\API\Interfaces\ProcessorInterface;
use ArrayAccess\WP\Libraries\Core\API\Interfaces\EndpointCollectionInterface;
use ArrayAccess\WP\Libraries\Core\API\Interfaces\RootEndpointInterface;
use ArrayAccess\WP\Libraries\Core\API\Traits\EndpointTrait;
use function did_action;
use function doing_action;
use function register_rest_route;

class RootEndpoint implements RootEndpointInterface
{
    use EndpointTrait;

    protected ?EndpointCollectionInterface $endpointCollection;

    /**
     * RootEndpoint constructor.
     *
     * @param string $namespace Namespace to endpoint.
     * @param string $route Route to endpoint.
     * @param string|array $methods Methods to endpoint.
     * @throws ApiEndpointExceptionInterface
     */
    public function __construct(
        string $namespace,
        string $route,
        string|array $methods,
        ?ProcessorInterface $processor = null,
        ?EndpointCollectionInterface $endpoints = null
    ) {
        // assert namespace & set into property
        $this->assertNamespaceAndSet($namespace, $route);
        $this->assertMethodsAndSet($methods);
        $this->processor = $processor;
        if ($endpoints) {
            $this->setEndpointCollection($endpoints);
        }
    }

    /**
     * Set endpoint processor.
     * Do not proceed if already registered.
     *
     * @param ProcessorInterface $processor
     * @return void
     */
    public function setProcessor(ProcessorInterface $processor): void
    {
        // do not proceed if already registered
        if ($this->isRegistered()) {
            return;
        }

        $this->processor = $processor;
    }

    /**
     * @inheritdoc
     */

    public function setEndpointCollection(EndpointCollectionInterface $endpoints): void
    {
        // do not proceed if already registered
        if ($this->isRegistered() && isset($this->endpointCollection)) {
            return;
        }
        $this->endpointCollection = $endpoints;
    }

    /**
     * @inheritdoc
     */
    public function getEndpointCollection(): ?EndpointCollectionInterface
    {
        return $this->endpointCollection??null;
    }

    /**
     * @inheritdoc
     */
    public function register(EndpointCollectionInterface $endpoints): bool
    {
        if ($this->isRegistered()) {
            return false;
        }

        // do not proceed if the rest api is not initialized
        if (!did_action('rest_api_init') && !doing_action('rest_api_init')) {
            // stop here
            return false;
        }

        $this->setEndpointCollection($endpoints);
        $this->registered = true;
        $processor = $this->getProcessor();
        // do not process if not allowed
        if (!$processor->processable($this)) {
            return false;
        }
        // combine namespace from collection namespace & current namespace
        $parentNamespace = $this->getEndpointCollection()->getNamespace();
        $currentNamespace = $this->getNamespace();
        $namespace = $currentNamespace
            ? $parentNamespace . '/' . $currentNamespace
            : $parentNamespace;

        // register endpoint
        $result = register_rest_route(
            $namespace,
            $this->getRoute(),
            [
                'methods' => $this->getMethods(),
                'callback' => function ($request) use ($processor) {
                    return $processor->process($this, $request);
                },
            ]
        );

        $hook = $this->getEndpointCollection()->getHook();
        // register sub endpoints
        $hook->do('endpoint.before.register.subEndpoints', $this);
        foreach ($this->getEndpoints() as $endpoint) {
            $hook->do('endpoint.before.register.subEndpoint', $endpoint);
            $endpoint->setParentEndpoint($this);
            $status = $endpoint->register($this);
            $hook->do('endpoint.after.register.subEndpoint', $endpoint, $status);
        }
        $hook->do('endpoint.after.register.subEndpoints', $this);
        // return result
        return $result;
    }
}

<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\API;

use ArrayAccess\WP\Libraries\Core\API\Interfaces\ProcessorInterface;
use ArrayAccess\WP\Libraries\Core\API\Interfaces\RootEndpointInterface;
use ArrayAccess\WP\Libraries\Core\API\Interfaces\SubEndpointInterface;
use ArrayAccess\WP\Libraries\Core\API\Traits\EndpointTrait;
use function doing_action;

class SubEndpoint implements SubEndpointInterface
{
    use EndpointTrait;

    /**
     * @var RootEndpointInterface|SubEndpointInterface|null $parentEndpoint
     */
    protected RootEndpointInterface|SubEndpointInterface|null $parentEndpoint;

    /**
     * @throws ApiEndpointExceptionInterface
     */
    public function __construct(
        string $namespace,
        string $route,
        string|array $methods,
        ?ProcessorInterface $processor = null,
        RootEndpointInterface|SubEndpointInterface|null $parentEndpoint = null
    ) {
        // assert namespace & set into property
        $this->assertNamespaceAndSet($namespace, $route);
        $this->assertMethodsAndSet($methods);
        $this->processor = $processor;
        if ($parentEndpoint) {
            $this->setParentEndpoint($parentEndpoint);
        }
    }

    /**
     * @inheritdoc
     */
    public function setParentEndpoint(SubEndpointInterface|RootEndpointInterface $endpoint)
    {
        // do not proceed if already registered
        if ($this->isRegistered()) {
            return;
        }
        $this->parentEndpoint = $endpoint;
    }

    public function getParentEndpoint(): RootEndpointInterface|SubEndpointInterface|null
    {
        return $this->parentEndpoint??null;
    }

    /**
     * @param SubEndpointInterface|RootEndpointInterface $endpoint
     * @return bool
     */
    public function register(SubEndpointInterface|RootEndpointInterface $endpoint): bool
    {
        if ($this->isRegistered()) {
            return false;
        }

        // do not proceed if the rest api is not initialized
        if (!did_action('rest_api_init') && !doing_action('rest_api_init')) {
            // stop here
            return false;
        }
        // set registered to true
        $this->registered = true;
        // set parent endpoint
        $this->setParentEndpoint($endpoint);
        $processor = $this->getProcessor();
        // do not process if not allowed
        if (!$processor->processable($this)) {
            return false;
        }
        // combine namespace from parent namespace & current namespace
        $parentNamespace = $this->getParentEndpoint()->getNamespace();
        $currentNamespace = $this->getNamespace();
        $namespace = $currentNamespace
            ? $parentNamespace . '/' . $currentNamespace
            : $parentNamespace;
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

        // register sub endpoints
        foreach ($this->getEndpoints() as $endpoint) {
            // do not register if endpoint is current endpoint or already registered
            if ($endpoint === $this || $endpoint->isRegistered()) {
                continue;
            }
            $endpoint->register($this);
        }

        return $result;
    }
}

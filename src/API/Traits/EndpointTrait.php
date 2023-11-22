<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\API\Traits;

use ArrayAccess\WP\Libraries\Core\API\Endpoints;
use ArrayAccess\WP\Libraries\Core\API\Exceptions\ApiEndpointException;
use ArrayAccess\WP\Libraries\Core\API\Interfaces\ApiEndpointExceptionInterface;
use ArrayAccess\WP\Libraries\Core\API\Interfaces\ProcessorInterface;
use ArrayAccess\WP\Libraries\Core\API\Interfaces\SubEndpointInterface;
use ArrayAccess\WP\Libraries\Core\API\Processors\NotFoundResourceProcessor;
use function is_string;
use function preg_match;
use function preg_replace;
use function sprintf;
use function trim;

trait EndpointTrait
{
    /**
     * @var string $namespace Namespace to endpoint, sub endpoint allowed empty namespace.
     */
    protected string $namespace = '';

    /**
     * @var string $route Route to endpoint.
     */
    protected string $route;

    /**
     * @var array<string> $methods Methods to endpoint.
     */
    protected array $methods;

    /**
     * @var array<string, SubEndpointInterface> $endpoints List of sub endpoints.
     */
    protected array $endpoints = [];

    /**
     * @var bool $registered Whether endpoint is registered.
     */
    protected bool $registered = false;

    /**
     * @var ProcessorInterface $processor Endpoint processor.
     */
    protected ProcessorInterface $processor;

    /**
     * Assert namespace and set into property.
     *
     * @param string $namespace
     * @param string $route
     * @return void
     * @throws ApiEndpointExceptionInterface
     */
    private function assertNamespaceAndSet(string $namespace, string $route): void
    {
        // remove multiple slashes and replace backslash with slash
        $namespace = preg_replace('~[/\\\]+~', '/', $namespace);
        $namespace = trim($namespace, '/');
        // validate namespace
        if (!empty($namespace) && !Endpoints::isValidNamespace($namespace)) {
            throw new ApiEndpointException(
                sprintf(
                    __('Namespace "%s" is not valid for route "%s".'),
                    $namespace,
                    $route
                ),
                ApiEndpointExceptionInterface::INVALID_ENDPOINT_NAMESPACE
            );
        }
        // validate route (route use regexP to validate)
        if (!Endpoints::isValidRoute($route)) {
            throw new ApiEndpointException(
                sprintf(
                    __('Route "%s" is not valid for namespace "%s".'),
                    $route,
                    $namespace
                ),
                ApiEndpointExceptionInterface::INVALID_ENDPOINT_NAMESPACE
            );
        }

        // set properties namespace
        $this->namespace = $namespace;
        // validate when the route does not have slash on prefix (even it on group regex)
        // add the slash into the first route pattern
        if (str_starts_with($route, '/')) {
            $this->route = $route;
            return;
        }
        // validate when the route does not have a slash on prefix (even it on group regex)
        if (!preg_match('~^(?:/|[(]+(?:\?(?:[:>=]+|P<[^>]+>))?/)~', $route)) {
            // prepend slash
            $route = '/' . $route;
        }
        $this->route = $route;
    }

    /**
     * Assert methods and set into property.
     *
     * @param string|array $methods
     * @return void Throw exception if methods are empty.
     * @throws ApiEndpointExceptionInterface
     */
    private function assertMethodsAndSet(string|array $methods): void
    {
        if (is_string($methods)) {
            $methods = [$methods];
        }
        // filter methods that should be string
        $methods = array_filter($methods, 'is_string');
        // filter that empty method
        $methods = array_filter($methods, 'trim');
        // if empty throw empty method exception
        if (empty($methods)) {
            throw new ApiEndpointException(
                __('Methods cannot be empty.', 'arrayaccess'),
                ApiEndpointExceptionInterface::INVALID_ENDPOINT_METHODS
            );
        }
        // make methods uppercase
        $methods = array_map('strtoupper', $methods);
        // set methods with increment reset values
        $this->methods = array_values($methods);
    }

    /**
     * Assert nested endpoint, max depth check is 10.
     * This prevents infinite loop.
     * Max depth is 10, that prevents too many nested endpoints.
     *
     * @param SubEndpointInterface $endpointToCheck
     * @param SubEndpointInterface $endpoint
     * @param int $depth
     * @return void
     * @throws ApiEndpointExceptionInterface if nested parent endpoint contains current object.
     */
    private function assertNestedEndpoint(
        SubEndpointInterface $endpointToCheck,
        SubEndpointInterface $endpoint,
        int $depth = 0
    ): void {
        // throw exception if endpoint to check is current object
        if ($endpoint === $this) {
            throw new ApiEndpointException(
                __('Cannot add endpoint that current object endpoint.', 'arrayaccess'),
                ApiEndpointExceptionInterface::INVALID_ENDPOINT_NESTED
            );
        }
        // throw exception if endpoint too many nested
        if ($depth > 10) {
            throw new ApiEndpointException(
                __('Cannot add endpoint that too many nested endpoint.', 'arrayaccess'),
                ApiEndpointExceptionInterface::INVALID_ENDPOINT_NESTED
            );
        }
        // throw exception if endpoint to check is parent endpoint
        if ($endpoint->hasEndpoint($endpointToCheck)) {
            throw new ApiEndpointException(
                __('Cannot add endpoint that contains parent endpoint.', 'arrayaccess'),
                ApiEndpointExceptionInterface::INVALID_ENDPOINT_NESTED
            );
        }
        // check deep nested endpoints
        foreach ($endpoint->getEndpoints() as $subEndpoint) {
            $this->assertNestedEndpoint($endpointToCheck, $subEndpoint, $depth + 1);
        }
    }

    /**
     * @inheritdoc
     */
    public function getProcessor(): ProcessorInterface
    {
        return $this->processor ??= new NotFoundResourceProcessor();
    }

    /**
     * @inheritdoc
     */
    public function isAllowed(): bool
    {
        return $this->getProcessor()->processable($this);
    }

    /**
     * @inheritdoc
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * @inheritdoc
     */
    public function getRoute(): string
    {
        return $this->route;
    }

    /**
     * @return array
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * @inheritdoc
     */
    public function hasEndpoint(SubEndpointInterface|string $endpoint): bool
    {
        if (is_string($endpoint)) {
            return isset($this->endpoints[$endpoint]);
        }
        $endPointName = $endpoint->getNamespace() . '/' . $endpoint->getRoute();
        return isset($this->endpoints[$endPointName]);
    }

    /**
     * @inheritdoc
     */
    public function addEndpoint(SubEndpointInterface $endpoint) : bool
    {
        if ($this->isRegistered()) {
            return false;
        }

        $endpointToCheck = $this instanceof SubEndpointInterface ? $this : $endpoint;
        $this->assertNestedEndpoint($endpointToCheck, $endpoint);
        $this->endpoints[$endpoint->getNamespace() . '/' . $endpoint->getRoute()] = $endpoint;
        return true;
    }

    /**
     * @inheritdoc
     */
    public function removeEndpoint(SubEndpointInterface|string $endpoint) : ?SubEndpointInterface
    {
        if ($this->isRegistered()) {
            return null;
        }
        if (is_string($endpoint)) {
            $endpoint = $this->getEndpoint($endpoint);
        }
        if ($endpoint instanceof SubEndpointInterface) {
            unset($this->endpoints[$endpoint->getNamespace() . '/' . $endpoint->getRoute()]);
        }
        return $endpoint;
    }

    /**
     * @inheritdoc
     */
    public function getEndpoint(SubEndpointInterface|string $endpoint): ?SubEndpointInterface
    {
        if (is_string($endpoint)) {
            return $this->endpoints[$endpoint] ?? null;
        }
        $endPointName = $endpoint->getNamespace() . '/' . $endpoint->getRoute();
        return $this->endpoints[$endPointName] ?? null;
    }

    /**
     * @inheritdoc
     * @return int implement countable
     */
    public function count() : int
    {
        return count($this->endpoints);
    }

    /**
     * @inheritdoc
     * @return array<string, SubEndpointInterface>
     */
    public function getEndpoints(): array
    {
        return $this->endpoints;
    }

    /**
     * @inheritdoc
     */
    public function isRegistered(): bool
    {
        return $this->registered;
    }

    /**
     * @inheritdoc
     */
    public function clearEndpoints(): void
    {
        if ($this->isRegistered()) {
            throw new ApiEndpointException(
                __('Cannot clear endpoints when endpoint already registered.', 'arrayaccess'),
                ApiEndpointExceptionInterface::ENDPOINT_ALREADY_REGISTERED
            );
        }
        $this->endpoints = [];
    }
}

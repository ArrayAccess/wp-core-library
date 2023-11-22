<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\API;

use ArrayAccess\WP\Libraries\Core\API\Exceptions\ApiEndpointException;
use ArrayAccess\WP\Libraries\Core\API\Interfaces\ApiEndpointExceptionInterface;
use ArrayAccess\WP\Libraries\Core\API\Interfaces\EndpointCollectionInterface;
use ArrayAccess\WP\Libraries\Core\API\Interfaces\RootEndpointInterface;
use ArrayAccess\WP\Libraries\Core\Service\Hook;
use ArrayAccess\WP\Libraries\Core\Service\Interfaces\HookInterface;
use Throwable;
use function __;
use function preg_last_error;
use function preg_match;
use function preg_quote;
use function preg_replace;
use function remove_action;

/**
 * Handle endpoints for WordPress REST API.
 */
class Endpoints implements EndpointCollectionInterface
{
    /**
     * @var string NAMESPACE_REGEX Namespace regex.
     */
    public const NAMESPACE_REGEX = '~^[a-z0-9-]+(?:/[a-z0-9-]+)?$~i';

    /**
     * @var array<string, RootEndpointInterface> $endpoints List of root endpoints.
     */
    protected array $endpoints = [];

    /**
     * @var string $namespace The namespace. Default is 'arrayaccess/v1'.
     */
    protected string $namespace = 'arrayaccess/v1';

    /**
     * @var bool $registered Whether endpoints are registered.
     */
    private bool $registered = false;

    /**
     * Endpoints constructor.
     *
     * @param HookInterface|null $hook The hook.
     */
    public function __construct(protected ?HookInterface $hook)
    {
    }

    /**
     * @param string $namespace
     * @return bool
     */
    public static function isValidNamespace(string $namespace): bool
    {
        return (bool) preg_match(self::NAMESPACE_REGEX, $namespace);
    }

    /**
     * @param string $route
     * @return bool
     */
    public static function isValidRoute(string $route): bool
    {
        // validate / test if regExp valid
        $delimiter = '#';
        $routeRegex = preg_quote($route, $delimiter);
        try {
            @preg_match($routeRegex, '', $matches, PREG_UNMATCHED_AS_NULL);
            return preg_last_error() === PREG_NO_ERROR;
        } catch (Throwable) {
            return false;
        }
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
    public function setNamespace(string $namespace): ?string
    {
        // If collections already registered, do nothing.
        if ($this->isRegistered()) {
            return null;
        }
        // remove multiple slashes and replace backslash with slash
        $namespace = preg_replace('~[/\\\]+~', '/', $namespace);
        $namespace = trim($namespace, '/');
        if (!self::isValidNamespace($namespace)) {
            return null;
        }
        return $this->namespace = $namespace;
    }

    /**
     * @inheritdoc
     */
    public function getHook(): HookInterface
    {
        return $this->hook ??= new Hook();
    }

    /**
     * @inheritdoc
     */
    public function setHook(HookInterface $hook): void
    {
        $this->hook = $hook;
    }

    /**
     * @inheritdoc
     */
    public function addEndpoint(RootEndpointInterface $endpoint): void
    {
        // If collection-already registered, do nothing.
        if ($this->isRegistered()) {
            return;
        }
        $this->endpoints[$endpoint->getNamespace()] = $endpoint;
    }

    /**
     * @inheritdoc
     */
    public function getEndpoint(string|RootEndpointInterface $endpoint): ?RootEndpointInterface
    {
        if (is_string($endpoint)) {
            return $this->endpoints[$endpoint] ?? null;
        }

        return $this->endpoints[$endpoint->getNamespace()] ?? null;
    }

    /**
     * @inheritdoc
     */
    public function hasEndpoint(string|RootEndpointInterface $endpoint): bool
    {
        if (is_string($endpoint)) {
            return isset($this->endpoints[$endpoint]);
        }

        return isset($this->endpoints[$endpoint->getNamespace()]);
    }

    /**
     * @inheritdoc
     */
    public function removeEndpoint(string|RootEndpointInterface $endpoint): ?RootEndpointInterface
    {
        // If collection-already registered, do nothing.
        if ($this->isRegistered()) {
            return null;
        }
        if (is_string($endpoint)) {
            $endpoint = $this->getEndpoint($endpoint);
        }

        if ($endpoint instanceof RootEndpointInterface) {
            unset($this->endpoints[$endpoint->getNamespace()]);
        }

        return $endpoint;
    }

    /**
     * @inheritdoc
     */
    public function getEndpoints(): array
    {
        return $this->endpoints;
    }

    /**
     * @inheritdoc
     */
    public function clearEndpoints(): void
    {
        // If collection-already registered, do nothing.
        if ($this->isRegistered()) {
            throw new ApiEndpointException(
                __('Cannot clear endpoints after they are registered.', 'arrayaccess'),
                ApiEndpointExceptionInterface::ENDPOINT_ALREADY_REGISTERED
            );
        }
        $this->endpoints = [];
    }

    /**
     * @return int Number of endpoints.
     */
    public function count() : int
    {
        return count($this->endpoints);
    }

    /**
     * Register endpoints.
     */
    public function register(): void
    {
        if ($this->registered) {
            return;
        }

        $this->registered = true;
        $callback = function () use (&$callback) {
            // remove the callback to prevent it from being called twice
            remove_action('rest_api_init', $callback);

            // use local variable to prevent it being overwritten by other hooks
            $hook = $this->getHook();
            $hook->do('endpoints.before.register.root', $this);
            foreach ($this->endpoints as $endpoint) {
                $hook->do('endpoint.before.register.root', $endpoint);

                // set endpoint collection
                $endpoint->setEndpointCollection($this);
                // register endpoint
                $status = $endpoint->register($this);

                $hook->do('endpoint.after.register.root', $endpoint, $status);
            }
            $hook->do('endpoints.after.register.root', $this);
        };

        // If the REST API is already initialized, run callback immediately.
        if (did_action('rest_api_init')) {
            $callback();
        } else {
            add_action('rest_api_init', $callback);
        }
    }

    /**
     * @inheritdoc
     */
    public function isRegistered(): bool
    {
        return $this->registered;
    }
}

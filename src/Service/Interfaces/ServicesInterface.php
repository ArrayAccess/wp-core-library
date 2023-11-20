<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Service\Interfaces;

/**
 * Interface ServicesInterface for services
 */
interface ServicesInterface
{
    /**
     * Add service if not exists
     *
     * @param ServiceInterface|class-string<ServiceInterface> $service The service
     * @return bool true if the service was added
     */
    public function add(ServiceInterface|string $service) : bool;

    /**
     * Set service
     *
     * @param ServiceInterface|class-string<ServiceInterface> $service
     * @return bool true if the service was set
     */
    public function set(ServiceInterface|string $service) : bool;

    /**
     * Remove the service by id or object
     *
     * @param ServiceInterface|class-string<ServiceInterface> $service
     * @return bool true if the service was removed
     */
    public function remove(ServiceInterface|string $service) : bool;

    /**
     * Check if the service exists
     *
     * @param ServiceInterface|class-string<ServiceInterface> $service
     * @return bool
     */
    public function contain(ServiceInterface|string $service) : bool;

    /**
     * Get service by id or ServiceInterface class name
     *
     * @template T of ServiceInterface
     *
     * @psalm-param T|class-string<T> $service
     * @psalm-return ?T
     */
    public function get(ServiceInterface|string $service) : ?ServiceInterface;

    /**
     * Get all services
     * The key is lower case service class name
     *
     * @return array<class-string<ServiceInterface>, ServiceInterface>
     */
    public function getServices() : array;

    /**
     * Load wp admin plugin file
     */
    public static function loadPluginFile();
}

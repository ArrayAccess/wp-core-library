<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Service;

use ArrayAccess\WP\Libraries\Core\Service\Interfaces\ServiceInterface;
use ArrayAccess\WP\Libraries\Core\Service\Interfaces\ServicesInterface;
use ArrayAccess\WP\Libraries\Core\Service\Services\Database;
use ArrayAccess\WP\Libraries\Core\Service\Services\Hooks;
use ArrayAccess\WP\Libraries\Core\Service\Services\Option;
use ArrayAccess\WP\Libraries\Core\Service\Services\StatelessCookie;
use ArrayAccess\WP\Libraries\Core\Util\Consolidator;
use ReflectionClass;
use Throwable;
use function in_array;
use function is_object;
use function is_string;
use function strtolower;

/**
 * @template ObjectService of ServiceInterface
 */
final class Services implements ServicesInterface
{
    /**
     * The services.
     *
     * @var array<class-string<ObjectService>, ObjectService>
     */
    private array $services = [];

    /**
     * @var array <class-string<ObjectService>, class-string<ObjectService>>
     */
    private array $queuedServices = [];

    private array $coreService = [];

    /**
     * @var array<string, false|string>
     */
    private static array $classMap = [];

    /**
     * Default services list.
     * @var array<class-string<ObjectService>>
     */
    public const DEFAULT_SERVICES = [
        Database::class,
        Hooks::class,
        Option::class,
        StatelessCookie::class
    ];

    /**
     * Services constructor.
     * Register default services
     */
    public function __construct()
    {
        foreach (self::DEFAULT_SERVICES as $service) {
            $this->add($service);
            $serviceId = $this->getServiceId($service);
            if (!$serviceId) {
                continue;
            }
            $this->coreService[$serviceId] = true;
        }
    }

    /**
     * Service class string should subclass if ServiceInterface
     *
     * @param string|ServiceInterface $service
     * @return ?string
     */
    public function getServiceClassName(string|ServiceInterface $service) : ?string
    {
        if (is_object($service)) {
            return $service::class;
        }
        $service = ltrim($service, '\\');
        if (!Consolidator::isValidClassName($service)) {
            return null;
        }
        $serviceId = strtolower($service);
        if (isset(self::$classMap[$serviceId])) {
            return self::$classMap[$serviceId]?:null;
        }
        self::$classMap[$serviceId] = false;
        try {
            $serviceRef = new ReflectionClass($service);
            if ($serviceRef->isSubclassOf(ServiceInterface::class)) {
                self::$classMap[$serviceId] = $serviceRef->getName();
            }
        } catch (Throwable) {
        }
        return self::$classMap[$serviceId]?:null;
    }

    private function getServiceId(string|ServiceInterface $service) : ?string
    {
        $serviceId = $this->getServiceClassName($service);
        if (!$serviceId) {
            return null;
        }
        return strtolower($serviceId);
    }

    /**
     * @inheritdoc
     */
    public function add(ServiceInterface|string $service): bool
    {
        $serviceId = $this->getServiceId($service);
        if (!$serviceId) {
            return false;
        }
        if (isset($this->services[$serviceId])
            || isset($this->queuedServices[$serviceId])
        ) {
            return false;
        }
        if (is_object($service)) {
            $this->services[$serviceId] = $service;
            unset($this->queuedServices[$serviceId]);
        } else {
            $this->queuedServices[$serviceId] = self::$classMap[$serviceId]??$service;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function set(ServiceInterface|string $service): bool
    {
        $serviceId = $this->getServiceId($service);
        if (!$serviceId) {
            return false;
        }
        // ignore core service
        if (isset($this->coreService[$serviceId])) {
            return false;
        }
        // check first if argument is class name,
        // when the argument is an object ignore the string checking
        if (is_string($service)) {
            // because class name, it will ignore
            if (isset($this->services[$serviceId])) {
                return false;
            }
            $service = new $service($this);
        }

        $this->services[$serviceId] = $service;
        unset($this->queuedServices[$serviceId]);
        return true;
    }

    /**
     * @inheritdoc
     */
    public function remove(ServiceInterface|string $service) : bool
    {
        $serviceId = $this->getServiceId($service);
        if (!$serviceId) {
            return false;
        }
        /**
         * If the service is core service, do not remove
         */
        if (isset($this->coreService[$serviceId])) {
            return false;
        }

        if (isset($this->services[$serviceId])
            || isset($this->queuedServices[$serviceId])
        ) {
            unset(
                $this->services[$serviceId],
                $this->queuedServices[$serviceId]
            );
            return true;
        }
        return false;
    }

    /**
     * @inheritdoc
     */
    public function contain(ServiceInterface|string $service): bool
    {
        $serviceId = $this->getServiceClassName($service);
        if (!$serviceId) {
            return false;
        }
        return in_array($service, $this->services, true) || (
            in_array($serviceId, $this->queuedServices, true)
        );
    }

    /**
     * @inheritdoc
     * No exception
     */
    public function get(string|ServiceInterface $service): ?ServiceInterface
    {
        $serviceId = $this->getServiceId($service);
        if (!$serviceId) {
            return null;
        }
        if (isset($this->services[$serviceId])) {
            return $this->services[$serviceId];
        }
        if (isset($this->queuedServices[$serviceId])) {
            $service = $this->queuedServices[$serviceId];
            unset($this->queuedServices[$serviceId]);
            if (is_string($service)) {
                return $this->services[$serviceId] = new $service($this);
            }
        }
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getServices(): array
    {
        foreach ($this->queuedServices as $item) {
            $this->get($item);
        }
        return $this->services;
    }
}

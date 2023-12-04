<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Service;

use ArrayAccess\WP\Libraries\Core\Service\Interfaces\InitServiceInterface;
use ArrayAccess\WP\Libraries\Core\Service\Interfaces\ServiceInterface;
use ArrayAccess\WP\Libraries\Core\Service\Interfaces\ServicesInterface;
use ArrayAccess\WP\Libraries\Core\Service\Services\AdminMenu;
use ArrayAccess\WP\Libraries\Core\Service\Services\Ajax;
use ArrayAccess\WP\Libraries\Core\Service\Services\Blocks;
use ArrayAccess\WP\Libraries\Core\Service\Services\Database;
use ArrayAccess\WP\Libraries\Core\Service\Services\DefaultAssets;
use ArrayAccess\WP\Libraries\Core\Service\Services\Hooks;
use ArrayAccess\WP\Libraries\Core\Service\Services\Option;
use ArrayAccess\WP\Libraries\Core\Service\Services\Rest;
use ArrayAccess\WP\Libraries\Core\Service\Services\SiteOption;
use ArrayAccess\WP\Libraries\Core\Service\Services\StatelessHash;
use ArrayAccess\WP\Libraries\Core\Service\Traits\URLReplacerTrait;
use ArrayAccess\WP\Libraries\Core\Util\Consolidator;
use ArrayAccess\WP\Libraries\Core\Util\Filter;
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
    use URLReplacerTrait;

    /**
     * The services.
     *
     * @var array<class-string<ObjectService>, ObjectService>
     */
    private array $services = [];

    /**
     * The queued services that contain class name & not yet created.
     *
     * @var array <class-string<ObjectService>, class-string<ObjectService>>
     */
    private array $queuedServices = [];

    /**
     * List of core services.
     *
     * @var array<class-string<ObjectService>, true>
     */
    private array $coreService = [];

    /**
     * Class map of service class name as cache
     *
     * @var array<string, false|string> false if not yet checked, string if valid class name
     */
    private static array $classMap = [];

    /**
     * @var bool true if plugin file is loaded
     */
    private static bool $pluginFileLoaded = false;

    /**
     * @var bool true if pluggable file is loaded
     */
    private static bool $pluggableFileLoaded = false;

    /**
     * Default services list.
     *
     * @var array<class-string<ObjectService>>
     */
    public const DEFAULT_SERVICES = [
        AdminMenu::class,
        Ajax::class,
        Blocks::class,
        Database::class,
        DefaultAssets::class,
        Hooks::class,
        Option::class,
        SiteOption::class,
        Rest::class,
        StatelessHash::class,
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
        $this->initCore();
    }

    /**
     * Initialize core services
     *
     * @return void
     */
    private function initCore(): void
    {
        foreach ($this->coreService as $serviceId => $item) {
            $service = $this->get($serviceId);
            if ($service instanceof InitServiceInterface && !$service->hasInit()) {
                $service->init();
            }
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
        if (!Filter::className($service)) {
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

    /**
     * Get service id from service class name or object
     *
     * @param string|ServiceInterface $service
     * @return string|null
     */
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
        // check first if argument is class name,
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

        if ($service instanceof InitServiceInterface && !$service->hasInit()) {
            $service->init();
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
        // ignore invalid service
        if (!$serviceId) {
            return false;
        }

        // If the service is a core service, do not remove
        if (isset($this->coreService[$serviceId])) {
            return false;
        }

        // check first if argument is class name,
        // when the argument is an object ignore the string checking
        if (isset($this->services[$serviceId])
            || isset($this->queuedServices[$serviceId])
        ) {
            // remove from services & queued services
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
        $serviceId = $this->getServiceId($service);
        // ignore invalid service
        if (!$serviceId) {
            return false;
        }
        if (is_object($service)) {
            if (isset($this->services[$serviceId])) {
                return in_array(
                    $service,
                    $this->services,
                    true
                );
            }
        }

        // If service object, check if the service is in the services
        return isset($this->services[$serviceId]) || (
            isset($this->queuedServices[$serviceId])
        );
    }

    /**
     * @inheritdoc
     * No exception
     */
    public function get(string|ServiceInterface $service): ?ServiceInterface
    {
        $serviceId = $this->getServiceId($service);
        // ignore invalid service
        if (!$serviceId) {
            return null;
        }
        $service = null;
        // If service object, check if the service is in the services
        if (isset($this->services[$serviceId])) {
            $service = $this->services[$serviceId];
        } else {
            // If service is in the queued services, create the service
            if (isset($this->queuedServices[$serviceId])) {
                $service = $this->queuedServices[$serviceId];
                unset($this->queuedServices[$serviceId]);
                if (is_string($service)) {
                    $service = new $service($this);
                    $this->services[$serviceId] = $service;
                }
            }
        }
        // init the service if it is an init service
        if ($service instanceof InitServiceInterface && !$service->hasInit()) {
            $service->init();
        }
        return $service;
    }

    /**
     * @inheritdoc
     */
    public function getServices(): array
    {
        // create all queued services
        foreach ($this->queuedServices as $item) {
            $this->get($item);
        }
        return $this->services;
    }

    /**
     * @inheritdoc
     */
    final public static function loadPluginFile(): void
    {
        if (self::$pluginFileLoaded) {
            return;
        }
        self::$pluginFileLoaded = true;
        Consolidator::requireNull(
            ABSPATH . 'wp-admin/includes/plugin.php'
        );
    }

    /**
     * @return void load pluggable.php file
     */
    final public static function loadPluggableFile(): void
    {
        if (self::$pluggableFileLoaded) {
            return;
        }
        self::$pluggableFileLoaded = true;
        Consolidator::requireNull(
            ABSPATH . WPINC . '/pluggable.php'
        );
    }
}

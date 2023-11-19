<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Service\Abstracts;

use ArrayAccess\WP\Libraries\Core\Service\Interfaces\ServiceInterface;
use ArrayAccess\WP\Libraries\Core\Service\Interfaces\ServicesInterface;
use function basename;
use function str_replace;

/**
 * AbstractService class that help to create service of various service objects.
 */
abstract class AbstractService implements ServiceInterface
{
    /**
     * @var string The service name.
     */
    protected string $serviceName;

    /**
     * @var string|null The service description.
     */
    protected ?string $description = null;


    /**
     * Service constructor.
     */
    final public function __construct(protected ServicesInterface $services)
    {
        $this->onConstruct();
    }

    /**
     * Method that will be called on construct.
     */
    protected function onConstruct()
    {
        // pass
    }

    /**
     * @inheritdoc
     */
    public function getServices(): ServicesInterface
    {
        return $this->services;
    }

    /**
     * @inheritdoc
     */
    public function getServiceName(): string
    {
        return $this->serviceName ??= basename(str_replace('', '/', static::class));
    }

    /**
     * @inheritdoc
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }
}

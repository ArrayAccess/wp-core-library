<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Service\Abstracts;

use ArrayAccess\WP\Libraries\Core\Service\Interfaces\ServiceInterface;
use ArrayAccess\WP\Libraries\Core\Service\Interfaces\ServicesInterface;
use function basename;
use function str_replace;

abstract class AbstractService implements ServiceInterface
{
    /**
     * @var string The service name.
     */
    protected string $serviceName;

    /**
     * Service constructor.
     */
    public function __construct(protected ServicesInterface $services)
    {
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
}

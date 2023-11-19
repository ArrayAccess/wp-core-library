<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Service\Interfaces;

/**
 * Interface ServiceInterface for services
 */
interface ServiceInterface
{
    /**
     * ServiceInterface constructor.
     *
     * @param ServicesInterface $services
     */
    public function __construct(ServicesInterface $services);

    /**
     * Get ServicesInterface object
     *
     * @return ServicesInterface
     */
    public function getServices() : ServicesInterface;

    /**
     * Service name
     *
     * @return string
     */
    public function getServiceName() : string;

    /**
     * Service description
     *
     * @return ?string null if not set
     */
    public function getDescription(): ?string;
}

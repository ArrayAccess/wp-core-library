<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Service\Interfaces;

interface InitServiceInterface extends ServiceInterface
{
    /**
     * Initialize the service.
     */
    public function init();

    /**
     * @return bool Whether the service is initialized.
     */
    public function hasInit() : bool;
}

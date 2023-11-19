<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Service\Services;

use ArrayAccess\WP\Libraries\Core\Service\Abstracts\AbstractService;

/**
 * Service that help to create admin menu page.
 *
 * Create new admin root menu-object by given argument like constructor of root menu page.
 *
 * @uses \ArrayAccess\WP\Libraries\Core\MenuPage\Interfaces\RootMenuPageInterface
 * @uses \ArrayAccess\WP\Libraries\Core\MenuPage\AdminRootMenu
 */

class AdminMenu extends AbstractService
{
    protected string $serviceName = 'adminMenu';

    /**
     * @inheritdoc
     */
    protected function onConstruct(): void
    {
        $this->description = __(
            'Service that help to create admin menu page.',
            'arrayaccess'
        );
    }
}

<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Service\Services;

use ArrayAccess\WP\Libraries\Core\MenuPage\AdminRootMenu;
use ArrayAccess\WP\Libraries\Core\MenuPage\RootMenuPage;
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
     * @var array<string, AdminRootMenu>
     */
    protected array $registeredRootMenus = [];

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

    /**
     * Get all registered root menus.
     *
     * @return array<string, AdminRootMenu>
     */
    public function getRegisteredRootMenus(): array
    {
        return $this->registeredRootMenus;
    }

    /**
     * Get the root menu page.
     *
     * @param string $slug
     * @return AdminRootMenu|null
     */
    public function getRootMenu(string $slug): ?AdminRootMenu
    {
        return $this->registeredRootMenus[$slug]??null;
    }

    /**
     * Check if root menu page is registered.
     *
     * @param string $slug
     * @return bool
     */
    public function hasRootMenu(string $slug): bool
    {
        return isset($this->registeredRootMenus[$slug]);
    }

    /**
     * @param string $pageTitle
     * @param string $menuTitle
     * @param string $capability
     * @param string $menuSlug
     * @param string $iconUrl
     * @param ?int $position
     * @return AdminRootMenu
     */
    public function createRootMenuPage(
        string $pageTitle,
        string $menuTitle,
        string $capability,
        string $menuSlug,
        string $iconUrl = '',
        ?int $position = null
    ): AdminRootMenu {
        $rootMenu = new RootMenuPage(
            $pageTitle,
            $menuTitle,
            $capability,
            $menuSlug,
            $iconUrl,
            $position
        );
        $adminMenu = new AdminRootMenu($rootMenu, $this->getServices()->get(Hooks::class));
        $this->registeredRootMenus[$adminMenu->getSlug()] = $adminMenu;
        return $adminMenu;
    }
}

<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Service\Services;

use ArrayAccess\WP\Libraries\Core\MenuPage\AdminRootMenu;
use ArrayAccess\WP\Libraries\Core\MenuPage\RootMenuPage;
use ArrayAccess\WP\Libraries\Core\Service\Abstracts\AbstractService;
use Throwable;

/**
 * Service that help to create admin menu page.
 *
 * Create new admin root menu-object by given argument like constructor of root menu page.
 *
 * @uses \ArrayAccess\WP\Libraries\Core\MenuPage\Interfaces\RootMenuPageInterface
 * @uses AdminRootMenu
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
     * Add root menu page. If the root menu page is already registered, it will be replaced.
     *
     * @param AdminRootMenu $rootMenu
     * @return void
     */
    public function addRootMenu(AdminRootMenu $rootMenu): void
    {
        $this->registeredRootMenus[$rootMenu->getSlug()] = $rootMenu;
    }

    /**
     * Remove root menu page by slug.
     * Return the removed root menu page.
     * If the root menu page is not registered, return null.
     *
     * @param string $slug
     * @return ?AdminRootMenu
     */
    public function removeRootMenu(string $slug): ?AdminRootMenu
    {
        $rootMenu = $this->getRootMenu($slug);
        if ($rootMenu) {
            unset($this->registeredRootMenus[$slug]);
        }
        return $rootMenu;
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
        $this->addRootMenu($adminMenu);
        return $adminMenu;
    }

    /**
     * Create root menu page from an array. Return AdminRootMenu if success, Throwable if failed.
     *
     * @param array $data
     * @return AdminRootMenu|Throwable
     */
    public function createFromArray(array $data): AdminRootMenu|Throwable
    {
        try {
            $rootMenu = RootMenuPage::fromArray($data);
            $adminMenu = new AdminRootMenu($rootMenu, $this->getServices()->get(Hooks::class));
            $this->addRootMenu($adminMenu);
            return $adminMenu;
        } catch (Throwable $e) {
            return $e;
        }
    }
}

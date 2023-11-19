<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\MenuPage;

use ArrayAccess\WP\Libraries\Core\MenuPage\Interfaces\RootMenuPageInterface;
use ArrayAccess\WP\Libraries\Core\Service\Interfaces\HookInterface;

/**
 * Admin menu placed into the root of the admin menu.
 * This object handles the root menu page and the submenu pages.
 * @uses RootMenuPageInterface
 */
class AdminRootMenu
{
    /**
     * @var bool true if registered
     */
    protected bool $isRegistered = false;

    /**
     * @param RootMenuPageInterface $menu
     * @param ?HookInterface $hook
     */
    public function __construct(
        protected RootMenuPageInterface $menu,
        protected ?HookInterface $hook
    ) {
    }

    /**
     * @param HookInterface|null $hook
     */
    public function setHook(?HookInterface $hook): void
    {
        $this->hook = $hook;
    }

    /**
     * Get the hook.
     *
     * @return ?HookInterface
     */
    public function getHook(): ?HookInterface
    {
        return $this->hook;
    }

    /**
     * Get the root menu page.
     *
     * @return RootMenuPageInterface
     */
    public function getMenu(): RootMenuPageInterface
    {
        return $this->menu;
    }

    /**
     * Add a submenu page.
     *
     * @param SubMenuPage $submenu
     * @return void
     */
    public function addSubmenu(SubMenuPage $submenu): void
    {
        $this->menu->addSubMenu($submenu);
    }

    /**
     * Remove a submenu page.
     *
     * @param string $slug
     * @return Interfaces\SubMenuPagePageInterface|null
     */
    public function removeSubmenu(string $slug): ?Interfaces\SubMenuPagePageInterface
    {
        return $this->menu->removeSubMenuPage($slug);
    }

    public function getSubmenu(string $slug): ?Interfaces\SubMenuPagePageInterface
    {
        return $this->menu->getSubMenuPage($slug);
    }

    /**
     * Register the menu.
     *
     * @return void
     */
    public function register(): void
    {
        if ($this->isRegistered) {
            return;
        }
        $this->isRegistered = true;
        $hook = $this->getHook();
        $hook?->apply('menu.before.register', $this);
        $result = $this->menu->register();
        $hook?->apply('menu.after.register', $this, $result);
    }
}

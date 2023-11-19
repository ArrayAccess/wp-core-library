<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\MenuPage;

use ArrayAccess\WP\Libraries\Core\MenuPage\Exceptions\MenuRegisteredException;
use ArrayAccess\WP\Libraries\Core\MenuPage\Interfaces\SubMenuPagePageInterface;
use ArrayAccess\WP\Libraries\Core\MenuPage\Interfaces\MenuPageRendererInterface;
use ArrayAccess\WP\Libraries\Core\MenuPage\Interfaces\RootMenuPageInterface;
use ArrayAccess\WP\Libraries\Core\MenuPage\Traits\AdminMenuTrait;

/**
 * Admin menu placed into the root of the admin menu.
 * @see MenuPageInterface comment for position
 */
class RootMenuPage implements RootMenuPageInterface
{
    use AdminMenuTrait;

    /**
     * Key is the menu slug.
     *
     * @var array<string, SubMenuPagePageInterface>
     */
    protected array $subMenuPages = [];

    /**
     * @var bool true if registered
     */
    protected bool $isRegistered = false;

    /**
     * @var string|false|null the hook name if registered
     */
    protected string|null|false $hookName = null;

    /**
     * @inheritdoc
     */
    public function addSubMenu(SubMenuPagePageInterface $menuPage): void
    {
        if ($this->isRegistered()) {
            throw new MenuRegisteredException(
                __(
                    'Cannot add a submenu page after the root menu page has been registered.',
                    'arrayaccess'
                )
            );
        }

        $this->subMenuPages[$menuPage->getSlug()] = $menuPage;
    }

    /**
     * @inheritdoc
     */
    public function addSubMenuPage(
        string $pageTitle,
        string $menuTitle,
        string $capability,
        string $menuSlug,
        string $iconUrl = '',
        int $position = null,
        MenuPageRendererInterface $renderer = null
    ) : SubMenuPagePageInterface {
        if ($this->isRegistered()) {
            throw new MenuRegisteredException(
                __(
                    'Cannot add a submenu page after the root menu page has been registered.',
                    'arrayaccess'
                )
            );
        }
        $menuPage = new SubMenuPage(
            $pageTitle,
            $menuTitle,
            $capability,
            $menuSlug,
            $iconUrl,
            $position
        );

        $this->addSubMenu($menuPage);
        if ($renderer) {
            $menuPage->setRenderer($renderer);
        }
        return $menuPage;
    }

    /**
     * @inheritdoc
     */
    public function getSubMenuPage(string $menuSlug): ?SubMenuPagePageInterface
    {
        return $this->subMenuPages[$menuSlug] ?? null;
    }

    /**
     * @inheritdoc
     */
    public function removeSubMenuPage(string $menuSlug): ?SubMenuPagePageInterface
    {
        if ($this->isRegistered()) {
            throw new MenuRegisteredException(
                __(
                    'Cannot remove a submenu page after the root menu page has been registered.',
                    'arrayaccess'
                )
            );
        }
        $menuPage = $this->getSubMenuPage($menuSlug);
        if ($menuPage) {
            unset($this->subMenuPages[$menuSlug]);
        }
        return $menuPage;
    }

    /**
     * @inheritdoc
     */
    public function getSubMenuPages(): array
    {
        return $this->subMenuPages;
    }

    /**
     * @inheritdoc
     */
    public function count() : int
    {
        return count($this->subMenuPages);
    }

    /**
     * @inheritdoc
     */
    public function register(): ?string
    {
        if ($this->isRegistered) {
            return $this->getHookName();
        }
        // don't register if there are no sub menu pages
        if (count($this->subMenuPages) === 0) {
            return null;
        }
        $this->isRegistered = true;
        if ($this->registerMenu()) {
            foreach ($this->getSubMenuPages() as $menuPage) {
                $menuPage->register($this->getSlug(), $this);
            }
        }
        return $this->getHookName();
    }

    /**
     * Get the hook name.
     *
     * @return ?string the hook name if registered
     */
    public function getHookName(): ?string
    {
        return $this->hookName?:null;
    }

    /**
     * Register the root menu page.
     *
     * @return ?string the hook name if registered
     */
    protected function registerMenu(): ?string
    {
        return ($this->hookName ??= add_menu_page(
            $this->getPageTitle(),
            $this->getMenuTitle(),
            $this->getCapability(),
            $this->getSlug(),
            '',
            $this->getIconUrl(),
            $this->getPosition()
        ))?:null;
    }

    /**
     * @inheritdoc
     */
    public function isRegistered(): bool
    {
        return $this->isRegistered;
    }
}

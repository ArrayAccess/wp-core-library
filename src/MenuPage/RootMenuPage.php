<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\MenuPage;

use ArrayAccess\WP\Libraries\Core\Exceptions\InvalidArgumentException;
use ArrayAccess\WP\Libraries\Core\MenuPage\Exceptions\MenuRegisteredException;
use ArrayAccess\WP\Libraries\Core\MenuPage\Interfaces\MenuPageRendererInterface;
use ArrayAccess\WP\Libraries\Core\MenuPage\Interfaces\RootMenuPageInterface;
use ArrayAccess\WP\Libraries\Core\MenuPage\Interfaces\SubMenuPagePageInterface;
use ArrayAccess\WP\Libraries\Core\MenuPage\Traits\AdminMenuTrait;
use ArrayAccess\WP\Libraries\Core\Service\Services;
use ArrayAccess\WP\Libraries\Core\Util\Filter;
use function add_action;
use function add_menu_page;
use function did_action;
use function doing_action;
use function function_exists;
use function get_plugin_page_hookname;
use function has_action;
use function is_array;
use function remove_action;

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
     * @var string|null|false the hook name if registered
     */
    private string|null|false $hookName = null;

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
     * Register the menu.
     */
    public function register(): ?string
    {
        if ($this->isRegistered) {
            return $this->getHookName();
        }

        $this->isRegistered = true;
        return $this->registerMenu();
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
        if ($this->hookName !== null) {
            return $this->hookName?:null;
        }

        if (!function_exists('get_plugin_page_hookname')) {
            // doing load plugin file
            Services::loadPluginFile();
        }

        $actionMenu = $this->getHookAction();
        $callbackAddMenuPage = function () use ($actionMenu, &$callbackAddMenuPage) {
            if (has_action($actionMenu, $callbackAddMenuPage)) {
                remove_action($actionMenu, $callbackAddMenuPage);
            }
            // don't register if there are no sub menu pages
            if (count($this->subMenuPages) === 0) {
                return $this->hookName = false;
            }
            // don't register if on network admin and not rendering in network admin
            if ($this->isNetworkAdmin() && !$this->isRenderInNetworkAdmin()) {
                return $this->hookName = false;
            }
            $this->hookName = add_menu_page(
                $this->getPageTitle(),
                $this->getMenuTitle(),
                $this->getCapability(),
                $this->getSlug(),
                '',
                $this->getIconUrl(),
                $this->getPosition()
            )?:false;
            // if successfully registered, then register submenu pages
            if ($this->hookName) {
                // register sub menu pages
                foreach ($this->getSubMenuPages() as $menuPage) {
                    $menuPage->register($this);
                }
            }
            return $this->hookName;
        };
        if (!doing_action($actionMenu)
            || did_action($actionMenu)
        ) {
            $this->hookName = $callbackAddMenuPage()?:false;
        } else {
            /**
             * Create hook name based on get_plugin_page_hookname
             * @uses get_plugin_page_hookname()
             * @see add_menu_page()
             */
            $this->hookName = get_plugin_page_hookname($this->getSlug(), '');
            add_action($actionMenu, $callbackAddMenuPage);
        }

        return $this->hookName?:null;
    }

    /**
     * @inheritdoc
     */
    public function isRegistered(): bool
    {
        return $this->isRegistered;
    }

    /**
     * @param array{
     *     page_title: string,
     *     menu_title: string,
     *     capability: string,
     *     menu_slug: string,
     *     icon_url: string,
     *     position: int,
     *     render_in_network_admin: bool,
     *     submenu: SubMenuPagePageInterface[]|array<array{
     *          page_title: string,
     *          menu_title: string,
     *          capability: string,
     *          menu_slug: string,
     *          icon_url: string,
     *          position: int,
     *          render_in_network_admin: bool,
     *          renderer: ?MenuPageRendererInterface|callable|string
     *     }>
     * } $data
     * @return RootMenuPageInterface
     * @see SubMenuPage::fromArray()
     */
    public static function fromArray(array $data): RootMenuPageInterface
    {
        if (!Filter::shouldStrings(
            $data['page_title']??null,
            $data['menu_title']??null,
            $data['capability']??null,
            $data['menu_slug']??null
        )
        ) {
            throw new InvalidArgumentException(
                __(
                    'Invalid data for root menu page.',
                    'arrayaccess'
                )
            );
        }

        $data['icon_url'] = Filter::shouldString($data['icon_url']??'');
        $data['position'] = Filter::shouldInteger($data['position']??null);
        $menu = new static(
            $data['page_title'],
            $data['menu_title'],
            $data['capability'],
            $data['menu_slug'],
            $data['icon_url'],
            $data['position']
        );
        // default to true
        $menu->renderInNetworkAdmin((bool)($data['render_in_network_admin']??true));
        if (is_array($data['submenu']??null)) {
            foreach ($data['submenu'] as $submenu) {
                if (is_array($submenu)
                    && Filter::shouldStrings(
                        $submenu['page_title']??null,
                        $submenu['menu_title']??null,
                        $submenu['capability']??null,
                        $submenu['menu_slug']??null
                    )
                ) {
                    $submenu = SubMenuPage::fromArray($submenu);
                }
                if (Filter::shouldInstanceOf($submenu, SubMenuPagePageInterface::class)) {
                    $menu->addSubMenu($submenu);
                }
            }
        }
        return $menu;
    }
}

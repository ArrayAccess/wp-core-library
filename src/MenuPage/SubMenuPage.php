<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\MenuPage;

use ArrayAccess\WP\Libraries\Core\MenuPage\Interfaces\RootMenuPageInterface;
use ArrayAccess\WP\Libraries\Core\MenuPage\Interfaces\SubMenuPagePageInterface;
use ArrayAccess\WP\Libraries\Core\MenuPage\Interfaces\MenuPageRendererInterface;
use ArrayAccess\WP\Libraries\Core\MenuPage\Traits\AdminMenuTrait;
use ArrayAccess\WP\Libraries\Core\Service\Services;
use function add_submenu_page;
use function doing_action;
use function function_exists;
use function get_plugin_page_hookname;
use function has_action;
use function remove_action;

/**
 * Submenu page that places the menu under a root menu.
 */
class SubMenuPage implements SubMenuPagePageInterface
{
    use AdminMenuTrait;

    /**
     * Default parent menu slug, if register submenu page without root menu page.
     *
     * @var string
     */
    protected string $defaultParentMenuSlug = 'admin.php';

    /**
     * @var MenuPageRendererInterface $renderer
     */
    protected MenuPageRendererInterface $renderer;

    /**
     * @inheritdoc
     */
    public function getDefaultParentMenuSlug(): string
    {
        return $this->defaultParentMenuSlug;
    }

    /**
     * @inheritdoc
     */
    public function setDefaultParentMenuSlug(string $defaultParentMenuSlug): void
    {
        $this->defaultParentMenuSlug = $defaultParentMenuSlug;
    }

    /**
     * @inheritdoc
     */
    public function setRenderer(MenuPageRendererInterface $renderer): void
    {
        $this->renderer = $renderer;
    }

    /**
     * @inheritdoc
     */
    public function getRenderer(): ?MenuPageRendererInterface
    {
        return $this->renderer??null;
    }

    /**
     * @inheritdoc
     */
    public function register(?RootMenuPageInterface $root = null): ?string
    {
        // if the function does not exist, then we need to load the plugin file
        if (!function_exists('get_plugin_page_hookname')) {
            // doing load plugin file
            Services::loadPluginFile();
        }
        $actionMenu = $this->getHookAction();
        $callbackAddSubmenuPage = function () use ($root, $actionMenu, &$callbackAddSubmenuPage) {
            if (has_action($actionMenu, $callbackAddSubmenuPage)) {
                remove_action($actionMenu, $callbackAddSubmenuPage);
            }
            // don't register if on network admin and not rendering in network admin
            if ($this->isNetworkAdmin() && !$this->isRenderInNetworkAdmin()) {
                return false;
            }
            $slug = $root?->getSlug()??$this->getDefaultParentMenuSlug();
            return add_submenu_page(
                $slug,
                $this->getPageTitle(),
                $this->getMenuTitle(),
                $this->getCapability(),
                $this->getSlug(),
                function () {
                    $this->getRenderer()?->render($this);
                }
            );
        };

        if (!doing_action($actionMenu)
            || did_action($actionMenu)
        ) {
            return $callbackAddSubmenuPage()?:null;
        } else {
            /**
             * Create hook name based on get_plugin_page_hookname
             * @uses get_plugin_page_hookname()
             * @see add_submenu_page()
             */
            $hookName = get_plugin_page_hookname($this->getSlug(), '');
            add_action($actionMenu, $callbackAddSubmenuPage);
            return $hookName;
        }
    }
}

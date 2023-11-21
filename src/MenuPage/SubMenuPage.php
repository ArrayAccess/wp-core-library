<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\MenuPage;

use ArrayAccess\WP\Libraries\Core\Exceptions\InvalidArgumentException;
use ArrayAccess\WP\Libraries\Core\MenuPage\Interfaces\RootMenuPageInterface;
use ArrayAccess\WP\Libraries\Core\MenuPage\Interfaces\SubMenuPagePageInterface;
use ArrayAccess\WP\Libraries\Core\MenuPage\Interfaces\MenuPageRendererInterface;
use ArrayAccess\WP\Libraries\Core\MenuPage\Renderer\CallbackRenderer;
use ArrayAccess\WP\Libraries\Core\MenuPage\Renderer\FileRenderer;
use ArrayAccess\WP\Libraries\Core\MenuPage\Traits\AdminMenuTrait;
use ArrayAccess\WP\Libraries\Core\Service\Services;
use ArrayAccess\WP\Libraries\Core\Util\Filter;
use function add_submenu_page;
use function doing_action;
use function function_exists;
use function get_plugin_page_hookname;
use function has_action;
use function is_callable;
use function is_file;
use function is_string;
use function remove_action;
use function str_ends_with;

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

    /**
     * @param array{
     *     page_title: string,
     *     menu_title: string,
     *     capability: string,
     *     menu_slug: string,
     *     icon_url: string,
     *     position: int,
     *     render_in_network_admin: bool,
     *     renderer: ?MenuPageRendererInterface|callable|string,
     * } $data
     * @return SubMenuPagePageInterface
     */
    public static function fromArray(array $data): SubMenuPagePageInterface
    {
        if (!Filter::shouldStrings(
            $data['page_title']??null,
            $data['menu_title']??null,
            $data['capability']??null,
            $data['menu_slug']??null
        )) {
            throw new InvalidArgumentException(
                __('Invalid data for submenu page.', 'arrayaccess')
            );
        }

        $data['icon_url'] = Filter::shouldString($data['icon_url']??'');
        $data['position'] = Filter::shouldInteger($data['position']??null);
        $menu = new static(
            $data['menu_title'],
            $data['page_title'],
            $data['capability'],
            $data['menu_slug'],
            $data['icon_url'],
            $data['position']
        );
        // default render in network admin is true
        $menu->renderInNetworkAdmin((bool)($data['render_in_network_admin']??true));
        $data['renderer'] ??= null;
        // if renderer is object renderer
        if (Filter::shouldInstanceOf($data['renderer'], MenuPageRendererInterface::class)) {
            $menu->setRenderer($data['renderer']);
        } elseif (is_callable($data['renderer'])) { // if renderer is callable
            $menu->setRenderer(new CallbackRenderer($data['renderer']));
        } elseif (is_string($data['renderer'])
            && str_ends_with($data['renderer'], '.php')
            && is_file($data['renderer'])
        ) { // if renderer is a php file
            $menu->setRenderer(new FileRenderer($data['renderer']));
        }
        return $menu;
    }
}

<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\MenuPage;

use ArrayAccess\WP\Libraries\Core\MenuPage\Interfaces\RootMenuPageInterface;
use ArrayAccess\WP\Libraries\Core\MenuPage\Interfaces\SubMenuPagePageInterface;
use ArrayAccess\WP\Libraries\Core\MenuPage\Interfaces\MenuPageRendererInterface;
use ArrayAccess\WP\Libraries\Core\MenuPage\Traits\AdminMenuTrait;

/**
 * Submenu page that places the menu under a root menu.
 */
class SubMenuPage implements SubMenuPagePageInterface
{
    use AdminMenuTrait;

    /**
     * @var MenuPageRendererInterface $renderer
     */
    protected MenuPageRendererInterface $renderer;

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
     * @param string $parentSlug
     * @param RootMenuPageInterface|null $root
     * @inheritdoc
     */
    public function register(string $parentSlug, ?RootMenuPageInterface $root = null): ?string
    {
        return add_submenu_page(
            $root ? $root->getSlug() : $parentSlug,
            $this->getPageTitle(),
            $this->getMenuTitle(),
            $this->getCapability(),
            $this->getSlug(),
            function () {
                $this->getRenderer()?->render($this);
            }
        )?:null;
    }
}

<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\MenuPage\Interfaces;

use function add_submenu_page;

interface SubMenuPagePageInterface extends MenuPageInterface
{
    /**
     * Set the renderer.
     *
     * @param MenuPageRendererInterface $renderer
     */
    public function setRenderer(MenuPageRendererInterface $renderer);

    /**
     * Get the renderer.
     *
     * @return MenuPageRendererInterface
     */
    public function getRenderer(): MenuPageRendererInterface;

    /**
     * Register the menu page.
     *
     * @param string $parentSlug the slug of the parent menu page
     * @param RootMenuPageInterface|null $root if null, use the root menu page
     * @return ?string null if not registered
     * @uses add_submenu_page()
     */
    public function register(string $parentSlug, ?RootMenuPageInterface $root = null) : ?string;
}

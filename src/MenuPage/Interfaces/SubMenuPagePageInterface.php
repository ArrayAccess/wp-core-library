<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\MenuPage\Interfaces;

interface SubMenuPagePageInterface extends MenuPageInterface
{
    /**
     * Get the default parent menu slug.
     *
     * @return string
     */
    public function getDefaultParentMenuSlug(): string;

    /**
     * Set the default parent menu slug to use if the root menu page is not registered.
     *
     * @param string $defaultParentMenuSlug
     */
    public function setDefaultParentMenuSlug(string $defaultParentMenuSlug);

    /**
     * Set the renderer.
     *
     * @param MenuPageRendererInterface $renderer
     */
    public function setRenderer(MenuPageRendererInterface $renderer);

    /**
     * Get the renderer.
     *
     * @return ?MenuPageRendererInterface
     */
    public function getRenderer(): ?MenuPageRendererInterface;

    /**
     * Register the menu page.
     *
     * @param ?RootMenuPageInterface $root
     * @return ?string null if not registered
     * @uses \add_submenu_page()
     */
    public function register(?RootMenuPageInterface $root = null) : ?string;
}

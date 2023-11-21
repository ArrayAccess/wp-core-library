<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\MenuPage\Interfaces;

use Countable;

/**
 * @see MenuPageInterface
 */
interface RootMenuPageInterface extends MenuPageInterface, Countable
{
    /**
     * Add a sub menu page.
     *
     * @param SubMenuPagePageInterface $menuPage
     */
    public function addSubMenu(SubMenuPagePageInterface $menuPage);

    /**
     * Add a sub menu page.
     *
     * @param string $pageTitle
     * @param string $menuTitle
     * @param string $capability
     * @param string $menuSlug
     * @param string $iconUrl
     * @param int|null $position
     * @param MenuPageRendererInterface|null $renderer
     * @return SubMenuPagePageInterface
     */
    public function addSubMenuPage(
        string $pageTitle,
        string $menuTitle,
        string $capability,
        string $menuSlug,
        string $iconUrl = '',
        int $position = null,
        MenuPageRendererInterface $renderer = null
    ) : SubMenuPagePageInterface;

    /**
     * Get a sub menu page.
     *
     * @param string $menuSlug
     * @return SubMenuPagePageInterface|null null if not found
     */
    public function getSubMenuPage(string $menuSlug) : ?SubMenuPagePageInterface;

    /**
     * Remove a sub menu page.
     *
     * @param string $menuSlug
     * @return SubMenuPagePageInterface|null null if not found
     */
    public function removeSubMenuPage(string $menuSlug) : ?SubMenuPagePageInterface;

    /**
     * Get the sub menu pages.
     * @return array<string, SubMenuPagePageInterface>
     */
    public function getSubMenuPages() : array;

    /**
     * Check if the menu page is registered.
     *
     * @return bool
     */
    public function isRegistered(): bool;
}

<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\MenuPage\Interfaces;

interface MenuPageRendererInterface
{
    /**
     * Render the menu page.
     *
     * @param SubMenuPagePageInterface $menuPage
     * @return void
     */
    public function render(SubMenuPagePageInterface $menuPage): void;

    /**
     * Check if the menu page has been rendered.
     *
     * @return bool
     */
    public function isRendered(): bool;
}

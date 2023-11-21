<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\MenuPage\Renderer;

use ArrayAccess\WP\Libraries\Core\MenuPage\Interfaces\MenuPageRendererInterface;
use ArrayAccess\WP\Libraries\Core\MenuPage\Interfaces\SubMenuPagePageInterface;

class VoidRenderer implements MenuPageRendererInterface
{
    /**
     * @param SubMenuPagePageInterface $menuPage
     * @inheritdoc
     */
    public function render(SubMenuPagePageInterface $menuPage): void
    {
        // Do nothing.
    }

    /**
     * @inheritdoc
     */
    public function isRendered(): bool
    {
        return true;
    }
}

<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\MenuPage\Renderer;

use ArrayAccess\WP\Libraries\Core\MenuPage\Interfaces\MenuPageRendererInterface;
use ArrayAccess\WP\Libraries\Core\MenuPage\Interfaces\SubMenuPagePageInterface;

/**
 * Callback renderer used to render a menu page with a callback.
 */
class CallbackRenderer implements MenuPageRendererInterface
{
    /**
     * @var callable $callback The callback to render.
     */
    protected $callback;

    /**
     * @var bool $rendered If the callback has been rendered.
     */
    protected bool $rendered = false;

    /**
     * @param callable $callback The callback to render.
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * @param SubMenuPagePageInterface $menuPage
     * @inheritdoc
     */
    public function render(SubMenuPagePageInterface $menuPage): void
    {
        if ($this->isRendered()) {
            return;
        }

        $this->rendered = true;
        call_user_func($this->getCallback(), $menuPage, $this);
    }

    /**
     * @inheritdoc
     */
    public function isRendered(): bool
    {
        return $this->rendered;
    }

    /**
     * Get the callback.
     *
     * @return callable
     */
    protected function getCallback(): callable
    {
        return $this->callback;
    }
}

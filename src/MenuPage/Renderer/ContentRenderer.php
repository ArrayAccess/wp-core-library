<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\MenuPage\Renderer;

use ArrayAccess\WP\Libraries\Core\MenuPage\Interfaces\MenuPageRendererInterface;
use ArrayAccess\WP\Libraries\Core\MenuPage\Interfaces\SubMenuPagePageInterface;

/**
 * Render a menu page with content from a string.
 */
class ContentRenderer implements MenuPageRendererInterface
{
    /**
     * @var string $content The content to render.
     */
    protected string $content;

    /**
     * @var bool $rendered If the content has been rendered.
     */
    protected bool $rendered = false;

    /**
     * @param string $content The content to render.
     */
    public function __construct(string $content)
    {
        $this->content = $content;
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
        echo $this->getContent();
    }

    /**
     * @param string $content The content to render.
     */
    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    /**
     * @inheritdoc
     */
    public function isRendered(): bool
    {
        return $this->rendered;
    }

    /**
     * Get the content.
     *
     * @return string
     */
    protected function getContent(): string
    {
        return $this->content;
    }
}

<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\MenuPage\Renderer;

use ArrayAccess\WP\Libraries\Core\MenuPage\Interfaces\MenuPageRendererInterface;
use ArrayAccess\WP\Libraries\Core\MenuPage\Interfaces\SubMenuPagePageInterface;
use function is_file;

/**
 * Render a menu page with content from a file.
 */
class FileRenderer implements MenuPageRendererInterface
{
    /**
     * @var string $file The file to render.
     */
    protected string $file;

    /**
     * @var bool $rendered If the file has been rendered.
     */
    protected bool $rendered = false;

    /**
     * @param string $file The file to render.
     */
    public function __construct(string $file)
    {
        $this->file = $file;
    }

    /**
     * @inheritdoc
     */
    public function render(SubMenuPagePageInterface $menuPage): void
    {
        if ($this->isRendered()) {
            return;
        }

        $this->rendered = true;
        if (is_file($this->getFile())) {
            require $this->getFile();
        }
    }

    /**
     * @inheritdoc
     */
    public function isRendered(): bool
    {
        return $this->rendered;
    }

    /**
     * Get the file.
     *
     * @return string
     */
    protected function getFile(): string
    {
        return $this->file;
    }
}

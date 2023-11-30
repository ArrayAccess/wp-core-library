<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Block\Abstracts;

use ArrayAccess\WP\Libraries\Core\Block\Interfaces\BlockInterface;
use ArrayAccess\WP\Libraries\Core\Block\Interfaces\BlockServiceInterface;
use ArrayAccess\WP\Libraries\Core\Util\Filter;
use function array_filter;
use function array_merge;
use function array_unique;
use function doing_action;
use function get_class;
use function preg_replace_callback;
use function sprintf;
use function ucfirst;
use function ucwords;

abstract class AbstractBlock implements BlockInterface
{
    /**
     * @var bool Whether the block is dispatched
     */
    private bool $dispatched = false;

    /**
     * @var string The block unique id
     */
    protected string $id;

    /**
     * @var ?string The block version
     */
    protected ?string $version = null;

    /**
     * @var string The block name / title
     */
    protected string $title;

    /**
     * @var string The block icon
     */
    protected string $icon = 'admin-generic';

    /**
     * @var array|string[] required block components / dependency
     */
    protected array $components = self::DEFAULT_COMPONENTS;

    /**
     * @inheritdoc
     */
    final public function dispatch(BlockServiceInterface $blockService) : void
    {
        if ($this->dispatched) {
            return;
        }

        $this->dispatched = true;
        $callback = function () use ($blockService) {
            $components = $this->getComponents();
            // fallback to default components
            $this->components = array_filter($components, 'is_string');
            $this->components = array_merge(self::DEFAULT_COMPONENTS, $this->components);
            $this->components = array_unique($this->components);
            $handle = sprintf('block-%s', $this->getId());
            wp_enqueue_script(
                $handle,
                $this->getUrl(),
                $this->components,
                $this->getVersion()?:false
            );
            $this->doDispatch($blockService);
        };

        if (did_action('enqueue_block_editor_assets')
            || doing_action('enqueue_block_editor_assets')
        ) {
            $callback();
        } else {
            add_action('enqueue_block_editor_assets', $callback);
        }
    }

    /**
     * @return bool Whether the block is dispatched
     */
    public function isDispatched(): bool
    {
        return $this->dispatched;
    }

    /**
     * @return array|string[] required block components / dependency
     */
    public function getComponents(): array
    {
        return $this->components;
    }

    /**
     * @inheritdoc
     */
    public function getId() : string
    {
        return $this->id ??= get_class($this);
    }

    /**
     * @return string The block version
     */
    public function getTitle(): string
    {
        if (!empty($this->title)) {
            return $this->title;
        }
        $className = Filter::classShortName($this);
        $className = ucwords(ucfirst($className), '_');
        // split camel/pascal case
        $className = preg_replace_callback('/[A-Z]/', function ($matches) {
            return ' ' . $matches[0];
        }, $className);
        $className = trim($className);
        return $this->title = sprintf(
            '%s Block',
            $className
        );
    }

    /**
     * @inheritdoc
     */
    public function getIcon(): string
    {
        return $this->icon;
    }

    /**
     * @return ?string The block version
     */
    public function getVersion(): ?string
    {
        return $this->version;
    }

    /**
     * Dispatch the block
     *
     * @param BlockServiceInterface $blockService
     */
    protected function doDispatch(BlockServiceInterface $blockService)
    {
        // pass
    }
}

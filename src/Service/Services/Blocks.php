<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Service\Services;

use ArrayAccess\WP\Libraries\Core\Block\Blocks\BlockCodeEditor;
use ArrayAccess\WP\Libraries\Core\Block\Interfaces\BlockInterface;
use ArrayAccess\WP\Libraries\Core\Block\Interfaces\BlockServiceInterface;
use ArrayAccess\WP\Libraries\Core\Service\Abstracts\AbstractService;
use function array_filter;
use function array_merge;
use function did_action;
use function doing_action;
use function is_array;
use function remove_action;
use function trim;
use function wp_add_inline_script;

/**
 * Handle and provide widget for block editor
 */
class Blocks extends AbstractService implements BlockServiceInterface
{
    /**
     * @var string The service name.
     */
    protected string $serviceName = 'blockWidgets';

    /**
     * @var array<string, BlockInterface> The block widget lists.
     */
    protected array $blocks = [];

    /**
     * @var array<string, bool> The enqueued block widget lists.
     */
    protected array $enqueued = [];

    /**
     * @var array<string, bool> The dispatched blocks lists.
     */
    protected array $dispatched = [];

    /**
     * @var bool Whether the service is initialized.
     */
    private bool $init = false;

    /**
     * @var array<string, array> The widget lists.
     */
    public const WIDGETS = [
        BlockCodeEditor::class
    ];

    protected function onConstruct(): void
    {
        $this->description = __(
            'Handle and provide widget for block editor',
            'arrayaccess'
        );

        foreach (self::WIDGETS as $widget) {
            $this->register(new $widget($this));
        }
    }

    /**
     * @inheritdoc
     */
    public function register(BlockInterface $block, bool $skipExists = true): bool
    {
        $id = $block->getId();
        // if block is already enqueued
        if (isset($this->enqueued[$id])) {
            return false;
        }
        if (isset($this->blocks[$id])) {
            if ($skipExists) {
                return false;
            }
        }
        $this->blocks[$id] = $block;
        return true;
    }

    /**
     * @inheritdoc
     */
    public function get(BlockInterface|string $block): ?BlockInterface
    {
        $id = $block instanceof BlockInterface
            ? $block->getId()
            : $block;
        if (!isset($this->blocks[$id])) {
            return null;
        }

        if ($block instanceof BlockInterface) {
            if ($block !== $this->blocks[$id]) {
                return null;
            }
        }
        return $this->blocks[$id];
    }

    /**
     * @inheritdoc
     */
    public function remove(BlockInterface|string $block): ?BlockInterface
    {
        $instance = $this->get($block);
        if (!$instance) {
            return null;
        }
        $id = $instance->getId();
        if (!isset($this->blocks[$id])) {
            return null;
        }
        if (isset($this->enqueued[$id])) {
            return null;
        }
        if ($block instanceof BlockInterface) {
            if ($block !== $instance) {
                return null;
            }
        }
        unset($this->blocks[$id]);
        return $instance;
    }

    /**
     * @inheritdoc
     */
    public function getBlocks(): array
    {
        return $this->blocks;
    }

    /**
     * @inheritdoc
     */
    public function has(BlockInterface|string $block): bool
    {
        return (bool) $this->get($block);
    }

    /**
     * @param BlockInterface|string $block
     * @return bool
     */
    public function enqueueBlock(BlockInterface|string $block): bool
    {
        if ($block instanceof BlockInterface) {
            if (!$this->register($block, false)) {
                return false;
            }
        }

        $block = $this->get($block);
        // if block is already dispatched
        if (!$block || isset($this->dispatched[$block->getId()])) {
            return false;
        }
        $this->enqueued[$block->getId()] = true;
        return true;
    }

    /**
     * @inheritdoc
     */
    public function hasInit(): bool
    {
        return $this->init;
    }

    /**
     * @inheritdoc
     */
    public function init() : void
    {
        if ($this->init) {
            return;
        }
        $this->init = true;
        $this->dispatchBlocks();
        $this->localizeScript();
    }

    /**
     * Dispatch the blocks.
     *
     * @return void
     */
    public function dispatchBlocks(): void
    {
        $callback = function () use (&$callback) {
            remove_action('enqueue_block_editor_assets', $callback);
            foreach ($this->enqueued as $block => $value) {
                if (isset($this->dispatched[$block])) {
                    continue;
                }
                $block = $this->get($block);
                if (!$block) {
                    unset($this->enqueued[$block]);
                    continue;
                }
                if (!$block->isDispatched()) {
                    $block->dispatch($this);
                }
                $this->dispatched[$block->getId()] = true;
            }
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
     * @inheritdoc
     */
    public function getEnqueuedBlocks(): array
    {
        $enqueued = [];
        foreach ($this->enqueued as $name => $value) {
            if (!isset($this->blocks[$name])) {
                continue;
            }
            $enqueued[$name] = $this->blocks[$name];
        }
        return $enqueued;
    }

    /**
     * Block widget lists
     */
    public const WIDGET_LISTS = [
        'arrayaccess-block-code-editor' => [
            'url' => '{{dist_url}}/blocks/code-editor.min.js',
            'components' => [
                'wp-blocks',
                'wp-components',
                'wp-editor',
            ],
            'version' => '1.0.0',
        ],
    ];


    protected array $widgetLists = self::WIDGET_LISTS;

    /**
     * @var array<string, bool> The rendered block.
     */
    private array $rendered = [];

    /**
     * @param string $name The name of the widget.
     * @param string $url The url of the script.
     * @param array $components The components of the script.
     * @param string|null $version The version of the script.
     * @return bool Whether the widget is added.
     */
    public function addWidget(
        string $name,
        string $url,
        array $components = [
            'wp-blocks',
            'wp-components',
            'wp-editor',
        ],
        ?string $version = null
    ): bool {
        $name = trim($name);
        if (!$name || isset(self::WIDGET_LISTS[$name])) {
            return false;
        }
        $components = array_filter($components, 'is_string');
        $this->widgetLists[$name] = [
            'url' => $url,
            'components' => $components,
            'version' => $version,
        ];
        return true;
    }

    /**
     * Remove the widget.
     *
     * @param string $name
     * @return array|null
     */
    public function removeWidget(string $name) : ?array
    {
        $name = trim($name);
        if (isset(self::WIDGET_LISTS[$name])
            || !isset($this->widgetLists[$name])
            || isset($this->rendered[$name])
        ) {
            return null;
        }
        $meta = $this->widgetLists[$name];
        unset($this->widgetLists[$name]);
        return $meta;
    }

    /**
     * @return array<string, array> The rendered block.
     */
    public function getRendered(): array
    {
        $rendered = [];
        foreach ($this->rendered as $name => $value) {
            if (!isset($this->widgetLists[$name])) {
                continue;
            }
            $rendered[$name] = $this->widgetLists[$name];
        }
        return $rendered;
    }

    /**
     * Localize the script.
     *
     * @return void
     */
    private function localizeScript(): void
    {
        // add global localized script variable of arrayaccessBlockWidgets
        // on admin page
        $callback = function () use (&$callback) {
            remove_action('admin_enqueue_scripts', $callback, 99999);
            $widgetIconList = [];
            $widgetTitleList = [];
            foreach ($this->getEnqueuedBlocks() as $block) {
                $widgetIconList[$block->getId()] = $block->getIcon();
                $widgetTitleList[$block->getId()] = $block->getTitle();
            }

            $hook = $this->getServices()->get(Hooks::class);
            // render default block widgets
            $newWidgetLists = $hook?->apply('blocks.widgets.title', $widgetTitleList)??$widgetTitleList;
            $newWidgetIcons = $hook?->apply('blocks.widgets.icon', $widgetIconList)??$widgetIconList;
            $filter = static function ($lists, array $default) {
                $newArray = !is_array($lists) ? [] : $lists;
                $newArray = array_filter($newArray, 'is_string');
                return array_merge($default, $newArray);
            };
            $newWidgetLists = $filter($widgetTitleList, $newWidgetLists);
            $newWidgetIcons = $filter($widgetIconList, $newWidgetIcons);
            wp_add_inline_script(
                'wp-i18n',
                '(() => window.arrayaccessBlockWidgetsTitle = ' . wp_json_encode($newWidgetLists) . ')();'
            );
            wp_add_inline_script(
                'wp-i18n',
                '(() => window.arrayaccessBlockWidgetsIcons = ' . wp_json_encode($newWidgetIcons) . ')();'
            );
        };
        if (did_action('admin_enqueue_scripts')
            || doing_action('admin_enqueue_scripts')
        ) {
            $callback();
        } else {
            add_action('admin_enqueue_scripts', $callback, 99999);
        }
    }
}

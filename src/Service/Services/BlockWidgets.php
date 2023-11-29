<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Service\Services;

use ArrayAccess\WP\Libraries\Core\Service\Abstracts\AbstractService;
use ArrayAccess\WP\Libraries\Core\Service\Traits\URLReplacerTrait;
use function array_filter;
use function array_merge;
use function did_action;
use function doing_action;
use function has_filter;
use function is_string;
use function preg_match;
use function remove_action;
use function str_contains;
use function str_starts_with;
use function strtolower;
use function trim;

/**
 * Handle and provide widget for block editor
 */
class BlockWidgets extends AbstractService
{
    use URLReplacerTrait;

    /**
     * @var string The service name.
     */
    protected string $serviceName = 'blockWidgets';

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

    /**
     * @var array<string, bool> The rendered block.
     */
    private array $rendered = [];

    /**
     * @var bool Whether the service is initialized.
     */
    private bool $init = false;

    /**
     * Init the service.
     * @return void No return type.
     */
    public function init(): void
    {
        if ($this->init) {
            return;
        }
        $this->init = true;
        $this->localizeScript();
        $callback = function ($content) use (&$callback) {
            remove_filter('the_content', $callback);
            // if content contains data-code-editor attribute
            // render
            if (str_contains($content, 'data-code-editor=')
                && preg_match('~<[a-z]+\s+.+data-code-editor=(["\']?)[^\"\'\s]+\1~', $content)
            ) {
                /**
                 * @var DefaultAssets $defaultAssets
                 */
                $defaultAssets = $this
                    ->services
                    ->get(DefaultAssets::class);
                $defaultAssets->enqueueAsset('arrayaccess-highlightjs', 'css');
                $defaultAssets->enqueueAsset('arrayaccess-editor');
            }

            return $content;
        };
        if (has_filter('the_content', $callback)) {
            return;
        }
        add_filter('the_content', $callback);
    }

    /**
     * Localize the script.
     *
     * @return void
     */
    private function localizeScript(): void
    {
        $widgetTitleList = [
            'arrayaccess-block-code-editor' => __('ArrayAccess Block Widgets', 'arrayaccess'),
        ];
        $hook = $this->getServices()->get(Hooks::class);
        $newWidgetLists = $hook?->apply('blocks.widgets.title', $widgetTitleList)??$widgetTitleList;
        $newWidgetLists = !is_string($newWidgetLists) ? [] : $newWidgetLists;
        $newWidgetLists = array_filter($newWidgetLists, 'is_string');
        $newWidgetLists = array_merge($widgetTitleList, $newWidgetLists);
        // add global localized script variable of arrayaccessBlockWidgets
        // on admin page
        $callback = function () use (&$callback, $newWidgetLists) {
            remove_action('admin_enqueue_scripts', $callback);
            wp_localize_script(
                'arrayaccess-admin',
                'arrayaccessBlockWidgets',
                $newWidgetLists
            );
        };
        if (did_action('admin_enqueue_scripts')
            || doing_action('admin_enqueue_scripts')
        ) {
            $callback();
        } else {
            add_action('admin_enqueue_scripts', $callback);
        }
    }

    /**
     * Render the block widget.
     *
     * @param string $name The block name.
     * @return bool
     */
    public function enqueueBlock(string $name) : bool
    {
        $name = strtolower(trim($name));
        if (!$name) {
            return false;
        }
        if (!str_starts_with('arrayaccess-block-', $name)) {
            $name = 'arrayaccess-block-' . $name;
        }
        $meta = self::WIDGET_LISTS[$name]??null;
        if (!$meta) {
            return false;
        }
        if (isset($this->rendered[$name])) {
            return true;
        }
        $this->rendered[$name] = true;
        $callback = function () use (&$callback, $name, $meta) {
            remove_action('enqueue_block_editor_assets', $callback);
            $url = $meta['url'];
            $url = $this->replaceAssets($url);
            wp_enqueue_script(
                $name,
                $url,
                $meta['components'],
                $meta['version']??null,
                true
            );
        };
        $this->getServices()
            ->get(DefaultAssets::class)
            ->enqueueAsset('arrayaccess-admin');
        if (did_action('enqueue_block_editor_assets')
            || doing_action('enqueue_block_editor_assets')
        ) {
            $callback();
        } else {
            add_action('enqueue_block_editor_assets', $callback);
        }
        return true;
    }
}

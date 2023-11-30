<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Block\Blocks;

use ArrayAccess\WP\Libraries\Core\Block\Abstracts\AbstractBlock;
use ArrayAccess\WP\Libraries\Core\Block\Interfaces\BlockServiceInterface;
use ArrayAccess\WP\Libraries\Core\Service\Services\DefaultAssets;
use function preg_match;
use function remove_filter;

class BlockCodeEditor extends AbstractBlock
{
    /**
     * @var string The block unique id
     */
    protected string $id = 'arrayaccess-block-code-editor';

    /**
     * @var ?string The block version
     */
    protected ?string $version = '1.0.0';

    /**
     * @var array|string[] required block components / dependency
     */
    protected array $components = [
        'wp-blocks',
        'wp-element',
        'wp-i18n',
        'wp-editor',
        'arrayaccess-editor'
    ];

    /**
     * @var string The block name / title
     */
    protected string $url;

    /**
     * Construct the block
     */
    public function __construct(BlockServiceInterface $blockService)
    {
        $this->title = __('ArrayAccess Code Editor', 'arrayaccess');
        $this->url = $blockService
            ->getServices()
            ->replaceURL('{{dist_url}}/blocks/code-editor.min.js');
        $this->doConstruct($blockService);
    }

    /**
     * Do construct
     *
     * @param BlockServiceInterface $blockService
     * @return void
     */
    private function doConstruct(BlockServiceInterface $blockService): void
    {
        $callback = function ($content) use (&$callback, $blockService) {
            remove_filter('the_content', $callback);
            if (!$this->isDispatched() && !$blockService->has($this)) {
                return $content;
            }
            // if content contains data-code-editor attribute
            // render
            if (str_contains($content, 'data-code-editor=')
                && preg_match('~<[a-z]+\s+.+data-code-editor=(["\']?)[^\"\'\s]+\1~', $content)
            ) {
                /**
                 * @var DefaultAssets $defaultAssets
                 */
                $defaultAssets = $blockService->getServices()->get(DefaultAssets::class);
                $defaultAssets?->enqueueAsset('arrayaccess-editor');
            }

            return $content;
        };
        if (has_filter('the_content', $callback)) {
            return;
        }
        add_filter('the_content', $callback);
    }

    /**
     * @return string
     */
    public function getBlockType(): string
    {
        return 'widget';
    }

    /**
     * @inheritdoc
     */
    public function getUrl(): string
    {
        return $this->url;
    }
}

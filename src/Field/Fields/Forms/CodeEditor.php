<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Fields\Forms;

use ArrayAccess\WP\Libraries\Core\Field\Interfaces\UnsupportedNameAttributeInterface;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\UnsupportedValueAttributeInterface;
use ArrayAccess\WP\Libraries\Core\Service\Services\DefaultAssets;
use ArrayAccess\WP\Libraries\Core\Util\HighlightJS;
use ArrayAccess\WP\Libraries\Core\Util\HtmlAttributes;
use function dirname;
use function force_balance_tags;
use function sprintf;
use function wp_kses_post;
use function wp_script_is;
use function wp_style_is;

class CodeEditor extends Textarea
{
    /**
     * Code editor is textarea
     *
     * @var string The default tag name.
     */
    protected string $tagName = 'textarea';

    /**
     * @var array|string[] The default attributes.
     */
    protected array $defaultClass = [
        'large-text'
    ];

    /**
     * @var array|int[] The default attributes.
     */
    protected array $attributes = [
        'rows' => 12,
        'wrapper' => 'div',
        'data-code-editor' => 'codejar'
    ];

    /**
     * @var array|string[] The disallowing remove attributes.
     */
    protected array $disallowRemoveAttributes = [
        'data-code-editor',
        'data-code-editor-mode',
        'data-code-editor-theme',
    ];

    /**
     * @var string The code mirror theme path.
     */
    private string $distDirectory;

    /**
     * @var string The code editor type.
     */
    private string $theme = HighlightJS::DEFAULT_THEME;

    /**
     * @var string The code editor type.
     */
    private string $language = 'plaintext';

    /**
     * @param ?string $name
     */
    final public function __construct(?string $name = null)
    {
        parent::__construct($name);
        $this->distDirectory = dirname(__DIR__, 4) . '/dist';
        $this->doConstruct();
    }

    /**
     * @return string The code mirror theme path.
     */
    public function getDistDirectory(): string
    {
        return $this->distDirectory;
    }

    /**
     * Do something after construct.
     */
    protected function doConstruct()
    {
        // pass
    }

    /**
     * @param string $theme
     * @param null $found
     * @return $this
     */
    public function setTheme(string $theme, &$found = null): static
    {
        $theme = HighlightJS::filterTheme($theme, $found);
        if ($found) {
            $this->theme = $theme;
        }
        return $this;
    }

    public function getTheme(): string
    {
        return $this->theme;
    }

    public function setLanguage(string $language, &$found = null): static
    {
        $language = HighlightJS::filterLanguage($language, $found);
        if (!$found) {
            return $this;
        }
        $this->language = $language;
        return $this;
    }

    /**
     * @return ?string The code editor mode.
     */
    public function getLanguage(): ?string
    {
        return $this->language;
    }

    /**
     * @inheritdoc
     */
    protected function doEnqueueAssets(): static
    {
        if (!wp_script_is('arrayaccess-editor')
            || !wp_style_is('arrayaccess-common')
            || !wp_style_is('arrayaccess-highlightjs')
        ) {
            $defaultAssets = DefaultAssets::getInstance();
            $defaultAssets->init();
            $defaultAssets->enqueueAsset('arrayaccess-highlightjs', 'css');
            $defaultAssets->enqueueAsset('arrayaccess-editor');
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function build(?bool $inline = null): string
    {
        $inline ??= $this->isInline();
        $attributes = $this->getAttributes();
        // dont set value
        if ($this instanceof UnsupportedValueAttributeInterface) {
            unset($attributes['value']);
        }
        if ($this instanceof UnsupportedNameAttributeInterface) {
            unset($attributes['name']);
        }

        $theme = $this->getTheme();
        $language = $this->getLanguage();
        $attributes['class'] ??= [];
        $attributes['class'][] = 'aa-code-editor';
        $attributes['class'][] = sprintf('aa-code-editor-%s', $theme);
        $attributes['data-code-editor-theme'] = $theme;
        $attributes['data-code-editor-language'] = $language;
        /** @noinspection DuplicatedCode */
        $tag = HtmlAttributes::createHtmlTag($this->getTagName(), $attributes);
        $label = $this->getLabel();
        $html = $tag;
        if ($label) {
            $html = '';
            $label = force_balance_tags($label);
            $html .= '<label class="aa-label" for="' . $this->getId() . '">' . $label . '</label>' . $tag;
        }
        $description = $this->getDescription();
        if ($description !== null) {
            // check if contain html tag > use force_balance_tag
            if (str_contains($description, '<')) {
                $description = wp_kses_post($description);
            }
            $html .= '<span class="aa-field-description">' . $description . '</span>';
        }
        return $html;
    }
}

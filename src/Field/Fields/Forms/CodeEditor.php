<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Fields\Forms;

use ArrayAccess\WP\Libraries\Core\Field\Interfaces\UnsupportedNameAttributeInterface;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\UnsupportedValueAttributeInterface;
use ArrayAccess\WP\Libraries\Core\Service\Services\DefaultAssets;
use ArrayAccess\WP\Libraries\Core\Util\HighlightJS;
use ArrayAccess\WP\Libraries\Core\Util\HtmlAttributes;
use function force_balance_tags;
use function html_entity_decode;
use function is_string;
use function preg_match;
use function sprintf;
use function str_contains;
use function strtolower;
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
        'data-code-editor-resizable',
    ];

    /**
     * @var string The code editor type.
     */
    private string $theme = HighlightJS::DEFAULT_THEME;

    /**
     * @var string The code editor type.
     */
    private string $language = 'plaintext';

    /**
     * @var bool The code editor is resizable.
     */
    private bool $resizable = true;

    /**
     * @var bool The language has set.
     */
    private bool $languageHasSet = false;

    /**
     * Set Theme
     * @param string $theme The code editor theme.
     * @param null $found
     * @return $this
     * @see HighlightJS::THEMES
     */
    public function setTheme(string $theme, &$found = null): static
    {
        $theme = HighlightJS::filterTheme($theme, $found);
        if ($found) {
            $this->theme = $theme;
        }
        return $this;
    }

    /**
     * Get Theme
     *
     * @return string The code editor theme.
     */
    public function getTheme(): string
    {
        return $this->theme;
    }

    /**
     * Set Language
     *
     * @param string $language The code editor mode.
     * @param $found
     * @return $this
     * @see HighlightJS::LANGUAGES
     */
    public function setLanguage(string $language, &$found = null): static
    {
        $language = HighlightJS::filterLanguage($language, $found);
        if (!$found) {
            return $this;
        }
        $this->languageHasSet = true;
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
     * Is the code editor resizable?
     * @return bool
     */
    public function isResizable(): bool
    {
        return $this->resizable;
    }

    /**
     * Set the code editor is resizable.
     *
     * @param bool $resizable The code editor is resizable.
     * @return $this
     */
    public function setResizable(bool $resizable): static
    {
        $this->resizable = $resizable;
        return $this;
    }

    /**
     * Detect language
     *
     * @param string $content The content.
     * @return void
     */
    private function languageDetect(string $content): void
    {
        if (preg_match('~<\?php\s+~i', $content)) {
            $this->setLanguage('php');
        } elseif (preg_match('~(?:^|[,\s}])[.#][a-z0-9]~', $content)
            && str_contains($content, '{')) {
            $this->setLanguage('css');
        } elseif (preg_match('~<(head|body|html|!doctype\s+html|div|article)[^>]*>~i', $content)) {
            $this->setLanguage('html');
        } elseif (preg_match('~^#!(\S+/env\s+(?:bash|sh)|/bin/(?:bash|sh))~i', $content, $match)
            || preg_match('~if\s+\[[^\n]+](?:\s*;)?then.*\n.*fi~', $content)
        ) {
            if (!empty($match) && stripos($match[1], 'sh')) {
                $this->setLanguage('shell');
            } else {
                $this->setLanguage('bash');
            }
        } elseif (preg_match(
            '~let\s+[a-z0-9$_]+\s*=|module\.export|window\.~i',
            $content
        )
        ) {
            $this->setLanguage('javascript');
        } elseif (preg_match('~<\?(?:xml(\s+|stylesheet)|svg\s+)~', $content, $match)) {
            $lang = !empty($match[1]) && stripos($match[1], 'svg') ? 'svg' : 'xml';
            $this->setLanguage($lang);
        } elseif (preg_match('~(?:^|[,\s}])def\s+[a-z0-9]~', $content)) {
            $this->setLanguage('python');
        } elseif (preg_match(
            '#upstream\s+[{]+\s*\{|\s+root\s+[~/]|listen\s+[0-9]+|server_name\s+[^;]+#',
            $content
        )
        ) {
            $this->setLanguage('nginx');
        } elseif (preg_match(
            '#Order\s+(Deny|Allow)|\s+DocumentRoot\s+[~/]|Listen\s+[0-9]+|ServerName\s+[^;]+#',
            $content
        )) {
            $this->setLanguage('apache');
        } elseif (preg_match('~\{\{|\{%[^%]+%}\s+~', $content)) {
            $this->setLanguage('twig');
        } elseif (preg_match('~\b%%.+|-(?:module|export|record|author)\(\s*["\'\[(]~i', $content)) {
            $this->setLanguage('erlang');
        } elseif (preg_match('~\{[^{]+:\s*[^}]+~', $content)) {
            $this->setLanguage('json');
        } elseif (preg_match('~\bselect\s+.+\s+from\s+|(?:left|right|outer|inner)\s+join\s+~i', $content)) {
            $this->setLanguage('sql');
        } elseif (preg_match('~\(\s*defn\s+~i', $content)) {
            $this->setLanguage('clojure');
        } elseif (str_contains($content, 'fun ')
            && preg_match('~\bpackage\s+~i', $content)
        ) {
            $this->setLanguage('go');
        } elseif (preg_match('~\bapply\s+plugin\s*:\s*~i', $content)) {
            $this->setLanguage('gradle');
        } elseif (preg_match(
            '~
                (^|\s+)import\s+java\.
                |(?:public|private|protected)\s+class\s+[_a-z]+
                | class\s+[_a-z][a-z0-9_]+<[^>]+>
                |(?:public|private|protected)\s+static\s+(?:\s+
                    [a-z]+(\[[^]]+)?
                \s+)?[_a-z][a-z0-9_]+\([^]]+(?:\[[^])]+]\s+)?[a-z0-9_$]+\)
            ~xi',
            $content
        )) {
            $this->setLanguage('java');
        }

        $this->languageHasSet = false;
    }

    /**
     * @inheritdoc
     */
    public function setAttribute(string $attributeName, mixed $value): static
    {
        if (!$this->languageHasSet
            && is_string($value)
            && $this->getLanguage() === 'plaintext'
            && strtolower(trim($attributeName)) === 'value'
        ) {
            $html = html_entity_decode($value);
            $this->languageDetect($html);
        }

        return parent::setAttribute($attributeName, $value);
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
            $defaultAssets->enqueueAsset('arrayaccess-editor');
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function build(?bool $inline = null): string
    {
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
        $attributes['data-code-editor-resizable'] = $this->isResizable() ? 'true' : 'false';
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

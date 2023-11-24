<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Fields\Forms;

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
    ];

    /**
     * This for use wp_enqueue_code_editor() function
     *
     * @var array code editor options
     * @see wp_get_code_editor_settings()
     * @see wp_enqueue_code_editor()
     * @link https://developer.wordpress.org/reference/functions/wp_enqueue_code_editor/
     */
    private array $codeEditorOptions = [
        'codemirror' => [
            'indentWithTabs' => false,
        ]
    ];

    /**
     * Set code editor options
     *
     * @param array $options
     * @return $this
     */
    public function setCodeEditorOptions(array $options): static
    {
        $this->codeEditorOptions = $options;
        return $this;
    }

    /**
     * @return array
     */
    public function getCodeEditorOptions(): array
    {
        return $this->codeEditorOptions;
    }

    /**
     * Set code editor type
     *
     * @param string $type The type of code editor to use.
     * @return $this
     */
    public function setCodeEditorType(string $type): static
    {
        $this->codeEditorOptions['type'] = $type;
        return $this;
    }

    /**
     * Set code editor code mirror options
     *
     * @param array $options
     * @return $this
     */
    public function setCodeEditorCodeMirrorOptions(array $options): static
    {
        $this->codeEditorOptions['codemirror'] = $options;
        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function doEnqueueAssets(): static
    {
        // enqueue code editor
        $settings = wp_enqueue_code_editor($this->codeEditorOptions);
        if ($settings === false) {
            return $this;
        }

        wp_add_inline_script(
            'code-editor',
            sprintf(
                '
                (function($) {
                    $( function() { 
                        wp.codeEditor.initialize( "%s", %s );
                    });
                })(window.jQuery);',
                $this->getId(),
                wp_json_encode($settings)??'{}'
            )
        );
        return $this;
    }
}

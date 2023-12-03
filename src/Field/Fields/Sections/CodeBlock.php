<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Fields\Sections;

use ArrayAccess\WP\Libraries\Core\Field\Fields\Forms\CodeEditor;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\UnsupportedNameAttributeInterface;
use ArrayAccess\WP\Libraries\Core\Util\HtmlAttributes;

/**
 * Div section
 * Label is title
 */
class CodeBlock extends CodeEditor implements UnsupportedNameAttributeInterface
{
    /**
     * @param ?string $name the content
     */
    public function __construct(?string $name = null)
    {
        parent::__construct();
        $this->attributes['value'] = $name ?? '';
        $this->attributes['readonly'] = true;
        $this->attributes['disabled'] = true;
        $this->setContent($name ?? '');
    }

    /**
     * @inheritdoc
     */
    public function setAttribute(string $attributeName, mixed $value): static
    {
        $attributeName = HtmlAttributes::filterAttributeName($attributeName);
        if ($attributeName === 'name'
            || $attributeName === 'readonly'
            || $attributeName === 'disabled'
        ) {
            return $this;
        }
        if ($attributeName === 'value') {
            $this->attributes['value'] = $value;
            return $this;
        }

        return parent::setAttribute($attributeName, $value);
    }

    /**
     * Set content
     *
     * @param string $content
     * @return $this
     */
    public function setContent(string $content): static
    {
        $this->setValue($content);
        return $this;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return (string) $this->getValue();
    }

    /**
     * @inheritdoc
     */
    public function getAttributes(): array
    {
        $this->attributes['readonly'] = true;
        $this->attributes['disabled'] = true;
        return parent::getAttributes();
    }
}

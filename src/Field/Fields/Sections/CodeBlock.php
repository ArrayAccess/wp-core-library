<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Fields\Sections;

use ArrayAccess\WP\Libraries\Core\Field\Fields\Forms\CodeEditor;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\UnsupportedNameAttributeInterface;

/**
 * Div section
 * Label is title
 */
class CodeBlock extends CodeEditor implements UnsupportedNameAttributeInterface
{
    public function setContent(string $content): static
    {
        $this->setValue($content);
        return $this;
    }

    public function getContent(): string
    {
        return $this->getValue();
    }

    public function getAttributes(): array
    {
        $this->attributes['readonly'] = true;
        $this->attributes['disabled'] = true;
        return parent::getAttributes();
    }
}

<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Fields\Sections;

use ArrayAccess\WP\Libraries\Core\Field\Abstracts\AbstractField;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\UnsupportedNameAttributeInterface;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\UnsupportedValueAttributeInterface;
use function force_balance_tags;

/**
 * Div section
 * Label is title
 */
class HtmlContent extends AbstractField implements
    UnsupportedValueAttributeInterface,
    UnsupportedNameAttributeInterface
{
    /**
     * @var string The default tag name.
     */
    protected string $tagName = 'div';

    /**
     * @var array|string[] disallowed attributes
     */
    protected array $disallowedAttributes = [
        'type',
        'value',
        'label',
        "name", // div is not input type
    ];

    /**
     * @var array Disallowed remove attribute
     */
    protected array $disallowRemoveAttributes = [];

    /**
     * @param ?string $name html content
     */
    public function __construct(?string $name = null)
    {
        parent::__construct();
        $this->setContent($name ?? '');
    }

    /**
     * @return bool form is inline
     */
    public function isInline(): bool
    {
        return true;
    }

    /**
     * @param string $attributeName
     * @param mixed $value
     * @return $this
     */
    public function setAttribute(string $attributeName, mixed $value): static
    {
        return $this;
    }

    /**
     * @param string $content
     * @return $this
     */
    public function setContent(string $content): static
    {
        $this->attributes['html'] = $content;
        return $this;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent(): string
    {
        return $this->attributes['html'] ?? '';
    }

    /**
     * @inheritdoc
     */
    public function build(?bool $inline = null): string
    {
        return force_balance_tags($this->getContent());
    }
}

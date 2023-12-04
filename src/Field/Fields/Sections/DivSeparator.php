<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Fields\Sections;

use ArrayAccess\WP\Libraries\Core\Field\Abstracts\AbstractField;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\UnsupportedNameAttributeInterface;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\UnsupportedValueAttributeInterface;
use ArrayAccess\WP\Libraries\Core\Util\HtmlAttributes;

class DivSeparator extends AbstractField implements
    UnsupportedValueAttributeInterface,
    UnsupportedNameAttributeInterface
{
    protected string $tagName = 'div';

    /**
     * Default class for the field.
     */
    protected array $defaultClass = [
        'aa-separator'
    ];

    /**
     * @var array|string[] The default attributes.
     */
    protected array $attributes = [];

    /**
     * @var array Disallowed remove attribute
     */
    protected array $disallowRemoveAttributes = [];

    /**
     * @var array|string[] The disallowed attributes.
     */
    protected array $disallowedAttributes = [
        'type',
        'name',
        'value',
    ];

    public function valueIsValid(): bool
    {
        return true;
    }

    public function isValidValue(mixed $value = null, bool $allowNull = true): bool
    {
        return true;
    }

    /**
     * @return bool separator is inline
     */
    public function isInline(): bool
    {
        return true;
    }

    /**
     * Separator does not support description
     * @inheritdoc
     */
    public function setDescription(?string $description): static
    {
        return $this;
    }

    /**
     * Separator does not support description
     * @inheritdoc
     */
    public function getDescription(): ?string
    {
        return null;
    }

    /**
     * @param bool $inline
     * @return string
     */
    public function build(?bool $inline = null): string
    {
        $attributes = $this->getAttributes();
        unset($attributes['name'], $attributes['value'], $attributes['type']);
        $html = $attributes['html'] ?? '';
        if ($html === '') {
            $html = '&nbsp;';
        }
        $attributes['html'] = $html;
        return HtmlAttributes::createHtmlTag($this->getTagName(), $attributes);
    }
}

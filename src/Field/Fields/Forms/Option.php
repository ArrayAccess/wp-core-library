<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Fields\Forms;

use ArrayAccess\WP\Libraries\Core\Field\Abstracts\AbstractField;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\FormFieldTypeInterface;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\UnsupportedNameAttributeInterface;
use ArrayAccess\WP\Libraries\Core\Util\HtmlAttributes;
use function is_scalar;

class Option extends AbstractField implements
    FormFieldTypeInterface,
    UnsupportedNameAttributeInterface
{
    /**
     * @var string $tagName the tag name
     */
    protected string $tagName = 'option';

    /**
     * @var ?string $staticType the static type
     */
    protected ?string $staticType = 'option';

    /**
     * @var bool whether the option is selected
     */
    private bool $selected = false;

    /**
     * @var string[] disallowed attributes
     */
    protected array $disallowedAttributes = [
        'type',
        'checked',
        'multiple',
        'min',
        'max',
        'step',
        'size',
        'maxlength',
        'minlength',
    ];

    /**
     * Set the value attribute.
     *
     * @param scalar $value
     * @return $this
     */
    public function seValue(mixed $value): static
    {
        return $this->setAttribute('value', $value);
    }

    public function setAttribute(string $attributeName, mixed $value): static
    {
        if ($attributeName === 'selected') {
            if (!$value) {
                $this->selected = false;
                return $this;
            }
            $this->selected = HtmlAttributes::isBooleanAttributeEnabled('selected', $value);
            return $this;
        }

        if ($attributeName === 'value') {
            if (!is_scalar($value)) {
                return $this;
            }
        }

        return parent::setAttribute($attributeName, $value);
    }

    /**
     * @param bool $selected
     * @return $this
     */
    public function setSelected(bool $selected): static
    {
        $this->selected = $selected;
        return $this;
    }

    /**
     * @return bool
     */
    public function isSelected(): bool
    {
        return $this->selected;
    }

    public function setValue(mixed $value): static
    {
        return $this->setAttribute('value', $value);
    }
    /**
     * @inheritdoc
     */
    public function getValue(): ?string
    {
        $value = $this->getAttribute('value');
        if ($value === null) {
            return null;
        }
        return (string)$value;
    }

    /**
     * @inheritdoc
     */
    public function build(?bool $inline = null): string
    {
        $value = $this->getValue();
        if ($value === null) {
            return '';
        }
        $attributes = $this->getAttributes();
        $attributes['selected'] = $this->isSelected();
        unset($attributes['label']);
        $label = $this->getLabel();
        $label ??= $value;
        $attributes['label'] = $label;

        return HtmlAttributes::createHtmlTag(
            $this->getTagName(),
            $attributes
        );
    }
}

<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Fields\Forms;

use ArrayAccess\WP\Libraries\Core\Field\Abstracts\AbstractField;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\FieldInterface;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\FormFieldTypeInterface;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\MultipleFieldInterface;
use ArrayAccess\WP\Libraries\Core\Field\Traits\MultiFieldSetterTrait;
use function is_scalar;
use function spl_object_hash;

class MultiCheckbox extends AbstractField implements MultipleFieldInterface, FormFieldTypeInterface
{
    use MultiFieldSetterTrait {
        addField as protected addValue;
        build as protected buildMultiField;
    }

    /**
     * @var string $tagName the tag name
     */
    protected string $tagName = 'input';

    /**
     * @var array|string[]
     */
    protected array $attributes = [
        'type' => 'checkbox'
    ];

    /**
     * Add checkbox
     *
     * @param string|int|float $name
     * @param string|int|float $value
     * @param string|null $label
     * @return ?FieldInterface
     */
    public function add(mixed $name, mixed $value, mixed $label = null): ?FieldInterface
    {
        if (!is_scalar($value) || !is_scalar($name)) {
            return null;
        }
        $label = !is_scalar($label) ? null : $label;
        $label ??= (string) $value;
        $name = (string)$name;
        $inputName = $this->getName() . '[' . $name . ']';
        $checkbox = new Checkbox($inputName);
        $checkbox->setValue($value);
        $checkbox->setLabel($label);
        return $this->addValue($checkbox);
    }

    /**
     * @param FieldInterface|string $name
     * @return bool true if removed, false if not
     */
    public function remove(FieldInterface|string $name): bool
    {
        if ($name instanceof FieldInterface) {
            return $this->removeField($name);
        }
        $theName = $this->getName() . '[' . $name . ']';
        foreach ($this->getFields() as $field) {
            if (!$field instanceof Checkbox) {
                $this->removeField($field);
                continue;
            }
            if ($field->getName() === $theName
                || $field->getName() === $name
            ) {
                return $this->removeField($field);
            }
        }
        return false;
    }

    /**
     * Set checked field
     *
     * @param FieldInterface $field
     * @param bool $checked
     * @return $this
     */
    public function setChecked(FieldInterface $field, bool $checked = true): static
    {
        $id = spl_object_hash($field);
        $field = $this->getFields()[$id]??null;
        if ($field) {
            if ($checked) {
                $field->setAttribute('checked', true);
            } else {
                $field->removeAttribute('checked');
            }
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getValue(): mixed
    {
        $result = null;
        foreach ($this->getFields() as $field) {
            if ($field->getAttribute('checked')) {
                $result ??=[];
                $result[$field->getName()] = $field->getAttribute('value');
            }
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function build(?bool $inline = null, mixed $wrapper = 'div'): string
    {
        return $this->buildMultiField($inline, $wrapper, null);
    }
}

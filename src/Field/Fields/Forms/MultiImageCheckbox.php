<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Fields\Forms;

use ArrayAccess\WP\Libraries\Core\Field\Abstracts\AbstractField;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\FieldInterface;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\FormFieldTypeInterface;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\MultipleFieldInterface;
use ArrayAccess\WP\Libraries\Core\Field\Traits\MultiFieldSetterTrait;
use function spl_object_hash;

class MultiImageCheckbox extends AbstractField implements MultipleFieldInterface, FormFieldTypeInterface
{
    use MultiFieldSetterTrait {
        addField as protected addValue;
    }

    /**
     * @var string tag name
     */
    protected string $tagName = 'input';

    /**
     * @var array|string[]
     */
    protected array $attributes = [
        'type' => 'checkbox'
    ];

    /**
     * @param string|int|float $name
     * @param string|int|float $value
     * @param string $imageUrl The image url.
     * @return ?FieldInterface
     */
    public function add(string|int|float $name, string|int|float $value, string $imageUrl): ?FieldInterface
    {
        $name = (string)$name;
        $inputName = $this->getName() . '[' . $name . ']';
        $checkbox = new ImageCheckbox($inputName);
        $checkbox->setImageUrl($imageUrl);
        $checkbox->setValue($value);
        return $this->addValue($checkbox);
    }

    /**
     * Remove field
     *
     * @param FieldInterface|string $fieldOrImageUrl
     * @return bool true if removed, false if not
     */
    public function remove(FieldInterface|string $fieldOrImageUrl): bool
    {
        if ($fieldOrImageUrl instanceof FieldInterface) {
            return $this->removeField($fieldOrImageUrl);
        }
        foreach ($this->getFields() as $field) {
            if (!$field instanceof ImageCheckbox) {
                $this->removeField($field);
                continue;
            }
            if ($field->getImageUrl() === $fieldOrImageUrl) {
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
}

<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Fields\Forms;

use ArrayAccess\WP\Libraries\Core\Field\Abstracts\AbstractField;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\FieldInterface;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\FormFieldTypeInterface;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\MultipleFieldInterface;
use ArrayAccess\WP\Libraries\Core\Field\Traits\MultiFieldSetterTrait;
use function spl_object_hash;

class MultiRadio extends AbstractField implements MultipleFieldInterface, FormFieldTypeInterface
{
    use MultiFieldSetterTrait {
        addField as protected addValue;
    }

    /**
     * @param string|int|float $value
     * @return ?FieldInterface
     */
    public function add(string|int|float $value): ?FieldInterface
    {
        $radio = new Radio($this->getName());
        $radio->setValue($value);
        $this->remove($value);
        return $this->addValue($radio);
    }

    /**
     * Remove field
     *
     * @param FieldInterface|mixed $nameOrValue
     * @return bool true if removed, false if not
     */
    public function remove(mixed $nameOrValue): bool
    {
        $removed = false;
        if ($nameOrValue instanceof FieldInterface) {
            $removed = $this->removeField($nameOrValue);
            $nameOrValue = $nameOrValue->getAttribute('value');
        }
        foreach ($this->getFields() as $field) {
            if (!$field instanceof Radio) {
                $this->removeField($field);
                continue;
            }
            $value = $field->getValue();
            if ($value === $nameOrValue) {
                $removed = $this->removeField($field)?:$removed;
            }
        }
        return $removed;
    }

    /**
     * Set checked field
     *
     * @param FieldInterface $field
     * @return $this
     */
    public function setChecked(FieldInterface $field): static
    {
        $id = spl_object_hash($field);
        foreach ($this->getFields() as $f) {
            if (spl_object_hash($f) === $id) {
                $f->setAttribute('checked', true);
            } else {
                $f->removeAttribute('checked');
            }
        }
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getValue(): mixed
    {
        foreach ($this->getFields() as $field) {
            if ($field->getAttribute('checked')) {
                return $field->getAttribute('value');
            }
        }

        return null;
    }
}

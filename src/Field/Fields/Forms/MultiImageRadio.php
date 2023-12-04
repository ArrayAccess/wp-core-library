<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Fields\Forms;

use ArrayAccess\WP\Libraries\Core\Field\Abstracts\AbstractField;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\FieldInterface;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\FormFieldTypeInterface;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\MultipleFieldInterface;
use ArrayAccess\WP\Libraries\Core\Field\Traits\MultiFieldSetterTrait;
use function spl_object_hash;

class MultiImageRadio extends AbstractField implements MultipleFieldInterface, FormFieldTypeInterface
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
        'type' => 'radio'
    ];

    /**
     * @param string|int|float $value
     * @param string $imageUrl The image url.
     * @return ?FieldInterface
     */
    public function add(string|int|float $value, string $imageUrl): ?FieldInterface
    {
        $radio = new ImageRadio($this->getName(), $imageUrl);
        $radio->setValue($value);
        return $this->addValue($radio);
    }

    /**
     * Remove field
     *
     * @param FieldInterface|string $fieldOrImageUrl
     * @return bool true if removed, false if not
     */
    public function remove(FieldInterface|string $fieldOrImageUrl): bool
    {
        $result = false;
        if ($fieldOrImageUrl instanceof FieldInterface) {
            $result = $this->removeField($fieldOrImageUrl);
        }
        foreach ($this->getFields() as $field) {
            if (!$field instanceof ImageRadio) {
                $this->removeField($field);
                continue;
            }
            if ($field->getImageUrl() === $fieldOrImageUrl) {
                $result = $this->removeField($field)?:$result;
            }
        }
        return $result;
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

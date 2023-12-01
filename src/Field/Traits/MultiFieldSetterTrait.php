<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Traits;

use ArrayAccess\WP\Libraries\Core\Field\Interfaces\FieldInterface;
use function spl_object_hash;

trait MultiFieldSetterTrait
{
    use MultipleFieldTrait;

    /**
     * @inheritdoc
     */
    public function addField(FieldInterface $field) : ?FieldInterface
    {
        if ($field === $this) {
            return null;
        }

        // if tag name is not same, return null
        if ($field->getTagName() !== $this->tagName) {
            return null;
        }

        $field = clone $field;
        $type = $this->getAttribute('type');
        if ($field->getAttribute('type') !== $type) {
            return null;
        }
        $this->fields[spl_object_hash($field)] = $field;
        return $field;
    }

    /**
     * @inheritdoc
     * @return bool true if field is removed, false if not
     */
    public function removeField(FieldInterface $field): bool
    {
        if (isset($this->fields[spl_object_hash($field)])) {
            unset($this->fields[spl_object_hash($field)]);
            return true;
        }
        return false;
    }
}

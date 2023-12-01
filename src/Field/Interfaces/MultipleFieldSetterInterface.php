<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Interfaces;

interface MultipleFieldSetterInterface extends MultipleFieldInterface
{
    /**
     * Add a field, the object will be cloned
     * Field tag name must be the same as the parent tag name.
     * The type attribute will change to the current object type attribute.
     *
     * @param FieldInterface $field
     * @return ?FieldInterface cloned field if added, null if not added.
     */
    public function addField(FieldInterface $field) : ?FieldInterface;

    /**
     * Remove a field
     *
     * @param FieldInterface $field
     */
    public function removeField(FieldInterface $field);
}

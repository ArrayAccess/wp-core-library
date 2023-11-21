<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Interfaces;

interface MultipleFieldInterface extends FieldInterface
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

    /**
     * Clear all fields
     */
    public function clearFields();

    /**
     * Get the fields
     *
     * @return array<FieldInterface>
     */
    public function getFields() : array;
}

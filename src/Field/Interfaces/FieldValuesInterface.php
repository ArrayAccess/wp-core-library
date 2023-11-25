<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Interfaces;

interface FieldValuesInterface extends FieldInterface
{
    /**
     * Set the values.
     *
     * @return array The values.
     */
    public function getValues() : array;

    /**
     * Get the values with the given values appended.
     *
     * @param array $values
     * @param FieldInterface|null $appendedField
     * @return array The values.
     */
    public function getValuesAppended(array $values, ?FieldInterface $appendedField = null) : array;
}

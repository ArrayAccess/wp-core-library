<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Interfaces;

use Countable;

interface MultipleFieldInterface extends FieldInterface, Countable
{
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

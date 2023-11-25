<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Interfaces;

interface FormFieldTypeInterface
{
    /**
     * Get form input name
     *
     * @return ?string name of the input
     */
    public function getName() : ?string;

    /**
     * Get form input value
     *
     * @return mixed|scalar value of the input
     */
    public function getValue() : mixed;
}

<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Fields\Forms;

use ArrayAccess\WP\Libraries\Core\Field\Interfaces\UnsupportedNameAttributeInterface;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\UnsupportedValueAttributeInterface;

class ResetButton extends Button implements
    UnsupportedNameAttributeInterface,
    UnsupportedValueAttributeInterface
{
    /**
     * @var array|string[] The default attributes.
     */
    protected array $attributes = [
        'type' => 'reset',
    ];

    public function __construct()
    {
        $this->attributes['html'] = __('Reset', 'array-access');
        parent::__construct();
    }

    /**
     * Does not support a custom type
     *
     * @param string $type
     * @return $this
     */
    public function setType(string $type): static
    {
        return $this;
    }
}

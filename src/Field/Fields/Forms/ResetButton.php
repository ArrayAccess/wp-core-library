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

    /**
     * @var ?string The static type.
     */
    protected ?string $staticType = 'reset';

    /**
     * @var array|string[] The default disallowed attributes.
     */
    protected array $disallowedAttributes = [
        'maxlength',
        'minlength',
        'pattern',
        'placeholder',
        'readonly',
        'required',
        'size',
        'autocomplete',
        'autofocus',
        'list',
        'multiple',
        'step',
        'max',
        'min',
        'name',
        'value',
    ];

    public function __construct(?string $name = null)
    {
        $this->attributes['html'] = __('Reset', 'arrayaccess');
        parent::__construct($name);
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

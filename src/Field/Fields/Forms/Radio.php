<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Fields\Forms;

use ArrayAccess\WP\Libraries\Core\Field\Abstracts\AbstractField;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\FormFieldTypeInterface;
use ArrayAccess\WP\Libraries\Core\Field\Traits\StandardInputAttributeTrait;

class Radio extends AbstractField implements FormFieldTypeInterface
{
    use StandardInputAttributeTrait;

    /**
     * @var string The tag name.
     */
    protected string $tagName = 'input';

    /**
     * @var array|string[] The default attributes.
     */
    protected array $attributes = [
        'type' => 'radio',
    ];

    /**
     * @var ?string The static type.
     */
    protected ?string $staticType = 'radio';

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
        'cols',
        'rows',
    ];

    /**
     * Set checked attribute
     *
     * @param bool $checked true to set checked attribute, false to remove
     * @return $this for chaining
     */
    public function setChecked(bool $checked): static
    {
        $this->attributes['checked'] = $checked ? true : '';
        return $this;
    }
}

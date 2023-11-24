<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Fields\Forms;

use ArrayAccess\WP\Libraries\Core\Field\Abstracts\AbstractField;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\FormFieldTypeInterface;
use ArrayAccess\WP\Libraries\Core\Field\Traits\StandardInputAttributeTrait;

class Input extends AbstractField implements FormFieldTypeInterface
{
    use StandardInputAttributeTrait;

    /**
     * @var array|string[] The default attributes.
     */
    protected array $attributes = [
        'type' => 'text',
    ];

    /**
     * Set input type
     *
     * @param string $type input type
     * @return $this for chaining
     */
    public function setType(string $type): static
    {
        $this->setAttribute('type', trim($type)?:$this->attributes['type']);
        return $this;
    }
}

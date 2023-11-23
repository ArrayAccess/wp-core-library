<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Fields;

use ArrayAccess\WP\Libraries\Core\Field\Abstracts\AbstractField;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\FormFieldTypeInterface;
use ArrayAccess\WP\Libraries\Core\Field\Traits\StandardInputAttributeTrait;

class Textarea extends AbstractField implements FormFieldTypeInterface
{
    use StandardInputAttributeTrait;

    /**
     * @var string The default tag name.
     */
    protected string $tagName = 'textarea';

    /**
     * @var array|string[] The default attributes.
     */
    protected array $attributes = [
        'rows' => 5,
    ];

    /**
     * @var array|string[] The disallowing remove attributes.
     */
    protected array $disallowRemoveAttributes = [];

    /**
     * Textarea does not have a type attribute.
     *
     * @var array|string[] The disallowing attributes.
     */
    protected array $disallowedAttributes = [
        'type',
    ];

    /**
     * Set rows
     *
     * @param int $rows
     * @return void
     */
    public function setRows(int $rows): void
    {
        $this->attributes['rows'] = $rows;
    }

    /**
     * Set cols
     *
     * @param int $cols
     * @return void
     */
    public function setCols(int $cols): void
    {
        $this->attributes['cols'] = $cols;
    }
}

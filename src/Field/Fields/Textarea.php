<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Fields;

use ArrayAccess\WP\Libraries\Core\Field\Abstracts\AbstractField;

class Textarea extends AbstractField
{
    /**
     * @var array|string[] The default attributes.
     */
    protected array $attributes = [
        'rows' => 5,
        'cols' => 50
    ];

    /**
     * @var array|string[] The disallowing remove attributes.
     */
    protected array $disallowRemoveAttributes = [];

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

<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Fields\Forms;

class DateTime extends Date
{
    /**
     * @var array|string[] The default attributes.
     */
    protected array $attributes = [
        'type' => 'date',
        'data-flatpickr' => 'true',
        'data-flatpickr-options' => [
            'dateFormat' => 'Y-m-d H:i:s',
            'allowInput' => true,
        ],
    ];

    /**
     * @var string The date time format.
     */
    protected string $dateFormat = 'Y-m-d H:i:s';
}

<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Fields\Forms;

use ArrayAccess\WP\Libraries\Core\Util\HtmlAttributes;

class DateTime extends Date
{
    /**
     * @var array|string[] The default attributes.
     */
    protected array $attributes = [
        'type' => 'datetime',
        'data-flatpickr' => 'true',
    ];

    protected ?string $staticType = 'datetime';

    /**
     * @var string The date time format.
     */
    protected string $dateFormat = HtmlAttributes::DATETIME_FORMAT;
}

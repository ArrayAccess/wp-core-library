<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Fields\Forms;

use ArrayAccess\WP\Libraries\Core\Util\HtmlAttributes;

class Time extends Date
{
    /**
     * @var array|string[] The default attributes.
     */
    protected array $attributes = [
        'type' => 'time',
        'data-flatpickr' => 'true',
    ];

    protected ?string $staticType = 'time';

    /**
     * @var string The date time format.
     */
    protected string $dateFormat = HtmlAttributes::TIME_FORMAT;
}

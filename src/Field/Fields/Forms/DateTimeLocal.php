<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Fields\Forms;

use ArrayAccess\WP\Libraries\Core\Util\HtmlAttributes;

class DateTimeLocal extends Date
{
    /**
     * @var array|string[] The default attributes.
     */
    protected array $attributes = [
        'type' => 'datetime-local',
        'data-flatpickr' => 'true',
    ];

    /**
     * @var string|null The static type.
     */
    protected ?string $staticType = 'datetime-local';

    /**
     * @var string The date time format.
     */
    protected string $dateFormat = HtmlAttributes::DATETIME_LOCAL_FORMAT;
}

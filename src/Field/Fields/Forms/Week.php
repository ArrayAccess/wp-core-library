<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Fields\Forms;

use ArrayAccess\WP\Libraries\Core\Util\HtmlAttributes;

class Week extends Date
{
    /**
     * @var array|string[] The default attributes.
     */
    protected array $attributes = [
        'type' => 'week',
        'data-flatpickr' => 'true',
    ];

    protected ?string $staticType = 'week';

    /**
     * @var string The date time format.
     */
    protected string $dateFormat = HtmlAttributes::WEEK_FORMAT;
}

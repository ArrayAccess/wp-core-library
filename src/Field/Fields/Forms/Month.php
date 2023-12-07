<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Fields\Forms;

use ArrayAccess\WP\Libraries\Core\Util\HtmlAttributes;

class Month extends Date
{
    /**
     * @var array|string[] The default attributes.
     */
    protected array $attributes = [
        'type' => 'month',
        'data-flatpickr' => 'true',
    ];

    protected ?string $staticType = 'month';

    /**
     * @var string The date time format.
     */
    protected string $dateFormat = HtmlAttributes::MONTH_FORMAT;
}

<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Fields\Forms;

use ArrayAccess\WP\Libraries\Core\Util\Consolidator;
use DateTimeInterface;
use function date;
use function is_int;
use function is_string;

class DateTimeLocal extends Input
{
    /**
     * @var array|string[] The default attributes.
     */
    protected array $attributes = [
        'type' => 'datetime-local',
    ];

    /**
     * @var string|null The static type.
     */
    protected ?string $staticType = 'datetime-local';

    /**
     * @var string The date time format.
     */
    protected string $dateFormat = 'Y-m-d\TH:i:s';

    /**
     * @inheritdoc
     */
    public function setAttribute(string $attributeName, mixed $value): static
    {
        if ($attributeName === 'value') {
            if (is_int($value)) {
                $value = date('Y-m-d\TH:i:s', $value);
            } elseif (is_string($value)) {
                $value = Consolidator::callNoError('strtotime', $value);
                if ($value === false) {
                    return $this;
                }
                $value = date('Y-m-d\TH:i:s', $value);
            }
            if ($value instanceof DateTimeInterface) {
                $value = $value->format('Y-m-d\TH:i:s');
            }
        }
        return parent::setAttribute($attributeName, $value);
    }
}

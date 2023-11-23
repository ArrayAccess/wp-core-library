<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Fields;

use ArrayAccess\WP\Libraries\Core\Field\Abstracts\AbstractField;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\FormFieldTypeInterface;
use ArrayAccess\WP\Libraries\Core\Field\Traits\StandardInputAttributeTrait;
use ArrayAccess\WP\Libraries\Core\Util\Filter;
use Throwable;

class Slider extends AbstractField implements FormFieldTypeInterface
{
    use StandardInputAttributeTrait;

    protected string $tagName = 'input';

    /**
     * @var array|string[] The default attributes.
     */
    protected array $attributes = [
        'type' => 'slider',
        'min' => 0,
        'max' => 100,
        'step' => 1,
    ];

    /**
     * Set the min attribute.
     *
     * @param int $min
     * @return $this
     */
    public function setMin(int $min): static
    {
        $this->attributes['min'] = $min;
        return $this;
    }

    /**
     * Set the max attribute.
     *
     * @param int $max
     * @return $this
     */
    public function setMax(int $max): static
    {
        $this->attributes['max'] = $max;
        return $this;
    }

    /**
     * Set the step attribute.
     *
     * @param int $step
     * @return $this
     */
    public function setStep(int $step): static
    {
        $this->attributes['step'] = $step;
        return $this;
    }

    /**
     * Validate value
     *
     * @param mixed|null $value
     * @param bool $allowNull
     * @return bool
     */
    public function isValidValue(mixed $value = null, bool $allowNull = true): bool
    {
        if (!parent::isValidValue($value, $allowNull)) {
            return false;
        }
        if (!is_numeric($value)) {
            return false;
        }
        try {
            $number = Filter::number($value);
        } catch (Throwable) {
            return false;
        }
        if ($number < $this->attributes['min']) {
            return false;
        }
        if ($number > $this->attributes['max']) {
            return false;
        }
        return true;
    }
}

<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Fields\Forms;

use ArrayAccess\WP\Libraries\Core\Field\Abstracts\AbstractField;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\FormFieldTypeInterface;
use ArrayAccess\WP\Libraries\Core\Field\Traits\StandardInputAttributeTrait;
use ArrayAccess\WP\Libraries\Core\Util\Filter;
use ArrayAccess\WP\Libraries\Core\Util\HtmlAttributes;
use Throwable;
use function is_numeric;

class Slider extends AbstractField implements FormFieldTypeInterface
{
    use StandardInputAttributeTrait;

    /**
     * @var string The tag name.
     */
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
     * @var ?string The static type.
     */
    protected ?string $staticType = 'slider';

    /**
     * @param string $attributeName
     * @param mixed $value
     * @return $this
     */
    public function setAttribute(string $attributeName, mixed $value): static
    {
        $attributeName = HtmlAttributes::filterAttributeName($attributeName);
        return is_numeric($value) ? match (strtolower($attributeName)) {
            'min' => $this->setMin((int)$value),
            'max' => $this->setMax((int)$value),
            'step' => $this->setStep((int)$value),
            default => parent::setAttribute($attributeName, $value),
        } : parent::setAttribute($attributeName, $value);
    }

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
        if ($value === null && $allowNull) {
            return true;
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

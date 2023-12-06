<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Fields\Forms;

use ArrayAccess\WP\Libraries\Core\Field\Abstracts\AbstractOptionCollection;
use function is_scalar;

class OptGroup extends AbstractOptionCollection
{
    /**
     * @var string The tag name.
     */
    protected string $tagName = 'optgroup';

    /**
     * @var string|null The static type.
     */
    protected ?string $staticType = 'optgroup';

    /**
     * @param string $label
     * @param mixed $value
     * @param array $attributes
     * @return $this
     */
    public function addOption(mixed $label, mixed $value, array $attributes = []): static
    {
        // Only allow scalar values.
        if (!is_scalar($value) || !is_scalar($label)) {
            return $this;
        }
        $option = Option::create($attributes);
        $option->setLabel($label);
        $option->setValue($value);
        $this->add($option);
        return $this;
    }
    /**
     * @inheritdoc
     */
    protected function doAppendAttributes(array $attributes): array
    {
        $attributes['label'] = $this->getLabel();
        return parent::doAppendAttributes($attributes);
    }
}

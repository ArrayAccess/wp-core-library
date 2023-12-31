<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Fields\Sections;

use ArrayAccess\WP\Libraries\Core\Field\Abstracts\AbstractField;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\FieldInterface;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\FieldValuesInterface;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\LabelAsTitleInterface;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\MultipleFieldSetterInterface;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\UnsupportedNameAttributeInterface;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\UnsupportedValueAttributeInterface;
use ArrayAccess\WP\Libraries\Core\Field\Traits\AppendedValuesTrait;
use ArrayAccess\WP\Libraries\Core\Field\Traits\MultiFieldSetterTrait;
use function spl_object_hash;

/**
 * Div section
 * Label is title
 */
class DivSection extends AbstractField implements
    MultipleFieldSetterInterface,
    UnsupportedValueAttributeInterface,
    UnsupportedNameAttributeInterface,
    FieldValuesInterface,
    LabelAsTitleInterface
{
    use MultiFieldSetterTrait,
        AppendedValuesTrait;

    /**
     * @var string The default tag name.
     */
    protected string $tagName = 'div';

    /**
     * @var array|string[] disallowed attributes
     */
    protected array $disallowedAttributes = [
        'type',
        'value',
        'label',
        "name", // div is not input type
    ];

    /**
     * @var array Disallowed remove attribute
     */
    protected array $disallowRemoveAttributes = [];

    /**
     * @inheritdoc
     */
    protected function useCurrentLabel(): bool
    {
        return false;
    }

    /**
     * @return bool form is inline
     */
    public function isInline(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function addField(FieldInterface $field): ?FieldInterface
    {
        // disallow to add self or form field
        // phpcs::ignore SlevomatCodingStandard.Operators.DisallowEqualOperators
        if ($field === $this) {
            return null;
        }
        $this->fields[spl_object_hash($field)] = $field;
        return $field;
    }
}

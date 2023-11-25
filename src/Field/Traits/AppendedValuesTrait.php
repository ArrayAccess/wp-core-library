<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Traits;

use ArrayAccess\WP\Libraries\Core\Field\Interfaces\FieldInterface;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\FieldValuesInterface;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\FormFieldTypeInterface;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\MultipleFieldInterface;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\UnsupportedNameAttributeInterface;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\UnsupportedValueAttributeInterface;
use JsonSerializable;
use Stringable;
use function http_build_query;
use function is_array;
use function is_bool;
use function is_iterable;
use function is_numeric;
use function is_scalar;
use function json_encode;
use function parse_str;
use function spl_object_hash;

trait AppendedValuesTrait
{

    /**
     * Form is not input type
     *
     * @return mixed
     */
    public function getValues(): array
    {
        return $this->getValuesAppended([]);
    }

    /**
     * @noinspection PhpInstanceofIsAlwaysTrueInspection
     */
    public function getValuesAppended(array $values, ?FieldInterface $appendedField = null): array
    {
        if (! $this instanceof FieldValuesInterface
            && ! $this instanceof MultipleFieldInterface
        ) {
            return $values;
        }

        $appendedFields = [];
        if ($appendedField) {
            $appendedFields[spl_object_hash($appendedField)] = true;
        }
        // get name from all fields
        // if form has name
        $values = [];
        foreach ($this->getFields() as $field) {
            if (isset($appendedFields[spl_object_hash($field)])) {
                continue;
            }
            // skip self, without identical
            // prevent infinite loop
            // phpcs:ignore SlevomatCodingStandard.Operators.DisallowEqualOperators
            if ($field == $this) {
                continue;
            }
            if ($field instanceof FieldValuesInterface) {
                $values = $this->getValuesAppended($values, $field);
                continue;
            }
            if ($field instanceof UnsupportedNameAttributeInterface
                || $field instanceof UnsupportedValueAttributeInterface
            ) {
                continue;
            }
            $name = $field->getName();
            $value = $field instanceof FormFieldTypeInterface
                ? $field->getValue()
                : $field->getAttribute('value');
            if ($name === null) {
                continue;
            }
            $value = $this->convertValue($value);
            if ($value === null && $field->valueIsValid()) {
                $value = ''; // null as empty string
            }
            if (isset($values[$name])) {
                if (!is_array($values[$name])) {
                    $values[$name] = [$values[$name]];
                }
                $values[$name][] = $value;
                continue;
            }
            $values[$name] = $value;
        }
        unset($appendedFields);
        // create encoded query string
        $str = http_build_query($values);
        // parse query string to array, manipulate array like $_POST / GET
        parse_str($str, $values);
        return $values;
    }

    private function convertValue(mixed $value) : mixed
    {
        if ($value instanceof Stringable) {
            return (string) $value;
        }
        /** @noinspection PhpConditionAlreadyCheckedInspection */
        if ($value instanceof JsonSerializable) {
            return json_encode($value);
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_scalar($value) || is_numeric($value)) {
            return $value;
        }
        if (is_array($value) || is_iterable($value)) {
            $val = [];
            foreach ($value as $k => $v) {
                $val[$k] = $this->convertValue($v);
            }
            return $val;
        }
        return null;
    }
}

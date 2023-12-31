<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Abstracts;

use ArrayAccess\WP\Libraries\Core\Field\Interfaces\FieldInterface;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\UnsupportedNameAttributeInterface;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\UnsupportedValueAttributeInterface;
use ArrayAccess\WP\Libraries\Core\Util\Filter;
use ArrayAccess\WP\Libraries\Core\Util\HtmlAttributes;
use DateTimeInterface;
use ReflectionClass;
use ReflectionObject;
use Stringable;
use function array_filter;
use function array_key_exists;
use function array_map;
use function array_unique;
use function array_values;
use function did_action;
use function doing_action;
use function explode;
use function force_balance_tags;
use function in_array;
use function is_array;
use function is_bool;
use function is_callable;
use function is_iterable;
use function is_null;
use function is_numeric;
use function is_scalar;
use function is_string;
use function sanitize_html_class;
use function str_contains;
use function strtolower;
use function substr;

/**
 * Abstract field class to create and manage fields for subclasses
 */
abstract class AbstractField implements FieldInterface
{
    /**
     * @var array The default class attribute.
     */
    protected array $defaultClass = [];

    /**
     * @var ?string The label.
     */
    protected ?string $label = null;

    /**
     * @var ?string The description.
     */
    protected ?string $description = null;

    /**
     * @var string The id.
     */
    protected string $id = '';

    /**
     * @var array<string, mixed>
     */
    protected array $attributes = [];

    /**
     * The tag name.
     *
     * @var string
     */
    protected string $tagName;

    /**
     * The disallowing remove attributes.
     *
     * @var array<string>
     */
    protected array $disallowRemoveAttributes = [
        'type'
    ];

    /**
     * @var ?string The static type.
     */
    protected ?string $staticType = null;

    /**
     * @var array The allowed types.
     */
    protected array $allowedTypes = [];

    /**
     * @var array<string> The disallowed attributes.
     */
    protected array $disallowedAttributes = [];

    /**
     * @var int[] The increment id.
     */
    private static array $incrementId = [];

    /**
     * @var bool Whether the asset is enqueued.
     */
    private bool $assetEnqueued = false;

    /**
     * @var bool
     */
    protected bool $inline = false;

    /**
     * @var int The increment.
     */
    private int $increment;

    /**
     * @var string The date format.
     */
    protected string $dateFormat = HtmlAttributes::DATETIME_FORMAT;

    /**
     * @inheritdoc
     */
    public function __construct(?string $name = null)
    {
        $this->tagName ??= strtolower((new ReflectionClass($this))->getShortName());
        $tagName = $this->getTagName();
        $this->defaultClass[] = 'aa-field';
        $this->defaultClass[] = 'aa-field-' . sanitize_html_class($tagName);
        $this->attributes['class'] ??= $this->defaultClass;
        $this->setAttribute('data-field-type', $tagName);
        $this->setAttribute('class', $this->attributes['class']);
        if ($name !== null) {
            $this->setName($name);
        }
        if ($this->staticType !== null) {
            $this->disallowRemoveAttributes[] = 'type';
            $this->disallowRemoveAttributes = array_values(
                array_unique($this->disallowRemoveAttributes)
            );
            $this->attributes['type'] = $this->staticType;
        }

        if (in_array('type', $this->disallowedAttributes)) {
            unset($this->attributes['type']);
        }
    }

    /**
     * @inheritdoc
     */
    public function setId(string $id): static
    {
        $id = sanitize_html_class($id);
        if ($id === '') {
            if ($this->id !== '') {
                return $this;
            }
            self::$incrementId[$this->tagName] ??= 0;
            $id = sanitize_html_class(
                strtolower($this->tagName) . '-' . self::$incrementId[$this->tagName]++
            );
        }
        $this->id = $id;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getDateFormat(): string
    {
        return $this->dateFormat;
    }

    /**
     * @inheritdoc
     */
    public function getId(): string
    {
        if ($this->id === '') {
            self::$incrementId[$this->tagName] ??= 0;
            $this->increment ??= self::$incrementId[$this->tagName]++;
            $this->id = sanitize_html_class(
                'aa-field-'
                . strtolower($this->tagName)
                . '-'
                . $this->increment
            );
        }
        return $this->id;
    }

    /**
     * @inheritdoc
     */
    public function setLabel(mixed $label = null): static
    {
        if (!is_string($label) && !is_null($label)) {
            return $this;
        }
        $this->label = $label;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getLabel(): ?string
    {
        return $this->label;
    }

    /**
     * @inheritdoc
     */
    public function setName(?string $name): static
    {
        $this->setAttribute('name', $name);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getName(): ?string
    {
        return $this->getAttribute('name');
    }

    /**
     * @inheritdoc
     */
    public function hasAttribute(string $attributeName): bool
    {
        return array_key_exists($attributeName, $this->attributes);
    }

    /**
     * @inheritdoc
     */
    public function setAttribute(string $attributeName, mixed $value): static
    {
        $attributeName = HtmlAttributes::filterAttributeName($attributeName);
        if ($attributeName === '') {
            return $this;
        }
        if ($attributeName === 'type') {
            if ($this->staticType !== null) {
                return $this;
            }
            if (!empty($this->allowedTypes) && !in_array($value, $this->allowedTypes, true)) {
                return $this;
            }
        }
        if (in_array($attributeName, $this->disallowedAttributes, true)) {
            return $this;
        }
        // dont set value
        if ($attributeName === 'value'
            && $this instanceof UnsupportedValueAttributeInterface
        ) {
            return $this;
        }
        if ($attributeName === 'name' && $this instanceof UnsupportedNameAttributeInterface) {
            return $this;
        }
        if ($attributeName === 'id') {
            $this->setId($value);
            return $this;
        }
        if ($attributeName === 'name') {
            if (!$value) {
                unset($this->attributes['name']);
                return $this;
            }
            $this->attributes['name'] = $value;
            return $this;
        }
        /**
         * If the attribute is class, we will convert the value to array.
         */
        if ($attributeName === 'class') {
            if ($value instanceof Stringable) {
                $value = (string) $value;
            }
            if (is_string($value)) {
                $value = explode(' ', trim($value));
            } elseif (!is_array($value) && is_iterable($value)) {
                $value = iterator_to_array($value);
            }
            if (!is_array($value)) {
                $value = [];
            } else {
                $value = array_filter($value, 'is_string');
                $value = array_map('sanitize_html_class', $value);
            }
        }
        if ($attributeName === 'value') {
            $value = $this->filterValue($value);
        }
        if (isset(HtmlAttributes::ATTRIBUTES_INTEGER_TYPES[$attributeName])
            || isset(HtmlAttributes::ATTRIBUTES_POSITIVE_INTEGER_TYPES[$attributeName])
        ) {
            if (!is_numeric($value)) {
                return $this;
            }
            $value = (int)$value;
            if ($value < 0
                && isset(HtmlAttributes::ATTRIBUTES_POSITIVE_INTEGER_TYPES[$attributeName])
            ) {
                // remove when zero or negative
                unset($this->attributes[$attributeName]);
                return $this;
            }
        }
        if ($attributeName === 'accept'
            && in_array($this->getTagName(), ['input', 'form'], true)
        ) {
            $value = Filter::filterAccept($value);
            if (empty($value)) {
                unset($this->attributes[$attributeName]);
                return $this;
            }
        }
        if ($attributeName === 'label' && ($value === null || is_scalar($value))) {
            $this->setLabel($value === null ? null : (string) $value);
            return $this;
        }
        $this->attributes[$attributeName] = $value;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setAttributes(array $attributes): static
    {
        $preservedAttributes = $this->attributes;
        $this->attributes = [];
        $attributes['class'] ??= $this->defaultClass;
        $attributes['data-field-type'] ??= $this->getTagName();
        foreach ($attributes as $attributeName => $value) {
            if (!is_string($attributeName)) {
                continue;
            }
            $this->setAttribute($attributeName, $value);
        }
        foreach ($this->disallowRemoveAttributes as $attribute) {
            if (!is_string($attribute)) {
                continue;
            }
            if (!isset($this->attributes[$attribute])
                && !in_array($attribute, $this->disallowedAttributes)
            ) {
                $this->attributes[$attribute] = $preservedAttributes[$attribute]??null;
            }
        }
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getAttribute(string $attributeName)
    {
        return $this->getAttributes()[$attributeName]??null;
    }

    /**
     * @inheritdoc
     */
    public function getAttributes(): array
    {
        if ($this instanceof UnsupportedNameAttributeInterface) {
            unset($this->attributes['name']);
        }
        if ($this instanceof UnsupportedValueAttributeInterface) {
            unset($this->attributes['value']);
        }
        $attributes = $this->attributes;
        $attributes['id'] = $this->getId();
        return $attributes;
    }

    /**
     * @inheritdoc
     */
    public function getTagName(): string
    {
        return $this->tagName;
    }

    /**
     * Remove an attribute.
     *
     * @param string $attributeName
     * @return $this
     */
    public function removeAttribute(string $attributeName): static
    {
        if (in_array($attributeName, $this->disallowRemoveAttributes, true)) {
            return $this;
        }
        $attributeName = HtmlAttributes::filterAttributeName($attributeName);
        unset($this->attributes[$attributeName]);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setInline(bool $inline): static
    {
        $this->inline = $inline;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function isInline(): bool
    {
        return $this->inline;
    }

    /**
     * @inheritdoc
     */
    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @inheritdoc
     */
    public function build(?bool $inline = null): string
    {
        $inline ??= $this->isInline();
        $attributes = $this->getAttributes();
        // dont set value
        if ($this instanceof UnsupportedValueAttributeInterface) {
            unset($attributes['value']);
        }
        if (empty($attributes['name'])
            || !is_string($attributes['name'])
            || $this instanceof UnsupportedNameAttributeInterface
        ) {
            unset($attributes['name']);
        }
        if (($attributes['value']??null) instanceof DateTimeInterface) {
            $attributes['value'] = $attributes['value']->format($this->getDateFormat());
        }

        $tag = HtmlAttributes::createHtmlTag($this->getTagName(), $attributes);
        $label = $this->getLabel();
        if (is_callable([$this, 'getOverrideLabel'])) {
            $overrideLabel = $this->getOverrideLabel();
            $label = is_string($overrideLabel) || is_null($overrideLabel)
                ? $overrideLabel
                : $label;
        }
        $html = $tag;
        if ($label) {
            $html = '';
            $label = force_balance_tags($label);
            if ($inline) {
                $html .= '<label class="aa-label aa-label-inline" for="' . $this->getId() . '">'
                    . '<span class="aa-field-label">'
                    . $label
                    . '</span>'
                    . $tag
                    . '</label>';
            } else {
                $html .= '<label class="aa-label" for="' . $this->getId() . '">' . $label . '</label>' . $tag;
            }
        }
        $description = $this->getDescription();
        if ($description !== null) {
            // check if contain html tag > use force_balance_tag
            if (str_contains($description, '<')) {
                $description = wp_kses_post($description);
            }
            $html .= '<span class="aa-field-description">' . $description . '</span>';
        }
        return $html;
    }

    /**
     * @inheritdoc
     */
    public static function create(array $attributes = []): static
    {
        $instance = new static();
        $instance->setAttributes($attributes);
        return $instance;
    }

    /**
     * @inheritdoc
     */
    public function isRequired(): bool
    {
        return !HtmlAttributes::isBooleanAttributeEnabled(
            'required',
            $this->getAttribute('required')
        );
    }

    /**
     * @inheritdoc
     */
    public function valueIsValid(): bool|FieldInterface
    {
        $value = $this->isValidValue(
            $this->getAttribute('value'),
            $this->isRequired()
        );
        return $value ? true : $this;
    }

    /**
     * @inheritdoc
     */
    public function isValidValue(mixed $value = null, bool $allowNull = true): bool
    {
        $required = $this->getAttribute('required');
        if (is_null($value) && $allowNull) {
            return !HtmlAttributes::isBooleanAttributeEnabled('required', $required);
        }
        // radio && checkbox is not required length
        $type = $this->getAttribute('type')??'';
        $type = is_string($type) ? strtolower($type) : '';
        if (!in_array($type, ['radio', 'checkbox'], true)) {
            $minLength = $this->getAttribute('minlength');
            $maxLength = $this->getAttribute('maxlength');
            if (is_numeric($minLength)) {
                $minLength = (int)$minLength;
                if (is_string($value) && strlen($value) < $minLength) {
                    return false;
                }
                return true;
            }
            if (is_numeric($maxLength)) {
                $maxLength = (int)$maxLength;
                if (is_string($value) && strlen($value) > $maxLength) {
                    return false;
                }
                return true;
            }
        }

        if ($type === 'date' && $value instanceof DateTimeInterface) {
            return true;
        }
        return ! empty($value) || !HtmlAttributes::isBooleanAttributeEnabled(
            'required',
            $required
        );
    }

    public function __toString(): string
    {
        return $this->build();
    }

    /**
     * Get the debug info.
     * This method will be called when the object is used in var_dump().
     * This method will redact the value attribute, data-* attribute, password, and key.
     *
     * @return array The debug info.
     */
    public function __debugInfo(): array
    {
        $reflection = new ReflectionObject($this);
        $properties = $reflection->getProperties();
        $attributes = [];
        $currentClass = $reflection->getName();
        foreach ($properties as $property) {
            $isPrivate = $property->isPrivate();
            if ($isPrivate) {
                $property->setAccessible(true);
            }
            if (!$property->isInitialized($this)) {
                continue;
            }
            $value = $isPrivate ? $property->getValue($this) : $this->{$property->getName()};
            $key   = $property->getName();
            $isAttribute = $key === 'attributes';
            // Redact the options property.
            if ($key === 'options' && is_array($value)) {
                $val = [];
                foreach ($value as $k => $v) {
                    if (is_array($v)) {
                        foreach ($v as $optionsKey => $optionsValue) {
                            if ($optionsKey === 'value'
                                || str_starts_with($optionsKey, 'data-')
                                || str_contains($optionsKey, 'password')
                                || str_contains($optionsKey, 'key')
                            ) {
                                $optionsValue = '<redacted>';
                            }
                            $v[$optionsKey] = $optionsValue;
                        }
                        $val[$k] = $v;
                        continue;
                    }
                    $val[$k] = $v;
                }
                $value = $val;
            }
            if ($isPrivate && $currentClass !== $property->getDeclaringClass()->getName()) {
                $key = $property->getDeclaringClass()->getName() . '::' . $key;
            }
            if ($isPrivate || !$property->isPublic()) {
                $key .= $isPrivate ? ':private' : ':protected';
            }
            $attributes[$key] = $value;
            if ($isAttribute) {
                foreach ($attributes[$key] as $k => $value) {
                    if ($k === 'value'
                        || str_starts_with($k, 'data-')
                        || str_contains($k, 'password')
                        || str_contains($k, 'key')
                    ) {
                        $attributes[$key][$k] = '<redacted>';
                    }
                }
            }
        }

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    public function filterValue(mixed $value = null): mixed
    {
        if (!is_scalar($value) || is_bool($value)) {
            return $value;
        }
        $type = $this->getAttribute('type');
        $maxLength = $this->getAttribute('maxlength');
        if (is_numeric($maxLength)
            && is_string($type)
            && in_array(strtolower($type), ['input', 'textarea'])
        ) {
            $newValue = substr($value, 0, (int)$maxLength);
            if ($newValue !== $value) {
                return $newValue;
            }
        }
        return $value;
    }

    /**
     * @inheritdoc
     */
    final public function enqueueAssets() : static
    {
        if ($this->assetEnqueued) {
            return $this;
        }
        $this->assetEnqueued = true;
        if (doing_action('wp_enqueue_scripts')
            || doing_action('admin_enqueue_scripts')
            || did_action('wp_enqueue_scripts')
            || did_action('admin_enqueue_scripts')
        ) {
            return $this->doEnqueueAssets();
        }
        return $this;
    }

    /**
     * Enqueue assets. Called when the field is rendered, enqueue the assets.
     *
     * @return $this The current instance.
     */
    protected function doEnqueueAssets() : static
    {
        return $this;
    }
}

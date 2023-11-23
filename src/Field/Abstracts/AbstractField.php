<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Abstracts;

use ArrayAccess\WP\Libraries\Core\Field\Interfaces\FieldInterface;
use ArrayAccess\WP\Libraries\Core\Util\HtmlAttributes;
use ReflectionClass;
use ReflectionObject;
use Stringable;
use function array_filter;
use function array_key_exists;
use function array_map;
use function did_action;
use function doing_action;
use function explode;
use function in_array;
use function is_array;
use function is_iterable;
use function is_null;
use function is_numeric;
use function is_string;
use function sanitize_html_class;
use function str_contains;
use function strtolower;

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

    public function __construct()
    {
        $this->tagName ??= strtolower((new ReflectionClass($this))->getShortName());
        $tagName = $this->getTagName();
        $this->defaultClass[] = 'aa-field';
        $this->defaultClass[] = 'aa-field-' . sanitize_html_class($tagName);
        $this->attributes['class'] ??= $this->defaultClass;
        $this->setAttribute('data-field-type', $tagName);
        $this->setAttribute('class', $this->attributes['class']);
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
    public function getId(): string
    {
        if ($this->id === '') {
            self::$incrementId[$this->tagName] ??= 0;
            $this->id = sanitize_html_class(
                strtolower($this->tagName) . '-' . self::$incrementId[$this->tagName]++
            );
        }
        return $this->id;
    }

    /**
     * @inheritdoc
     */
    public function setLabel(?string $label): static
    {
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
        if (in_array($attributeName, $this->disallowedAttributes, true)) {
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
            if (!isset($this->attributes[$attribute])) {
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
        unset($this->attributes[$attributeName]);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function build(bool $inline = false): string
    {
        $tag = HtmlAttributes::createHtmlTag($this->getTagName(), $this->getAttributes());
        if (!$this->label) {
            return $tag;
        }
        $html = '';
        if ($inline) {
            $html .= '<label class="aa-label aa-label-inline" for="' . $this->getId() . '">'
                . '<span class="field-label">'
                . $this->label
                . '</span>'
                . $tag
                . '</label>';
        } else {
            $html .= '<label class="aa-label" for="' . $this->getId() . '">' . $this->label . '</label>' . $tag;
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
    public function isValidValue(mixed $value = null, bool $allowNull = true): bool
    {
        $required = $this->getAttribute('required');
        if (is_null($value) && $allowNull) {
            return !HtmlAttributes::isBooleanAttributeEnabled('required', $required);
        }
        $minLength = $this->getAttribute('minlength');
        $maxLength = $this->getAttribute('maxlength');
        if (is_numeric($minLength)) {
            $minLength = (int) $minLength;
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
            $value = $property->getValue($this);
            $key = $property->getName();
            $isAttribute = $key === 'attributes';
            // Redact the options property.
            if ($key === 'options' && is_array($value)) {
                $inc = 0;
                $val = [];
                foreach ($value as $v) {
                    $val['<redacted>:' . $inc++] = $v;
                }
                $value = $val;
            }
            if ($currentClass !== $property->getDeclaringClass()->getName()) {
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
        if (doing_action('wp_enqueue_scripts')
            || doing_action('admin_enqueue_scripts')
            || did_action('wp_enqueue_scripts')
            || did_action('admin_enqueue_scripts')
        ) {
            $this->assetEnqueued = true;
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

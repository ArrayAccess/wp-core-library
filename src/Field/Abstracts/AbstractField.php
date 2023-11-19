<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Abstracts;

use ArrayAccess\WP\Libraries\Core\Field\Interfaces\FieldInterface;
use ArrayAccess\WP\Libraries\Core\Util\HtmlAttributes;
use ReflectionClass;
use Stringable;
use function array_filter;
use function array_key_exists;
use function array_map;
use function explode;
use function in_array;
use function is_array;
use function is_iterable;
use function is_string;
use function sanitize_html_class;
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
    protected array $disallowRemoveAttributes = [];

    /**
     * @var int[] The increment id.
     */
    private static array $incrementId = [];

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
    public function setId(string $id): void
    {
        $id = sanitize_html_class($id);
        if ($id === '') {
            if ($this->id !== '') {
                return;
            }
            self::$incrementId[$this->tagName] ??= 0;
            $id = sanitize_html_class(
                strtolower($this->tagName) . '-' . self::$incrementId[$this->tagName]++
            );
        }
        $this->id = $id;
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
    public function setLabel(?string $label): void
    {
        $this->label = $label;
    }

    /**
     * @inheritdoc
     */
    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setName(?string $name): void
    {
        $this->setAttribute('name', $name);
    }

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
    public function setAttribute(string $attributeName, mixed $value): void
    {
        $attributeName = HtmlAttributes::filterAttributeName($attributeName);
        if ($attributeName === '') {
            return;
        }
        if ($attributeName === 'id') {
            $this->setId($value);
            return;
        }
        if ($attributeName === 'name') {
            if (!$value) {
                unset($this->attributes['name']);
                return;
            }
            $this->attributes['name'] = $value;
            return;
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
    }

    /**
     * @inheritdoc
     */
    public function setAttributes(array $attributes): void
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
     * @return void
     */
    public function removeAttribute(string $attributeName): void
    {
        if (in_array($attributeName, $this->disallowRemoveAttributes, true)) {
            return;
        }
        unset($this->attributes[$attributeName]);
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
            $html .= '<label for="' . $this->getId() . '">'
                . '<span class="field-label">'
                . $this->label
                . '</span>'
                . $tag
                . '</label>';
        } else {
            $html .= '<label for="' . $this->getId() . '">' . $this->label . '</label>' . $tag;
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

    public function __toString(): string
    {
        return $this->build();
    }
}

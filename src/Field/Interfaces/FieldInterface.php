<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Interfaces;

use Stringable;

interface FieldInterface extends Stringable
{
    /**
     * FieldInterface constructor.
     * Empty constructor
     */
    public function __construct();

    /**
     * Get the name of the tag
     * @return string
     */
    public function getTagName() : string;

    /**
     * Set the id
     *
     * @param string $id
     * @return $this
     */
    public function setId(string $id): static;

    /**
     * Get the id
     * @return string
     */
    public function getId() : string;

    /**
     * Set label
     *
     * @param ?string $label
     * @return $this
     */
    public function setLabel(?string $label): static;

    /**
     * Get the label
     * @return ?string
     */
    public function getLabel() : ?string;

    /**
     * Set attribute name
     *
     * @param ?string|null $name null if no name
     * @return $this
     */
    public function setName(?string $name): static;

    /**
     * Get the attribute name
     *
     * @return ?string
     */
    public function getName() : ?string;

    /**
     * Set the attribute
     *
     * @param string $attributeName
     * @param mixed $value
     * @return $this
     */
    public function setAttribute(string $attributeName, mixed $value): static;

    /**
     * Set the attributes
     *
     * @param array $attributes
     * @return $this
     */
    public function setAttributes(array $attributes): static;

    /**
     * Check if the tag has the attribute
     *
     * @param string $attributeName
     * @return bool
     */
    public function hasAttribute(string $attributeName) : bool;

    /**
     * Get the attribute value
     * @param string $attributeName
     */
    public function getAttribute(string $attributeName);

    /**
     * Remove the attribute
     *
     * @param string $attributeName
     * @return $this
     */
    public function removeAttribute(string $attributeName): static;

    /**
     * Get all attributes
     * @return array<string, mixed>
     */
    public function getAttributes() : array;

    /**
     * Build the html tag
     *
     * @param bool $inline Whether to inline the tag
     * @return string The html tag
     */
    public function build(bool $inline = false) : string;

    /**
     * Create a new instance
     *
     * @param array $attributes
     * @return static
     */
    public static function create(array $attributes = []) : static;

    /**
     * Validate the value
     *
     * @param mixed $value if no argument specified, use current set value
     * @param bool $allowNull Whether to allow null value and attribute required return false
     * @return bool
     */
    public function isValidValue(mixed $value = null, bool $allowNull = true): bool;

    /**
     * Filter the value
     *
     * @param mixed|null $value
     */
    public function filterValue(mixed $value = null);

    /**
     * Enqueue assets
     * Called when the field is rendered, enqueue the assets
     * called in hook wp_enqueue_scripts, admin_enqueue_scripts
     */
    public function enqueueAssets();
}

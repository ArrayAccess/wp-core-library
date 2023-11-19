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
     */
    public function setId(string $id);

    /**
     * Get the id
     * @return string
     */
    public function getId() : string;

    /**
     * Set label
     *
     * @param ?string $label
     */
    public function setLabel(?string $label);

    /**
     * Get the label
     * @return ?string
     */
    public function getLabel() : ?string;

    /**
     * Set attribute name
     *
     * @param ?string|null $name null if no name
     */
    public function setName(?string $name);

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
     */
    public function setAttribute(string $attributeName, mixed $value);

    /**
     * Set the attributes
     *
     * @param array $attributes
     */
    public function setAttributes(array $attributes);

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
     */
    public function removeAttribute(string $attributeName);

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
}

<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Fields\Forms;

use ArrayAccess\WP\Libraries\Core\Service\Services;
use function function_exists;
use function is_string;

class Nonce extends Hidden
{
    /**
     * @var string The default nonce name.
     */
    protected string $nonceName = '_wpnonce';

    /**
     * @var string|null The default nonce value.
     */
    protected ?string $nonce = null;

    /**
     * Set input type
     */
    public function setType(string $type): static
    {
        return $this;
    }

    /**
     * Set nonce name
     *
     * @param string $nonceName nonce name
     * @return $this for chaining
     */
    public function setNonceName(string $nonceName): static
    {
        $this->nonceName = $nonceName;
        // reset nonce
        $this->nonce = null;
        return $this;
    }

    /**
     * Get nonce name
     *
     * @return string
     */
    public function getNonceName(): string
    {
        return $this->nonceName;
    }

    /**
     * Set nonce value
     *
     * @param string $nonce
     * @return $this
     */
    public function setNonce(string $nonce): static
    {
        $this->nonce = $nonce;
        return $this;
    }

    /**
     * Get nonce value
     *
     * @return string
     */
    public function getNonce(): string
    {
        if ($this->nonce === null) {
            if (!function_exists('wp_create_nonce')) {
                // load pluggable file
                Services::loadPluggableFile();
            }
            $this->nonce = wp_create_nonce($this->getNonceName());
        }
        return $this->nonce;
    }

    /**
     * @inheritdoc
     */
    public function getAttributes(): array
    {
        $attributes = parent::getAttributes();
        $attributes['value'] = $this->getNonce();
        return $attributes;
    }

    /**
     * @param mixed|null $value
     * @param bool $allowNull
     * @return bool
     */
    public function isValidValue(mixed $value = null, bool $allowNull = true): bool
    {
        if (!is_string($value)) {
            return false;
        }

        if (function_exists('wp_verify_nonce')) {
            // load pluggable file
            Services::loadPluggableFile();
        }

        // validate nonce
        return wp_verify_nonce($value, $this->getNonceName()) !== false;
    }
}

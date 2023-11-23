<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Traits;

trait StandardInputAttributeTrait
{
    /**
     * @param string $attributeName
     * @param string|int|float $value
     * @return $this
     */
    abstract public function setAttribute(string $attributeName, mixed $value): static;

    /**
     * Set input value
     *
     * @param string|int|float $val
     * @return $this
     */
    public function setValue(string|int|float $val): static
    {
        $this->setAttribute('value', $val);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getValue(): mixed
    {
        return $this->getAttribute('value');
    }

    /**
     * Set readonly attribute
     *
     * @param bool $val true to set readonly attribute, false to remove
     * @return $this
     */
    public function setReadOnly(bool $val): static
    {
        if ($val === false) {
            $this->removeAttribute('readonly');
            return $this;
        }
        $this->setAttribute('readonly', true);
        return $this;
    }

    /**
     * Set disabled attribute
     *
     * @param bool $val true to set disabled attribute, false to remove
     * @return $this
     */
    public function setDisabled(bool $val): static
    {
        if ($val === false) {
            $this->removeAttribute('disabled');
            return $this;
        }
        $this->setAttribute('disabled', true);
        return $this;
    }

    /**
     * Set required attribute
     *
     * @param bool $val true to set required attribute, false to remove
     * @return $this
     */
    public function setRequired(bool $val): static
    {
        if ($val === false) {
            $this->removeAttribute('required');
            return $this;
        }
        $this->setAttribute('required', true);
        return $this;
    }

    /**
     * Set placeholder attribute
     *
     * @param string $val
     * @return $this
     */
    public function setPlaceholder(string $val): static
    {
        $this->setAttribute('placeholder', $val);
        return $this;
    }
}

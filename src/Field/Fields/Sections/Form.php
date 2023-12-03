<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Fields\Sections;

use ArrayAccess\WP\Libraries\Core\Field\Abstracts\AbstractField;
use ArrayAccess\WP\Libraries\Core\Field\Fields\Forms\Hidden;
use ArrayAccess\WP\Libraries\Core\Field\Fields\Forms\Nonce;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\FieldInterface;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\FieldValuesInterface;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\FileFieldInterface;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\MultipleFieldInterface;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\MultipleFieldSetterInterface;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\UnsupportedNameAttributeInterface;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\UnsupportedValueAttributeInterface;
use ArrayAccess\WP\Libraries\Core\Field\Traits\AppendedValuesTrait;
use ArrayAccess\WP\Libraries\Core\Field\Traits\MultiFieldSetterTrait;
use ArrayAccess\WP\Libraries\Core\Util\HtmlAttributes;
use ArrayAccess\WP\Libraries\Core\Util\HttpRequest\Server;
use function force_balance_tags;
use function is_string;
use function remove_query_arg;
use function set_url_scheme;
use function spl_object_hash;
use function strtolower;
use function wp_removable_query_args;

/**
 * Form wrapper
 */
class Form extends AbstractField implements
    MultipleFieldSetterInterface,
    UnsupportedValueAttributeInterface,
    UnsupportedNameAttributeInterface,
    FieldValuesInterface
{
    use MultiFieldSetterTrait,
        AppendedValuesTrait;

    public const ENC_TYPE_APPLICATION_X_WWW_FORM_URLENCODED = 'application/x-www-form-urlencoded';
    public const ENC_TYPE_MULTIPART_FORM_DATA = 'multipart/form-data';
    public const ENC_TYPE_TEXT_PLAIN = 'text/plain';

    /**
     * @var string[] list of valid enctype
     */
    public const ENCODE_TYPE_LIST = [
        self::ENC_TYPE_APPLICATION_X_WWW_FORM_URLENCODED,
        self::ENC_TYPE_MULTIPART_FORM_DATA,
        self::ENC_TYPE_TEXT_PLAIN,
    ];

    /**
     * @var array|string[] The default attributes.
     */
    protected array $attributes = [
        'enctype' => self::ENC_TYPE_APPLICATION_X_WWW_FORM_URLENCODED,
        // default form method is post
        'method' => 'post'
    ];

    /**
     * @var array|string[] disallowed attributes
     */
    protected array $disallowedAttributes = [
        'type',
        'value',
        'label',
        'name', // form is not input type
    ];

    /**
     * @var bool enable nonce for form
     */
    private bool $enableNonce = false;

    /**
     * @var bool hide the empty sections
     */
    private bool $hideEmptySection = false;

    /**
     * @return bool hide the empty sections
     */
    public function isHideEmptySection(): bool
    {
        return $this->hideEmptySection;
    }

    /**
     * Hide the empty sections
     *
     * @param bool $hideEmptySection
     * @return void
     */
    public function setHideEmptySection(bool $hideEmptySection): void
    {
        $this->hideEmptySection = $hideEmptySection;
    }

    /**
     * @return bool enable nonce for form
     */
    public function isEnableNonce(): bool
    {
        return $this->enableNonce;
    }

    /**
     * Enable nonce for form
     *
     * @param bool $enableNonce
     * @return void enable nonce for form
     */
    public function setEnableNonce(bool $enableNonce): void
    {
        $this->enableNonce = $enableNonce;
    }

    /**
     * @inheritdoc
     */
    public function setLabel(?string $label): static
    {
        return $this;
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
    public function getLabel(): ?string
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function isValidValue(mixed $value = null, bool $allowNull = true): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     * @noinspection PhpNonStrictObjectEqualityInspection
     */
    public function addField(FieldInterface $field): ?FieldInterface
    {
        // disallow to add self or form field
        // phpcs::ignore SlevomatCodingStandard.Operators.DisallowEqualOperators
        if ($field === $this || $field->getTagName() === 'form') {
            return null;
        }
        $this->fields[spl_object_hash($field)] = $field;
        return $field;
    }

    /**
     * Set enctype attribute, encrypt will remain the same if invalid
     * and should be one of the ENCODE_TYPE_LIST.
     * multipart/form-data is the enctype for file upload
     *
     * @param string $encType
     * @return $this
     */
    public function setEncType(string $encType): static
    {
        $encType = strtolower(trim($encType));
        // check if valid
        if (!in_array($encType, self::ENCODE_TYPE_LIST)) {
            return $this;
        }
        $this->setAttribute('enctype', $encType);
        return $this;
    }

    /**
     * Set action attribute. If null, or empty remove method attribute
     *
     * @param string|null $method
     * @return $this
     */
    public function setMethod(?string $method = null): static
    {
        $method = is_string($method) ? trim($method) : null;
        if (!$method) {
            $this->removeAttribute('method');
            return $this;
        }
        $method = strtolower($method);
        if (!in_array($method, ['get', 'post'])) {
            return $this;
        }
        $this->setAttribute('method', $method);
        return $this;
    }

    /**
     * Set target attribute. If null, or empty remove target attribute
     *
     * @param string|null $target
     * @return $this
     */
    public function setTarget(?string $target = null): static
    {
        $target = is_string($target) ? trim($target) : null;
        if (!$target) {
            $this->removeAttribute('target');
            return $this;
        }
        $this->setAttribute('target', $target);
        return $this;
    }

    /**
     * Set action attribute. If null, or empty remove action attribute
     *
     * @param string|null $action
     * @return $this
     */
    public function setAction(?string $action = null): static
    {
        $action = is_string($action) ? trim($action) : null;
        if (!$action) {
            $this->removeAttribute('action');
            return $this;
        }
        $this->setAttribute('action', $action);
        return $this;
    }

    /**
     * Set accept-charset attribute. If null, or empty remove accept-charset attribute
     *
     * @param string|null $acceptCharset
     * @return $this
     */
    public function setAcceptCharset(?string $acceptCharset = null): static
    {
        $acceptCharset = is_string($acceptCharset) ? trim($acceptCharset) : null;
        if (!$acceptCharset) {
            $this->removeAttribute('accept-charset');
            return $this;
        }
        $this->setAttribute('accept-charset', $acceptCharset);
        return $this;
    }

    /**
     * @inheritdoc
     * @noinspection PhpNonStrictObjectEqualityInspection
     */
    public function build(?bool $inline = null): string
    {
        $html = '';
        $increment = 0;
        $enableNonce = $this->isEnableNonce();
        $hasNonce = false;
        $hideEmpty = $this->isHideEmptySection();
        foreach ($this->getFields() as $field) {
            // phpcs:ignore SlevomatCodingStandard.Operators.DisallowEqualOperators
            if ($field == $this) {
                continue;
            }
            if ($hideEmpty && $field instanceof MultipleFieldInterface && $field->count() === 0) {
                continue;
            }

            if (!$hasNonce && $enableNonce) {
                $hasNonce = $field instanceof Nonce;
            }
            if ($field instanceof FileFieldInterface) {
                // set enctype to multipart/form-data if there is the file field
                $this->setEncType(self::ENC_TYPE_MULTIPART_FORM_DATA);
            }
            $field = clone $field;
            ++$increment;
            $field->setAttribute('data-form-increment-id', $increment);
            // if inline don't add the field section
            if ($field->isInline()
                || $field instanceof Hidden
                || $field->getAttribute('type') === 'hidden'
            ) {
                $html .= $field->build(true);
                continue;
            }
            $html .= '<div class="aa-class-field-section" data-increment-id="'.$increment.'">';
            $html .= $field->build();
            $html .= '</div>';
        }
        // add nonce if not exist
        if ($enableNonce && !$hasNonce) {
            $html = (new Nonce())->build() . $html;
        }
        $attributes = $this->getAttributes();
        $attributes['action'] = $attributes['action'] ?? null;
        if ($attributes['action'] === null) {
            $scheme = is_ssl() ? 'https://' : 'http://';
            $host = Server::string('HTTP_HOST');
            $requestUri = Server::string('REQUEST_URI');
            $attributes['action'] = set_url_scheme(
                $scheme
                . $host
                . $requestUri
            );
            $removableQueryArgs = wp_removable_query_args();
            $attributes['action'] = remove_query_arg($removableQueryArgs, $attributes['action']);
        }
        $description = $this->getDescription();
        // description for form as form text / html
        if ($description !== null) {
            $html = sprintf(
                '<div class="aa-field-description aa-form-description">%s</div>',
                str_contains($description, '<') ? force_balance_tags($description) : esc_html($description)
            ) . $html;
        }
        $attributes['html'] = $html;

        return HtmlAttributes::createHtmlTag($this->getTagName(), $attributes);
    }
}

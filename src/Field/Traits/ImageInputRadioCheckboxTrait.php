<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Traits;

use function force_balance_tags;
use function is_bool;
use function is_scalar;
use function sprintf;

trait ImageInputRadioCheckboxTrait
{
    /**
     * @var bool Whether the image is inline or not.
     */
    protected bool $inline = true;

    /**
     * @var ?string
     */
    protected ?string $imageUrl;

    /**
     * Constructor
     *
     * @param ?string $name
     * @param ?string $imageUrl
     */
    public function __construct(?string $name = null, ?string $imageUrl = '')
    {
        parent::__construct($name);
        if ($imageUrl !== null) {
            $this->setImageUrl($imageUrl);
        }
    }

    /**
     * Set image url
     *
     * @param string $imageUrl the image url
     * @return $this for chaining
     */
    public function setImageUrl(string $imageUrl): static
    {
        $this->imageUrl = $imageUrl;
        return $this;
    }

    /**
     * @return ?string the image url
     */
    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    /**
     * @return ?string
     */
    public function getOverrideLabel(): ?string
    {
        $image = $this->getImageUrl();
        if ($image) {
            $image = $this->createImageBased($image);
        }

        $label = parent::getLabel();
        return sprintf(
            '<span class="aa-field-image">%s</span><span class="aa-field-label-image">%s</span>',
            $image,
            $label ? force_balance_tags($label) : ''
        );
    }

    /**
     * @param string $imageUrl the image url
     * @return string the image-based html
     */
    private function createImageBased(string $imageUrl): string
    {
        $value = $this->getValue();
        $value = !is_scalar($value) ? '' : (
        is_bool($value)
            ? ($value ? 'true' : 'false')
            : (string)$value
        );
        /** @noinspection HtmlUnknownTarget */
        return sprintf(
            '<img src="%1$s" title="%2$s" alt="%3$s" data-value="%4$s" class="aa-field-image-selector">',
            esc_attr($imageUrl),
            sprintf(
            /* translators: %s: image url */
                __('Image for %s', 'arrayaccess'),
                esc_attr($value)
            ),
            __('Image selection', 'arrayaccess'),
            esc_attr($value)
        );
    }

    /**
     * @return bool
     */
    public function isInline(): bool
    {
        return true;
    }
}

<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Fields\Forms;

use ArrayAccess\WP\Libraries\Core\Service\Services\DefaultAssets;
use function wp_script_is;
use function wp_style_is;

class Date extends Input
{
    /**
     * @var array|string[] The default attributes.
     */
    protected array $attributes = [
        'type' => 'date',
        'data-flatpickr' => 'true',
        'data-flatpickr-options' => [
            'dateFormat' => 'Y-m-d',
            'allowInput' => true,
        ],
    ];

    /**
     * @var ?string The static type.
     */
    protected ?string $staticType = 'date';

    /**
     * @var string The date format.
     */
    protected string $dateFormat = 'Y-m-d';

    /**
     * Set input type
     */
    public function setType(mixed $type): static
    {
        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function doEnqueueAssets(): static
    {
        if (!wp_script_is('flatpickr-bundle')
            || !wp_style_is('flatpickr-bundle')
        ) {
            $defaultAssets = DefaultAssets::getInstance();
            $defaultAssets->init();
            $defaultAssets->enqueueAsset('flatpickr-bundle');
        }
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getAttributes(): array
    {
        $attributes = parent::getAttributes();
        if (isset($attributes['data-flatpickr'])) {
            $attributes['data-flatpickr-options'] ??= [];
            if (!is_array($attributes['data-flatpickr-options'])) {
                $attributes['data-flatpickr-options'] = [];
            }
            $attributes['data-flatpickr-options']['dateFormat'] = $this->dateFormat;
        }
        $attributes['data-flatpickr-date-format'] = $this->dateFormat;
        return $attributes;
    }
}

<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field\Fields\Forms;

use ArrayAccess\WP\Libraries\Core\Service\Services\DefaultAssets;
use ArrayAccess\WP\Libraries\Core\Util\Consolidator;
use ArrayAccess\WP\Libraries\Core\Util\HtmlAttributes;
use DateTimeInterface;
use function explode;
use function get_locale;
use function is_file;
use function is_string;
use function rtrim;
use function str_replace;
use function strtolower;
use function wp_script_is;
use function wp_style_is;
use const ABSPATH;

class Date extends Input
{
    /**
     * @var array|string[] The default attributes.
     */
    protected array $attributes = [
        'type' => 'date',
        'data-flatpickr' => 'true',
        'data-flatpickr-theme' => 'default',
        'data-flatpickr-options' => [
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
    protected string $dateFormat = HtmlAttributes::DATE_FORMAT;

    /**
     * Set input type
     */
    public function setType(mixed $type): static
    {
        return $this;
    }
    public function __construct(?string $name = null)
    {
        parent::__construct($name);
    }

    /**
     * @inheritdoc
     */
    public function setAttribute(string $attributeName, mixed $value): static
    {
        $attributeName = HtmlAttributes::filterAttributeName($attributeName);
        if ($attributeName === 'settings'
            || $attributeName === 'setting'
        ) {
            $attributeName = 'data-flatpickr-options';
        }
        if ($attributeName === 'value') {
            if (is_string($value)) {
                $newValue = Consolidator::callNoError('strtotime', $value);
                if (is_int($newValue)) {
                    $value = date($this->dateFormat, $newValue);
                }
            } elseif ($value instanceof DateTimeInterface) {
                $value = $value->format($this->dateFormat);
            } elseif (is_int($value)) {
                $value = date($this->dateFormat, $value);
            }
        }
        if ($attributeName === 'theme') {
            $attributeName = 'data-flatpickr-theme';
        }
        if ($attributeName === 'data-flatpickr-theme') {
            if (!is_string($value)) {
                return $this;
            }
        }

        return parent::setAttribute($attributeName, $value);
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
            $defaultAssets->enqueueAsset('flatpickr-bundle');
        }

        return $this;
    }

    /**
     * Render the locale
     *
     * @param mixed $locale
     * @return ?string
     */
    protected function renderLocale(mixed $locale): ?string
    {
        if (!is_string($locale) || trim($locale) === '') {
            return null;
        }

        $defaultAssets = DefaultAssets::getInstance();
        $locale = explode('_', str_replace('-', '_', $locale))[0];
        $locale = strtolower($locale);
        $distPath = rtrim(ABSPATH, '/\\'). $defaultAssets->getServices()->getDistPath();
        $handle = "flatpickr-l10n-lang-$locale";
        if ($defaultAssets->isRegistered($handle, 'js')) {
            $defaultAssets->enqueueAsset($handle, 'js');
            return $locale;
        }
        if (is_file($distPath . "/vendor/flatpickr/l10n/$locale.min.js")) {
            $defaultAssets->registerAsset(
                $handle,
                [
                    'src' => "{{dist_url}}/vendor/flatpickr/l10n/$locale.min.js",
                    'deps' => [],
                    'ver' => DefaultAssets::ASSETS['js']['flatpickr-bundle']['ver']??null,
                    'media' => 'all',
                    'attributes' => [
                        'strategy' => 'async'
                    ]
                ],
                'js'
            );
            $defaultAssets->enqueueAsset($handle);
            return $locale;
        }
        return null;
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
                // reverse compatibility
                $this->setAttribute('data-flatpickr-options', []);
            }
        }
        return $attributes;
    }


    /**
     * @param bool|null $inline
     * @return string
     */
    public function build(?bool $inline = null): string
    {
        $locale = $this->getAttribute('data-flatpickr-locale');
        $locale = !is_string($locale) || trim($locale) === '' ? get_locale() : $locale;
        if (is_string($locale)) {
            $locale = $this->renderLocale($locale);
            if ($locale) {
                $this->setAttribute('data-flatpickr-locale', $locale);
            } else {
                $this->removeAttribute('data-flatpickr-locale');
            }
        }
        return parent::build($inline);
    }
}

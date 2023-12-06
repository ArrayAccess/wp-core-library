<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Options;

use ArrayAccess\WP\Libraries\Core\Field\Builder;
use ArrayAccess\WP\Libraries\Core\Field\Fields\Sections\DivSection;
use ArrayAccess\WP\Libraries\Core\Field\Fields\Sections\Form;
use function get_site_option;
use function is_array;
use function is_string;

class Option
{
    public const PREFIX = 'array_access_options_';

    /**
     * @var string $optionName
     */
    protected string $optionName;

    /**
     * @var array<string, DivSection> $sections
     */
    protected array $sections = [];

    /**
     * @var array $options
     */
    protected array $options;

    /**
     * @param string $optionName
     */
    public function __construct(string $optionName)
    {
        $this->optionName = $optionName;
    }

    /**
     * @return string
     */
    public function getOptionName(): string
    {
        return $this->optionName;
    }

    /**
     * @return string
     */
    public function getOptionNameWithPrefix(): string
    {
        $prefix = static::PREFIX;
        /** @noinspection PhpConditionAlreadyCheckedInspection */
        $prefix = ! is_string($prefix) ? self::PREFIX : $prefix;
        return  $prefix . $this->optionName;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        if (!isset($this->options)) {
            $options = get_site_option($this->getOptionNameWithPrefix(), []);
            $this->options = !is_array($options) ? [] : $options;
        }
        return $this->options;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function setOption(string $name, mixed $value): static
    {
        $this->options ??= $this->getOptions();
        $this->options[$name] = $value;
        return $this;
    }

    /**
     * @param string $name
     * @param mixed|null $default
     * @return mixed
     */
    public function getOption(string $name, mixed $default = null): mixed
    {
        $this->options ??= $this->getOptions();
        return $this->options[$name] ?? $default;
    }

    /**
     * @return DivSection[]
     */
    public function getSections(): array
    {
        return $this->sections;
    }

    /**
     * New Section
     *
     * @param string $title
     * @return DivSection
     */
    public function section(string $title): DivSection
    {
        $id = count($this->sections);
        $id++;
        $section = new DivSection();
        $section->setLabel($title);
        $section->setAttribute('section-id', $id);
        $this->sections[$id] = $section;
        return $section;
    }

    /**
     * @param int $sectionId
     * @return ?DivSection
     */
    public function getSection(int $sectionId): ?DivSection
    {
        return $this->sections[$sectionId] ?? null;
    }

    /**
     * @param string $title
     * @param array $definitions
     * @param bool $useOption
     * @return DivSection
     */
    public function addSection(string $title, array $definitions, bool $useOption = true) : DivSection
    {
        $section = $this->section($title);
        foreach ($definitions as $name => $definition) {
            $name = is_string($name) ? $name : ($definition['name']??'');
            if (!is_string($name)) {
                continue;
            }
            $name = trim($name);
            if ($name === '') {
                continue;
            }
            $definition['default'] ??= null;
            if ($useOption) {
                $definition['value'] = $this->getOption(
                    $name,
                    $definition['value'] ?? $definition['default']
                );
            }
            $field = Builder::createField($name, $definition);
            if ($field) {
                $section->addField($field);
            }
        }
        return $section;
    }

    /**
     * @param string $optionName
     * @param array $definitions
     * @param bool $useOption
     * @return static
     */
    public static function createFromArray(
        string $optionName,
        array $definitions,
        bool $useOption = true
    ): static {
        $option = new static($optionName);
        foreach ($definitions as $key => $definition) {
            $title = $definition['title']??(string) $key;
            $title = (string) $title;
            // we use 2 alternative key fields or sections
            $sections = isset($definition['fields']) && is_array($definition['fields'])
                ? $definition['fields']
                : $definition['sections']??[];
            $sections = !is_array($sections) ? [] : $sections;
            $option->addSection($title, $sections, $useOption);
        }
        return $option;
    }

    /**
     * @param string|null $target
     * @return Form
     */
    public function createForm(?string $target = null): Form
    {
        $form = new Form();
        $form
            ->setMethod('POST')
            ->setTarget($target);
        foreach ($this->getSections() as $section) {
            $form->addField($section);
        }
        return $form;
    }
}

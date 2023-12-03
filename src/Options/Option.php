<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Options;

use ArrayAccess\WP\Libraries\Core\Field\Builder;
use ArrayAccess\WP\Libraries\Core\Field\Fields\Sections\DivSection;
use function is_string;

class Option
{
    protected string $optionName;

    /**
     * @var array<string, DivSection> $sections
     */
    protected array $sections = [];

    public function __construct(string $optionName)
    {
        $this->optionName = $optionName;
    }

    public function getOptionName(): string
    {
        return $this->optionName;
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

    public function addSection(string $title, array $definitions) : DivSection
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
            $field = Builder::createField($name, $definition);
            if ($field) {
                $section->addField($field);
            }
        }
        return $section;
    }
}

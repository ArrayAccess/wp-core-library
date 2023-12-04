<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Field;

use ArrayAccess\WP\Libraries\Core\Field\Fields\Forms\Button;
use ArrayAccess\WP\Libraries\Core\Field\Fields\Forms\Checkbox;
use ArrayAccess\WP\Libraries\Core\Field\Fields\Forms\CodeEditor;
use ArrayAccess\WP\Libraries\Core\Field\Fields\Forms\ColorPicker;
use ArrayAccess\WP\Libraries\Core\Field\Fields\Forms\Email;
use ArrayAccess\WP\Libraries\Core\Field\Fields\Forms\File;
use ArrayAccess\WP\Libraries\Core\Field\Fields\Forms\Hidden;
use ArrayAccess\WP\Libraries\Core\Field\Fields\Forms\ImageCheckbox;
use ArrayAccess\WP\Libraries\Core\Field\Fields\Forms\ImageFile;
use ArrayAccess\WP\Libraries\Core\Field\Fields\Forms\ImageRadio;
use ArrayAccess\WP\Libraries\Core\Field\Fields\Forms\Input;
use ArrayAccess\WP\Libraries\Core\Field\Fields\Forms\MultiCheckbox;
use ArrayAccess\WP\Libraries\Core\Field\Fields\Forms\MultiImageCheckbox;
use ArrayAccess\WP\Libraries\Core\Field\Fields\Forms\MultiImageRadio;
use ArrayAccess\WP\Libraries\Core\Field\Fields\Forms\MultiInput;
use ArrayAccess\WP\Libraries\Core\Field\Fields\Forms\MultiRadio;
use ArrayAccess\WP\Libraries\Core\Field\Fields\Forms\MultiSelect;
use ArrayAccess\WP\Libraries\Core\Field\Fields\Forms\Nonce;
use ArrayAccess\WP\Libraries\Core\Field\Fields\Forms\Radio;
use ArrayAccess\WP\Libraries\Core\Field\Fields\Forms\ResetButton as ButtonReset;
use ArrayAccess\WP\Libraries\Core\Field\Fields\Forms\Select;
use ArrayAccess\WP\Libraries\Core\Field\Fields\Forms\Slider;
use ArrayAccess\WP\Libraries\Core\Field\Fields\Forms\SubmitButton;
use ArrayAccess\WP\Libraries\Core\Field\Fields\Forms\Textarea;
use ArrayAccess\WP\Libraries\Core\Field\Fields\Sections\CodeBlock;
use ArrayAccess\WP\Libraries\Core\Field\Fields\Sections\DivSection;
use ArrayAccess\WP\Libraries\Core\Field\Fields\Sections\DivSeparator;
use ArrayAccess\WP\Libraries\Core\Field\Fields\Sections\Form;
use ArrayAccess\WP\Libraries\Core\Field\Fields\Sections\HtmlContent;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\FieldInterface;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\UnsupportedNameAttributeInterface;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\UnsupportedValueAttributeInterface;
use ArrayAccess\WP\Libraries\Core\Util\HtmlAttributes;
use function array_key_exists;
use function in_array;
use function is_array;
use function is_bool;
use function is_scalar;
use function is_string;
use function method_exists;
use function strtolower;
use function trim;
use function ucwords;

/**
 * @todo add more fields & complete the builder
 */
class Builder
{
    /**
     * Create a field with the given name and definition
     *
     * @param string $name the field name
     * @param array $definition the field definition
     * @return FieldInterface|null the field or null if invalid
     */
    public static function createField(string $name, array $definition) : ?FieldInterface
    {
        $type = $definition['type']??null;
        $type = !is_string($type) || trim($type) === '' ? null : strtolower($type);
        if (!$type) {
            return null;
        }
        $name = trim($name);
        $title = $definition['title']??$name;
        $title = !is_string($title) || trim($title) === '' ? ucwords($name) : $title;
        $description = $definition['description']??'';
        if (trim($name) === '' && !in_array(
            $type,
            [
                    'section',
                    'separator',
                    'html',
                    'html-content',
                    'html-content-input',
                    'html-input',
                    'form',
                    'button',
                    'submit',
                    'submit-button',
                    'nonce',
                    'wp_nonce',
                    'wp_nonce',
                    'wpnonce',
                ]
        )) {
            return null;
        }
        $name = $name?:null;
        $value = $definition['value']??null;
        $checked = $definition['checked']??null;
        $selected = $definition['selected']??null;
        unset(
            $definition['title'],
            $definition['type'],
            $definition['description'],
            $definition['value'],
            $definition['checked'],
            $definition['selected']
        );
        switch ($type) {
            case 'button':
                $field = new Button($name);
                break;

            case 'checkbox':
            case 'checkbox-input':
                $field = new Checkbox($name);
                break;

            case 'editor':
            case 'code-editor':
            case 'codeeditor':
            case 'code-input':
            case 'code-editor-input':
            case 'codeeditor-input':
            case 'editor-input':
                $field = new CodeEditor($name);
                $content = $value??(
                    $definition['html']??(
                    $definition['content']??null
                )
                );
                unset($definition['html'], $definition['content']);
                $field->setValue($content);
                break;
            case 'code':
            case 'code-block':
            case 'codeblock':
            case 'code-block-input':
            case 'codeblock-input':
                $field = new CodeBlock($name);
                $content = $value??($definition['html']??($definition['content']??null));
                unset($definition['html'], $definition['content']);
                $content = (string) $content;
                $field->setContent($content);
                break;
            case 'color-picker':
            case 'colorpicker':
            case 'color':
            case 'color-input':
            case 'color-picker-input':
            case 'colorpicker-input':
                $field = new ColorPicker($name);
                break;

            case 'email':
            case 'email-input':
                $field = new Email($name);
                break;

            case 'file':
            case 'file-input':
                $field = new File($name);
                break;

            case 'hidden':
            case 'hidden-input':
                $field = new Hidden($name);
                break;

            case 'image-checkbox':
            case 'image-checkbox-input':
                $field = new ImageCheckbox($name);
                break;

            case 'image':
            case 'image-file':
            case 'image-input':
                $field = new ImageFile($name);
                break;

            case 'image-radio':
            case 'image-radio-input':
                $field = new ImageRadio($name);
                break;

            case 'text':
            case 'input':
            case 'text-input':
                $field = new Input($name);
                break;
            case 'multi-checkbox':
            case 'multi-checkbox-input':
                $field = new MultiCheckbox($name);
                /** @noinspection DuplicatedCode */
                $checkboxes = $definition['checkboxes']??($definition['checkbox']??($definition['options']??[]));
                unset($definition['checkboxes'], $definition['checkbox'], $definition['options']);
                if (!is_array($checkboxes)) {
                    return null;
                }
                $checked = is_array($value) ? $value : (is_array($checked) ? $checked : []);
                foreach ($checkboxes as $optionName => $checkboxValue) {
                    $opt = $checkboxValue;
                    if (!is_array($opt)) {
                        $opt = [
                            'value' => $checkboxValue,
                            'checked' => null,
                            'name' => $optionName,
                            'label' => is_string($checkboxValue) ? $checkboxValue : null
                        ];
                    } elseif (count($checkboxValue) === 1 && array_key_exists(0, $checkboxValue)) {
                        $opt = [
                            'value' => $checkboxValue[0],
                            'checked' => null,
                            'name' => $optionName,
                            'label' => is_string($checkboxValue) ? $checkboxValue : null
                        ];
                    }
                    $opt['value'] = $opt['value']??null;
                    $opt['name'] ??= $optionName;
                    if (!is_string($opt['name'])) {
                        $opt['name'] = $optionName;
                    }
                    $opt['checked'] ??= $checked[$optionName]??false;
                    $opt['label'] ??= null;
                    $opt['label'] = !is_string($opt['label']) ? null : $opt['label'];
                    $fieldInput = $field->add($opt['name'], $opt['value'], $opt['label']);
                    if ($fieldInput instanceof Checkbox) {
                        $fieldInput->setChecked(
                            HtmlAttributes::isBooleanAttributeEnabled(
                                'checked',
                                $opt['checked']
                            )
                        );
                    }
                }
                break;
            case 'multi-image-checkbox':
            case 'multiimage-checkbox':
            case 'multi-image-checkbox-input':
            case 'multiimage-checkbox-input':
                $field = new MultiImageCheckbox($name);
                /** @noinspection DuplicatedCode */
                $checkboxes = $definition['checkboxes']??($definition['checkbox']??($definition['options']??[]));
                $checkboxes = !is_array($checkboxes) ? [] : $checkboxes;
                unset($definition['checkboxes'], $definition['checkbox'], $definition['options']);
                if (!is_array($checkboxes)) {
                    return null;
                }
                $checked = is_array($value) ? $value : (is_array($checked) ? $checked : []);
                foreach ($checkboxes as $optionName => $checkboxDefinition) {
                    if ($checkboxDefinition === null) {
                        continue;
                    }
                    /** @noinspection DuplicatedCode */
                    if (!is_array($checkboxDefinition)) {
                        $checkboxDefinition = [
                            'value' => $checkboxDefinition,
                            'checked' => null,
                            'name' => $optionName
                        ];
                    } elseif (count($checkboxDefinition) === 1 && array_key_exists(0, $checkboxDefinition)) {
                        $checkboxDefinition = [
                            'value' => $checkboxDefinition[0],
                            'checked' => null,
                            'name' => $optionName
                        ];
                    }
                    $checkboxDefinition['name'] ??= $optionName;
                    $value = $checkboxDefinition['value']??null;
                    $imageUrl = $checkboxDefinition['image_url']??(
                        $checkboxDefinition['image']??(
                        $checkboxDefinition['url']??null
                    )
                    );
                    $checkboxDefinition['checked'] ??= $checked[$optionName]??false;
                    $imageUrl = is_string($imageUrl) ? $imageUrl : '';
                    $subField = $field->add(
                        $checkboxDefinition['name'],
                        $value,
                        $imageUrl
                    );
                    if ($subField instanceof ImageCheckbox) {
                        $subField->setChecked(
                            HtmlAttributes::isBooleanAttributeEnabled(
                                'checked',
                                $checkboxDefinition['checked']
                            )
                        );
                    }
                }
                break;
            case 'multi-image-radio':
            case 'multiimage-radio':
            case 'multi-image-radio-input':
            case 'multiimage-radio-input':
                $field = new MultiImageRadio($name);
                $radios = $definition['radio']??($definition['radios']??($definition['options']??[]));
                unset($definition['radio'], $definition['radios'], $definition['options']);
                if (!is_array($radios)) {
                    return null;
                }
                $checked = is_array($value) ? $value : (is_array($checked) ? $checked : []);
                foreach ($radios as $radioDefinition) {
                    /** @noinspection DuplicatedCode */
                    if (!is_array($radioDefinition)) {
                        $radioDefinition = [
                            'value' => $radioDefinition,
                            'checked' => null
                        ];
                    } elseif (count($radioDefinition) === 1 && array_key_exists(0, $radioDefinition)) {
                        $radioDefinition = [
                            'value' => $radioDefinition[0],
                            'checked' => null
                        ];
                    }
                    if (is_scalar($radioDefinition['value'])) {
                        $radioDefinition['checked'] ??= $checked[$radioDefinition['value']] ?? false;
                    }
                    $value = $radioDefinition['value']??null;
                    $imageUrl = $radioDefinition['image_url']??(
                        $radioDefinition['image']??(
                        $radioDefinition['url']??null
                    )
                    );
                    $imageUrl = is_string($imageUrl) ? $imageUrl : '';
                    $subField = $field->add($value, $imageUrl);
                    if ($subField instanceof ImageRadio) {
                        $subField->setChecked(
                            HtmlAttributes::isBooleanAttributeEnabled(
                                'checked',
                                $radioDefinition['checked']??false
                            )
                        );
                    }
                }
                break;
            case 'multi-input':
            case 'multiinput':
                $inputs = $definition['radio']??(
                    $definition['radios']??null
                );
                $isRadio = is_array($inputs);
                $inputs = !$isRadio  ? (
                        $definition['checkbox']??(
                        $definition['checkboxes']??null
                    )
                ) : $inputs;
                $inputs ??= $definition['options']??null;
                if (empty($inputs)) {
                    return null;
                }
                $checked = is_array($value) ? $value : (is_array($checked) ? $checked : []);
                unset(
                    $definition['radio'],
                    $definition['radios'],
                    $definition['checkbox'],
                    $definition['checkboxes'],
                    $definition['options']
                );
                $field = new MultiInput($name, $isRadio);
                foreach ($inputs as $inputName => $inputDefinition) {
                    if (!is_array($inputDefinition)) {
                        $inputDefinition = [
                            'value' => $inputDefinition
                        ];
                    } elseif (count($inputDefinition) === 1 && array_key_exists(0, $inputDefinition)) {
                        $inputDefinition = [
                            'value' => $inputDefinition[0]
                        ];
                    }
                    $inputDefinition['type'] = $isRadio ? 'radio' : 'checkbox';
                    if ($isRadio) {
                        $inputName = $name;
                    } else {
                        $inputName = is_string($inputName) ? $inputName : '';
                        if ($inputName === '') {
                            continue;
                        }
                        $inputName = trim($inputName);
                        if ($inputName === '') {
                            continue;
                        }
                    }
                    $input = Builder::createField($inputName, $inputDefinition);
                    if (!$input) {
                        continue;
                    }
                    if ($field->addField($input)) {
                        $field->setChecked(
                            $input,
                            ($value !== null && $value === $input->getAttribute('value'))
                                || HtmlAttributes::isBooleanAttributeEnabled(
                                    'checked',
                                    $inputDefinition['checked']??false
                                )
                        );
                    }
                }
                break;
            case 'multi-radio':
            case 'multiradio':
            case 'multi-radio-input':
            case 'multiradio-input':
                $field = new MultiRadio($name);
                $radios = $definition['radio']??($definition['radios']??($definition['options']??[]));
                unset($definition['radio'], $definition['radios'], $definition['options']);
                if (!is_array($radios)) {
                    return null;
                }
                foreach ($radios as $radioDefinition) {
                    if (!is_array($radioDefinition)) {
                        $radioDefinition = [
                            'label' => (string) $radioDefinition,
                            'value' => (string) $radioDefinition,
                            'checked' => null
                        ];
                    } elseif (count($radioDefinition) === 1 && array_key_exists(0, $radioDefinition)) {
                        $radioDefinition = [
                            'value' => (string) $radioDefinition[0],
                            'label' => (string) $radioDefinition[0],
                            'checked' => null
                        ];
                    }
                    if (is_scalar($radioDefinition['value']??null)) {
                        $radioDefinition['checked'] ??= $checked[$radioDefinition['value']] ?? false;
                    }
                    $radioDefinition['value'] ??= null;
                    $radioDefinition['label'] ??= $radioDefinition['title']??$radioDefinition['value'];
                    $radioDefinition['label'] = !is_string($radioDefinition['label'])
                        ? null : $radioDefinition['label'];
                    $subField = $field->add((string) $radioDefinition['value'], $radioDefinition['label']);
                    if ($subField instanceof Radio) {
                        $subField->setChecked(
                            HtmlAttributes::isBooleanAttributeEnabled(
                                'checked',
                                $radioDefinition['checked']??false
                            )
                        );
                    }
                }
                break;
            case 'multi-select':
            case 'multiselect':
            case 'multi-select-input':
            case 'multiselect-input':
            case 'select':
            case 'select-input':
                $isMultiple = !in_array($type, ['select', 'select-input']);
                $field = $isMultiple ? new MultiSelect($name) : new Select($name);
                $field->setMultiple($isMultiple);
                $options = $definition['options']??[];
                $selected = is_array($selected) ? $selected : [];
                unset($definition['options']);
                if (empty($options)) {
                    return null;
                }
                $selectedVal = is_array($value) ? $value : [];
                /** @noinspection DuplicatedCode */
                foreach ($options as $optionDefinition) {
                    if (!is_array($optionDefinition)) {
                        if (!is_scalar($optionDefinition)) {
                            // invalid! stop!
                            return null;
                        }
                        $optionDefinition = [
                            'label'  => (string) $optionDefinition,
                            'value' => $optionDefinition,
                            'selected' => null
                        ];
                    } elseif (count($optionDefinition) === 1 && array_key_exists(0, $optionDefinition)) {
                        $optionDefinition = [
                            'label' => (string) $optionDefinition[0],
                            'value' => (string) $optionDefinition[0],
                            'selected' => null
                        ];
                    }
                    $optName = $optionDefinition['label']??null;
                    $optValue = $optionDefinition['value']??null;
                    if (!is_string($optName) || !is_scalar($optValue)) {
                        // invalid! stop!
                        return null;
                    }
                    $optValue = (string) $optValue;
                    $optionDefinition['selected'] ??= $selected[$optValue]??false;
                    $field->addOption($optValue, $optName);
                    if (in_array($optValue, $selectedVal, true)) {
                        continue;
                    }
                    $isSelected = ($value !== null || $value === $optValue)
                        || HtmlAttributes::isBooleanAttributeEnabled(
                            'selected',
                            $optionDefinition['selected']
                        );
                    if ($isSelected) {
                        $selectedVal[] = $optValue;
                    }
                }
                $field->setSelected(...$selectedVal);
                break;
            case 'nonce':
            case 'wpnonce':
            case 'wp_nonce':
            case 'wp-nonce':
                $field = new Nonce($name);
                break;

            case 'radio':
            case 'radio-input':
                $field = new Radio($name);
                break;
            case 'reset-button':
            case 'resetbutton':
            case 'button-reset':
            case 'buttonreset':
                $field = new ButtonReset($name);
                break;
            case 'slider':
            case 'slider-input':
                $field = new Slider($name);
                break;

            case 'textarea':
            case 'textarea-input':
                $field = new Textarea($name);
                break;

            case 'submit':
            case 'submit-button':
                $field = new SubmitButton($name);
                break;

            // SECTIONS
            case 'section':
            case 'form':
                $field = $type === 'form' ? new Form($name) : new DivSection($name);
                $sections = $definition['sections']??[];
                $sections = !is_array($sections) ? [] : $sections;
                unset($definition['sections']);
                foreach ($sections as $sectionName => $sectionDefinition) {
                    $section = Builder::createField($sectionName, $sectionDefinition);
                    if ($section) {
                        $field->addField($section);
                    }
                }
                break;
            case 'separator':
                $field = new DivSeparator($name);
                break;

            case 'html':
            case 'html-content':
            case 'html-content-input':
            case 'html-input':
                $field = new HtmlContent();
                $content = $definition['content']??(
                    $definition['html']??null
                );
                unset($definition['content'], $definition['html']);
                $field->setContent((string) $content);
                break;
            default:
                return null;
        }
        if (!$field instanceof FieldInterface) {
            return null;
        }

        if ($field instanceof Checkbox || $field instanceof Radio) {
            $field->setChecked(
                HtmlAttributes::isBooleanAttributeEnabled(
                    'checked',
                    $checked
                )
            );
        }
        if ($value !== null
            && $field->getAttribute('value') === null
            && is_scalar($value)
            && ! $field instanceof UnsupportedValueAttributeInterface
        ) {
            if (method_exists($field, 'setValue')
                && !is_bool($value)
            ) {
                $field->setValue($value);
            } else {
                $field->setAttribute('value', $value);
            }
        }

        $field
            ->setLabel($title)
            ->setDescription($description)
            ->setAttributes($definition)
            ->setAttribute('value', $value);
        if (!$field instanceof UnsupportedNameAttributeInterface) {
            $field->setName($name);
        }
        return $field;
    }
}

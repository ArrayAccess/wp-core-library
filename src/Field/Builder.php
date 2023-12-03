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
use ArrayAccess\WP\Libraries\Core\Field\Fields\Forms\MultiRadio;
use ArrayAccess\WP\Libraries\Core\Field\Fields\Forms\Nonce;
use ArrayAccess\WP\Libraries\Core\Field\Fields\Forms\Radio;
use ArrayAccess\WP\Libraries\Core\Field\Fields\Forms\Select;
use ArrayAccess\WP\Libraries\Core\Field\Fields\Forms\Slider;
use ArrayAccess\WP\Libraries\Core\Field\Fields\Forms\SubmitButton;
use ArrayAccess\WP\Libraries\Core\Field\Fields\Forms\Textarea;
use ArrayAccess\WP\Libraries\Core\Field\Fields\Sections\DivSection;
use ArrayAccess\WP\Libraries\Core\Field\Fields\Sections\DivSeparator;
use ArrayAccess\WP\Libraries\Core\Field\Fields\Sections\Form;
use ArrayAccess\WP\Libraries\Core\Field\Fields\Sections\HtmlContent;
use ArrayAccess\WP\Libraries\Core\Field\Interfaces\FieldInterface;
use function in_array;
use function is_array;
use function is_string;
use function strtolower;
use function trim;
use function ucwords;

class Builder
{

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
        unset(
            $definition['title'],
            $definition['type'],
            $definition['description'],
            $definition['value']
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
            case 'code':
            case 'code-input':
            case 'code-editor-input':
            case 'codeeditor-input':
            case 'editor-input':
                $field = new CodeEditor($name);
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
                $checkboxes = $definition['checkboxes']??[];
                unset($definition['checkboxes']);
                foreach ($checkboxes as $optionName => $checkboxValue) {
                    $name = $optionName;
                    $field->add($name, $checkboxValue);
                }
                break;
            case 'multi-image-checkbox':
            case 'multiimage-checkbox':
            case 'multi-image-checkbox-input':
            case 'multiimage-checkbox-input':
                $field = new MultiImageCheckbox($name);
                $checkboxes = $definition['checkboxes']??[];
                unset($definition['checkboxes']);
                foreach ($checkboxes as $optionName => $checkboxDefinition) {
                    $name = $optionName;
                    /** @noinspection DuplicatedCode */
                    if (!is_array($checkboxDefinition)) {
                        $checkboxDefinition = [
                            'value' => $checkboxDefinition
                        ];
                    }
                    $value = $checkboxDefinition['value']??null;
                    $imageUrl = $checkboxDefinition['image_url']??(
                            $checkboxDefinition['image']??(
                            $checkboxDefinition['url']??null
                        )
                    );
                    $imageUrl = is_string($imageUrl) ? $imageUrl : '';
                    $field->add($name, $value, $imageUrl);
                }
                break;
            case 'multi-image-radio':
            case 'multiimage-radio':
            case 'multi-image-radio-input':
            case 'multiimage-radio-input':
                $field = new MultiImageRadio($name);
                $radios = $definition['radio']??(
                    $definition['radios']??[]
                );
                unset($definition['radio'], $definition['radios']);
                foreach ($radios as $radioDefinition) {
                    /** @noinspection DuplicatedCode */
                    if (!is_array($radioDefinition)) {
                        $radioDefinition = [
                            'value' => $radioDefinition
                        ];
                    }
                    $value = $radioDefinition['value']??null;
                    $imageUrl = $radioDefinition['image_url']??(
                            $radioDefinition['image']??(
                            $radioDefinition['url']??null
                        )
                    );
                    $imageUrl = is_string($imageUrl) ? $imageUrl : '';
                    $field->add($value, $imageUrl);
                }
                break;
            case 'multi-radio':
            case 'multiradio':
            case 'multi-radio-input':
            case 'multiradio-input':
                $field = new MultiRadio($name);
                $radios = $definition['radio']??(
                    $definition['radios']??[]
                );
                unset($definition['radio'], $definition['radios']);
                foreach ($radios as $value) {
                    $field->add($value);
                }
                break;
                // todo: add multi-select
            case 'textarea':
            case 'textarea-input':
                $field = new Textarea($name);
                break;
            case 'section':
                $field = new DivSection($name);
                break;
            case 'separator':
                $field = new DivSeparator($name);
                break;
            case 'html':
            case 'html-content':
            case 'html-content-input':
            case 'html-input':
                $field = new HtmlContent($name);
                break;
            case 'select':
            case 'select-input':
                $field = new Select($name);
                break;
            case 'form':
                $field = new Form($name);
                break;
            case 'submit':
            case 'submit-button':
                $field = new SubmitButton($name);
                break;
            case 'nonce':
            case 'wpnonce':
            case 'wp_nonce':
            case 'wp-nonce':
                $field = new Nonce($name);
                break;
            case 'slider':
                $field = new Slider($name);
                break;
            case 'radio':
            case 'radio-input':
                $field = new Radio($name);
                break;
            default:
                $field = null;
                break;
        }

        $field
            ?->setLabel($title)
            ->setDescription($description)
            ->setAttributes($definition)
            ->setAttribute('value', $value);
        return $field;
    }
}

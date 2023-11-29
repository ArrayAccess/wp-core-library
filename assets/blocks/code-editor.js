(function (wp) {
    "use strict";

    const {registerBlockType} = wp.blocks;
    const {TextareaControl, SelectControl, Button, PanelBody} = wp.components;
    const {createElement} = wp.element;
    const {InspectorControls} = wp.blockEditor;
    const {__} = wp.i18n;

    let incrementId = Math.random() * 1000000;
    const themes = [
        'a11y-dark',
        'a11y-light',
        'agate',
        'an-old-hope',
        'androidstudio',
        'arduino-light',
        'arta',
        'ascetic',
        'atom-one-dark',
        'atom-one-dark-reasonable',
        'atom-one-light',
        'brown-paper',
        'codepen-embed',
        'color-brewer',
        'dark',
        'default',
        'devibeans',
        'docco',
        'far',
        'felipec',
        'foundation',
        'github',
        'github-dark',
        'github-dark-dimmed',
        'gml',
        'googlecode',
        'gradient-dark',
        'gradient-light',
        'grayscale',
        'hybrid',
        'idea',
        'intellij-light',
        'ir-black',
        'isbl-editor-dark',
        'isbl-editor-light',
        'kimbie-dark',
        'kimbie-light',
        'lightfair',
        'lioshi',
        'magula',
        'mono-blue',
        'monokai',
        'monokai-sublime',
        'night-owl',
        'nnfx-dark',
        'nnfx-light',
        'nord',
        'obsidian',
        'panda-syntax-dark',
        'panda-syntax-light',
        'paraiso-dark',
        'paraiso-light',
        'pojoaque',
        'purebasic',
        'qtcreator-dark',
        'qtcreator-light',
        'rainbow',
        'routeros',
        'school-book',
        'shades-of-purple',
        'srcery',
        'stackoverflow-dark',
        'stackoverflow-light',
        'sunburst',
        'tokyo-night-dark',
        'tokyo-night-light',
        'tomorrow-night-blue',
        'tomorrow-night-bright',
        'vs',
        'vs2015',
        'xcode',
        'xt256'
    ];
    const languages = [];
    const HighlightJS = window.hljs;
    HighlightJS.listLanguages().forEach((lang) => {
        if (languages.indexOf(lang) === -1) {
            languages.push(lang);
        }
        const _lang = HighlightJS.getLanguage(lang);
        if (!_lang || typeof _lang.aliases !== 'object') {
            return;
        }
        _lang.aliases.forEach((alias) => {
            if (languages.indexOf(alias) === -1) {
                languages.push(alias);
            }
        });
    });
    languages.sort();
    themes.sort();
    const widget = window['arrayaccessBlockWidgets'] || {};
    let title = widget['arrayaccess-block-code-editor'];
    if (!title || typeof title !== 'string') {
        title = __('Highlight Code Editor', 'arrayaccess');
    }
    registerBlockType('arrayaccess-block-code-editor/widget', {
        title      : title,
        icon       : 'editor-code',
        category   : 'text',
        attributes : {
            content: {
                type    : 'string',
                default : ''
            },
            selectedLanguage: {
                type    : 'string',
                default : 'html'
            },
            selectedTheme: {
                type    : 'string',
                default : 'default'
            },
            editable: {
                type    : 'string',
                default : 'no'
            },
            buttonWindow: {
                type    : 'string',
                default : 'no'
            },
            height: {
                type    : 'string',
                default : ''
            }
        },
        edit: function (props) {
            const {attributes, setAttributes} = props;
            const languageOptions = languages.map((lang) => ({
                label : lang,
                value : lang
            }));

            const themeOptions = themes.map((theme) => ({
                label : theme,
                value : theme
            }));
            let codeJarInit = window.codeJarWindowInit || null;
            codeJarInit = codeJarInit ? {init: codeJarInit} : null;
            const incId = attributes.incrementId || incrementId++;
            setAttributes({incrementId: incId});
            const selector = '[data-code-editor="codejar"][data-increment-id="' + (incId) + '"]';
            let textArea = document.querySelector(selector),
                editor = null;
            function callTextArea() {
                if (!codeJarInit) {
                    return;
                }
                if (!textArea) {
                    textArea = editor?.closest('.aa-editor-wrap')?.querySelector('[data-code-editor="codejar"]');
                }
                textArea = textArea || document.querySelector(selector);
                setAttributes({deleted: false});
                if (textArea?.codeJar) {
                    return editor;
                }
                editor = codeJarInit.init(textArea, props) || editor;
                return editor;
            }
            const textAreaElement = createElement(TextareaControl, {
                value                        : attributes.content,
                'data-increment-id'          : incId, // To avoid conflict with other blocks
                'data-code-editor'           : 'codejar',
                'data-code-editor-theme'     : attributes.selectedTheme,
                'data-code-editor-language'  : attributes.selectedLanguage,
                'data-code-editor-resizable' : 'true',
                'data-code-editor-height'    : attributes.height,
                'data-code-enable-button'    : attributes.buttonWindow,
                onChange                     : function (newContent) {
                    callTextArea();
                    setAttributes({content: newContent});
                }
            });
            const getEditor = () => {
                editor = editor || textArea
                    ?.closest('.aa-editor-wrap')
                    ?.querySelector('.aa-editor-area.codejar');
                return editor;
            };
            const getWrap = () => {
                return getEditor()?.closest('.aa-editor-wrap');
            };
            let _height = attributes.height || '';
            editor = getEditor();
            if (editor) {
                _height = !attributes.deleted && editor.style.height
                    ? editor.style.height
                    : '';
                textArea?.setAttribute(
                    'data-code-editor-height',
                    _height
                );
                editor.style.height = _height;
                setAttributes({deleted: false, height: _height});
            }
            callTextArea();
            return [
                createElement(
                    InspectorControls,
                    {key: 'inspector-controls'},
                    createElement(
                        PanelBody,
                        {
                            title       : __('Code Editor Settings', 'arrayaccess'),
                            initialOpen : true
                        },
                        [
                            createElement(
                                SelectControl,
                                {
                                    key      : 'language-select',
                                    label    : __('Select Language', 'arrayaccess'),
                                    value    : attributes.selectedLanguage,
                                    help     : __('Select programming language for code editor', 'arrayaccess'),
                                    options  : languageOptions,
                                    onChange : (newLanguage) => {
                                        setAttributes({selectedLanguage: newLanguage});
                                        if (textArea) {
                                            textArea.setAttribute('data-code-editor-language', newLanguage);
                                            editor = codeJarInit?.init(textArea, props) || editor;
                                            setAttributes({deleted: false});
                                        }
                                    }
                                }
                            ),
                            createElement(
                                SelectControl,
                                {
                                    key      : 'theme-select',
                                    label    : __('Select Theme', 'arrayaccess'),
                                    value    : attributes.selectedTheme,
                                    help     : __('Select theme for code editor', 'arrayaccess'),
                                    options  : themeOptions,
                                    onChange : (newTheme) => {
                                        setAttributes({selectedTheme: newTheme});
                                        if (textArea) {
                                            textArea.setAttribute('data-code-editor-theme', newTheme);
                                            editor = codeJarInit?.init(textArea, props) || editor;
                                            setAttributes({deleted: false});
                                        }
                                    }
                                }
                            ),
                            createElement(
                                SelectControl,
                                {
                                    key     : 'button-select',
                                    label   : __('Enable Window Button', 'arrayaccess'),
                                    value   : attributes.buttonWindow,
                                    help    : __('Enable functional window button (collapse & maximize)', 'arrayaccess'),
                                    options : [
                                        {label: __('Yes', 'arrayaccess'), value: 'yes'},
                                        {label: __('No', 'arrayaccess'), value: 'no'}
                                    ],
                                    onChange: (yesNo) => {
                                        yesNo = yesNo === 'yes' ? 'yes' : 'no';
                                        editor = getEditor();
                                        document.body.removeAttribute('data-code-editor-screen-mode');
                                        getWrap()?.removeAttribute('data-code-editor-screen');
                                        setAttributes({buttonWindow: yesNo});
                                    }
                                }
                            ),
                            createElement(
                                SelectControl,
                                {
                                    key     : 'button-editable',
                                    label   : __('Enable Front Editable Code', 'arrayaccess'),
                                    value   : attributes.editable,
                                    help    : __('Enable functional to enable frontend editable. If enable, editor will resizable.', 'arrayaccess'),
                                    options : [
                                        {label: __('Yes', 'arrayaccess'), value: 'yes'},
                                        {label: __('No', 'arrayaccess'), value: 'no'}
                                    ],
                                    onChange: (yesNo) => {
                                        yesNo = yesNo === 'yes' ? 'yes' : 'no';
                                        setAttributes({editable: yesNo});
                                    }
                                }
                            ),
                            _height ? createElement(
                                Button,
                                {
                                    label                   : __('Reset Resized Editor', 'arrayaccess'),
                                    isPrimary               : true,
                                    'data-editor-increment' : incId,
                                    onClick                 : () => {
                                        if (!editor && !textArea) {
                                            return;
                                        }
                                        editor = editor || textArea
                                            ?.closest('.aa-editor-wrap')
                                            ?.querySelector('.aa-editor-area.codejar');
                                        if (editor) {
                                            editor.style.height = '';
                                        }
                                        setAttributes({height: '', deleted: true});
                                    }
                                },
                                __('Reset Editor Height', 'arrayaccess')
                            ) : null
                        ]
                    )
                ),
                createElement('div', null,
                    textAreaElement
                )
            ];
        },
        save: (props) => {
            const {attributes} = props;
            const attr = {
                class                       : 'aa-code-editor aa-code-editor-' + attributes.selectedTheme,
                'data-code-editor-theme'    : attributes.selectedTheme,
                'data-code-editor-language' : attributes.selectedLanguage,
                'data-code-editor'          : 'codejar',
                'data-arrayaccess-widget'   : 'code-editor',
                'data-code-enable-button'   : attributes.buttonWindow,
                'data-code-editor-height'   : attributes.height,
                'data-code-editor-editable' : attributes.editable
            };
            if (attributes.height) {
                attr.style = {height: attributes.height};
            }
            return (
                createElement('pre', attr, attributes.content)
            );
        }
    });
})(window.wp);

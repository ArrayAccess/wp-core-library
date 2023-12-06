// noinspection SillyAssignmentJS

/*!
 * Admin Library JS init
 */
(function (w) {
    "use strict";
    let $ = window.jQuery || null;
    const createDiv = (className, content, ...child) => {
        const div = document.createElement('div');
        div.className = className;
        if (content) {
            div.innerHTML = content;
        }
        for (let i = 0; i < child.length; i++) {
            div.appendChild(child[i]);
        }
        return div;
    };

    const dispatchReadyEvent = (el, eventName, ...detail) => {
        if (!el || (!(el instanceof HTMLElement) && !(el instanceof Window))) {
            return;
        }
        if ($?.fn.trigger) {
            $(el).trigger(eventName, [el, ...detail]);
            return;
        }
        el.dispatchEvent(new CustomEvent(eventName, {detail: [el, ...detail]}));
    };

    const ready = (fn) => {
        if (w.document.readyState === "complete" || (w.document.readyState !== "loading")) {
            // if document is already ready, then run
            fn();
        } else {
            // if document hasn't finished loading, add event listener
            w.document.addEventListener("DOMContentLoaded", fn);
        }
    };
    const isInputForm = (el) => el instanceof HTMLElement && ['TEXTAREA', 'INPUT'].indexOf(el.tagName) !== -1;
    const select = (selector, context) => (context || document)?.querySelector(selector);
    const selects = (selector, context) => (context || document)?.querySelectorAll(selector)||[];
    const html = (el, ...element) => {
        el.innerHTML = '';
        for (let i = 0; i < element.length; i++) {
            let e = element[i];
            if (e === null || typeof e === 'undefined') {
                continue;
            }
            if (!(e instanceof HTMLElement)) {
                e = createDiv(null, e).childNodes[0];
            }
            el.appendChild(e);
        }
    };
    const style = (el, obj) => {
        if (!obj || typeof obj !== 'object') {
            return;
        }
        let i;
        for (i in obj) {
            if (!Object.prototype.hasOwnProperty.call(obj, i)) {
                continue;
            }
            el.style[i] = obj[i];
        }
    };
    const setValue = (el, editor) => {
        if (isInputForm(el)) {
            el.value = editor.textContent;
        } else {
            el.innerHTML = editor.textContent;
        }
    };

    let incrementEditor = 0;

    const init = () => {
        //const wp = w.wp||null;
        $ = w.jQuery || null;

        dispatchReadyEvent(window, 'arrayaccess-common-ready');
        if (w['flatpickr'] && typeof w['flatpickr'] === 'function') {
            selects('input[data-flatpickr=true]').forEach((e) => {
                const options = e.getAttribute('data-flatpickr-options') || '{}';
                let _options = {};
                try {
                    if (typeof options === 'string') {
                        _options = JSON.parse(options);
                    }
                    if (typeof _options !== 'object') {
                        _options = {};
                    }
                } catch (err) {
                    // skip
                }
                w['easepick'].create(e, _options);
                dispatchReadyEvent(e, 'easepick-ready', _options);
            });
        }

        /* Code Editor */
        if (typeof w.CodeJar === 'function'
            && typeof w['hljs'] === 'object'
            && typeof w['hljs'].highlightElement === 'function'
        ) {
            const
                HighLight = w['hljs'],
                CodeJarLineNumber = HighLight ? (w['CodeJarWithLineNumbers'] || null) : null,
                withLineNumbers = CodeJarLineNumber ? CodeJarLineNumber['withLineNumbers'] : null;
            const makeEditorReadOnly = (editor) => {
                if (typeof editor.inrement === 'number') {
                    return;
                }
                editor.inrement = ++incrementEditor;
                editor.addEventListener('keydown', (e) => {
                    if (!e.metaKey && !/^(Arrow|Escape|Tab)/i.test(e.code)) {
                        e.preventDefault();
                        return false;
                    }
                    if (e.code === 'Escape') {
                        window.getSelection()?.removeAllRanges();
                    }
                });
            };
            const addCopy = (editor, textarea) => {
                if (editor.parentNode.querySelector('.aa-document-copy')) {
                    return;
                }
                const copy = createDiv('aa-document-copy');
                copy.title = 'Copy to clipboard';
                copy.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"'
                    + ' stroke-width="1.5" stroke="currentColor" class="w-6 h-6">'
                    + '<path stroke-linecap="round" stroke-linejoin="round" '
                    + 'd="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125'
                    + ' 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06'
                    + ' 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.'
                    + '25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621'
                    + ' 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125'
                    + '-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125'
                    + ' 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75" /></svg>';
                editor.parentNode.prepend(copy);
                style(copy, {
                    position   : 'absolute',
                    height     : '20px',
                    width      : '20px',
                    top        : 'auto',
                    marginTop  : '1rem',
                    right      : '20px',
                    lineHeight : '25px',
                    cursor     : 'pointer',
                    zIndex     : '100'
                });
                let timeout,
                    interval;
                copy.addEventListener('click', (a) => {
                    a.preventDefault();
                    if (!navigator || !navigator.clipboard) {
                        return;
                    }
                    let content = textarea ? textarea.value : editor.textContent;
                    let saved = editor.parentNode.querySelector('.aa-copied');
                    if (saved) {
                        saved.remove();
                    }
                    if (timeout) {
                        clearTimeout(timeout);
                        timeout = null;
                    }
                    if (interval) {
                        clearInterval(interval);
                        interval = null;
                    }
                    saved = createDiv('aa-copied', 'Copied');
                    copy.prepend(saved);
                    style(saved, {
                        position   : 'absolute',
                        left       : 'calc(-100% - 2em)',
                        top        : '-.2em',
                        color      : '#8e6927',
                        visibility : 'visible',
                        opacity    : '1',
                        transition : 'all ease 1s',
                        zIndex     : '1000',
                        fontSize   : '12px'
                    });
                    timeout = setTimeout(() => {
                        interval = setInterval(() => {
                            // calculate opacity
                            let opacity = parseFloat(saved.style.opacity.toString() || 0.1);
                            opacity -= 0.1;
                            if (opacity <= 0) {
                                clearInterval(interval);
                                interval = null;
                                // saved.remove();
                                return;
                            }
                            saved.style.opacity = opacity;
                        }, 50);
                    }, 500);
                    content = content || editor.textContent.replace(new RegExp(String.fromCharCode(160), "g"), ' ');
                    const p = navigator?.clipboard.writeText(content);
                    if (p instanceof Promise) {
                        p.then((e) => e);
                    }
                });
            };
            const codeEditorRender = (_this, props) => {
                if (!HighLight
                    || !(_this instanceof HTMLElement)
                    || _this.getAttribute('data-code-editor') !== 'codejar'
                    || props !== undefined && (
                        typeof props !== 'object'
                        || typeof props.setAttributes !== 'function'
                        || typeof props.attributes !== 'object'
                    )
                ) {
                    return;
                }

                let maxLength = _this.maxLength || 0;
                if (typeof maxLength !== 'number') {
                    maxLength = 0;
                }
                if (maxLength < 0) {
                    maxLength = 0;
                }

                const getValue = (el) => isInputForm(el) ? el.value : el.textContent;
                const attr = (e, _default) => _this.getAttribute(e) || _default;
                const rect = _this.getBoundingClientRect();
                const mainWrapper = _this.closest('.aa-editor-wrap');
                const isWidget = attr('data-arrayaccess-widget') === 'code-editor';
                const contentEditable = attr('data-code-editor-editable') === 'yes';
                const isReadOnly = isWidget && contentEditable
                    ? false
                    : (isWidget || ! isInputForm(_this) ? true : !!(_this.readOnly || _this.disabled));
                // make readonly
                _this.readOnly = isReadOnly;
                const _height = rect.height,
                    theme = attr('data-code-editor-theme', 'default'),
                    language = attr('data-code-editor-language', 'plaintext');
                let isResizable = attr('data-code-editor-resizable') === 'true';
                // allow resize if is widget and not readonly, allow resize
                if (isWidget && !isReadOnly) {
                    isResizable = true;
                }

                const wrapper = mainWrapper || createDiv();
                const innerWrapper = select('.aa-editor-inner-wrap', wrapper) || createDiv();
                const headerWrapper = select('.aa-editor-header', innerWrapper) || createDiv();

                const editor  = select('.aa-editor-area', innerWrapper) || createDiv();

                wrapper.style.position = 'relative';
                wrapper.className = 'aa-editor-wrap language-' + language + ' theme-' + theme + (isResizable ? ' aa-editor-resizable' : '');
                innerWrapper.className = 'aa-editor-inner-wrap hljs';
                headerWrapper.className = 'aa-editor-header';
                headerWrapper.innerHTML = '<span class="aa-editor-header-btn"><span class="aa-c"></span><span class="aa-m"></span><span class="aa-f"></span></span></span>'
                    + '<span class="aa-header-language">' + language + '</span>';
                editor.className = 'aa-editor-area codejar';
                editor.language = language;
                editor.style.whiteSpace = 'pre';
                if (!mainWrapper) {
                    _this.parentNode.insertBefore(wrapper, _this);
                }
                html(wrapper, innerWrapper, _this);
                if (!mainWrapper) {
                    html(innerWrapper, headerWrapper, editor);
                }

                if (isReadOnly) {
                    makeEditorReadOnly(editor);
                }

                let _value = getValue(_this);
                let setAttributes = null;
                if (props && typeof props === "object"
                    && typeof props.setAttributes === "function"
                ) {
                    setAttributes = props.setAttributes;
                    _value = props.attributes.content;
                }

                let jar;
                if (props && typeof props.attributes === 'object') {
                    jar = props.attributes.codeJar || null;
                    jar = typeof jar?.updateCode !== 'function' ? null : jar;
                } else if (_this.codeJar) {
                    jar = _this.codeJar;
                }
                if (!jar) {
                    // noinspection JSUnresolvedReference
                    const hl = (editor) => {
                        if (!isReadOnly) {
                            let content = editor.textContent;
                            if (maxLength > 0 && content.length > maxLength) {
                                content = content.substring(0, maxLength);
                                editor.textContent = content;
                            }
                            setValue(_this, editor);
                            if (setAttributes) {
                                setAttributes({content: editor.textContent});
                            }
                        } else {
                            editor.textContent = _value;
                        }
                        try {
                            if (lineNumbers) {
                                const editorStyle = {
                                    marginLeft  : (lineNumbers.getBoundingClientRect().width + 5) + 'px',
                                    paddingLeft : 0
                                };
                                style(editor, editorStyle);
                            }
                        } catch (err) {
                            //
                        }
                        editor.innerHTML = HighLight
                            .highlight(editor.textContent, {language: editor.language, ignoreIllegals: true})
                            .value;
                    };
                    const highlight = CodeJarLineNumber ? withLineNumbers(
                        hl,
                        {
                            class     : 'codejar-linenumbers',
                            wrapClass : 'inner-codejar-wrap'
                        }
                    ) : hl;
                    jar = w.CodeJar(
                        editor,
                        highlight,
                        {tab: ' '.repeat(4)}
                    );
                }

                style(_this, {display: 'none'});
                jar.updateCode(_value);
                _this.codeJar = jar;

                // compute editor style
                const compute = window.getComputedStyle(editor);
                const lineNumbers = select('.codejar-linenumbers', wrapper);
                style(lineNumbers, {
                    width        : 'auto',
                    paddingLeft  : '1rem',
                    paddingRight : '1rem'
                });
                let editorStyle = {};
                if (lineNumbers) {
                    editorStyle = {
                        marginLeft  : (lineNumbers.getBoundingClientRect().width + 5) + 'px',
                        paddingLeft : 0
                    };
                }
                let editorHeight = attr('data-code-editor-height');
                if (editorHeight && !editorHeight.match(/^[0-9]+px$/)) {
                    editorHeight = Infinity;
                }
                if (props && editorHeight && editorHeight !== Infinity) {
                    editorStyle.height = editorHeight;
                } else if (isWidget && !editorHeight && editorHeight !== Infinity) {
                    editorStyle.height = '';
                }
                if (!setAttributes && lineNumbers) {
                    editor.style.height = (
                        _height
                        - parseInt(compute.paddingTop || 0)
                        - parseInt(compute.paddingBottom || 0)
                    ) + 'px';
                }

                style(editor, editorStyle);
                if (setAttributes) {
                    setAttributes({codeJar: jar});
                }
                addCopy(editor, _this);
                return editor;
            };
            selects('[data-code-editor=codejar]')
                .forEach((e) => {
                    if (!codeEditorRender(e)) {
                        return;
                    }
                    dispatchReadyEvent(e, 'code-editor-ready', e.codeJar);
                });
            selects('[data-code-highlight]')
                .forEach((e) => {
                    const language = e.getAttribute('data-code-highlight') || 'plaintext';
                    e.classList.add('language-' + language);
                    e.classList.add('hljs');
                    e.style.whiteSpace = 'pre';
                    e.innerHTML = HighLight
                        .highlight(e.textContent, {language, ignoreIllegals: true})
                        .value;
                    dispatchReadyEvent(e, 'code-highlight-ready');
                });
            window.codeJarWindowInit = (_textarea, props) => codeEditorRender(_textarea, props);
            Object.freeze(window.codeJarWindowInit);
        }
        /* Color Picker */
        if ($?.fn?.wpColorPicker) {
            $('[data-color-picker=true]').each(function () {
                const _this = $(this);
                if (typeof _this.wpColorPicker !== "function") {
                    return;
                }
                let options = {};
                try {
                    options = _this.attr('data-color-picker-options');
                    if (typeof options === 'string') {
                        options = JSON.parse(options);
                    }
                    if (typeof options !== 'object') {
                        options = {};
                    }
                } catch (err) {
                    // skip
                }
                options.change = function (event, ui) {
                    $(this).trigger('wp-color-picker-change', [event, ui]);
                };
                _this.trigger('wp-color-picker-ready', [_this[0], _this.wpColorPicker(options)]);
                _this
                    .closest('.wp-picker-container')
                    .addClass('aa-wp-color-picker-ready');
            });
        }
        if ($?.fn?.selectize) {
            $('select[data-selectize=true]').each(function () {
                const _this = $(this);
                if (typeof _this.selectize !== "function") {
                    return;
                }
                let options = _this.data('selectize-options');
                if (!options || typeof options !== 'object') {
                    options = {};
                }
                _this.trigger('selectize-ready', [_this[0], _this.selectize(options)]);
            });
        }
        if ($) {
            $(document).on('click', '.aa-editor-header-btn span[class^=aa-]',  function (e)  {
                const editor = $(this).closest('.aa-editor-wrap');
                e.preventDefault();
                if (!editor.length) {
                    return;
                }
                const enableButton = editor
                    .find('[data-code-editor]')
                    .attr('data-code-enable-button') !== 'no';
                let className;
                if (enableButton) {
                    className = this.className.trim().replace(/^.*?aa-([cfm]).*/g, '$1');
                    if (['c', 'f', 'm'].indexOf(className) === -1) {
                        return;
                    }
                    const currentAttr = editor.attr('data-code-editor-screen');
                    if (currentAttr === 'f' && (className === 'f' || className === 'c')) {
                        className = '';
                    } else {
                        className = currentAttr === className ? '' : className;
                    }
                } else {
                    className = '';
                }
                if (className === 'f') {
                    $('[data-code-editor-screen=f]').not(editor).attr('data-code-editor-screen', '');
                    document.body.setAttribute('data-code-editor-screen-mode', className);
                } else {
                    document.body.removeAttribute('data-code-editor-screen-mode');
                }

                editor.attr('data-code-editor-screen', className);
            });
            $(document).on('keydown', (e) => {
                // if is escape key
                if (e.keyCode === 27) {
                    const editor = $('.aa-editor-wrap[data-code-editor-screen="f"]');
                    if (!editor.length) {
                        return;
                    }
                    $('body').removeAttr('data-code-editor-screen-mode');
                    editor.attr('data-code-editor-screen', '');
                }
            });
        }
        return true;
    };

    ready(() => {
        const scriptIdSelector = 'script[id^=arrayaccess][async]';
        const asyncScripts = selects(scriptIdSelector);
        !asyncScripts.length ? init() : Promise.all(asyncScripts.map((e) => {
            return new Promise((resolve, reject) => {
                e.onload = resolve;
                e.onerror = reject;
            });
        })).then(init).catch(init);
    });
})(window);

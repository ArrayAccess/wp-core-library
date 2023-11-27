// noinspection SillyAssignmentJS

/*!
 * Admin Library JS init
 */
(function (w) {
    "use strict";
    const init = function () {
        //const wp = w.wp||null;
        const $ = w.jQuery || null;
        if (!$) {
            return;
        }
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
        // const select = (selector, context) => (context || document).querySelector(selector);
        const selects = (selector, context) => (context || document).querySelectorAll(selector);
        if ($.fn.wpColorPicker) {
            $('[data-color-picker=true]').each(function () {
                const _this = $(this);
                if (typeof _this.wpColorPicker !== "function") {
                    return;
                }
                const options = _this.data('color-picker-options');
                if (!options || typeof options !== 'object') {
                    return;
                }
                options.change = function (event, ui) {
                    $(this).trigger('wp-color-picker-change', [event, ui]);
                };
                _this.trigger('wp-color-picker-ready', [_this, _this.wpColorPicker(options)]);
                _this
                    .closest('.wp-picker-container')
                    .addClass('aa-wp-color-picker-ready');
            });
            if (typeof w.CodeJar === 'function' && (typeof w['HighlightJS'] === 'object' ||  typeof w['hljs'] === 'object')) {
                const HighLight = typeof w['HighlightJS'] === 'object'
                    && typeof w['HighlightJS'].highlightElement === 'function'
                    ? w['HighlightJS']
                    : (w['hljs'] && typeof w['hljs'].highlightElement ==='function' ? w['hljs'] : null);
                const CodeJarLineNumber = HighLight ? (w['CodeJarWithLineNumbers'] || null) : null;
                const withLineNumbers = CodeJarLineNumber ? CodeJarLineNumber['withLineNumbers'] : null;
                const makeEditorReadOnly = (editor) => {
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
                    const copy = createDiv('aa-document-copy');
                    copy.title = 'Copy content to clipboard';
                    copy.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"'
                        +' stroke-width="1.5" stroke="currentColor" class="w-6 h-6">'
                        +'<path stroke-linecap="round" stroke-linejoin="round" '
                        +'d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125'
                        +' 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06'
                        +' 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.'
                        +'25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621'
                        +' 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125'
                        +'-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125'
                        +' 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75" /></svg>';
                    editor.parentNode.prepend(copy);
                    copy.style.position = 'absolute';
                    copy.style.height = '20px';
                    copy.style.width = '20px';
                    copy.style.top = 'auto';
                    copy.style.marginTop = '1em';
                    copy.style.right = '1.8em';
                    let timeout,
                        interval;
                    copy.addEventListener('click', (a) => {
                        a.preventDefault();
                        if (!navigator||!navigator.clipboard) {
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
                        saved.style.position = 'absolute';
                        saved.style.left = 'calc(-100% - 2em)';
                        saved.style.top = '.2em';
                        saved.style.color = '#8e6927';
                        saved.style.opacity = '1';
                        saved.style.visibility = 'visible';
                        saved.style.transition = 'all ease 1s';
                        saved.style.zIndex = '1000';
                        saved.style.fontSize = '12px';
                        timeout = setTimeout(() => {
                            interval = setInterval(() => {
                                // calculate opacity
                                let opacity = parseFloat(saved.style.opacity.toString());
                                opacity -= 0.1;
                                if (opacity <= 0) {
                                    clearInterval(interval);
                                    interval = null;
                                    saved.remove();
                                    return;
                                }
                                saved.style.opacity = opacity;
                            }, 50);
                        }, 500);
                        content = content || editor.textContent.replace(new RegExp(String.fromCharCode(160), "g"), ' ');
                        const p = navigator.clipboard.writeText(content);
                        if (p instanceof Promise) {
                            p.then((e) => e);
                        }
                    });
                };
                const codeEditorFunction = function(_this) {
                    if (!HighLight) {
                        return;
                    }
                    const theme = _this.getAttribute('data-code-editor-theme') || 'default';
                    const language = _this.getAttribute('data-code-editor-language') || 'plaintext';
                    const isResizable = (_this.getAttribute('data-code-editor-resizable') || false) === 'true';
                    const editor = createDiv(
                        'aa-editor-area codejar'
                    );
                    const headerWrapper = createDiv(
                        'aa-editor-header',
                        '<span class="aa-header-language">'
                        + language
                        + '</span><span></span><span></span>'
                    );
                    const innerWrapper = createDiv(
                        'codejar-wrap hljs' + ' language-' + language,
                        null,
                        headerWrapper,
                        editor
                    );
                    const wrapper = createDiv(
                        'aa-editor-wrap theme-' + theme + ' language-' + language + (
                            isResizable ? ' aa-editor-resizable' : ''
                        ),
                        null,
                        innerWrapper
                    );
                    const isReadOnly = !!(_this.readOnly || _this.disabled);
                    if (isReadOnly) {
                        makeEditorReadOnly(editor);
                    }

                    const _value = _this.value;
                    wrapper.style.position = 'relative';
                    _this.parentNode.insertBefore(wrapper, _this);
                    // noinspection JSUnresolvedReference
                    const hl = (editor) => {
                        /*if (editor.dataset.highlighted) {
                            delete editor.dataset.highlighted;
                        }*/
                        if (!isReadOnly) {
                            _this.value = editor.textContent;
                        } else {
                            editor.textContent = _value;
                        }
                        editor.innerHTML = HighLight
                            .highlight(editor.textContent, { language, ignoreIllegals: true })
                            .value;
                    };
                    editor.style.height = _this.innerHeight;
                    const highlight = CodeJarLineNumber ? withLineNumbers(
                        hl,
                        {
                            class     : 'codejar-linenumbers',
                            wrapClass : 'inner-codejar-wrap'
                        }
                    ) : hl;
                    const jar = w.CodeJar(
                        editor,
                        highlight,
                        {tab: ' '.repeat(4)}
                    );
                    _this.style.display = 'none';
                    jar.updateCode(_value);
                    addCopy(editor, _this);
                };
                selects('textarea[data-code-editor=codejar]')
                    .forEach(codeEditorFunction);
                selects('[data-code-highlight]').forEach((e) => {
                    console.log(e);
                    const language = e.getAttribute('data-code-highlight') || 'plaintext';
                    e.classList.add('language-' + language);
                    e.classList.add('hljs');
                    e.style.whiteSpace = 'pre';
                    e.innerHTML = HighLight
                        .highlight(e.textContent, { language, ignoreIllegals: true })
                        .value;
                });
            }
        }
    };

    // init on document ready
    if (w.document.readyState === "complete" || (w.document.readyState !== "loading")) {
        // if document is already ready, then init
        init();
    } else {
        // if document hasn't finished loading, add event listener
        w.document.addEventListener("DOMContentLoaded", init);
    }
})(window);

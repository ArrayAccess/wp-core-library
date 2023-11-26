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
                $('textarea[data-code-editor=codejar]').each(function () {
                    if (!HighLight) {
                        return;
                    }
                    const $this = $(this);
                    const wrapper = document.createElement('div');
                    const innerWrapper = document.createElement('div');
                    const headerWrapper = document.createElement('div');
                    const editor = document.createElement('div');
                    const theme = $this.data('code-editor-theme') || 'default';
                    const language = $this.data('code-editor-language') || 'plaintext';
                    headerWrapper.className = 'aa-editor-header';
                    headerWrapper.innerHTML = '<span class="aa-header-language">'
                        +language+'</span><span></span><span></span>';
                    wrapper.className = 'aa-editor-wrap theme-' + theme + ' language-' + language;
                    innerWrapper.className = 'codejar-wrap hljs' + ' language-' + language;
                    wrapper.style.position = 'relative';
                    editor.className = 'aa-editor-area codejar';
                    wrapper.appendChild(innerWrapper);
                    innerWrapper.appendChild(headerWrapper);
                    innerWrapper.appendChild(editor);
                    // noinspection JSUnresolvedReference
                    const hl = (editor) => {
                        const code = editor.textContent;
                        if (editor.dataset.highlighted) {
                            delete editor.dataset.highlighted;
                        }
                        $this.val(code);
                        editor.innerHTML = HighLight
                            .highlight(code, { language, ignoreIllegals: true })
                            .value;
                    };
                    editor.style.height = $this.css('height');
                    $this.before(wrapper);
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
                    $this.hide();
                    jar.updateCode($this.val());
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

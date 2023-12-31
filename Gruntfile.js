/**
 * Gruntfile.js
 */
(function() {
    "use strict";
    /**
     * Clean css comment
     *
     * @param {string} src
     * @return {string}
     */
    function cleanCssComment(src) {
        // replace all css comment
        src = src.replace(/\/\*[\s\S]*?\*\//g, '');
        const _array = src.split("\n");
        const data = [];
        let i;
        for (i in _array) {
            if (_array[i].match(/^\s*$/)) {
                continue;
            }
            // check if invalid data hljs without a comma on the end
            if (_array[i].match(/^\s*\.hljs-/) && ! _array[i].match(/[,{]\s*$/)) {
                _array[i] += ',';
            }
            _array[i] = _array[i].replace(/(^\s*|\s*$)/g, '');
            // add semicolon if not exist
            if (_array[i].match(/^[^:]+:[^;,]+[^;,]\s*$/g)
                && ! _array[i].match(/[}{]\s*$/g)
            ) {
                _array[i] += ';';
            }
            data.push(_array[i]);
        }
        return data.join("\n");
    }

    /**
     * Replace css theme
     *
     * @param src
     * @param filepath
     * @param prefix
     * @param defaultTheme
     * @param preventContains
     * @returns {*|string}
     */
    function cssThemeReplace(src, filepath, prefix, defaultTheme = 'default', preventContains = null) {
        if (filepath.match(/\.min\.css$/)) {
            return '';
        }
        if (preventContains && filepath.match(preventContains)) {
            return src;
        }
        // add prefix before .hljs
        const fileName = filepath
            .replace(/^.+[\\/]([^\\/]+)(?:\.min)?\.css$/g, '$1')
            .replace('_', '-')
            .trim()
            .trim('-');
        src = cleanCssComment(src);
        const isDefault = fileName === defaultTheme;
        const banner = isDefault
            ? `/* Default */\n` : `/* theme : ${fileName} */\n`;
        function fixIndentation(cssCode) {
            let level = 0;
            let formattedCode = '';

            const lines = cssCode.split('\n');

            for (let i = 0; i < lines.length; i++) {
                const line = lines[i].trim();

                if (line.includes('}')) {
                    level--;
                }

                formattedCode += '    '.repeat(level) + line + '\n';

                if (line.includes('{')) {
                    level++;
                }
            }

            return formattedCode;
        }
        if (isDefault) {
            return banner + fixIndentation(src);
        }
        function replace(src, checked) {
            const a = src.split("\n");
            const firstRegexP= new RegExp(`^\\s*\\.${prefix}-`);
            const themeRegexP = new RegExp(`^\\s*([a-zA-Z.].+)?\\.${prefix}.*[,{]`, 'g');
            let i;
            for (i in a) {
                // hljs contain invalid
                if (a[i].match(firstRegexP) && !a[i].match(/[,{]\s*$/)) {
                    a[i] += ',';
                }
                if (a[i].match(/\.theme-/g) || ! a[i].match(themeRegexP)) {
                    continue;
                }

                a[i] = a[i].replace(/(^\s*|}\s*)/, (m, a) => {
                    return a + '.theme-' + fileName + ' ';
                });
            }
            src = a.join("\n");
            if (checked) {
                return banner + fixIndentation(src);
            }
            if (!src.match(/(\.theme-)/g)) {
                src = src
                    .replace(
                        /([}{,]|\*\/)/g,
                        "$1\n"
                    )
                    .replace(/[}]/g, "\n}")
                    .replace(/([a-zA-Z)])(\{)/g, '$1 $2')
                    .replace(/([{;]\n)([a-zA-Z\-_])/g, '$1  $2')
                    .replace(/(:[^;},]+)\n}/g, "$1;\n}")
                    .replace(/\n+/g, "\n");
                return replace(src, true);
            }
            return banner + fixIndentation(src);
        }
        return replace(src);
    }

    module.exports = (grunt) => {
        // noinspection JSUnresolvedReference
        grunt.initConfig({
            pkg  : grunt.file.readJSON('package.json'),
            copy : {
                // copy assets/js
                js: {
                    files: [
                        {
                            expand : true,
                            cwd    : 'assets/js',
                            src    : ['**/*.js'],
                            dest   : 'dist/js',
                            filter : 'isFile'
                        },
                        {
                            expand : true,
                            cwd    : 'assets/blocks',
                            src    : ['**/*.js'],
                            dest   : 'dist/blocks',
                            filter : 'isFile'
                        },
                        {
                            expand : true,
                            cwd    : 'assets/js',
                            src    : ['**/*.min.js'],
                            dest   : 'dist/js',
                            filter : 'isFile'
                        }
                    ]
                },
                vendor: {
                    files: [
                        {
                            expand : true,
                            cwd    : 'node_modules/@highlightjs/cdn-assets/styles',
                            src    : ['*.png', '*.gif', '*.jpg', '*.jpeg', '*.svg', '*.woff', '*.woff2', '*.ttf', '*.eot'],
                            dest   : 'dist/vendor/highlightjs',
                            filter : 'isFile'
                        },
                        {
                            expand : true,
                            cwd    : 'node_modules/@selectize/selectize/dist/js',
                            src    : ['**/*.js', '!**/*.min.js'],
                            dest   : 'dist/vendor/selectize',
                            filter : 'isFile'
                        },
                        {
                            expand : true,
                            cwd    : 'node_modules/@selectize/selectize/dist/css',
                            src    : ['selectize.default.css'],
                            dest   : 'dist/vendor/selectize',
                            filter : 'isFile'
                        },
                        {
                            expand : true,
                            cwd    : "node_modules/flatpickr/dist/l10n",
                            src    : ["*.js", "!*.min.js"],
                            dest   : "dist/vendor/flatpickr/l10n",
                            filter : "isFile"
                        }
                    ]
                }
            },
            concat: {
                codejar: {
                    files: {
                        "dist/vendor/codejar/codejar.bundle.css": [
                            "node_modules/codejar-linenumbers/js/codejar-linenumbers.css"
                        ]
                    }
                },
                flatpickrCSS: {
                    options: {
                        process: (src, filePath) => cssThemeReplace(
                            src,
                            filePath,
                            'flatpickr',
                            'flatpickr',
                            /\/+plugins\/+.+\.css$/g
                        )
                    },
                    files: {
                        "dist/vendor/flatpickr/flatpickr.bundle.css": [
                            "node_modules/flatpickr/dist/flatpickr.css",
                            "node_modules/flatpickr/dist/plugins/**/*.css",
                            // we do not need theme css
                            // "node_modules/flatpickr/dist/themes/*.css",
                            // "!node_modules/flatpickr/dist/themes/*.min.css",
                            "!node_modules/flatpickr/dist/plugins/**/*.min.css"
                        ]
                    }
                },
                flatpickr: {
                    files: {
                        "dist/vendor/flatpickr/flatpickr.bundle.js": [
                            "node_modules/flatpickr/dist/flatpickr.js",
                            "node_modules/flatpickr/dist/plugins/**/*.js",
                            "node_modules/flatpickr/dist/plugins/*.js",
                            "!node_modules/flatpickr/dist/plugins/**/*.min.js",
                            "!node_modules/flatpickr/dist/plugins/*.min.js"
                        ]
                    }
                },
                highlightjsCSS: {
                    options: {
                        process: (src, filePath) => cssThemeReplace(
                            src,
                            filePath,
                            'hljs',
                            'default'
                        )
                    },
                    files: {
                        "dist/vendor/highlightjs/highlight.bundle.css": [
                            "node_modules/@highlightjs/cdn-assets/styles/default.css",
                            "node_modules/@highlightjs/cdn-assets/styles/*.css",
                            "!node_modules/@highlightjs/cdn-assets/styles/*.min.css"
                        ]
                    }
                },
                highlightjs: {
                    options: {
                        process: function(src, filepath) {
                            if (filepath.match(/\/highlight\.js$/)) {
                                src = src + '\nwindow.hljs = window.HighlightJS = hljs;';
                                return '(function() {\n' + src + '\n})();';
                            }
                            return src;
                        }
                    },
                    src: [
                        "node_modules/@highlightjs/cdn-assets/highlight.js",
                        "node_modules/@highlightjs/cdn-assets/languages/*.js"
                    ],
                    dest: "dist/vendor/highlightjs/highlight.bundle.js"
                },
                // codejar just need replace export function
                editor: {
                    options: {
                        process: function(src, filename) {
                            if (filename.match(/\/codejar\.js$/)) {
                                src = src
                                    .replace(/export\s+function/g, 'function');
                                src = src + '\nwindow.CodeJar = CodeJar;';
                            } else if (filename.match(/\/codejar-linenumbers\.js$/)) {
                                src = 'window.CodeJarWithLineNumbers = '
                                    + src.replace(/^\s*var\s+[^(]+\(/gi, '(');
                            }
                            return '(function() {\n' + src + '\n})();';
                        }
                    },
                    src: [
                        "node_modules/codejar/dist/codejar.js",
                        "node_modules/codejar-linenumbers/js/codejar-linenumbers.js",
                        "dist/vendor/highlightjs/highlight.bundle.js"
                    ],
                    dest: "dist/js/editor.bundle.js"
                }
            },
            uglify: {
                dist: {
                    options: {
                        mangle    : true,
                        sourceMap : false
                    },
                    files: [
                        {
                            expand : true,
                            cwd    : 'assets/js',
                            src    : ["**/*.js", "!**/*.min.js"],
                            dest   : 'dist/js',
                            ext    : '.min.js'
                        },
                        {
                            expand : true,
                            cwd    : 'assets/blocks',
                            src    : ["**/*.js", "!**/*.min.js"],
                            dest   : 'dist/blocks',
                            ext    : '.min.js'
                        },
                        {
                            expand : true,
                            cwd    : 'dist/vendor/selectize',
                            src    : ["**/*.js", "!**/*.min.js"],
                            dest   : 'dist/vendor/selectize',
                            ext    : '.min.js'
                        },
                        {
                            expand : true,
                            cwd    : "dist/vendor/flatpickr/l10n",
                            src    : ["*.js", "!*.min.js"],
                            dest   : "dist/vendor/flatpickr/l10n",
                            ext    : ".min.js"
                        }
                    ]
                },
                vendor: {
                    options: {
                        mangle    : true,
                        sourceMap : false
                    },
                    files: [
                        {
                            src  : "dist/vendor/flatpickr/flatpickr.bundle.js",
                            dest : 'dist/vendor/flatpickr/flatpickr.bundle.min.js'
                        },
                        {
                            src  : "dist/vendor/highlightjs/highlight.bundle.js",
                            dest : 'dist/vendor/highlightjs/highlight.bundle.min.js'
                        },
                        {
                            src  : "node_modules/codejar/dist/codejar.js",
                            dest : 'dist/vendor/codejar/codejar.bundle.min.js'
                        },
                        {
                            src  : "dist/js/editor.bundle.js",
                            dest : 'dist/js/editor.bundle.min.js'
                        }
                    ]
                }
            },
            // minify css, called after concat
            cssmin: {
                vendor: {
                    options: {
                        keepSpecialComments: 0
                    },
                    files: [
                        {
                            src  : "dist/vendor/flatpickr/flatpickr.bundle.css",
                            dest : 'dist/vendor/flatpickr/flatpickr.bundle.min.css'
                        },
                        {
                            src  : "dist/vendor/highlightjs/highlight.bundle.css",
                            dest : 'dist/vendor/highlightjs/highlight.bundle.min.css'
                        },
                        {
                            src  : "dist/vendor/codejar/codejar.bundle.css",
                            dest : 'dist/vendor/codejar/codejar.bundle.min.css'
                        },
                        {
                            src  : "dist/vendor/selectize/selectize.default.css",
                            dest : 'dist/vendor/selectize/selectize.default.min.css'
                        }
                    ]
                }
            },
            sass: {
                dist: {
                    options: {
                        style           : 'compressed',
                        'no-source-map' : ''
                    },
                    files: [
                        {
                            expand : true,
                            cwd    : 'assets/scss',
                            src    : ["**/*.scss", "!**/*.min.scss"],
                            dest   : 'dist/css',
                            ext    : '.min.css'
                        }
                    ]
                },
                expanded: {
                    options: {
                        style           : 'expanded',
                        'no-source-map' : ''
                    },
                    files: [
                        {
                            expand : true,
                            cwd    : 'assets/scss',
                            src    : ["**/*.scss", "!**/*.min.scss"],
                            dest   : 'dist/css',
                            ext    : '.css'
                        }
                    ]
                }
            },
            watch: {
                uglify: {
                    files: [
                        "assets/js/**/*.js",
                        "assets/blocks/**/*.js",
                        "!assets/js/**/*.min.js",
                        "!assets/blocks/**/*.min.js"
                    ],
                    tasks: ['uglify:dist']
                },
                sass: {
                    files : ["assets/scss/**/*.scss", "!assets/scss/**/*.min.scss"],
                    tasks : ['sass']
                }
            }
        });

        grunt.loadNpmTasks('grunt-contrib-concat');
        grunt.loadNpmTasks('grunt-contrib-copy');
        grunt.loadNpmTasks('grunt-contrib-cssmin');
        grunt.loadNpmTasks('grunt-contrib-sass');
        grunt.loadNpmTasks('grunt-contrib-uglify');
        grunt.loadNpmTasks('grunt-contrib-watch');

        grunt.registerTask('default', [
            "copy",
            "concat",
            "cssmin",
            "sass",
            "uglify"
        ]);
    };
})();

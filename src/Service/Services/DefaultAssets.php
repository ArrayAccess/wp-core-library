<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Service\Services;

use ArrayAccess\WP\Libraries\Core\Service\Abstracts\AbstractService;
use ArrayAccess\WP\Libraries\Core\Service\Services;
use ArrayAccess\WP\Libraries\Core\Util\Filter;
use ArrayAccess\WP\Libraries\Core\Util\HighlightJS;
use function add_action;
use function call_user_func;
use function did_action;
use function dirname;
use function doing_action;
use function explode;
use function is_array;
use function is_callable;
use function is_numeric;
use function is_string;
use function plugins_url;
use function preg_match;
use function preg_replace_callback;
use function remove_action;
use function site_url;
use function str_contains;
use function str_ends_with;
use function strtolower;
use function substr;
use function trim;
use function wp_enqueue_script;
use function wp_enqueue_style;
use function wp_register_script;
use function wp_register_style;
use function wp_script_add_data;
use function wp_script_is;
use function wp_scripts;
use function wp_style_is;

/**
 * Service that help to register default assets.
 */
final class DefaultAssets extends AbstractService
{
    protected string $serviceName = 'defaultAssets';

    /**
     * @var string The dist path.
     */
    protected string $distPath;

    /**
     * @var array The queued assets.
     */
    protected array $assets = [
        'css' => [],
        'js' => [],
    ];
    /**
     * @var array<string, true> The registered assets handle. This is used to prevent duplicate register.
     */
    protected array $registeredAssetsHandle = [];

    /**
     * @var ?DefaultAssets The instance.
     */
    private static ?DefaultAssets $instance = null;

    /**
     * Default assets.
     */
    public const ASSETS = [
        'css' => [
            'arrayaccess-common' => [
                'src' => '{{dist_url}}/css/common.min.css',
                'deps' => [],
                'ver' => '1.0.0',
                'media' => 'all',
            ],
            'arrayaccess-admin' => [
                'src' => '{{dist_url}}/css/admin.min.css',
                'deps' => [
                    'arrayaccess-common'
                ],
                'ver' => '1.0.0',
                'media' => 'all',
            ],
            'arrayaccess-highlightjs' => [
                'src' => '{{dist_url}}/highlightjs/highlight.bundle.min.css',
                'deps' => [
                    'arrayaccess-common'
                ],
                'ver' => HighlightJS::VERSION,
                'media' => 'all',
            ],
        ],
        'js' => [
            'arrayaccess-common' => [
                'src' => '{{dist_url}}/js/common.min.js',
                'deps' => [
                    'jquery',
                ],
                'ver' => '1.0.0',
                'in_footer' => true,
            ],
            'arrayaccess-admin' => [
                'src' => '{{dist_url}}/js/admin.min.js',
                'deps' => [
                    'arrayaccess-common',
                    'wp-util',
                ],
                'ver' => '1.0.0',
                'in_footer' => true,
            ],
            'arrayaccess-highlightjs' => [
                'src' => '{{dist_url}}/highlightjs/highlight.bundle.js',
                'deps' => [],
                'ver' => HighlightJS::VERSION,
                'in_footer' => true,
            ],
            'arrayaccess-editor' => [
                'src' => '{{dist_url}}/js/editor.bundle.min.js',
                'deps' => [],
                'ver' => '1.0.0',
                'in_footer' => false,
                // 'type' => 'module'
            ]
        ],
    ];

    /**
     * @var bool Whether the service is initialized.
     */
    private bool $init = false;

    /**
     * @var array<string, callable> The replacer.
     */
    protected array $replacer = [];

    /**
     * @var array|string[] The reserved replacer.
     */
    protected array $reservedReplacer = [
        'dist_url',
        'dist_path',
        'site_url',
        'home_url',
        'template_uri',
        'stylesheet_uri',
        'plugin_url'
    ];

    /**
     * @inheritdoc
     */
    protected function onConstruct(): void
    {
        $this->description = __(
            'Service that help to register default assets.',
            'arrayaccess'
        );
        self::$instance = $this;
        $this->distPath = Filter::pathURL(
            dirname(__DIR__, 3) . '/dist'
        );

        $this->replacer = [];
    }

    /**
     * Add replacer.
     *
     * @param string $key
     * @param callable $callback
     * @return bool Whether the replacer is added.
     */
    public function addReplacer(string $key, callable $callback): bool
    {
        if (in_array($key, $this->reservedReplacer, true)) {
            return false;
        }
        $this->replacer[$key] = $callback;
        return true;
    }

    /**
     * @return DefaultAssets The instance.
     */
    public static function getInstance(): DefaultAssets
    {
        return self::$instance ??= new self(new Services());
    }

    /**
     * @param string $string
     * @return string
     */
    public function replace(string $string): string
    {
        // after = is argument
        return preg_replace_callback(
            '~\{{2}([^}=]+)(?:=([^}]+))?}{2}~',
            function ($matches) {
                $replacer = $matches[1];
                $callback = $this->replacer[$replacer]??null;
                // is reserved keyword
                $reserved = !$callback && in_array($replacer, $this->reservedReplacer, true);
                // return empty string
                if (!$reserved && (!$callback || !is_callable($callback))) {
                    return '';
                }

                // split by | for argument lists
                $args = $matches[2]??null;
                $args = $args ? explode('|', $args) : [];
                $arguments = [];
                // replace reserved keyword. with real data
                foreach ($args as $arg) {
                    $arg = trim($arg);
                    $lowerArgs = strtolower($arg);
                    $match = match ($lowerArgs) {
                        'false' => false,
                        'true' => true,
                        'null' => null,
                        'empty' => '',
                        default => $arg,
                    };
                    if (is_numeric($match)) {
                        // replace numeric float or integer
                        $match = str_contains($arg, '.') ? (float) $arg : (int) $arg;
                    }
                    if ($match === $arg) {
                        // replace quote literal have matched start & end
                        if (preg_match('~^([\'"])(.*)\1$~', $arg, $match)) {
                            $match = $match[2];
                        }
                    }
                    $arguments[] = $match;
                }
                return match ($replacer) {
                    'dist_url' => $this->getDistURL(),
                    'dist_path' => $this->getDistPath(),
                    'site_url' => site_url(...$arguments),
                    'home_url' => home_url(...$arguments),
                    'plugin_url' => plugins_url(...$arguments),
                    'template_uri' => get_template_directory_uri(),
                    'stylesheet_uri' => get_stylesheet_directory_uri(),
                    default => !$callback ? '' : call_user_func($callback, ...$arguments)
                };
            },
            $string
        );
    }

    /**
     * Initialize the service.
     *
     * @return void
     */
    public function init(): void
    {
        if ($this->init) {
            return;
        }
        add_action('wp_script_attributes', function ($attrs) {
            if (!is_string($attrs['id']??null)
                || !str_ends_with($attrs['id'], '-js')
            ) {
                return $attrs;
            }
            $id = substr($attrs['id'], 0, -3);
            if (!isset($this->registeredAssetsHandle['js'][$id])) {
                return $attrs;
            }
            $script = wp_scripts()->get_data($id, 'type');
            if ($script !== 'module') {
                return $attrs;
            }
            $attrs['type'] = 'module';
            return $attrs;
        });
        $this->init = true;
        $this->registerDefaultAssets();
        if (did_action('init')
            || doing_action('init')
        ) {
            $this->registerAdminAssets();
        } else {
            $callback = function () use (&$callback) {
                $this->registerAdminAssets();
            };
            add_action('init', $callback);
        }
    }

    /**
     * @return string The dist path.
     */
    public function getDistPath(): string
    {
        return $this->distPath;
    }

    /**
     * @return string The dist url.
     */
    public function getDistURL(): string
    {
        return site_url($this->getDistPath());
    }

    /**
     * @return void Register default assets.
     */
    private function registerDefaultAssets(): void
    {
        foreach (self::ASSETS as $type => $assets) {
            foreach ($assets as $handle => $asset) {
                $this->registerAsset($handle, $asset, $type);
            }
        }
        $this->register();
    }

    /**
     * @return void Register admin assets.
     */
    private function registerAdminAssets(): void
    {
        $callback = function () use (&$callback) {
            remove_action('admin_enqueue_scripts', $callback);
            $this->enqueueAsset('arrayaccess-common');
            $this->enqueueAsset('arrayaccess-admin');
        };
        add_action('admin_enqueue_scripts', $callback);
    }

    /**
     * Register asset.
     *
     * @param string $handle
     * @param array $asset
     * @param string $type
     * @return bool
     */
    public function registerAsset(string $handle, array $asset, string $type): bool
    {
        if (trim($handle) === '') {
            return false;
        }
        $type = strtolower(trim($type));
        // only accept css and js
        if (!isset(self::ASSETS[$type])) {
            return false;
        }

        // check if asset has src
        if (!is_string($asset['src']??null)) {
            return false;
        }
        // check if asset src is empty
        if (trim($asset['src']) === '') {
            return false;
        }
        if (str_contains($asset['src'], '{{')) {
            $asset['src'] = $this->replace($asset['src']);
        }
        if ($type === 'css') {
            $asset['media'] = $asset['media']??'all';
            $asset['media'] = !is_string($asset['media']) || trim($asset['media']) === '' ? 'all' : $asset['media'];
        } else {
            $asset['in_footer'] = $asset['in_footer']??true;
            $asset['in_footer'] = !is_bool($asset['in_footer']) || $asset['in_footer'];
        }
        $asset['deps'] = $asset['deps']??[];
        $asset['deps'] = is_string($asset['deps']) ? [$asset['deps']] : $asset['deps'];
        $asset['deps'] = !is_array($asset['deps']) ? [] : $asset['deps'];
        $asset['ver'] = $asset['ver']??false;
        $asset['ver'] = !is_string($asset['ver']) || trim($asset['ver']) === '' ? false : $asset['ver'];
        $asset['type'] = $asset['type']??null;
        // check if asset is already registered & same value
        if (isset($this->assets[$type][$handle])) {
            $registeredAsset = $this->assets[$type][$handle];
            if ($registeredAsset === $asset) {
                return true;
            }
        }
        $this->assets[$type][$handle] = $asset;
        return true;
    }

    /**
     * Check if scripts registration is doing wrong.
     * This to prevent error on register scripts.
     *
     * @return bool
     */
    private function isDoingWrongScripts() : bool
    {
        return !(
            did_action('init')
            || did_action('wp_enqueue_scripts')
            || did_action('admin_enqueue_scripts')
            || did_action('login_enqueue_scripts')
        );
    }

    /**
     * @return void Register assets.
     */
    private function registerAssets(): void
    {
        foreach ($this->assets as $type => $assets) {
            foreach ($assets as $handle => $asset) {
                if (isset($this->registeredAssetsHandle[$type][$handle])) {
                    continue;
                }
                if ($type === 'css') {
                    wp_register_style($handle, $asset['src'], $asset['deps'], $asset['ver'], $asset['media']);
                } else {
                    wp_register_script($handle, $asset['src'], $asset['deps'], $asset['ver'], $asset['in_footer']);
                    if (($asset['type']??null) === 'module') {
                        wp_script_add_data($handle, 'type', 'module');
                    }
                }

                $this->registeredAssetsHandle[$type][$handle] = true;
            }
        }
    }

    /**
     * Register assets.
     *
     * @return void
     */
    public function register(): void
    {
        $callback = function () use (&$callback) {
            remove_action('init', $callback);
            $this->registerAssets();
        };

        if ($this->isDoingWrongScripts()) {
            add_action('init', $callback);
            return;
        }
        $callback();
    }

    /**
     * Enqueue asset.
     *
     * @param string $handle
     * @param ?string $type null to enqueue both css and js, css to enqueue only css, js to enqueue only js.
     * @return void
     */
    public function enqueueAsset(string $handle, ?string $type = null): void
    {
        if (trim($handle) === '') {
            return;
        }

        if ($type !== null) {
            $type = strtolower(trim($type));
            // only accept css and js
            if (!isset(self::ASSETS[$type])) {
                return;
            }
        }
        $callback = function () use (&$callback, $handle, $type) {
            remove_action('init', $callback);
            if ($type && !isset($this->assets[$type][$handle])) {
                return;
            }

            if ($type === null || $type === 'css') {
                if (isset($this->assets['css'][$handle])
                    && !wp_style_is($handle)
                    && wp_style_is($handle, 'registered')
                ) {
                    wp_enqueue_style($handle);
                }
            }

            if ($type === null || $type === 'js') {
                if (isset($this->assets['js'][$handle])
                    && !wp_script_is($handle)
                    && wp_script_is($handle, 'registered')
                ) {
                    wp_enqueue_script($handle);
                }
            }
        };

        // check if scripts registration is doing wrong.
        if ($this->isDoingWrongScripts()) {
            add_action('init', $callback);
            return;
        }
        $callback();
    }
}

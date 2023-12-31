<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Service\Services;

use ArrayAccess\WP\Libraries\Core\Service\Abstracts\AbstractService;
use ArrayAccess\WP\Libraries\Core\Service\Interfaces\InitServiceInterface;
use ArrayAccess\WP\Libraries\Core\Service\Services;
use ArrayAccess\WP\Libraries\Core\Util\HighlightJS;
use function add_action;
use function did_action;
use function doing_action;
use function is_array;
use function is_bool;
use function is_string;
use function remove_action;
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
final class DefaultAssets extends AbstractService implements InitServiceInterface
{
    /**
     * @var string The service name.
     */
    protected string $serviceName = 'defaultAssets';

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
     * @var bool Whether the service is initialized.
     */
    private bool $init = false;

    /**
     * @inheritdoc
     */
    protected function onConstruct(): void
    {
        $this->description = __(
            'Service that help to register default assets.',
            'arrayaccess'
        );
        self::$instance ??= $this;
    }

    /**
     * @return bool Whether the instance is set.
     */
    public static function hasInstance(): bool
    {
        return self::$instance !== null;
    }

    /**
     * @return DefaultAssets The instance.
     */
    public static function getInstance(): DefaultAssets
    {
        return self::$instance ??= new self(new Services());
    }

    /**
     * Enqueue asset.
     *
     * @param string $handle
     * @param string|null $type
     * @return self
     */
    public static function enqueue(string $handle, ?string $type = null): self
    {
        return self::getInstance()->enqueueAsset($handle, $type);
    }

    /**
     * Register asset.
     *
     * @param string $handle
     * @param array $asset
     * @param string $type
     * @return bool Whether the asset is registered.
     */
    public static function register(string $handle, array $asset, string $type): bool
    {
        return self::getInstance()->registerAsset($handle, $asset, $type);
    }

    /**
     * @inheritdoc
     */
    public function hasInit(): bool
    {
        return $this->init;
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
                remove_action('init', $callback, 99999);
                $this->registerAdminAssets();
            };
            add_action('init', $callback, 99999);
        }
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
        $callback = function () use (&$callback) {
            remove_action('init', $callback);
            $this->registerAssets();
        };
        if ($this->services->isDoingWrongScripts()) {
            add_action('init', $callback);
            return;
        }
        $callback();
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
     * @param string $handle
     * @param string $type
     * @return bool
     */
    public function isRegistered(string $handle, string $type) : bool
    {
        $type = strtolower(trim($type));
        if (!isset($this->assets[$type], $this->assets[$type][$handle])) {
            // also use wordpress core
            return $type === 'js'
                ? wp_script_is($handle, 'registered')
                : ($type === 'css' && wp_style_is($handle, 'registered'));
        }

        return true;
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
            $asset['src'] = $this->services->replaceURL($asset['src']);
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
        $asset['ver'] = $asset['ver']??($asset['version']??null);
        $asset['ver'] = !is_string($asset['ver']) || trim($asset['ver']) === '' ? false : $asset['ver'];
        $asset['type'] = $asset['type']??null;
        $asset['attributes'] = $asset['attributes']??[];
        if (!is_array($asset['attributes'])) {
            $asset['attributes'] = [];
        }
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
                    $attributes = $asset['attributes'];
                    $attributes = is_array($attributes) ? $attributes : [];
                    if (!empty($asset['in_footer'])) {
                        $attributes['in_footer'] = true;
                    }
                    wp_register_script(
                        $handle,
                        $asset['src'],
                        $asset['deps'],
                        $asset['ver'],
                        $attributes
                    );
                    if (($asset['type']??null) === 'module') {
                        wp_script_add_data($handle, 'type', 'module');
                    }
                }

                $this->registeredAssetsHandle[$type][$handle] = true;
            }
        }
    }

    /**
     * Enqueue asset.
     *
     * @param string $handle
     * @param ?string $type null to enqueue both css and js, css to enqueue only css, js to enqueue only js.
     * @return $this
     */
    public function enqueueAsset(string $handle, ?string $type = null): self
    {
        if (trim($handle) === '') {
            return $this;
        }

        if ($type !== null) {
            $type = strtolower(trim($type));
            // only accept css and js
            if (!isset(self::ASSETS[$type])) {
                return $this;
            }
        }

        $this->registerAssets();
        $action = 'init';
        $callback = function () use ($action, &$callback, $handle, $type) {
            remove_action($action, $callback, 99999);
            if ($type && !isset($this->assets[$type][$handle])) {
                // register global assets if needed
                if ($type === 'js'
                    && wp_script_is($handle, 'registered')
                    && !wp_script_is($handle)
                ) {
                    wp_enqueue_script($handle);
                }
                if ($type === 'css'
                    && wp_style_is($handle, 'registered')
                    && !wp_style_is($handle)
                ) {
                    wp_enqueue_style($handle);
                }
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
            if (($type === 'js' || $type === null) && !isset($this->assets['js'][$handle])) {
                if (wp_script_is($handle, 'registered')
                    && !wp_script_is($handle)
                ) {
                    wp_enqueue_script($handle);
                }
            }
            if (($type === 'css' || $type === null) && !isset($this->assets['css'][$handle])) {
                if (wp_style_is($handle, 'registered')
                    && !wp_style_is($handle)
                ) {
                    wp_enqueue_style($handle);
                }
            }
        };

        // admin assets should be enqueued in admin_enqueue_scripts
        if ($handle === 'arrayaccess-admin') {
            $action = 'admin_enqueue_scripts';
        }
        // common assets should be enqueued in wp_enqueue_scripts
        if ($handle === 'arrayaccess-common') {
            $action = 'wp_enqueue_scripts';
        }
        try {
            // check if scripts registration is doing wrong.
            if ($this->services->isDoingWrongScripts()
                || $action !== 'init' && !did_action($action)
            ) {
                add_action($action, $callback, 99999);
                return $this;
            }
            $callback();
        } finally {
            // enqueue common assets if needed
            if ($handle !== 'arrayaccess-common'
                && ($type === null || $type === 'js')
                && isset(self::ASSETS['js'][$handle])
            ) {
                $this->enqueueAsset('arrayaccess-common');
            }
        }
        return $this;
    }

    /**
     * Default assets.
     * Move to bottom for readability.
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
            'arrayaccess-editor' => [
                'src' => '{{dist_url}}/vendor/highlightjs/highlight.bundle.min.css',
                'deps' => [
                    'arrayaccess-common'
                ],
                'ver' => HighlightJS::VERSION,
                'media' => 'all',
            ],
            'selectize' => [
                'src' => '{{dist_url}}/vendor/selectize/selectize.default.min.css',
                'deps' => [
                    'arrayaccess-common',
                ],
                'ver' => '0.15.2',
                'media' => 'all',
            ],
            'flatpickr-bundle' => [
                'src' => '{{dist_url}}/vendor/flatpickr/flatpickr.bundle.min.css',
                'deps' => [
                    'arrayaccess-common',
                ],
                'ver' => '4.6.13',
                'media' => 'all',
            ]
        ],
        'js' => [
            'arrayaccess-common' => [
                'src' => '{{dist_url}}/js/common.min.js',
                'deps' => [
                    'jquery',
                ],
                'ver' => '1.0.0',
                'in_footer' => true,
                'attributes' => [
                    'strategy' => 'defer',
                ],
            ],
            'arrayaccess-admin' => [
                'src' => '{{dist_url}}/js/admin.min.js',
                'deps' => [
                    'arrayaccess-common',
                    'wp-util',
                ],
                'ver' => '1.0.0',
                'in_footer' => true,
                'attributes' => [
                    'strategy' => 'defer',
                ],
            ],
            'arrayaccess-editor' => [
                'src' => '{{dist_url}}/js/editor.bundle.min.js',
                'deps' => [],
                'ver' => '1.0.0',
                'in_footer' => true,
                'attributes' => [
                    'strategy' => 'defer',
                ],
            ],
            'selectize' => [
                'src' => '{{dist_url}}/vendor/selectize/selectize.min.js',
                'deps' => [],
                'ver' => '0.15.2',
                'in_footer' => true,
                'attributes' => [
                    'strategy' => 'async',
                ],
            ],
            'flatpickr-bundle' => [
                'src' => '{{dist_url}}/vendor/flatpickr/flatpickr.bundle.min.js',
                'deps' => [],
                'ver' => '4.6.13',
                'in_footer' => true,
                'attributes' => [
                    'strategy' => 'async',
                ],
            ],
        ],
    ];
}

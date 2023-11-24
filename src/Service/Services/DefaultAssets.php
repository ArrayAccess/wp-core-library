<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Service\Services;

use ArrayAccess\WP\Libraries\Core\Service\Abstracts\AbstractService;
use ArrayAccess\WP\Libraries\Core\Util\Filter;
use function add_action;
use function did_action;
use function dirname;
use function is_array;
use function is_string;
use function remove_action;
use function site_url;
use function str_starts_with;
use function strtolower;
use function trim;
use function wp_enqueue_script;
use function wp_enqueue_style;
use function wp_register_script;
use function wp_register_style;
use function wp_script_is;
use function wp_style_is;

/**
 * Service that help to register default assets.
 */
class DefaultAssets extends AbstractService
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
     * Default assets.
     */
    public const ASSETS = [
        'css' => [
            'arrayaccess-admin-css' => [
                'src' => '/css/admin.css',
                'deps' => [],
                'ver' => '1.0.0',
                'media' => 'all',
            ],
        ],
        'js' => [
            'arrayaccess-admin-js' => [
                'src' => '/js/admin.js',
                'deps' => [
                    'jquery',
                    'wp-util',
                ],
                'ver' => '1.0.0',
                'in_footer' => true,
            ],
        ],
    ];

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

        $this->distPath = Filter::pathURL(
            dirname(__DIR__, 3) . '/dist'
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
        $this->init = true;
        $this->registerDefaultAssets();
        $this->registerAdminAssets();
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
            $this->enqueueAsset('arrayaccess-admin-css');
            $this->enqueueAsset('arrayaccess-admin-js');
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
        // check if asset src is absolute url
        if (str_starts_with($asset['src'], '/')) {
            $asset['src'] = $this->getDistURL() . $asset['src'];
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
                if (isset($this->registeredAssetsHandle[$handle])) {
                    continue;
                }
                if ($type === 'css') {
                    wp_register_style($handle, $asset['src'], $asset['deps'], $asset['ver'], $asset['media']);
                } else {
                    wp_register_script($handle, $asset['src'], $asset['deps'], $asset['ver'], $asset['in_footer']);
                }
                $this->registeredAssetsHandle[$handle] = true;
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

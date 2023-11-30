<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Service\Traits;

use ArrayAccess\WP\Libraries\Core\Util\Filter;
use function call_user_func;
use function dirname;
use function explode;
use function get_stylesheet_directory_uri;
use function get_template_directory_uri;
use function in_array;
use function is_callable;
use function is_numeric;
use function plugins_url;
use function preg_match;
use function preg_replace_callback;
use function strtolower;
use function trim;

trait URLReplacerTrait
{
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
     * @var string The dist path.
     */
    protected string $distPath;

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
     * @param string $string
     * @return string
     */
    public function replaceURL(string $string): string
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
     * @return string The dist path.
     */
    public function getDistPath(): string
    {
        return $this->distPath ??= Filter::pathURL(
            dirname(__DIR__, 3) . '/dist'
        );
    }

    /**
     * @return string The dist url.
     */
    public function getDistURL(): string
    {
        return site_url($this->getDistPath());
    }

    /**
     * Check if scripts registration is doing wrong.
     * This to prevent error on register scripts.
     *
     * @return bool
     */
    public function isDoingWrongScripts() : bool
    {
        return !(
            did_action('init')
            || did_action('wp_enqueue_scripts')
            || did_action('admin_enqueue_scripts')
            || did_action('login_enqueue_scripts')
        );
    }
}

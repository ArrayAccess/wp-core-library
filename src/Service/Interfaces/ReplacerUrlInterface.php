<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Service\Interfaces;

interface ReplacerUrlInterface
{
    /**
     * Add replacer.
     *
     * @param string $key
     * @param callable $callback
     * @return bool Whether the replacer is added.
     */
    public function addReplacer(string $key, callable $callback): bool;

    /**
     * @param string $string
     * @return string
     */
    public function replaceURL(string $string): string;

    /**
     * @return string The dist path.
     */
    public function getDistPath(): string;

    /**
     * @return string The dist url.
     */
    public function getDistURL(): string;

    /**
     * Check if scripts registration is doing wrong.
     * This to prevent error on register scripts.
     *
     * @return bool
     */
    public function isDoingWrongScripts() : bool;
}

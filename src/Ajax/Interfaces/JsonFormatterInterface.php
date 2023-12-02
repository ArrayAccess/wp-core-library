<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Ajax\Interfaces;

use Throwable;

interface JsonFormatterInterface
{
    /**
     * Format error response
     *
     * @param JsonResponseInterface|Throwable $response
     * @return array
     */
    public function formatError(JsonResponseInterface|Throwable $response) : array;

    /**
     * Format success response
     *
     * @param JsonResponseInterface $response
     * @return array
     */
    public function formatSuccess(JsonResponseInterface $response) : array;

    /**
     * Format response
     *
     * @param JsonResponseInterface|Throwable $response
     * @return array
     */
    public function format(JsonResponseInterface|Throwable $response) : array;
}

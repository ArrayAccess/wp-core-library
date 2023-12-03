<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Ajax;

use ArrayAccess\WP\Libraries\Core\Ajax\Interfaces\JsonFormatterInterface;
use ArrayAccess\WP\Libraries\Core\Ajax\Interfaces\JsonResponseInterface;
use ArrayAccess\WP\Libraries\Core\Util\HttpStatusCode;
use Stringable;
use Throwable;
use function is_scalar;

class JsonFormatter implements JsonFormatterInterface
{
    /**
     * @param JsonResponseInterface $response
     * @return array{
     *     data: array{
     *          status: int,
     *          message: string,
     *          data: mixed
     *     }
     * }
     */
    public function formatSuccess(JsonResponseInterface $response): array
    {
        return [
            'data' => [
                'status' => $response->getHandler()->getHeader()->getStatusCode(),
                'message' => $response->getHandler()->getHeader()->getStatusMessage(),
                'data' => $response->getData(),
            ]
        ];
    }

    /**
     * Format error response
     *
     * @param JsonResponseInterface|Throwable $response
     * @return array{
     *     error: array{
     *          status: int,
     *          message: string,
     *          code: int,
     *          data: mixed
     *     }
     * }
     */
    public function formatError(JsonResponseInterface|Throwable $response): array
    {
        if ($response instanceof JsonResponseInterface) {
            // change the status code to expectation failed if not 4xx or 5xx
            $code = $response->getHandler()->getHeader()->getStatusCode();
            $errorCode = $code;
            if ($code < 400 || $code >= 600) {
                $code = HttpStatusCode::EXPECTATION_FAILED;
            }
            $message = $response->getData();
        } else {
            $code = HttpStatusCode::INTERNAL_SERVER_ERROR;
            $errorCode   = $response->getCode();
            $message = $response->getMessage();
        }
        $httpMessage = HttpStatusCode::getMessage($code);
        return [
            'error' => [
                'status' => $code,
                'message' => $httpMessage,
                'code' => $errorCode,
                'data' => is_scalar($message) || $message instanceof Stringable ? (string)$message : $message
            ]
        ];
    }

    /**
     * @param JsonResponseInterface|Throwable $response
     * @return array{
     *     data: array{
     *          status: int,
     *          message: string,
     *          data: mixed
     *     }|array{
     *          error: array{
     *              status: int,
     *              message: string,
     *              code: int,
     *              data: mixed
     *          }
     *     }
     * }
     */
    public function format(JsonResponseInterface|Throwable $response): array
    {
        if ($response instanceof JsonResponseInterface
            && $response->getHandler()->getHeader()->getStatusCode() < 400
        ) {
            return $this->formatSuccess($response);
        }
        return $this->formatError($response);
    }
}

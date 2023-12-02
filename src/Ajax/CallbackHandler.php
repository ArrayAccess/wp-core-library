<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Ajax;

use ArrayAccess\WP\Libraries\Core\Ajax\Abstracts\AbstractHandler;
use ArrayAccess\WP\Libraries\Core\Ajax\Interfaces\JsonResponseInterface;
use ArrayAccess\WP\Libraries\Core\Ajax\Interfaces\JsonSenderInterface;
use ArrayAccess\WP\Libraries\Core\Util\Filter;
use Throwable;
use function call_user_func;

class CallbackHandler extends AbstractHandler
{
    /**
     * @var callable The callback to be called.
     */
    private $callback;

    /**
     * CallbackHandler constructor.
     *
     * @param string $action
     * @param callable $callback
     * @param array|string|null $acceptedMethods
     * @param bool $requireLoggedIn
     * @param int $priority
     */
    public function __construct(
        string $action,
        callable $callback,
        array|string|null $acceptedMethods = null,
        bool $requireLoggedIn = false,
        int $priority = 10
    ) {
        $this->callback = $callback;
        $this->action = $action;
        $this->acceptedMethods = Filter::filterMethods($acceptedMethods);
        $this->requireLoggedIn = $requireLoggedIn;
        $this->priority = $priority;
    }

    /**
     * @param string $action
     * @param callable $callback
     * @param array|string|null $acceptedMethods
     * @param bool $requireLoggedIn
     * @param int $priority
     * @return static
     */
    public static function create(
        string $action,
        callable $callback,
        array|string|null $acceptedMethods = null,
        bool $requireLoggedIn = false,
        int $priority = 10
    ): static {
        return new static($action, $callback, $acceptedMethods, $requireLoggedIn, $priority);
    }

    /**
     * @inheritdoc
     */
    public function handle(JsonSenderInterface $jsonSender): JsonResponse
    {
        try {
            $data = call_user_func($this->callback, $this, $jsonSender);
            return $data instanceof JsonResponseInterface ? $data : new JsonResponse($this, $data);
        } catch (Throwable $e) {
            return new JsonResponse($this, $e);
        }
    }
}

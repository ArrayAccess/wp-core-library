<?php
declare(strict_types=1);

namespace ArrayAccess\WP\Libraries\Core\Ajax;

use ArrayAccess\WP\Libraries\Core\Ajax\Interfaces\HandlerInterface;
use ArrayAccess\WP\Libraries\Core\Ajax\Interfaces\JsonSenderInterface;
use ArrayAccess\WP\Libraries\Core\Service\Interfaces\HookInterface;
use ArrayAccess\WP\Libraries\Core\Util\Filter;
use ArrayAccess\WP\Libraries\Core\Util\HttpRequest\Request;
use ArrayAccess\WP\Libraries\Core\Util\HttpRequest\Server;
use Throwable;
use function array_values;
use function did_action;
use function doing_action;
use function has_action;
use function in_array;
use function is_user_logged_in;
use function ksort;
use function wp_die;
use function wp_doing_ajax;

/**
 * Admin Ajax Helper
 */
class Admin
{
    /**
     * @var JsonSenderInterface Json sender
     */
    private JsonSenderInterface $jsonSender;

    /**
     * @var array<string, array<int, HandlerInterface[]>> The handlers.
     */
    private array $handlers = [];

    /**
     * @var bool Whether the ajax request has been processed.
     */
    private bool $processed = false;

    /**
     * @var bool Whether the user is logged in.
     */
    private bool $userLoggedIn = false;

    /**
     * @var string The action hook.
     */
    private string $ajaxType = '';

    /**
     * @var string The action.
     */
    private string $action = '';

    /**
     * @param ?HookInterface $hook
     * @param ?JsonSenderInterface $jsonSender
     */
    public function __construct(
        ?HookInterface $hook = null,
        ?JsonSenderInterface $jsonSender = null
    ) {
        $jsonSender ??= new JsonSender(new JsonFormatter(), $hook);
        if ($hook && !$jsonSender->getHook()) {
            $jsonSender->setHook($hook);
        }
        $this->setJsonSender($jsonSender);
        $this->registerAjax();
    }

    private function registerAjax(): void
    {
        $callback = function () use (&$callback) {
            remove_action('wp_loaded', $callback, 99);
            $this->doRegister();
        };
        if (did_action('wp_loaded') || doing_action('wp_loaded')) {
            $callback();
        } else {
            add_action('wp_loaded', $callback, 99);
        }
    }

    /**
     * @return bool Whether the ajax request has been processed.
     */
    public function isProcessed(): bool
    {
        return $this->processed;
    }

    /**
     * @return void Register ajax
     */
    private function doRegister(): void
    {
        if ($this->isProcessed()) {
            return;
        }

        $this->userLoggedIn = is_user_logged_in();
        if (!wp_doing_ajax()) {
            return;
        }
        $action = Request::string('action')?:'';
        if ($action === '') {
            return;
        }

        $callback = $this->userLoggedIn ? 'wp_ajax_' : 'wp_ajax_nopriv_';
        $this->action = $action;
        $this->ajaxType = $callback;
        $hookAction = $this->ajaxType . $this->action;
        // do not process if action is already registered
        if (has_action($hookAction)) {
            return;
        }
        $callback = function () use ($hookAction, &$callback) {
            remove_action($hookAction, $callback, 1);
            if ($this->isProcessed()) {
                return;
            }
            $this->processed = false;
            // do not process if action is already registered
            if (has_action($hookAction)) {
                return;
            }
            $this->doHandleAjax();
        };
        add_action($hookAction, $callback, 1);
    }

    /**
     * Handle ajax
     *
     * @return void Handle ajax
     */
    private function doHandleAjax(): void
    {
        $this->processed = false;
        // do not process
        if (!$this->ajaxType || !$this->action) {
            return;
        }
        $method = Server::method();
        foreach (($this->getHandlers()[$this->action]??[]) as $handlers) {
            foreach ($handlers as $handler) {
                if ($handler->requireLoggedIn() && !$this->userLoggedIn) {
                    continue;
                }
                $acceptedMethods = Filter::filterMethods($handler->getAcceptedMethods());
                if (!empty($acceptedMethods) && !in_array($method, $acceptedMethods, true)) {
                    continue;
                }
                $this->processed = true;
                try {
                    $handle = $handler->handle($this->getJsonSender());
                } catch (Throwable $e) {
                    $handle = new JsonResponse($handler, $e);
                }
                break;
            }
        }
        if (!isset($handle)) {
            return;
        }
        $this->getJsonSender()->send($handle);
        /** @noinspection PhpUnreachableStatementInspection */
        wp_die(); // make sure stopped
    }

    /**
     * Get json sender
     *
     * @return JsonSenderInterface
     */
    public function getJsonSender(): JsonSenderInterface
    {
        return $this->jsonSender;
    }

    /**
     * Set json sender
     *
     * @param JsonSenderInterface $jsonSender
     * @return $this
     */
    public function setJsonSender(JsonSenderInterface $jsonSender): static
    {
        $this->jsonSender = $jsonSender;
        return $this;
    }

    /**
     * Add handler
     * @param HandlerInterface $handler
     * @return $this
     */
    public function add(HandlerInterface $handler): Admin
    {
        $action = $handler->getAction();
        $this->handlers[$action][$handler->getPriority()][] = $handler;
        ksort($this->handlers[$action]);
        return $this;
    }

    /**
     * @param HandlerInterface $handler
     * @return $this
     */
    public function remove(HandlerInterface $handler): Admin
    {
        $action = $handler->getAction();
        $priority = $handler->getPriority();
        if (!isset($this->handlers[$action][$priority])) {
            return $this;
        }
        if (empty($this->handlers[$action][$priority])) {
            unset($this->handlers[$action][$priority]);
            return $this;
        }
        foreach ($this->handlers[$action][$priority] as $k => $v) {
            if ($v === $handler) {
                unset($this->handlers[$action][$priority][$k]);
                break;
            }
        }
        if (empty($this->handlers[$action][$priority])) {
            unset($this->handlers[$action][$priority]);
            return $this;
        }
        $this->handlers[$action][$priority] = array_values($this->handlers[$action][$priority]);
        return $this;
    }

    /**
     * Get all handlers
     *
     * @return array<string, array<int, HandlerInterface[]>>
     */
    public function getHandlers(): array
    {
        return $this->handlers;
    }
}

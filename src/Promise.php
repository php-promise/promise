<?php

namespace Promise;

use Promise\Context\Context;
use Promise\Exceptions\PromiseException;
use Promise\Processors\Rejecter;
use Promise\Processors\Resolver;
use Promise\Services\SafetyLoader;

class Promise
{
    const PENDING   = Task::PENDING;
    const FULFILLED = Task::FULFILLED;
    const REJECTED  = Task::REJECTED;
    const ERROR     = Task::ERROR;

    /**
     * @var null|Context
     */
    private $context = null;

    /**
     * Promise constructor.
     * @param callable $callee
     * @param mixed ...$parameters
     * @throws PromiseException
     */
    public function __construct(callable $callee, ...$parameters)
    {
        if (!class_exists('Thread')) {
            throw new PromiseException(
                'Promise needs Thread class. Did you forgot to install pthreads extensions?'
            );
        }

        $this->context = new Context($callee, ...$parameters);
    }

    /**
     * Wait processing Promises when you called.
     *
     * @param array|Promise ...$promises Set Promises
     * @return static
     * @throws Exceptions\PromiseException
     */
    public static function all(...$promises)
    {
        return Context::all(...$promises);
    }

    /**
     * @param array|Promise ...$promises
     * @return static
     * @throws Exceptions\PromiseException
     */
    public static function race(...$promises)
    {
        return Context::race(...$promises);
    }

    /**
     * The method is called by Promise when called resolve function in $callee.
     *
     * @param callable $onFulfilled The defined closure to call when called resolve function in $callee.
     * @param callable|null $rejected The defined closure to call when called reject function in $callee.
     * @return Promise
     * @throws PromiseException
     */
    public function then(callable $onFulfilled, callable $rejected = null): self
    {
        $this->context->then($onFulfilled, $rejected);
        return $this;
    }

    /**
     * The method is called by Promise when called reject function in $callee.
     *
     * @param callable $rejected
     * @return Promise
     * @throws PromiseException
     */
    public function catch(callable $rejected): self
    {
        $this->context->catch($rejected);
        return $this;
    }

    /**
     * The method is called by Promise when called resolve/reject function in $callee.
     *
     * @param callable $onFinally
     * @return Promise
     * @throws PromiseException
     */
    public function finally(callable $onFinally): self
    {
        $this->context->finally($onFinally);
        return $this;
    }

    /**
     * Get a Thread context for a Promise when you defined.
     *
     * @return null|Context
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * The method use safety mode.
     * pthreads has problems that cannot serialize illegal classes and closures.
     * The method solves its problems.
     */
    public static function enableSafety(): void
    {
        SafetyLoader::setEnabled(true);
    }

    /**
     * The method do not use safety mode.
     * pthreads has problems that cannot serialize illegal classes and closures.
     * The method solves its problems.
     */
    public static function disableSafety(): void
    {
        SafetyLoader::setEnabled(false);
    }
}

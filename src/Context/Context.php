<?php

namespace Promise\Context;

use Promise\Collection\Collection;
use Promise\Exceptions\PromiseException;
use Promise\Processors\Processor;
use Promise\Processors\Resolver;
use Promise\Processors\Rejecter;
use Promise\Promise;
use Promise\Services\SafetyManager;

/**
 * @property array $parameters The property store parameters dynamically when passed from the main thread context.
 * @property mixed $result The property store values by Promise processor dynamically.
 */
class Context extends \Thread
{

    /**
     * @var string
     */
    private $status = Promise::PENDING;

    /**
     * @var callable|null
     */
    private $callee = null;

    /**
     * @var null|Collection
     */
    private $collection = null;

    /**
     * @var null|Resolver
     */
    private $resolver = null;

    /**
     * @var null|Rejecter
     */
    private $rejecter = null;

    /**
     * Context constructor.
     * @param callable $callee
     * @param mixed ...$parameters
     * @throws PromiseException
     */
    public function __construct(callable $callee, ...$parameters)
    {
        $this->callee = $callee;
        $this->collection = new Collection();
        $this->resolver = new Resolver($this);
        $this->rejecter = new Rejecter($this);

        // dynamically creating
        $this->parameters = $parameters;
        $this->start();
        SafetyManager::register($this);
    }

    /**
     *
     */
    public function run(): void
    {
        ($this->callee)(
            $this->resolver,
            $this->rejecter,
            ...$this->parameters
        );
    }

    /**
     * @param mixed ...$promises
     * @return Promise
     * @throws PromiseException
     */
    public static function all(...$promises): Promise
    {
        if (!empty($promises[0]) && is_array($promises[0])) {
            $promises = $promises[0];
        }

        // validate values
        foreach ($promises as $promise) {
            if (!($promise instanceof Promise)) {
                throw new PromiseException('Passed parameters are not instantiate by Promise');
            }
        }

        $resultStatus = Promise::FULFILLED;
        foreach ($promises as $promise) {
            static::await($promise);
            if ($promise->getContext()->getStatus() === Promise::REJECTED) {
                $resultStatus = Promise::REJECTED;
            }
        }

        return new Promise(function (Resolver $resolve, Rejecter $reject) use ($resultStatus) {
            if ($resultStatus === Promise::FULFILLED) {
                return $resolve();
            }
            return $reject();
        });
    }


    /**
     * @param mixed ...$promises
     * @return Promise
     * @throws PromiseException
     */
    public static function race(...$promises): Promise
    {
        if (!empty($promises[0])) {
            $promises = $promises[0];
        }

        // validate values
        foreach ($promises as $promise) {
            if (!($promise instanceof Promise)) {
                throw new PromiseException('Passed parameters are not instantiate by Promise');
            }
        }

        $resultStatus = Promise::PENDING;
        while ($resultStatus === Promise::PENDING) {
            // Stopping
            foreach ($promises as $promise) {
                if ($promise->getContext()->getStatus() === Promise::FULFILLED) {
                    $resultStatus = Promise::FULFILLED;
                    break;
                }
                if ($promise->getContext()->getStatus() === Promise::REJECTED) {
                    $resultStatus = Promise::REJECTED;
                    break;
                }
            }
            usleep(100);
        }

        foreach ($promises as $promise) {
            static::await($promise);
        }

        return new Promise(function (Resolver $resolve, Rejecter $reject) use ($resultStatus) {
            if ($resultStatus === Promise::FULFILLED) {
                return $resolve();
            }
            return $reject();
        });
    }

    /**
     * @param callable $onFulfilled
     * @param callable|null $rejected
     * @return Context
     * @throws PromiseException
     */
    public function then(callable $onFulfilled, callable $rejected = null): self
    {
        $this->collection->add($this->listener($onFulfilled, $rejected));
        return $this;
    }

    /**
     * @param callable $rejected
     * @return Context
     * @throws PromiseException
     */
    public function catch(callable $rejected): self
    {
        $this->collection->add($this->listener(null, $rejected));
        return $this;
    }

    /**
     * @param callable $onFinally
     * @return Context
     * @throws PromiseException
     */
    public function finally(callable $onFinally): self
    {
        $this->collection->add($this->listener($onFinally, $onFinally));
        return $this;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param $status
     * @return $this
     */
    public function setStatus($status): self
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return null|Collection
     */
    public function getCollection(): Collection
    {
        return $this->collection;
    }

    /**
     * @param null $onFulfilled
     * @param null $rejected
     * @return Processor
     * @throws PromiseException
     */
    private function listener($onFulfilled = null, $rejected = null): Processor
    {
        return new Processor($this, function () use (&$onFulfilled, &$rejected) {
            $this->collection->synchronized(function (
                Context $context,
                $onFulfilled,
                $rejected
            ) {
                if ($context->collection->getStatus() === Promise::PENDING) {
                    $context->collection->wait();
                }
                if (is_callable($onFulfilled) &&
                    $context->collection->getStatus() === Promise::FULFILLED
                ) {
                    $this->result = $onFulfilled(...$context->parameters);
                }
                if (is_callable($rejected) &&
                    $context->collection->getStatus() === Promise::REJECTED
                ) {
                    $this->result = $rejected(...$context->parameters);
                }
            }, $this, $onFulfilled, $rejected);
        });
    }

    /**
     * Get dynamically stored values by Processor
     *
     * @return mixed|null
     */
    public function getResult()
    {
        return $this->result ?? null;
    }


    /**
     * @param Promise $promise
     * @return Promise
     */
    public static function await(Promise $promise)
    {
        if ($promise->getContext()->isStarted() &&
            !$promise->getContext()->isJoined()
        ) {
            $promise->getContext()->join();
        }
        return $promise;
    }

    /**
     * @param callable $callee
     * @return Promise
     * @throws PromiseException
     */
    public static function async(callable $callee)
    {
        return new Promise($callee);
    }
}

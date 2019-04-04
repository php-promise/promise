<?php

namespace Promise\Context;

use Promise\Collection\Collection;
use Promise\Exceptions\PromiseException;
use Promise\Processors\Processor;
use Promise\Processors\Resolver;
use Promise\Processors\Rejecter;
use Promise\Promise;
use Promise\Services\SafetyLoader;
use Promise\Services\SafetyManager;
use Promise\Task;

/**
 * @property array $additionalParameters The property store temporarily parameters for passing it to rejecter and resolver.
 * @property array $parameters The property store parameters dynamically when passed from the main thread context.
 * @property mixed $result The property store values by Promise processor dynamically.
 * @property mixed $dependencies The property inheritance parent classes and functions with SafetyLoader.
 */
class Context extends Task
{

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
        $this->additionalParameters = serialize([]);
        $this->parameters = $parameters;
        $this->result = serialize([]);

        if (SafetyLoader::isEnabled()) {
            $this->dependencies = [
                SafetyLoader::getLoadedComposer(),
                SafetyLoader::getLoadedFiles(),

                // loaded safety loader file.
                __DIR__ . '/../Services/SafetyLoader.php',
            ];
        }
        $this->start(
            SafetyLoader::isEnabled()
                ? SafetyLoader::OPTIONS
                : PTHREADS_INHERIT_ALL
        );
        SafetyManager::register($this);
    }


    public function run(): void
    {
        if (property_exists($this, 'dependencies')) {
            require_once $this->dependencies[2];
            SafetyLoader::loadDependencies($this, $this->dependencies);
        }

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
        if (!empty($promises[0]) && is_array($promises[0])) {
            $promises = $promises[0];
        }

        // validate values
        foreach ($promises as $promise) {
            if (!($promise instanceof Promise)) {
                throw new PromiseException('Passed parameters are not instantiate by Promise');
            }
        }

        $resultStatus = Promise::PENDING;
        do {
            // Stopping
            foreach ($promises as $promise) {
                switch ($resultStatus = $promise->getContext()->getStatus()) {
                    case Promise::FULFILLED:
                    case Promise::REJECTED:
                        break 2;
                }
            }
            usleep(20);
        } while ($resultStatus === Promise::PENDING);

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

                $additionalParameters = unserialize($this->additionalParameters);
                if (is_callable($onFulfilled) &&
                    $context->collection->getStatus() === Promise::FULFILLED
                ) {
                    $this->result = serialize($onFulfilled(...$additionalParameters, ...$context->parameters));
                }
                if (is_callable($rejected) &&
                    $context->collection->getStatus() === Promise::REJECTED
                ) {
                    $this->result = serialize($rejected(...$additionalParameters, ...$context->parameters));
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
        return unserialize($this->result ?? null);
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

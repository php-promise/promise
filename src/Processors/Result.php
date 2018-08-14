<?php

namespace Promise\Processors;

use Promise\Collection\Collection;
use Promise\Context\Context;
use Promise\Exceptions\PromiseException;
use Promise\Promise;
use Promise\Services\SafetyLoader;
use Promise\Services\SafetyManager;
use Promise\Task;

/**
 * @property mixed $dependencies The property inheritance parent classes and functions with SafetyLoader.
 */
class Result extends Task
{

    /**
     * @var null|Context
     */
    private $context = null;

    /**
     * Result constructor.
     * @param Context $context
     * @throws PromiseException
     */
    public function __construct(Context $context)
    {
        $this->context = $context;
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

    /**
     * @param $status
     * @param mixed ...$parameters
     * @throws PromiseException
     */
    protected function invoker($status, ...$parameters)
    {
        if (property_exists($this, 'dependencies')) {
            require_once $this->dependencies[2];
            SafetyLoader::loadDependencies($this, $this->dependencies);
        }
        $this->context
            ->setStatus($status)
            ->getCollection()
            ->synchronized(function (Collection $collection) use ($status) {
                $collection
                    ->setStatus($status)
                    ->notify();
            }, $this->context->getCollection());
    }

    /**
     * @return null|Context
     */
    public function getContext()
    {
        return $this->context;
    }
}

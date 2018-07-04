<?php

namespace Promise\Processors;

use Promise\Collection\Collection;
use Promise\Context\Context;
use Promise\Exceptions\PromiseException;
use Promise\Services\SafetyManager;

class Result extends \Thread
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
        $this->start();
        SafetyManager::register($this);
    }


    /**
     * @param $status
     * @param mixed ...$parameters
     */
    protected function invoker($status, ...$parameters)
    {
        $this->context
            ->setStatus($status)
            ->getCollection()
            ->synchronized(function (Collection $collection) use ($status) {
                $collection
                    ->setStatus($status)
                    ->notifyOne();
            }, $this->context->getCollection());
    }
}

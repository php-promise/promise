<?php

namespace Promise\Collection;

use Promise\Exceptions\PromiseException;
use Promise\Processors\Processor;
use Promise\Promise;
use Promise\Services\SafetyManager;

class Collection extends \Thread
{

    /**
     * @var string
     */
    public $status = Promise::PENDING;

    /**
     * @var array
     */
    private $processors = [];

    /**
     * Collection constructor.
     * @throws PromiseException
     */
    public function __construct()
    {
        SafetyManager::register($this);
    }

    /**
     * @param Processor $processor
     */
    public function add(Processor $processor)
    {
        $this->processors[] = $processor;
    }

    /**
     * @param string $status
     * @return $this
     * @throws PromiseException
     */
    public function setStatus(string $status)
    {
        if (!in_array($status, [Promise::PENDING, Promise::FULFILLED, Promise::REJECTED])) {
            throw new PromiseException('Unknown passed status value ' . $status);
        }
        $this->status = $status;
        return $this;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }
}

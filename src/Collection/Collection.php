<?php

namespace Promise\Collection;

use Promise\Exceptions\PromiseException;
use Promise\Processors\Processor;
use Promise\Promise;
use Promise\Services\SafetyManager;
use Promise\Task;

class Collection extends Task
{
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
}

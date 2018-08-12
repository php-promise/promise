<?php

namespace Promise\Processors;

use Promise\Context\Context;
use Promise\Services\SafetyManager;

class Processor extends \Thread
{
    /**
     * @var callable|null
     */
    private $callee = null;

    /**
     * @var null|Context
     */
    private $context = null;

    /**
     * @var null
     */
    private $safetyManager = null;

    /**
     * Processor constructor.
     * @param Context $context
     * @param callable $callee
     * @throws \Promise\Exceptions\PromiseException
     */
    public function __construct(Context $context, callable $callee)
    {
        $this->callee = $callee;
        $this->context = $context;
        $this->start();
        SafetyManager::register($this);
    }

    /**
     *
     */
    public function run()
    {
        \Closure::bind($this->callee, $this->context)($this);
    }

    /**
     * @return Context
     */
    public function getContext(): Context
    {
        return $this->context;
    }
}

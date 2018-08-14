<?php

namespace Promise\Processors;

use Promise\Context\Context;
use Promise\Services\SafetyLoader;
use Promise\Services\SafetyManager;
use Promise\Task;

/**
 * @property mixed $dependencies The property inheritance parent classes and functions with SafetyLoader.
 */
class Processor extends Task
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
     *
     */
    public function run()
    {
        if (property_exists($this, 'dependencies')) {
            require_once $this->dependencies[2];
            SafetyLoader::loadDependencies($this, $this->dependencies);
        }
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

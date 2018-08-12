<?php

namespace Promise\Services;

use Promise\Context\Context;
use Promise\Exceptions\PromiseException;
use Promise\Processors\Processor;
use Promise\Processors\Result;
use Promise\Promise;

class SafetyManager
{

    private static $registeredThreads = [];

    /**
     * @param $thread
     * @throws PromiseException
     */
    public static function register($thread)
    {
        static $registeredFatalErrorCatcher = false;

        if (is_array($thread)) {
            foreach ($thread as $child) {
                static::register($child);
            }
            return;
        }

        if ($thread instanceof Promise) {
            $thread = $thread->getContext();
        }

        if (!($thread instanceof \Thread)) {
            throw new PromiseException('Passed parameters are not instantiate by Thread.');
        }

        static::$registeredThreads[] = $thread;

        register_shutdown_function(function (\Thread $thread) {
            if ($thread->isStarted() &&
                !$thread->isJoined()
            ) {
                $thread->join();
            }
        }, $thread);
    }

    public static function getRegisteredThreads()
    {
        return static::$registeredThreads;
    }
}

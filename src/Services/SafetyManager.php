<?php

namespace Promise\Services;

use Promise\Exceptions\PromiseException;
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

        static::$registeredThreads[] = get_class($thread);

        register_shutdown_function(function (\Thread $thread) {
            if ($thread->isStarted() &&
                !$thread->isJoined()
            ) {
                $thread->join();
            }
        }, $thread);
    }

    public static function getRegistered()
    {
        return static::$registeredThreads;
    }
}

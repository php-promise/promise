<?php

namespace Promise\Processors;

use Promise\Collection\Collection;
use Promise\Promise;

class Rejecter extends Result
{
    /**
     * @param mixed ...$parameters
     */
    public function __invoke(...$parameters)
    {
        return $this->invoker(Promise::REJECTED, ...$parameters);
    }
}

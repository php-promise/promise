<?php

namespace Promise\Processors;

use Promise\Collection\Collection;
use Promise\Promise;

class Resolver extends Result
{

    /**
     * @param mixed ...$parameters
     */
    public function __invoke(...$parameters)
    {
        return $this->invoker(Promise::FULFILLED, ...$parameters);
    }
}

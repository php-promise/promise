<?php

namespace Promise;

use Promise\Exceptions\PromiseException;

class Task extends \Thread
{
    const PENDING   = 'pending';
    const FULFILLED = 'fulfilled';
    const REJECTED  = 'rejected';
    const ERROR     = 'error';

    /**
     * @var string
     */
    public $status = Task::PENDING;

    /**
     * @param string $status
     * @return $this
     * @throws PromiseException
     */
    public function setStatus(string $status)
    {
        if (!in_array($status, [Promise::PENDING, Promise::FULFILLED, Promise::REJECTED, Promise::ERROR])) {
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

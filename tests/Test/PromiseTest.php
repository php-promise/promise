<?php

namespace Promise\Test;

use PHPUnit\Framework\TestCase;
use Promise\Processors\Rejecter;
use Promise\Processors\Resolver;
use Promise\Promise;

class PromiseTest extends TestCase
{

    /**
     * @throws \Promise\Exceptions\PromiseException
     */
    public function testPromiseCallingThen()
    {
        $promise = (new Promise(function (Resolver $resolve, Rejecter $reject) {
            $resolve();
        }));

        Promise::all($promise);

        $this->assertEquals(Promise::FULFILLED, $promise->getContext()->getStatus());
    }

    /**
     * @throws \Promise\Exceptions\PromiseException
     */
    public function testPromiseCallingCatch()
    {
        $promise = (new Promise(function (Resolver $resolve, Rejecter $reject) {
            $reject();
        }));

        Promise::all($promise);

        $this->assertEquals(Promise::REJECTED, $promise->getContext()->getStatus());
    }

    /**promise/src/Services/SafetyManager.php
     * @throws \Promise\Exceptions\PromiseException
     */
    public function testMultiplePromiseCallingThenWithAll()
    {
        $promise = Promise::all([
            new Promise(function (Resolver $resolve, Rejecter $reject) { $resolve(); }),
            new Promise(function (Resolver $resolve, Rejecter $reject) { $resolve(); }),
            new Promise(function (Resolver $resolve, Rejecter $reject) { $resolve(); }),
        ]);

        Promise::all($promise);

        $this->assertTrue($promise->getContext()->getStatus() === Promise::FULFILLED);
    }

    /**
     * @throws \Promise\Exceptions\PromiseException
     */
    public function testMultiplePromiseCallingCatchWithAll()
    {
        $promise = Promise::all([
            new Promise(function (Resolver $resolve, Rejecter $reject) { $resolve(); }),
            new Promise(function (Resolver $resolve, Rejecter $reject) { $resolve(); }),
            new Promise(function (Resolver $resolve, Rejecter $reject) { $reject(); }),
        ]);

        Promise::all($promise);

        $this->assertEquals(Promise::REJECTED, $promise->getContext()->getStatus());
    }

    /**
     * @throws \Promise\Exceptions\PromiseException
     */
    public function testMultiplePromiseCallingThenWithRace()
    {
        $promise = Promise::race([
            new Promise(function (Resolver $resolve, Rejecter $reject) { $resolve(); }),
            new Promise(function (Resolver $resolve, Rejecter $reject) { $resolve(); }),
            new Promise(function (Resolver $resolve, Rejecter $reject) { $resolve(); }),
        ]);

        Promise::all($promise);

        $this->assertEquals(Promise::FULFILLED, $promise->getContext()->getStatus());
    }

    /**
     * @throws \Promise\Exceptions\PromiseException
     */
    public function testMultiplePromiseCallingCatchWithRace()
    {
        $promise = Promise::race([
            new Promise(function (Resolver $resolve, Rejecter $reject) { $reject(); }),
            new Promise(function (Resolver $resolve, Rejecter $reject) { $resolve(); }),
            new Promise(function (Resolver $resolve, Rejecter $reject) { $resolve(); }),
        ]);

        Promise::all($promise);

        $this->assertEquals(Promise::REJECTED, $promise->getContext()->getStatus());
    }

    /**
     * @throws \Promise\Exceptions\PromiseException
     */
    public function testPromiseGetThen()
    {
        $file = tempnam(sys_get_temp_dir(), 'PHP');
        $promise = (new Promise(function (Resolver $resolve, Rejecter $reject, $file) {
            $resolve($file);
        }, $file))->then(function ($file) {
            fwrite(fopen($file, 'w'), 'Called Then.');
        })->catch(function ($file) {
            fwrite(fopen($file, 'w'), 'Called Catch.');
        });

        Promise::all($promise);
        $this->assertEquals('Called Then.', fread(fopen($file, 'r'), 1024));
        @unlink($file);
    }

    /**
     * @throws \Promise\Exceptions\PromiseException
     */
    public function testPromiseGetCatch()
    {
        $file = tempnam(sys_get_temp_dir(), 'PHP');
        $promise = (new Promise(function (Resolver $resolve, Rejecter $reject, $file) {
            $reject($file);
        }, $file))->then(function ($file) {
            fwrite(fopen($file, 'w'), 'Called Then.');
        })->catch(function ($file) {
            fwrite(fopen($file, 'w'), 'Called Catch.');
        });

        Promise::all($promise);
        $this->assertEquals('Called Catch.', fread(fopen($file, 'r'), 1024));
        @unlink($file);
    }

    /**
     * @throws \Promise\Exceptions\PromiseException
     */
    public function testPromiseGetFinally()
    {
        $file = tempnam(sys_get_temp_dir(), 'PHP');
        $promise = (new Promise(function (Resolver $resolve, Rejecter $reject, $file) {
            $resolve($file);
        }, $file))->then(function ($file) {
        })->catch(function ($file) {
        })->finally(function ($file) {
            fwrite(fopen($file, 'w'), 'Called Finally.');
        });

        Promise::all($promise);
        $this->assertEquals('Called Finally.', fread(fopen($file, 'r'), 1024));
        @unlink($file);
    }

    /**
     * @throws \Promise\Exceptions\PromiseException
     */
    public function testGetResultWithThenFunction()
    {
        $promise = (new Promise(function (Resolver $resolve, Rejecter $reject) {
            $resolve();
        }))->then(function () {
            return 'Returned Value.';
        });

        Promise::all($promise);
        $this->assertEquals('Returned Value.', $promise->getContext()->getResult());
    }

    /**
     * @throws \Promise\Exceptions\PromiseException
     */
    public function testGetResultWithCatchFunction()
    {
        $promise = (new Promise(function (Resolver $resolve, Rejecter $reject) {
            $reject();
        }))->catch(function () {
            return 'Returned Value.';
        });

        Promise::all($promise);
        $this->assertEquals('Returned Value.', $promise->getContext()->getResult());
    }

    /**
     * @throws \Promise\Exceptions\PromiseException
     */
    public function testRunWithSafetyLoader()
    {
        require_once __DIR__ . '/../Mocks/Dummy.php';
        Promise::setSafety(true);
        $promise = (new Promise(function (Resolver $resolve, Rejecter $reject) {
            // call dummy on safety thread
            dummy();
            new \Promise\Test\Dummy();
            $resolve();
        }))->then(function () {
            return 'Returned Value.';
        });

        Promise::all($promise);
        $this->assertEquals('Returned Value.', $promise->getContext()->getResult());
    }
}

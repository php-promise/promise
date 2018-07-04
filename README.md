# PHP Promise
<p align="center"><img src="https://user-images.githubusercontent.com/1282995/42281650-2887c028-7fdf-11e8-962c-bf7bdd339fdf.png"></p>
The PHP Promise is a library that asynchronously processes PHP like JavaScript Promise.
The library uses `pthreads`, which is a PHP extension.
If you want to use the library, you need to install the `pthreads` extension.
You may also need to re-compile PHP with `--enable-maincontainer-zts` option in configuration.

See also: [https://github.com/krakjoe/pthreads](https://github.com/krakjoe/pthreads)

For example in Dockerfile:

```Dockerfile
FROM centos:7

# Setup
RUN yum -y install epel-release wget
RUN cd /tmp && wget http://jp2.php.net/get/php-7.2.7.tar.gz/from/this/mirror -O php-7.2 && tar zxvf php-7.2

# Dependencies installation
RUN yum -y install git gcc gcc-c++ make libxml2-devel libicu-devel openssl-devel

# PHP installation
RUN cd /tmp/php-7.2.7 && \
    ./configure --enable-maintainer-zts --enable-pcntl --enable-intl --enable-zip --enable-pdo --enable-sockets --with-openssl && \
    make && \
    make install

# pthreads installation
RUN yum -y install autoconf
RUN cd /tmp && git clone https://github.com/krakjoe/pthreads.git && cd pthreads && \
    phpize && \
    ./configure && \
    make && \
    make install

# Add an extension to php.ini
RUN echo extension=pthreads.so >> /usr/local/lib/php.ini
```

# The PHP Promise structure
The PHP Promise structure is below.
![The Promise structure](https://user-images.githubusercontent.com/1282995/42281335-3643af5c-7fde-11e8-9c9d-ffc084664443.jpeg) 

# Requirements

- PHP >= 7.2
- pthreads 3


# Get Started

Run the composer require command.
```
$ composer require php-promise/promise:dev-master
```

Next, run the example code below and you will get started to Promise
```php
// After 5 seconds, Promise will say a message "solved It!"
$promise = (new \Promise\Promise(function (Resolver $resolve, Rejecter $reject) {
    sleep(5);
    $resolve("Solved It!\n");
}))->then(function ($message) {
    echo $message;
});
```

You can use `Promise::all` which waits multiple Promise processing, which will collect Promise results.

```php
// Say as follows:
// [RESOLVE] After 3 seconds says!
// [RESOLVE] After 5 seconds says!
// Sorry, Promise was rejected.
$promises = [];
$promises[] = (new \Promise\Promise(function (Resolver $resolve, Rejecter $reject) {
    sleep(5);
    $resolve("[RESOLVE] After 5 seconds!\n");
}))->then(function ($message) {
    echo $message;
});
$promises[] = (new \Promise\Promise(function (Resolver $resolve, Rejecter $reject) {
    sleep(3);
    $reject("[REJECT] After 3 seconds!\n");
}))->then(function ($message) {
    echo $message;
});

\Promise\Promise::all($promises)->then(function () {
    echo "Okay, Promise is glad\n";
})->catch(function () {
    echo "Sorry, Promise was rejected\n";
});

// or

\Promise\Promise::all($promises[0], $promises[1])->then(function () {
    echo "Okay, Promise is glad\n";
})->catch(function () {
    echo "Sorry, Promise was rejected\n";
});
```

# Provide methods

## Promise::__construct( callable $callee( Resolver $resolve, Rejecter $reject, ...$parameters ), ...$parameters ): Promise
- The constructor is called from your code and immediately runs `$callee` function.
- `$callee` has two parameters that are `$resolve` and `$reject`, which are callable functions.
- `$resolve` is called in `$callee`, which immediately runs the resolved function in `Promise::then`.
- `$reject` is called in `$callee`, which immediately runs the rejected function in `Promise::catch`.
- You can define `$parameters` which you want to pass resource, object and any types to Promise context.

e.g.)

```php
(new \Promise\Promise(function (Resolver $resolve, Rejecter $reject) {
    // Promise call `then` method when you called `$resolve` here.
    $resolve();
    
    // Promise call `reject` method when you called `$reject` here.
    $reject();
}))->then(function () {
    echo 'You can see this message when `$resolve` called.';
})->catch(function () {
    echo 'You can see this message when `$reject` called.';
});
```

e.g.)
```php
$handle = fopen('test.log', 'rw');
(new \Promise\Promise(function (Resolver $resolve, Rejecter $reject, $handle) {
    $reject($handle);
}, $handle))->then(function ($handle) {
    fwrite($handle, 'You can pass resource parameter.');
});
```

## Promise::all( Promise ...$promises ): Promise
- Wait multiple promise processing, then which will collect to promise results.

## Promise::race( Promise ...$promises ): Promise
- `Promise::race` return a Promise when promise get a success or failed.

## Promise::then( callable $onFulfilled, callable $rejected ): Promise
- `Promise::catch` is called when `$resolve` is called.

e.g.)
```php
(new \Promise\Promise(function (Resolver $resolve, Rejecter $reject) {
    $resolve();
}))->then(function () {
    echo 'You can see this message when `$resolve` call.';
});
```

## Promise::catch( callable $rejected ): Promise
- `Promise::catch` is called when `$reject` is called.

e.g.)
```php
(new \Promise\Promise(function (Resolver $resolve, Rejecter $reject) {
    $reject();
}))->catch(function () {
    echo 'You can see this message when `$resolve` call.';
});
```

## Promise::finally( callable $onFinally ): Promise
- `Promise::catch` is called when `$resolve` or `$catch` is called.

e.g.)
```php
(new \Promise\Promise(function (Resolver $resolve, Rejecter $reject) {
    $resolve();
}))->then(function () {
    // do something
})->catch(function () {
    // do something
})->finally(function () {
     echo 'You can see this message when `$resolve` or `$catch` called.';
});
```
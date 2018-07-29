# PHP Promise
<p align="center"><img src="https://user-images.githubusercontent.com/1282995/42281650-2887c028-7fdf-11e8-962c-bf7bdd339fdf.png"></p>
PHP PromiseはJavaScriptのPromiseのような記述で、マルチスレッディングで非同期を実現するためのライブラリです。
このライブラリはPHP拡張の `pthreads` を必要とし、このライブラリを利用するためには拡張をインストールする必要があります。
また、 `--enable-maincontainer-zts` が有効でない場合、再度PHP本体をビルドし直す必要があります。

(参照) [https://github.com/krakjoe/pthreads](https://github.com/krakjoe/pthreads)

下記は `pthreads` を扱うためのDockerfileのサンプルです。

```Dockerfile
FROM centos:7

# セットアップを行います
RUN yum -y install epel-release wget
RUN cd /tmp && wget http://jp2.php.net/get/php-7.2.7.tar.gz/from/this/mirror -O php-7.2 && tar zxvf php-7.2

# 依存パッケージをインストールします
RUN yum -y install git gcc gcc-c++ make libxml2-devel libicu-devel openssl-devel

# PHPをインストールします。
RUN cd /tmp/php-7.2.7 && \
    ./configure --enable-maintainer-zts --enable-pcntl --enable-intl --enable-zip --enable-pdo --enable-sockets --with-openssl && \
    make && \
    make install

# pthreadsをインストールします
RUN yum -y install autoconf
RUN cd /tmp && git clone https://github.com/krakjoe/pthreads.git && cd pthreads && \
    phpize && \
    ./configure && \
    make && \
    make install

# pthreadsの設定をphp.iniに書き込みます
RUN echo extension=pthreads.so >> /usr/local/lib/php.ini
```

# PHP Promiseの仕組み
PHP Promiseの仕組みは下記の通りとなります。
![The Promise structure](https://user-images.githubusercontent.com/1282995/42298295-20c6456a-8040-11e8-9c66-8b3422d327c8.jpeg) 

# 必要な環境

- PHP >= 7.2
- pthreads 3


# クイックスタート

composerコマンドを実行します。
```
$ composer require php-promise/promise:dev-master
```

次に下記のサンプルを実行することによりPHP Promiseを開始できます。
```php
// 5秒後, PHP Promise は "solved It!" を出力します。
$promise = (new \Promise\Promise(function (Resolver $resolve, Rejecter $reject) {
    sleep(5);
    $resolve("Solved It!\n");
}))->then(function ($message) {
    echo $message;
});
```

また、 複数のPromiseの実行を待機するため、 `Promise::all` を使用することが可能です。

```php
// 下記のような出力となります:
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

// または、

\Promise\Promise::all($promises[0], $promises[1])->then(function () {
    echo "Okay, Promise is glad\n";
})->catch(function () {
    echo "Sorry, Promise was rejected\n";
});
```

# 提供しているメソッド

## Promise::__construct( callable $callee( Resolver $resolve, Rejecter $reject, ...$parameters ), ...$parameters ): Promise
- `$callee` 関数はコンストラクタの定義時に即座に呼ばれます。.
- `$callee` は `$resolve` と `$reject` の2つのコールバック関数のパラメータを受け取ります。
- `$resolve` は `$callee` で呼ばれると、即時に `Promise::then` を呼びます。.
- `$reject` は `$callee` で呼ばれると、即時に `Promise::catch` を呼びます。.
- `$parameters` を定義すると各コールバックに値を引き渡すことが可能です。.

例)

```php
(new \Promise\Promise(function (Resolver $resolve, Rejecter $reject) {
    // `$resolve` をPromise上で呼ぶと `then` メソッドが呼ばれます。
    $resolve();
    
    // `$reject` を呼ぶと reject メソッドが実行されます。
    $reject();
}))->then(function () {
    echo 'You can see this message when `$resolve` called.';
})->catch(function () {
    echo 'You can see this message when `$reject` called.';
});
```

例)
```php
$handle = fopen('test.log', 'rw');
(new \Promise\Promise(function (Resolver $resolve, Rejecter $reject, $handle) {
    $reject($handle);
}, $handle))->then(function ($handle) {
    fwrite($handle, 'You can pass resource parameter.');
});
```

## Promise::all( Promise ...$promises ): Promise
- 複数のPromiseの処理結果を待ちます。

## Promise::race( Promise ...$promises ): Promise
- `Promise::race` は 渡されたパラメータのいずれかの処理が `resolve` もしくは `reject` 担った際に呼ばれます。 

## Promise::then( callable $onFulfilled, callable $rejected ): Promise
- Promise上で、 `$resolve` が呼ばれた際に `Promise::then` が呼ばれます。

例)
```php
(new \Promise\Promise(function (Resolver $resolve, Rejecter $reject) {
    $resolve();
}))->then(function () {
    echo 'You can see this message when `$resolve` call.';
});
```

## Promise::catch( callable $rejected ): Promise
- Promise上で、 `$reject` が呼ばれた際に `Promise::catch` が呼ばれます。

例)
```php
(new \Promise\Promise(function (Resolver $resolve, Rejecter $reject) {
    $reject();
}))->catch(function () {
    echo 'You can see this message when `$resolve` call.';
});
```

## Promise::finally( callable $onFinally ): Promise
- Promise上で `$resolve` または `$catch` が呼ばれた際に `Promise::finally` が呼ばれます。

例)
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
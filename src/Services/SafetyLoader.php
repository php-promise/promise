<?php

namespace Promise\Services;

use Promise\Exceptions\PromiseException;
use Promise\Promise;

class SafetyLoader
{
    const OPTIONS = PTHREADS_INHERIT_INI | PTHREADS_INHERIT_COMMENTS;

    /**
     * @var bool
     */
    private static $isEnable = false;

    /**
     * @param $which
     */
    public static function setEnable(bool $which)
    {
        static::$isEnable = $which;
    }

    /**
     * @return bool
     */
    public static function isEnabled(): bool
    {
        return static::$isEnable;
    }

    /**
     * @return array
     */
    public static function getLoadedFunction(): array
    {
        $loaded = [];
        try {
            foreach (get_defined_functions()['user'] as $function) {
                if (strpos($function, 'Closure@') !== false) {
                    continue;
                }
                $loaded[] = (new \ReflectionFunction($function))->getFileName();
            }
        } catch (\ReflectionException $exception) {
            // do nothing
        }
        return $loaded;
    }

    /**
     * @return array
     */
    public static function getLoadedClasses(): array
    {
        $loaded = [];
        try {
            foreach (array_merge(get_declared_classes(), get_declared_interfaces(), get_declared_traits()) as $class) {
                $loaded[] = (new \ReflectionClass($class))->getFileName();
            }
        } catch (\ReflectionException $exception) {
            // do nothing
        }
        return $loaded;
    }

    /**
     * @return array|null
     */
    public static function getLoadedComposer(): ?array
    {
        try {
            foreach (get_declared_classes() as $class) {
                if (strpos($class, 'ComposerAutoloaderInit') !== false) {
                    return [
                        'initializer' => $class,
                        'file' => (new \ReflectionClass($class))->getFileName(),
                    ];
                }
            }
        } catch (\ReflectionException $exception) {

        }

        // No composer.
        return null;
    }

    /**
     * @return array
     */
    public static function getLoadedFiles(): array
    {
        return array_unique(
            array_merge(
                static::getLoadedClasses(),
                static::getLoadedFunction()
            )
        );
    }

    /**
     * @param array $inheritanceFiles
     * @param array $ignoreFiles
     */
    public static function inheritanceIncludedArchitecturesFromParent(array $inheritanceFiles, array $ignoreFiles): void
    {
        foreach (array_diff($inheritanceFiles, array_diff(static::getLoadedFiles(), $ignoreFiles)) as $file) {
            if (!is_file($file)) {
                continue;
            }
            require_once $file;
        }
    }

    /**
     * @param string $composerInitializerFile
     * @param string $class
     */
    public static function callComposerInitializer(string $composerInitializerFile, string $class): void
    {
        require_once $composerInitializerFile;
        $class::getLoader();
    }

    /**
     * @param \Thread $thread
     * @param \Volatile $dependencies
     */
    public static function loadDependencies(\Thread $thread, \Volatile $dependencies)
    {
        $dependencies = (array) $dependencies;
        require_once $dependencies[2];

        $ignoreFiles = [];
        if ($dependencies[0] !== null) {
            static::callComposerInitializer(
                $dependencies[0]['file'],
                $dependencies[0]['initializer']
            );
            $ignoreFiles[] = $dependencies[0]['file'];
        }
        static::inheritanceIncludedArchitecturesFromParent((array) $dependencies[1], $ignoreFiles);
    }
}

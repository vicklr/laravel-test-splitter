<?php

declare(strict_types=1);

namespace Vicklr\LaravelTestSplitter;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Vicklr\LaravelTestSplitter\Commands\LaravelTestSplitterCommand;

class LaravelTestSplitterServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-test-splitter')
            ->hasConfigFile()
            ->hasCommand(LaravelTestSplitterCommand::class);
    }
}

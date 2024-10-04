<?php

declare(strict_types=1);

use function Pest\Laravel\artisan;

it('splits tests into groups', function () {
    $testListFile = base_path('phpunit.runtime.xml');

    artisan('laravel-test-splitter', ['--chunks' => 3]);

    expect(file_exists($testListFile))->toBeTrue();
    $xml = simplexml_load_file($testListFile);
    expect($xml)->not()->toBeFalse();

    $suites = $xml->xpath('//testsuite');
    expect(count($suites))->toBe(2); // There are only 2 tests, so we should only get 2 chunks
});

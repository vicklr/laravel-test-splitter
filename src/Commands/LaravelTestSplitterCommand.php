<?php

declare(strict_types=1);

namespace Vicklr\LaravelTestSplitter\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Vicklr\LaravelTestSplitter\LaravelTestSplitter;

class LaravelTestSplitterCommand extends Command
{
    public $signature = 'laravel-test-splitter {--chunks=5}';

    public $description = 'Split tests into manageable chunks';

    public function handle(LaravelTestSplitter $splitter): int
    {
        $chunks = (int) $this->option('chunks');

        if ($chunks <= 0) {
            $this->fail(new InvalidArgumentException('Number of groups must be a positive integer'));
        }

        $this->info("Splitting tests into $chunks groups...");
        $testCases = $splitter->getTestCases();

        $baselineCheckSumCount = $testCases->unique('classname')->count();

        // Split the test cases (grouped by class) into the desired number of groups
        $groups = $splitter->splitTestsIntoGroups($testCases, $chunks);

        $this->info("\nTest Groups:");
        $checkSumCount = 0;
        foreach ($groups as $group) {
            $checkSumCount += count($group['classes']);
        }

        $fileName = $splitter->generateRuntimeConfig($groups);

        $this->info('');
        $this->info("Number of groups: $chunks");
        $this->info('==== CHECKSUM ====');
        $this->info("Baseline: $baselineCheckSumCount files");
        $this->info("Check: $checkSumCount files");

        if ($baselineCheckSumCount !== $checkSumCount) {
            $this->error('❌ Checksum for count mismatch');

            return 1;
        }

        $this->info('✅ Checksum for count');

        $this->comment(sprintf('All done, %s generated', $fileName));

        return self::SUCCESS;
    }
}

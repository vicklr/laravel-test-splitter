<?php

declare(strict_types=1);

namespace Vicklr\LaravelTestSplitter;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class LaravelTestSplitter
{
    private const DEFAULT_TEST_TIME = 2;
    private const TEST_DIRECTORY = 'tests/';

    public function getTestCases(): Collection
    {
        $testList = base_path('available-tests.xml');
        $knownFilesDir = base_path('.github/test-runtime');

        $phpUnitExecutable = base_path('vendor/bin/' . config('laravel-test-splitter.executable', 'pest'));
        exec("$phpUnitExecutable --list-tests-xml " . base_path('available-tests.xml'));

        $knownTestCases = [];
        // Check if the folder exists
        if (is_dir($knownFilesDir)) {
            // Scan for all .xml files in the folder
            $files = glob($knownFilesDir . '/*.xml');
            // Parse all XML files from the given folder to gather known test case runtimes
            $knownTestCases = $this->parseJUnitXMLFromFiles($files);
        }

        // Parse the test list XML file, calculating total runtimes per class
        return collect($this->parseTestList($testList, $knownTestCases));
    }

    // Load and parse a single JUnit XML file to get test runtimes
    private function parseJUnitXML($xmlFile): array
    {
        if (!file_exists($xmlFile)) {
            throw new Exception("File not found: $xmlFile\n");
        }

        $xml = simplexml_load_file($xmlFile);
        if ($xml === false) {
            throw new Exception("Failed to parse XML: $xmlFile\n");
        }

        $testCases = [];

        // Iterate through all <testcase> elements in the XML
        foreach ($xml->xpath('//testcase') as $testCase) {
            $classname = (string)$testCase['classname'];
            $time = (float)$testCase['time'];

            // Add the time to the corresponding class in the array
            if (!isset($testCases[$classname])) {
                $testCases[$classname] = 0;
            }
            $testCases[$classname] += $time;
        }

        return $testCases;
    }

    // Parse all JUnit XML files from a folder and merge the test cases
    public function parseJUnitXMLFromFiles(Iterable $files): array
    {
        $allTestCases = [];

        if (empty($files)) {
            return $allTestCases;
        }

        foreach ($files as $xmlFile) {
            $testCases = $this->parseJUnitXML($xmlFile);
            $allTestCases = array_merge_recursive($allTestCases, $testCases);
        }

        return $allTestCases;
    }

    // Parse the input list of tests, calculate total runtime per class
    private function parseTestList(string $testListFile, array $knownTestCases)
    {
        if (!file_exists($testListFile)) {
            throw new Exception("Test list file not found: $testListFile");
        }

        $xml = simplexml_load_file($testListFile);
        if ($xml === false) {
            throw new Exception("Failed to parse test list XML");
        }

        $testCases = [];

        // Iterate through the test classes and methods
        foreach ($xml->xpath('//testCaseClass') as $testClass) {
            $className = (string)$testClass['name'];
            if (Str::startsWith($className, 'P\\')) {
                $className = Str::after($className, 'P\\');
            }
            $totalTimeForClass = 0;

            $format = str_replace('\\', '.', $className);
            $time = $knownTestCases[$format] ?? self::DEFAULT_TEST_TIME;

            if (is_array($time)) {
                $time = array_sum($time) / count($time);
            }
            $totalTimeForClass += $time;

            $filename = $this->getTestFilePath($className);

            if (!file_exists($filename)) {
                throw new Exception("File not found: $filename\n");
            }

            $testCases[] = [
                'classname' => $className,
                'totalTime' => $totalTimeForClass,
                'filename' => $filename,
            ];
        }

        return $testCases;
    }

    private function getTestFilePath($className): string
    {
        // Convert the class name to a file path (assuming PSR-4 structure)
        $path = str_replace('\\', '/', $className);
        $path = str_replace('Tests/', '', $path);
        return self::TEST_DIRECTORY . $path . '.php';
    }

    // Function to split test classes into X groups, optimized for shortest runtime on the slowest group
    public function splitTestsIntoGroups(Collection $testCases, int $numGroups): array
    {
        if ($numGroups > $testCases->count()) {
            $numGroups = $testCases->count();
        }
        // Sort the test classes by total time in descending order (LPT - Largest Processing Time first)
        $testCases = $testCases->sort(function ($a, $b) {
            return $b['totalTime'] <=> $a['totalTime'];
        })->unique('classname')->values();

        // Initialize groups with empty arrays and 0 total time
        $groups = array_fill(0, $numGroups, ['classes' => [], 'totalTime' => 0]);

        // Distribute classes, assigning each to the group with the least total time
        $testCases->each(function ($test) use (&$groups) {
            // Find the group with the smallest total time
            usort($groups, function ($a, $b) {
                return $a['totalTime'] <=> $b['totalTime'];
            });

            // Assign the class to the group with the smallest total time
            $groups[0]['classes'][] = $test;
            $groups[0]['totalTime'] += $test['totalTime'];
        });

        return $groups;
    }

    public function generateRuntimeConfig(array $groups): string
    {
        $xml = '';
        foreach ($groups as $index => $testCase) {
            $xml .= sprintf(
                '<testsuite name="group%s">' . "\n",
                ($index + 1)
            );
            foreach ($testCase['classes'] as $test) {
                $xml .= sprintf(
                    "  <file>%s</file>" . "\n",
                    $test['filename']
                );
            }
            $xml .= '</testsuite>' . "\n\n";
        }

        $baseXmlContent = file_get_contents(file_exists(base_path('phpunit.xml')) ? base_path('phpunit.xml') : __DIR__ . '/assets/phpunit.xml.base');
        $pattern = '/<testsuites>(.*?)<\/testsuites>/s';
        $replaced = preg_replace($pattern, '<testsuites>' . $xml . '</testsuites>', $baseXmlContent);
        $fileName = base_path('phpunit.runtime.xml');
        file_put_contents($fileName, $replaced);

        return $fileName;
    }
}

<?php

class TestRunner
{
    private $tests = 0;
    private $passed = 0;
    private $failed = 0;
    private $assertions = 0;

    public function run(array $classes, $label)
    {
        echo "\n============================================================\n";
        echo "Ichi ORM Query Test Suite: {$label}\n";
        echo "============================================================\n";

        foreach ($classes as $class) {
            $this->runClass($class);
        }

        echo "\n------------------------------------------------------------\n";
        echo "Driver:      {$label}\n";
        echo "Tests:       {$this->tests}\n";
        echo "Passed:      {$this->passed}\n";
        echo "Failed:      {$this->failed}\n";
        echo "Assertions:  {$this->assertions}\n";
        echo "------------------------------------------------------------\n";

        return $this->failed === 0 ? 0 : 1;
    }

    private function runClass($class)
    {
        $reflection = new ReflectionClass($class);
        echo "\n[{$class}]\n";

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if (strpos($method->name, 'test') !== 0) {
                continue;
            }

            $this->tests++;
            $instance = $reflection->newInstance();

            try {
                $instance->setUp();
                $method->invoke($instance);
                $instance->tearDown();
                $this->passed++;
                $this->assertions += $instance->getAssertionCount();
                echo "  PASS {$method->name}\n";
            } catch (Throwable $e) {
                $this->failed++;
                $this->assertions += $instance->getAssertionCount();
                echo "  FAIL {$method->name}\n";
                echo "       " . get_class($e) . ': ' . $e->getMessage() . "\n";
                try {
                    $instance->tearDown();
                } catch (Throwable $ignored) {
                }
            }
        }
    }
}

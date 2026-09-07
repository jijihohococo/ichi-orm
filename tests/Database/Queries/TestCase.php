<?php

abstract class TestCase
{
    private $assertions = 0;

    public function setUp()
    {
    }

    public function tearDown()
    {
    }

    protected function assertTrue($condition, $message = 'Expected true.')
    {
        $this->assertions++;
        if (!$condition) {
            throw new Exception($message);
        }
    }

    protected function assertFalse($condition, $message = 'Expected false.')
    {
        $this->assertions++;
        if ($condition) {
            throw new Exception($message);
        }
    }

    protected function assertSame($expected, $actual, $message = null)
    {
        $this->assertions++;
        if ($expected !== $actual) {
            throw new Exception($message ?: sprintf(
                'Expected %s, received %s.',
                var_export($expected, true),
                var_export($actual, true)
            ));
        }
    }

    protected function assertEquals($expected, $actual, $message = null)
    {
        $this->assertions++;
        if ($expected != $actual) {
            throw new Exception($message ?: sprintf(
                'Expected %s, received %s.',
                var_export($expected, true),
                var_export($actual, true)
            ));
        }
    }

    protected function assertNotSame($expected, $actual, $message = null)
    {
        $this->assertions++;
        if ($expected === $actual) {
            throw new Exception($message ?: 'Values must not be identical.');
        }
    }

    protected function assertNull($actual, $message = 'Expected null.')
    {
        $this->assertions++;
        if ($actual !== null) {
            throw new Exception($message);
        }
    }

    protected function assertNotNull($actual, $message = 'Expected a non-null value.')
    {
        $this->assertions++;
        if ($actual === null) {
            throw new Exception($message);
        }
    }

    protected function assertCount($expected, $actual, $message = null)
    {
        if (!is_countable($actual)) {
            throw new Exception('Value is not countable.');
        }
        $this->assertSame($expected, count($actual), $message ?: "Expected count {$expected}.");
    }

    protected function assertIsArray($actual, $message = 'Expected array.')
    {
        $this->assertions++;
        if (!is_array($actual)) {
            throw new Exception($message);
        }
    }

    protected function assertIsString($actual, $message = 'Expected string.')
    {
        $this->assertions++;
        if (!is_string($actual)) {
            throw new Exception($message);
        }
    }

    protected function assertArrayHasKey($key, $array, $message = null)
    {
        $this->assertions++;
        if (!is_array($array) || !array_key_exists($key, $array)) {
            throw new Exception($message ?: "Expected array key '{$key}'.");
        }
    }

    protected function assertContains($needle, $haystack, $message = null)
    {
        $this->assertions++;
        if (!in_array($needle, $haystack, true)) {
            throw new Exception($message ?: 'Expected value not found.');
        }
    }

    protected function assertNotContains($needle, $haystack, $message = null)
    {
        $this->assertions++;
        if (in_array($needle, $haystack, true)) {
            throw new Exception($message ?: 'Unexpected value found.');
        }
    }

    protected function assertInstanceOf($expected, $actual, $message = null)
    {
        $this->assertions++;
        if (!($actual instanceof $expected)) {
            throw new Exception($message ?: "Expected instance of {$expected}.");
        }
    }

    protected function assertStringContains($needle, $haystack, $message = null)
    {
        $this->assertions++;
        if (strpos($haystack, $needle) === false) {
            throw new Exception($message ?: "Expected '{$needle}' in string.");
        }
    }

    protected function assertStringNotContains($needle, $haystack, $message = null)
    {
        $this->assertions++;
        if (strpos($haystack, $needle) !== false) {
            throw new Exception($message ?: "Did not expect '{$needle}' in string.");
        }
    }

    protected function assertThrows($expectedException, callable $callback)
    {
        $this->assertions++;
        try {
            $callback();
        } catch (Throwable $exception) {
            if (!($exception instanceof $expectedException)) {
                throw new Exception(
                    "Expected {$expectedException}, received " . get_class($exception) . ': ' . $exception->getMessage()
                );
            }
            return;
        }
        throw new Exception("Expected {$expectedException} to be thrown.");
    }

    protected function each(array $values, callable $callback)
    {
        foreach ($values as $value) {
            $callback($value);
        }
    }

    public function getAssertionCount()
    {
        return $this->assertions;
    }
}

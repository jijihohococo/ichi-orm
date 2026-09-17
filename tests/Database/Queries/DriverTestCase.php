<?php

abstract class DriverTestCase extends TestCase
{
    protected function pdo()
    {
        return TestDatabase::pdo();
    }

    protected function driver()
    {
        return TestDatabase::driver();
    }

    protected function countRows($table)
    {
        return (int) TestDatabase::scalar('SELECT COUNT(*) FROM ' . $table);
    }

    protected function firstBlogIdByTitle($title)
    {
        $value = TestDatabase::scalar(
            'SELECT id FROM test_blogs WHERE title = ?',
            [$title]
        );

        return (int) $value;
    }

    protected function assertBlogCount($expected, $queryResult)
    {
        $this->assertCount($expected, $queryResult);
        return $queryResult;
    }

    /**
     * Assert the complete SQL string returned by toSQL()->get().
     *
     * The expected SQL must be supplied per PDO driver:
     * mysql, pgsql, sqlsrv.
     */
    protected function assertExactSql($query, array $expected)
    {
        $driver = $this->driver();

        $this->assertTrue(
            isset($expected[$driver]),
            "No expected SQL was supplied for driver '{$driver}'."
        );

        $actual = $query->toSQL()->get();

        $this->assertSame(
            $expected[$driver],
            $actual
        );
    }

    protected function assertBlogIds(array $rows, array $expected)
    {
        $actual = array_map(
            function ($row) {
                return (int) $row->id;
            },
            $rows
        );

        $this->assertSame($expected, $actual);
    }

    public function setUp()
    {
        TestDatabase::begin();
    }

    public function tearDown()
    {
        TestDatabase::rollback();
    }
}

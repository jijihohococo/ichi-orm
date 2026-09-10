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
        $value = TestDatabase::scalar('SELECT id FROM test_blogs WHERE title = ?', [$title]);
        return (int) $value;
    }

    protected function assertBlogCount($expected, $queryResult)
    {
        $this->assertCount($expected, $queryResult);
        return $queryResult;
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

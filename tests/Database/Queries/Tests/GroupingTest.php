<?php

class GroupingTest extends DriverTestCase
{
    public function testGroupBy()
    {
        $query = Blog::select(['author_id'])->groupBy('author_id');
        $expectedSQL = 'SELECT author_id FROM test_blogs WHERE test_blogs.deleted_at IS NULL GROUP BY author_id';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => $expectedSQL,
        ]);

        $rows = $query->get();
        $this->assertCount(3, $rows);
    }

    public function testGroupByAndHaving()
    {
        $query = Blog::select(['author_id'])
            ->groupBy('author_id')
            ->having('COUNT(id)', '>', 1);
        $expectedSQL = 'SELECT author_id FROM test_blogs WHERE test_blogs.deleted_at IS NULL GROUP BY author_id HAVING COUNT(id) > 1';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => $expectedSQL,
        ]);

        $rows = $query->get();
        $this->assertNotEmpty($rows);
    }

    public function testMultipleHavingConditions()
    {
        $query = Blog::select(['author_id'])
            ->groupBy('author_id')
            ->having('COUNT(id)', '>', 0)
            ->having('COUNT(id)', '<', 10);
        $expectedSQL = 'SELECT author_id FROM test_blogs WHERE test_blogs.deleted_at IS NULL GROUP BY author_id HAVING COUNT(id) > 0 AND COUNT(id) < 10';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => $expectedSQL,
        ]);

        $rows = $query->get();
        $this->assertNotEmpty($rows);
    }

    public function testHavingNumericLiteral()
    {
        $query = Blog::select(['author_id'])
            ->groupBy('author_id')
            ->having('COUNT(id)', '>=', 2);
        $expectedSQL = 'SELECT author_id FROM test_blogs WHERE test_blogs.deleted_at IS NULL GROUP BY author_id HAVING COUNT(id) >= 2';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => $expectedSQL,
        ]);

        $rows = $query->get();
        $this->assertNotEmpty($rows);
    }

    public function testMultipleGroupByColumns()
    {
        $query = Blog::select(['status', 'author_id'])
            ->groupBy('status', 'author_id');
        $expectedSQL = 'SELECT status,author_id FROM test_blogs WHERE test_blogs.deleted_at IS NULL GROUP BY status, author_id';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => $expectedSQL,
        ]);

        $rows = $query->get();
        $this->assertNotNull($rows);
    }

    public function testMultipleGroupByColumnsAsArray()
    {
        $query = Blog::select(['status', 'author_id'])
            ->groupBy(['status', 'author_id']);
        $expectedSQL = 'SELECT status,author_id FROM test_blogs WHERE test_blogs.deleted_at IS NULL GROUP BY status, author_id';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => $expectedSQL,
        ]);

        $rows = $query->get();
        $this->assertNotNull($rows);
    }
}

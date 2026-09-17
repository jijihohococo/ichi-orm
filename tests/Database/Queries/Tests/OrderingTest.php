<?php

class OrderingTest extends DriverTestCase
{
    public function testOrderAscending()
    {
        $query = Blog::orderBy('views', 'ASC');
        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs WHERE test_blogs.deleted_at IS NULL ORDER BY views ASC';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => $expectedSQL,
        ]);

        $rows = $query->get();

        $this->assertSame(10, (int) $rows[0]->views);
        $this->assertSame(400, (int) $rows[count($rows) - 1]->views);
    }

    public function testOrderDescending()
    {
        $query = Blog::orderBy('views', 'DESC');
        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs WHERE test_blogs.deleted_at IS NULL ORDER BY views DESC';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => $expectedSQL,
        ]);

        $rows = $query->get();

        $this->assertSame(400, (int) $rows[0]->views);
        $this->assertSame(10, (int) $rows[count($rows) - 1]->views);
    }

    public function testLatest()
    {
        $query = Blog::latest('views');
        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs WHERE test_blogs.deleted_at IS NULL ORDER BY test_blogs.views DESC';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => $expectedSQL,
        ]);

        $rows = $query->get();

        $this->assertSame(400, (int) $rows[0]->views);
    }
}

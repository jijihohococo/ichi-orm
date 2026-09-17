<?php

class OrWhereTest extends DriverTestCase
{
    public function testOrWhere()
    {
        $query = Blog::where('status', 'draft')->orWhere('status', 'published');
        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs WHERE status = ? OR status = ? AND test_blogs.deleted_at IS NULL';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => $expectedSQL,
        ]);

        $rows = $query->get();

        $this->assertCount(6, $rows);
    }

    public function testOrWhereWithOperator()
    {
        $query = Blog::where('views', '<', 20)->orWhere('views', '>', 350);
        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs WHERE views < ? OR views > ? AND test_blogs.deleted_at IS NULL';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => $expectedSQL,
        ]);

        $rows = $query->get();

        $this->assertCount(2, $rows);
    }

    public function testMultipleOrWhere()
    {
        $query = Blog::where('title', 'PHP ORM')->orWhere('title', 'Laravel ORM')->orWhere('title', 'Architecture');
        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs WHERE title = ? OR title = ? OR title = ? AND test_blogs.deleted_at IS NULL';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => $expectedSQL,
        ]);

        $rows = $query->get();

        $this->assertCount(3, $rows);
    }
}

<?php

class WhereColumnTest extends DriverTestCase
{
    public function testWhereColumnSameValues()
    {
        $query = Blog::whereColumn('test_blogs.author_id', 'test_blogs.author_id');
        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs WHERE test_blogs.author_id = test_blogs.author_id AND test_blogs.deleted_at IS NULL';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => $expectedSQL,
        ]);
        $rows = $query->get();
        $this->assertCount(6, $rows);
    }

    public function testWhereColumnGreaterThan()
    {
        $query = Blog::whereColumn('test_blogs.id', '>', 'test_blogs.author_id');
        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs WHERE test_blogs.id > test_blogs.author_id AND test_blogs.deleted_at IS NULL';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => $expectedSQL,
        ]);
        $rows = $query->get();
        $this->assertCount(5, $rows);
    }

    public function testWhereColumnQualifiedNames()
    {
        $query = Blog::whereColumn('test_blogs.author_id', 'test_blogs.id');
        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs WHERE test_blogs.author_id = test_blogs.id AND test_blogs.deleted_at IS NULL';
        
        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => $expectedSQL,
        ]);
        $rows = $query->get();
        $this->assertCount(1, $rows);
    }

    public function testWhereColumnSubquery()
    {
        $query = Blog::whereColumn('test_blogs.id', '>', function ($query) {
            return $query->select(['id'])
                ->where('title', 'PHP ORM')
                ->get();
        });
        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs WHERE test_blogs.id > (SELECT id FROM test_blogs WHERE title = ? AND test_blogs.deleted_at IS NULL) AND test_blogs.deleted_at IS NULL';

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

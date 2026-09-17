<?php

class CoreQueryTest extends DriverTestCase
{

    public function testGetReturnsModels()
    {
        $query = Blog::select(['id']);
        $expectedSQL = 'SELECT id FROM test_blogs WHERE test_blogs.deleted_at IS NULL';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => $expectedSQL,
        ]);

        $rows = $query->get();

        $this->assertCount(6, $rows);
        $this->assertInstanceOf(Blog::class, $rows[0]);
    }

    public function testToArrayReturnsAssociativeArrays()
    {
        $query = Blog::where('id', 1);
        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs WHERE id = ? AND test_blogs.deleted_at IS NULL'; 

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => $expectedSQL,
        ]);

        $rows = $query->toArray();

        $this->assertCount(1, $rows);
        $this->assertIsArray($rows[0]);
        $this->assertSame('PHP ORM', $rows[0]['title']);
    }

    public function testSelectSingleColumn()
    {
        $query = Blog::select(['title'])->where('id', 1);
        $expectedSQL = 'SELECT title FROM test_blogs WHERE id = ? AND test_blogs.deleted_at IS NULL';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => $expectedSQL,
        ]);

        $row = $query->get()[0];

        $this->assertSame('PHP ORM', $row->title);
        $this->assertFalse(isset($row->content));
    }

    public function testSelectMultipleColumns()
    {
        $query = Blog::select(['id', 'title', 'status'])->where('id', 1);
        $expectedSQL = 'SELECT id,title,status FROM test_blogs WHERE id = ? AND test_blogs.deleted_at IS NULL';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => $expectedSQL,
        ]);

        $row = $query->get()[0];

        $this->assertSame(1, (int) $row->id);
        $this->assertSame('PHP ORM', $row->title);
        $this->assertSame('published', $row->status);
    }

    public function testLatestDefaultsToPrimaryKey()
    {
        $query = Blog::latest();
        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs WHERE test_blogs.deleted_at IS NULL ORDER BY test_blogs.id DESC';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => $expectedSQL,
        ]);

        $rows = $query->get();

        $this->assertCount(6, $rows);
        $this->assertSame(6, (int) $rows[0]->id);
    }

    public function testLatestAcceptsField()
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

    public function testLimit()
    {
        $query = Blog::limit(2);
        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs WHERE test_blogs.deleted_at IS NULL LIMIT 2';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => 'SELECT TOP 2 test_blogs.* FROM test_blogs WHERE test_blogs.deleted_at IS NULL',
            'sqlite' => $expectedSQL,
        ]);

        $rows = $query->get();

        $this->assertCount(2, $rows);
    }

    public function testOffset()
    {
        $query = Blog::orderBy('id', 'ASC')->limit(2)->offset(2);
        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs WHERE test_blogs.deleted_at IS NULL ORDER BY id ASC LIMIT 2 OFFSET 2';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => 'SELECT test_blogs.* FROM test_blogs WHERE test_blogs.deleted_at IS NULL ORDER BY id ASC OFFSET 2 ROWS FETCH NEXT 2 ROWS ONLY ',
            'sqlite' => $expectedSQL,
        ]);

        $rows = $query->get();

        $this->assertCount(2, $rows);
        $this->assertSame(3, (int) $rows[0]->id);
    }

    public function testFromIsAvailableInsideSubquery()
    {
        $query = Blog::whereIn('author_id', function ($query) {
            return $query->from(Author::class)
                ->select(['id'])
                ->where('name', 'John')
                ->get();
        });
        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs WHERE author_id IN (SELECT id FROM test_authors WHERE name = ? AND test_authors.deleted_at IS NULL) AND test_blogs.deleted_at IS NULL';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => $expectedSQL,
        ]);

        $rows = $query->get();

        $this->assertCount(2, $rows);
        $this->assertSame(1, (int) $rows[0]->author_id);
    }

    public function testAggregateSumWithWhereAndLimit()
    {
        $query = Blog::select(['SUM(views) as total_views'])->where('status', 'published')->limit(1);
        $expectedSQL = 'SELECT SUM(views) as total_views FROM test_blogs WHERE status = ? AND test_blogs.deleted_at IS NULL LIMIT 1';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => 'SELECT TOP 1 SUM(views) as total_views FROM test_blogs WHERE status = ? AND test_blogs.deleted_at IS NULL',
            'sqlite' => $expectedSQL,
        ]);

        $rows = $query->get();

        $this->assertCount(1, $rows);
        $this->assertSame(1000, (int) $rows[0]->total_views);
    }
}

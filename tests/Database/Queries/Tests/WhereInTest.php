<?php

class WhereInTest extends DriverTestCase
{
    public function testWhereInMultipleIntegers()
    {
        $query = Blog::whereIn('id', [1, 2, 3]);
        $sql = 'SELECT test_blogs.* FROM test_blogs WHERE CAST(id AS %s) IN (?,?,?) AND test_blogs.deleted_at IS NULL';

        $this->assertExactSql(
            $query,
            $this->getExpectedCastSql($sql)
        );
        
        $rows = $query->get();
        $this->assertCount(3, $rows);
    }

    public function testWhereInSingleInteger()
    {
        $query = Blog::whereIn('id', [1]);
        $sql = 'SELECT test_blogs.* FROM test_blogs WHERE CAST(id AS %s) IN (?) AND test_blogs.deleted_at IS NULL';

        $this->assertExactSql(
            $query,
            $this->getExpectedCastSql($sql)
        );
        $rows = $query->get();
        $this->assertCount(1, $rows);
    }

    public function testWhereInStringValues()
    {
        $query = Blog::whereIn('status', ['published']);
        $sql = 'SELECT test_blogs.* FROM test_blogs WHERE CAST(status AS %s) IN (?) AND test_blogs.deleted_at IS NULL';

        $this->assertExactSql(
            $query,
            $this->getExpectedCastSql($sql)
        );
        $rows = $query->get();
        $this->assertCount(4, $rows);
    }

    public function testWhereInEmptyArrayProducesNoRows()
    {
        $query = Blog::whereIn('id', []);
        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs WHERE 1 = 0 AND test_blogs.deleted_at IS NULL';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => $expectedSQL,
        ]);
        $rows = $query->get();
        $this->assertCount(0, $rows);
    }

    public function testWhereInValuesAreBound()
    {
        $payload = "1 OR 1=1";
        $query = Blog::whereIn('id', [$payload]);
        $sql = 'SELECT test_blogs.* FROM test_blogs WHERE CAST(id AS %s) IN (?) AND test_blogs.deleted_at IS NULL';

        $this->assertExactSql(
            $query,
            $this->getExpectedCastSql($sql)
        );
        $rows = $query->get();
        $this->assertCount(0, $rows);
    }

    public function testWhereNotInMultipleIntegers()
    {
        $query = Blog::whereNotIn('id', [1, 2]);
        $sql = 'SELECT test_blogs.* FROM test_blogs WHERE CAST(id AS %s) NOT IN (?,?) AND test_blogs.deleted_at IS NULL';

        $this->assertExactSql(
            $query,
            $this->getExpectedCastSql($sql)
        );
        $rows = $query->get();
        $this->assertCount(4, $rows);
    }

    public function testWhereNotInEmptyArrayProducesNoRowsAccordingToCurrentContract()
    {
        $query = Blog::whereNotIn('id', []);
        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs WHERE 1 = 0 AND test_blogs.deleted_at IS NULL';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => $expectedSQL, 
        ]);
        $rows = $query->get();
        $this->assertCount(0, $rows);
    }

    public function testWhereInSubquery()
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
    }

    public function testWhereNotInSubquery()
    {
        $query = Blog::whereNotIn('author_id', function ($query) {
            return $query->from(Author::class)
                ->select(['id'])
                ->where('name', 'John')
                ->get();
        });
        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs WHERE author_id NOT IN (SELECT id FROM test_authors WHERE name = ? AND test_authors.deleted_at IS NULL) AND test_blogs.deleted_at IS NULL';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => $expectedSQL,
        ]);
        $rows = $query->get();
        $this->assertCount(4, $rows);
    }
}

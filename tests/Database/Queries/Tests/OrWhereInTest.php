<?php

class OrWhereInTest extends DriverTestCase
{
    public function testOrWhereInMultipleIntegers()
    {
        $query = Blog::where('id', 4)->orWhereIn('id', [1, 2, 3]);
        $sql = 'SELECT test_blogs.* FROM test_blogs WHERE id = ? OR CAST(id AS %s) IN (?,?,?) AND test_blogs.deleted_at IS NULL';

        $this->assertExactSql(
            $query,
            $this->getExpectedCastSql($sql)
        );

        $rows = $query->get();
        $this->assertCount(4, $rows);
    }

    public function testOrWhereInSingleInteger()
    {
        $query = Blog::where('id', 4)->orWhereIn('id', [1]);
        $sql = 'SELECT test_blogs.* FROM test_blogs WHERE id = ? OR CAST(id AS %s) IN (?) AND test_blogs.deleted_at IS NULL';

        $this->assertExactSql(
            $query,
            $this->getExpectedCastSql($sql)
        );

        $rows = $query->get();
        $this->assertCount(2, $rows);
    }

    public function testOrWhereInStringValues()
    {
        $query = Blog::where('id', 1)->orWhereIn('status', ['published']);
        $sql = 'SELECT test_blogs.* FROM test_blogs WHERE id = ? OR CAST(status AS %s) IN (?) AND test_blogs.deleted_at IS NULL';

        $this->assertExactSql(
            $query,
            $this->getExpectedCastSql($sql)
        );

        $rows = $query->get();
        $this->assertCount(4, $rows);
    }

    public function testOrWhereInEmptyArray()
    {
        $query = Blog::where('id', 1)->orWhereIn('id', []);
        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs WHERE id = ? OR 1 = 0 AND test_blogs.deleted_at IS NULL';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => $expectedSQL,
        ]);

        $rows = $query->get();
        $this->assertCount(1, $rows);
    }

    public function testOrWhereInValuesAreBound()
    {
        $payload = '1 OR 1=1';
        $query = Blog::where('id', 4)->orWhereIn('id', [$payload]);
        $sql = 'SELECT test_blogs.* FROM test_blogs WHERE id = ? OR CAST(id AS %s) IN (?) AND test_blogs.deleted_at IS NULL';

        $this->assertExactSql(
            $query,
            $this->getExpectedCastSql($sql)
        );

        $rows = $query->get();
        $this->assertCount(1, $rows);
    }

    public function testOrWhereInSubquery()
    {
        $query = Blog::where('id', 1)
            ->orWhereIn('author_id', function ($query) {
                return $query->from(Author::class)
                    ->select(['id'])
                    ->where('name', 'John')
                    ->get();
            });

        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs WHERE id = ? OR author_id IN (SELECT id FROM test_authors WHERE name = ? AND test_authors.deleted_at IS NULL) AND test_blogs.deleted_at IS NULL';

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

<?php

class OrWhereNotInTest extends DriverTestCase
{
    public function testOrWhereNotInMultipleIntegers()
    {
        $query = Blog::where('id', 1)
            ->orWhereNotIn('id', [2, 3]);
        $sql = 'SELECT test_blogs.* FROM test_blogs WHERE id = ? OR CAST(id AS %s) NOT IN (?,?) AND test_blogs.deleted_at IS NULL';

        $this->assertExactSql(
            $query,
            $this->getExpectedCastSql($sql)
        );

        $rows = $query->get();
        $this->assertCount(5, $rows);
    }

    public function testOrWhereNotInEmptyArray()
    {
        $query = Blog::where('id', 1)
            ->orWhereNotIn('id', []);
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

    public function testOrWhereNotInSubquery()
    {
        $query = Blog::where('id', 1)
            ->orWhereNotIn('author_id', function ($query) {
                return $query->from(Author::class)
                    ->select(['id'])
                    ->where('name', 'John')
                    ->get();
            });

        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs WHERE id = ? OR author_id NOT IN (SELECT id FROM test_authors WHERE name = ? AND test_authors.deleted_at IS NULL) AND test_blogs.deleted_at IS NULL';

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

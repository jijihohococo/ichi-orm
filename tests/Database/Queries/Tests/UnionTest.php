<?php

class UnionTest extends DriverTestCase
{
    public function testUnionWithConditionsOnBothQueries()
    {
        $query = Blog::where('status', 'published')
            ->where('views', '>=', 100)
            ->union(function () {
                return Blog::where('status', 'draft')
                    ->where('views', '>=', 50)
                    ->toSQL()
                    ->get();
            });
        
        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs WHERE status = ? AND views >= ? AND test_blogs.deleted_at IS NULL UNION SELECT test_blogs.* FROM test_blogs WHERE status = ? AND views >= ? AND test_blogs.deleted_at IS NULL';
        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => $expectedSQL,
        ]);
        $rows = $query->get();
        $this->assertCount(5, $rows);
    }

    public function testUnionAllWithConditionsOnBothQueries()
    {
        $query = Blog::where('status', 'published')
            ->where('views', '>=', 100)
            ->unionAll(function () {
                return Blog::where('status', 'published')
                    ->where('views', '>=', 100)
                    ->toSQL()
                    ->get();
            });

        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs WHERE status = ? AND views >= ? AND test_blogs.deleted_at IS NULL UNION ALL SELECT test_blogs.* FROM test_blogs WHERE status = ? AND views >= ? AND test_blogs.deleted_at IS NULL';
        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => $expectedSQL,
        ]);
        $rows = $query->get();
        $this->assertCount(8, $rows);
    }

    public function testUnionInsideWhereInSubqueryFromReadme()
    {
        $query = Blog::whereIn('id', function ($query) {
            return $query->select(['id'])
                ->where('id', 1)
                ->union(function ($query) {
                    return $query
                        ->select(['id'])
                        ->where('id', 2)
                        ->get();
                })
                ->get();
        });
        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs WHERE id IN (SELECT id FROM test_blogs WHERE id = ? AND test_blogs.deleted_at IS NULL UNION (SELECT id FROM test_blogs WHERE id = ? AND test_blogs.deleted_at IS NULL)) AND test_blogs.deleted_at IS NULL';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => 'SELECT test_blogs.* FROM test_blogs WHERE id IN (SELECT id FROM test_blogs WHERE id = ? AND test_blogs.deleted_at IS NULL UNION SELECT id FROM test_blogs WHERE id = ? AND test_blogs.deleted_at IS NULL) AND test_blogs.deleted_at IS NULL',
        ]);
        $rows = $query->get();
        $this->assertCount(2, $rows);
    }

    public function testUnionInsideWhereInSubqueryWithAdditionalConditions()
    {
        $query = Blog::whereIn('id', function ($query) {
            return $query
                ->select(['id'])
                ->where('status', 'published')
                ->where('views', '>=', 100)
                ->union(function ($query) {
                    return $query
                        ->select(['id'])
                        ->where('status', 'draft')
                        ->where('views', '>=', 50)
                        ->get();
                })
                ->get();
        });
        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs WHERE id IN (SELECT id FROM test_blogs WHERE status = ? AND views >= ? AND test_blogs.deleted_at IS NULL UNION (SELECT id FROM test_blogs WHERE status = ? AND views >= ? AND test_blogs.deleted_at IS NULL)) AND test_blogs.deleted_at IS NULL';
        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => 'SELECT test_blogs.* FROM test_blogs WHERE id IN (SELECT id FROM test_blogs WHERE status = ? AND views >= ? AND test_blogs.deleted_at IS NULL UNION SELECT id FROM test_blogs WHERE status = ? AND views >= ? AND test_blogs.deleted_at IS NULL) AND test_blogs.deleted_at IS NULL',
        ]);
        $rows = $query->get();
        // Published blogs with views >= 100: 1, 2, 4, 6
        // Draft blogs with views >= 50: 3
        $this->assertCount(5, $rows);
    }

    public function testUnionInsideWhereInSubqueryWithOuterCondition()
    {
        $query = Blog::where('status', 'published')
            ->whereIn('id', function ($query) {
                return $query
                    ->select(['id'])
                    ->where('id', 1)
                    ->union(function ($query) {
                        return $query
                            ->select(['id'])
                            ->where('id', 2)
                            ->get();
                    })
                    ->get();
            });
        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs WHERE status = ? AND id IN (SELECT id FROM test_blogs WHERE id = ? AND test_blogs.deleted_at IS NULL UNION (SELECT id FROM test_blogs WHERE id = ? AND test_blogs.deleted_at IS NULL)) AND test_blogs.deleted_at IS NULL';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => 'SELECT test_blogs.* FROM test_blogs WHERE status = ? AND id IN (SELECT id FROM test_blogs WHERE id = ? AND test_blogs.deleted_at IS NULL UNION SELECT id FROM test_blogs WHERE id = ? AND test_blogs.deleted_at IS NULL) AND test_blogs.deleted_at IS NULL',
        ]);
        $rows = $query->get();
        $this->assertCount(2, $rows);
    }

    public function testUnionSubqueryCanContainMultipleWhereConditions()
    {
        $query = Blog::whereIn('id', function ($query) {
            return $query
                ->select(['id'])
                ->where('author_id', 1)
                ->where('status', 'published')
                ->where('views', '>', 100)
                ->union(function ($query) {
                    return $query
                        ->select(['id'])
                        ->where('author_id', 2)
                        ->where('status', 'published')
                        ->where('views', '>', 100)
                        ->get();
                })
                ->get();
        });
        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs WHERE id IN (SELECT id FROM test_blogs WHERE author_id = ? AND status = ? AND views > ? AND test_blogs.deleted_at IS NULL UNION (SELECT id FROM test_blogs WHERE author_id = ? AND status = ? AND views > ? AND test_blogs.deleted_at IS NULL)) AND test_blogs.deleted_at IS NULL';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => 'SELECT test_blogs.* FROM test_blogs WHERE id IN (SELECT id FROM test_blogs WHERE author_id = ? AND status = ? AND views > ? AND test_blogs.deleted_at IS NULL UNION SELECT id FROM test_blogs WHERE author_id = ? AND status = ? AND views > ? AND test_blogs.deleted_at IS NULL) AND test_blogs.deleted_at IS NULL',
        ]);
        $rows = $query->get();
        // id 2 (author 1, published, > 100) and id 4 (author 2, published, > 100).
        $this->assertCount(2, $rows);
    }

    public function testUnionSubqueryWithOrWhereCondition()
    {
        $query = Blog::whereIn('id', function ($query) {
            return $query
                ->select(['id'])
                ->where('status', 'published')
                ->orWhere('title', 'Database Design')
                ->union(function ($query) {
                    return $query
                        ->select(['id'])
                        ->where('title', 'PostgreSQL Guide')
                        ->get();
                })
                ->get();
        });
        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs WHERE id IN (SELECT id FROM test_blogs WHERE status = ? OR title = ? AND test_blogs.deleted_at IS NULL UNION (SELECT id FROM test_blogs WHERE title = ? AND test_blogs.deleted_at IS NULL)) AND test_blogs.deleted_at IS NULL';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => 'SELECT test_blogs.* FROM test_blogs WHERE id IN (SELECT id FROM test_blogs WHERE status = ? OR title = ? AND test_blogs.deleted_at IS NULL UNION SELECT id FROM test_blogs WHERE title = ? AND test_blogs.deleted_at IS NULL) AND test_blogs.deleted_at IS NULL',
        ]);
        $rows = $query->get();
        // First query returns published blogs plus Database Design.
        // UNION adds PostgreSQL Guide.
        $this->assertCount(6, $rows);
    }

    public function testMultipleUnions()
    {
        $query = Blog::whereIn('id', function ($query) {
            return $query
                ->select(['id'])
                ->where('id', 1)
                ->union(function ($query) {
                    return $query
                        ->select(['id'])
                        ->where('id', 2)
                        ->get();
                })
                ->union(function ($query) {
                    return $query
                        ->select(['id'])
                        ->where('id', 3)
                        ->get();
                })
                ->union(function ($query) {
                    return $query
                        ->select(['id'])
                        ->where('id', 4)
                        ->get();
                })
                ->get();
            });
        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs WHERE id IN (SELECT id FROM test_blogs WHERE id = ? AND test_blogs.deleted_at IS NULL UNION (SELECT id FROM test_blogs WHERE id = ? AND test_blogs.deleted_at IS NULL) UNION (SELECT id FROM test_blogs WHERE id = ? AND test_blogs.deleted_at IS NULL) UNION (SELECT id FROM test_blogs WHERE id = ? AND test_blogs.deleted_at IS NULL)) AND test_blogs.deleted_at IS NULL';
        
        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => 'SELECT test_blogs.* FROM test_blogs WHERE id IN (SELECT id FROM test_blogs WHERE id = ? AND test_blogs.deleted_at IS NULL UNION SELECT id FROM test_blogs WHERE id = ? AND test_blogs.deleted_at IS NULL UNION SELECT id FROM test_blogs WHERE id = ? AND test_blogs.deleted_at IS NULL UNION SELECT id FROM test_blogs WHERE id = ? AND test_blogs.deleted_at IS NULL) AND test_blogs.deleted_at IS NULL',
        ]);
        $rows = $query->get();
        $this->assertCount(4, $rows);
    }

    public function testMultipleUnionAll()
    {
        $query = Blog::whereIn('id', function ($query) {
            return $query
                ->select(['id'])
                ->where('id', 1)
                ->unionAll(function ($query) {
                    return $query
                        ->select(['id'])
                        ->where('id', 1)
                        ->get();
                })
                ->unionAll(function ($query) {
                    return $query
                        ->select(['id'])
                        ->where('id', 1)
                        ->get();
                })
                ->get();
        });
        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs WHERE id IN (SELECT id FROM test_blogs WHERE id = ? AND test_blogs.deleted_at IS NULL UNION ALL (SELECT id FROM test_blogs WHERE id = ? AND test_blogs.deleted_at IS NULL) UNION ALL (SELECT id FROM test_blogs WHERE id = ? AND test_blogs.deleted_at IS NULL)) AND test_blogs.deleted_at IS NULL';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => 'SELECT test_blogs.* FROM test_blogs WHERE id IN (SELECT id FROM test_blogs WHERE id = ? AND test_blogs.deleted_at IS NULL UNION ALL SELECT id FROM test_blogs WHERE id = ? AND test_blogs.deleted_at IS NULL UNION ALL SELECT id FROM test_blogs WHERE id = ? AND test_blogs.deleted_at IS NULL) AND test_blogs.deleted_at IS NULL',
        ]);
        $rows = $query->get();
        $this->assertCount(1, $rows);
    }
}

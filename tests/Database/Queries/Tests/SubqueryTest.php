<?php

class SubqueryTest extends DriverTestCase
{
    public function testWhereSubquery()
    {
        $query = Blog::where('id', '>', function ($query) {
            return $query->select(['id'])->where('title', 'PHP ORM')->get();
        });
        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs WHERE id > (SELECT id FROM test_blogs WHERE title = ? AND test_blogs.deleted_at IS NULL) AND test_blogs.deleted_at IS NULL';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => $expectedSQL,
        ]);
        $rows = $query->get();
        $this->assertCount(5, $rows);
    }

    public function testOrWhereSubquery()
    {
        $query = Blog::where('id', 1)
            ->orWhere('id', '=', function ($query) {
                return $query->select(['id'])->where('title', 'Laravel ORM')->get();
            });
        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs WHERE id = ? OR id = (SELECT id FROM test_blogs WHERE title = ? AND test_blogs.deleted_at IS NULL) AND test_blogs.deleted_at IS NULL';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => $expectedSQL,
        ]);
        $rows = $query->get();
        $this->assertCount(2, $rows);
    }

    public function testWhereInSameModelSubquery()
    {
        $query = Blog::whereIn('author_id', function ($query) {
            return $query->select(['id'])->where('id', 1)->get();
        });
        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs WHERE author_id IN (SELECT id FROM test_blogs WHERE id = ? AND test_blogs.deleted_at IS NULL) AND test_blogs.deleted_at IS NULL';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => $expectedSQL,
        ]);
        $rows = $query->get();
        $this->assertCount(2, $rows);
    }

    public function testWhereInDifferentModelSubqueryUsingFrom()
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

    public function testNestedWhereInSubquery()
    {
        $query = Blog::whereIn('author_id', function ($query) {
            return $query->from(Author::class)->whereIn('id', function ($nested) {
                return $nested->select(['id'])->where('name', 'John')->get();
            })->select(['id'])->get();
        });
        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs WHERE author_id IN (SELECT id FROM test_authors WHERE author_id IN (SELECT id FROM test_authors WHERE name = ? AND test_authors.deleted_at IS NULL) AND test_authors.deleted_at IS NULL) AND test_blogs.deleted_at IS NULL';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => $expectedSQL,
        ]);
        $rows = $query->get();
        $this->assertNotNull($rows);
    }

    public function testSubqueryLimit()
    {
        $query = Blog::whereIn('id', function ($query) {
            return $query->select(['id'])
                ->orderBy('id', 'ASC')
                ->limit(2)
                ->get();
        });
        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs WHERE id IN (SELECT * FROM (SELECT id FROM test_blogs WHERE test_blogs.deleted_at IS NULL ORDER BY id ASC LIMIT 2) AS l1) AND test_blogs.deleted_at IS NULL';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => 'SELECT test_blogs.* FROM test_blogs WHERE id IN (SELECT TOP 2 id FROM test_blogs WHERE test_blogs.deleted_at IS NULL ORDER BY id ASC) AND test_blogs.deleted_at IS NULL',
            'sqlite' => $expectedSQL,
        ]);
        $rows = $query->get();
        $this->assertCount(2, $rows);
    }

    public function testSubqueryOffset()
    {
        $query = Blog::whereIn('id', function ($query) {
            return $query->select(['id'])
                ->orderBy('id', 'ASC')
                ->limit(2)
                ->offset(2)
                ->get();
        });
        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs WHERE id IN (SELECT * FROM (SELECT id FROM test_blogs WHERE test_blogs.deleted_at IS NULL ORDER BY id ASC LIMIT 2 OFFSET 2) AS l1) AND test_blogs.deleted_at IS NULL';        

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => 'SELECT test_blogs.* FROM test_blogs WHERE id IN (SELECT * FROM (SELECT id FROM test_blogs WHERE test_blogs.deleted_at IS NULL ORDER BY id ASC OFFSET 2 ROWS FETCH NEXT 2 ROWS ONLY) AS l1) AND test_blogs.deleted_at IS NULL',
            'sqlite' => $expectedSQL,
        ]);
        $rows = $query->get();
        $this->assertCount(2, $rows);
    }

    public function testAddSelectSubquery()
    {
        $query = Blog::select(['id', 'author_id'])
            ->addSelect([
                'author_name' => function ($query) {
                    return $query->from(Author::class)
                        ->select(['name'])
                        ->whereColumn('test_authors.id', 'test_blogs.author_id')
                        ->limit(1)
                        ->get();
                },
            ])
            ->where('id', 1);
        $expectedSQL = 'SELECT id,author_id,(SELECT * FROM (SELECT name FROM test_authors WHERE test_authors.id = test_blogs.author_id AND test_authors.deleted_at IS NULL LIMIT 1) AS l1) AS author_name FROM test_blogs WHERE id = ? AND test_blogs.deleted_at IS NULL';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => 'SELECT id,author_id,(SELECT TOP 1 name FROM test_authors WHERE test_authors.id = test_blogs.author_id AND test_authors.deleted_at IS NULL) AS author_name FROM test_blogs WHERE id = ? AND test_blogs.deleted_at IS NULL',
            'sqlite' => $expectedSQL,
        ]);
        $rows = $query->get();
        $this->assertCount(1, $rows);
        $this->assertSame('John', $rows[0]->author_name);
    }

    public function testAddOnlySelectSubquery()
    {
        $query = Blog::addOnlySelect([
            'author_name' => function ($query) {
                return $query->from(Author::class)
                    ->select(['name'])
                    ->whereColumn('test_authors.id', 'test_blogs.author_id')
                    ->limit(1)
                    ->get();
            },
        ]);
        $expectedSQL = 'SELECT test_blogs.*,(SELECT * FROM (SELECT name FROM test_authors WHERE test_authors.id = test_blogs.author_id AND test_authors.deleted_at IS NULL LIMIT 1) AS l1) AS author_name FROM test_blogs WHERE test_blogs.deleted_at IS NULL';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => 'SELECT test_blogs.*,(SELECT TOP 1 name FROM test_authors WHERE test_authors.id = test_blogs.author_id AND test_authors.deleted_at IS NULL) AS author_name FROM test_blogs WHERE test_blogs.deleted_at IS NULL',
            'sqlite' => $expectedSQL,
        ]);
        $rows = $query->get();
        $this->assertCount(6, $rows);
        $this->assertSame('John', $rows[0]->author_name);
    }

    public function testSubqueryWithTrashed()
    {
        TestDatabase::execute(
            'UPDATE test_blogs SET deleted_at = ? WHERE id = ?',
            ['2026-01-01 00:00:00', 1]
        );

        $query = Blog::whereIn('id', function ($query) {
            return $query->withTrashed()->select(['id'])->where('id', 1)->get();
        })->withTrashed();
        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs WHERE id IN (SELECT id FROM test_blogs WHERE id = ?)';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => $expectedSQL,
        ]);
        $rows = $query->get();
        $this->assertCount(1, $rows);
    }

    public function testThreeLevelNestedWhereInSubquery()
    {
        $query = Blog::whereIn('author_id', function ($query) {
            return $query->from(Author::class)
                ->whereIn('id', function ($nested) {
                    return $nested->from(Author::class)
                        ->whereIn('id', function ($deep) {
                            return $deep->select(['id'])
                                ->where('name', 'John')
                                ->get();
                        })
                        ->select(['id'])
                        ->get();
                })
                ->select(['id'])
                ->get();
        });
        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs WHERE author_id IN (SELECT id FROM test_authors WHERE author_id IN (SELECT id FROM test_authors WHERE id IN (SELECT id FROM test_authors WHERE name = ? AND test_authors.deleted_at IS NULL) AND test_authors.deleted_at IS NULL) AND test_authors.deleted_at IS NULL) AND test_blogs.deleted_at IS NULL';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => $expectedSQL,
        ]);
        $rows = $query->get();
        $this->assertNotNull($rows);
    }

    public function testNestedWhereInWithMultipleConditions()
    {
        $query = Blog::whereIn('author_id', function ($query) {
            return $query->from(Author::class)
                ->where('name', 'John')
                ->whereIn('id', function ($nested) {
                    return $nested->select(['id'])
                        ->where('name', 'John')
                        ->get();
                })
                ->select(['id'])
                ->get();
        });
        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs WHERE author_id IN (SELECT id FROM test_authors WHERE name = ? AND author_id IN (SELECT id FROM test_authors WHERE name = ? AND test_authors.deleted_at IS NULL) AND test_authors.deleted_at IS NULL) AND test_blogs.deleted_at IS NULL';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => $expectedSQL,
        ]);
        $rows = $query->get();
        $this->assertNotNull($rows);
    }

    public function testNestedWhereInWithMultipleBindings()
    {
        $query = Blog::whereIn('author_id', function ($query) {
            return $query->from(Author::class)
                ->where('name', 'John')
                ->whereIn('id', function ($nested) {
                    return $nested->select(['id'])
                        ->where('name', 'John')
                        ->where('id', 1)
                        ->get();
                })
                ->select(['id'])
                ->get();
        });
        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs WHERE author_id IN (SELECT id FROM test_authors WHERE name = ? AND author_id IN (SELECT id FROM test_authors WHERE name = ? AND id = ? AND test_authors.deleted_at IS NULL) AND test_authors.deleted_at IS NULL) AND test_blogs.deleted_at IS NULL';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => $expectedSQL,
        ]);
        $rows = $query->get();
        $this->assertNotNull($rows);
    }

    public function testNestedWhereSubqueryInsideWhereInSubquery()
    {
        $query = Blog::whereIn('author_id', function ($query) {
            return $query->from(Author::class)
                ->where('id', '>', function ($nested) {
                    return $nested->select(['id'])
                        ->where('name', 'John')
                        ->get();
                })
                ->select(['id'])
                ->get();
        });
        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs WHERE author_id IN (SELECT id FROM test_authors WHERE id > (SELECT id FROM test_authors WHERE name = ? AND test_authors.deleted_at IS NULL) AND test_authors.deleted_at IS NULL) AND test_blogs.deleted_at IS NULL';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => $expectedSQL,
        ]);
        $rows = $query->get();
        $this->assertNotNull($rows);
    }

    public function testNestedWhereColumnSubqueryInsideWhereIn()
    {
        $query = Blog::whereIn('author_id', function ($query) {
            return $query->from(Author::class)
                ->whereColumn('test_authors.id', '>', function ($nested) {
                    return $nested->select(['id'])
                        ->where('name', 'John')
                        ->get();
                })
                ->select(['id'])
                ->get();
        });
        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs WHERE author_id IN (SELECT id FROM test_authors WHERE author_id > (SELECT id FROM test_authors WHERE name = ? AND test_authors.deleted_at IS NULL) AND test_authors.deleted_at IS NULL) AND test_blogs.deleted_at IS NULL';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => $expectedSQL,
        ]);
        $rows = $query->get();
        $this->assertNotNull($rows);
    }

    public function testWhereInSubqueryWithOrWhere()
    {
        $query = Blog::whereIn('author_id', function ($query) {
            return $query->from(Author::class)
                ->where('name', 'John')
                ->orWhere('name', 'Jane')
                ->select(['id'])
                ->get();
        });
        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs WHERE author_id IN (SELECT id FROM test_authors WHERE name = ? OR name = ? AND test_authors.deleted_at IS NULL) AND test_blogs.deleted_at IS NULL';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => $expectedSQL,
        ]);
        $rows = $query->get();
        $this->assertNotNull($rows);
    }

    public function testNestedWhereInSubqueryWithOrWhere()
    {
        $query = Blog::where('status', 'published')
            ->whereIn('author_id', function ($query) {
                return $query->from(Author::class)
                    ->where('name', 'John')
                    ->orWhere('name', 'Jane')
                    ->whereIn('id', function ($nested) {
                        return $nested->select(['id'])
                            ->where('name', 'John')
                            ->get();
                    })
                    ->select(['id'])
                    ->get();
            });
        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs WHERE status = ? AND author_id IN (SELECT id FROM test_authors WHERE name = ? OR name = ? AND author_id IN (SELECT id FROM test_authors WHERE name = ? AND test_authors.deleted_at IS NULL) AND test_authors.deleted_at IS NULL) AND test_blogs.deleted_at IS NULL';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => $expectedSQL,
        ]);
        $rows = $query->get();
        $this->assertNotNull($rows);
    }

    public function testMultipleNestedWhereInSubqueries()
    {
        $query = Blog::whereIn('author_id', function ($query) {
            return $query->from(Author::class)
                ->where('name', 'John')
                ->select(['id'])
                ->get();
        })
        ->whereIn('id', function ($query) {
            return $query->select(['id'])
                ->where('title', 'PHP ORM')
                ->get();
        });
        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs WHERE author_id IN (SELECT id FROM test_authors WHERE name = ? AND test_authors.deleted_at IS NULL) AND id IN (SELECT id FROM test_blogs WHERE title = ? AND test_blogs.deleted_at IS NULL) AND test_blogs.deleted_at IS NULL';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => $expectedSQL,
        ]);
        $rows = $query->get();
        $this->assertNotNull($rows);
    }

    public function testNestedWhereInAndWhereSubqueryTogether()
    {
        $query = Blog::whereIn('author_id', function ($query) {
            return $query->from(Author::class)
                ->whereIn('id', function ($nested) {
                    return $nested->select(['id'])
                        ->where('name', 'John')
                        ->get();
                })
                ->where('id', '>', function ($nested) {
                    return $nested->select(['id'])
                        ->where('name', 'Jane')
                        ->get();
                })
                ->select(['id'])
                ->get();
        });
        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs WHERE author_id IN (SELECT id FROM test_authors WHERE author_id IN (SELECT id FROM test_authors WHERE name = ? AND test_authors.deleted_at IS NULL) AND id > (SELECT id FROM test_authors WHERE name = ? AND test_authors.deleted_at IS NULL) AND test_authors.deleted_at IS NULL) AND test_blogs.deleted_at IS NULL';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => $expectedSQL,
        ]);
        $rows = $query->get();
        $this->assertNotNull($rows);
    }

    public function testNestedWhereNotInSubquery()
    {
        $query = Blog::whereNotIn('author_id', function ($query) {
            return $query->from(Author::class)
                ->whereNotIn('id', function ($nested) {
                    return $nested->select(['id'])
                        ->where('name', 'John')
                        ->get();
                })
                ->select(['id'])
                ->get();
        });
        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs WHERE author_id NOT IN (SELECT id FROM test_authors WHERE author_id NOT IN (SELECT id FROM test_authors WHERE name = ? AND test_authors.deleted_at IS NULL) AND test_authors.deleted_at IS NULL) AND test_blogs.deleted_at IS NULL';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => $expectedSQL,
        ]);
        $rows = $query->get();
        $this->assertNotNull($rows);
    }

    public function testNestedWhereInSubqueryWithLimitAndOffset()
    {
        $query = Blog::whereIn('author_id', function ($query) {
            return $query->from(Author::class)
                ->whereIn('id', function ($nested) {
                    return $nested->select(['id'])
                        ->where('name', 'John')
                        ->orderBy('id', 'ASC')
                        ->limit(1)
                        ->get();
                })
                ->select(['id'])
                ->orderBy('id', 'ASC')
                ->limit(2)
                ->offset(0)
                ->get();
        });
        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs WHERE author_id IN (SELECT * FROM (SELECT id FROM test_authors WHERE author_id IN (SELECT * FROM (SELECT id FROM test_authors WHERE name = ? AND test_authors.deleted_at IS NULL ORDER BY id ASC LIMIT 1) AS l1) AND test_authors.deleted_at IS NULL ORDER BY id ASC LIMIT 2 OFFSET 0) AS l2) AND test_blogs.deleted_at IS NULL';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => 'SELECT test_blogs.* FROM test_blogs WHERE author_id IN (SELECT * FROM (SELECT id FROM test_authors WHERE author_id IN (SELECT TOP 1 id FROM test_authors WHERE name = ? AND test_authors.deleted_at IS NULL ORDER BY id ASC) AND test_authors.deleted_at IS NULL ORDER BY id ASC OFFSET 0 ROWS FETCH NEXT 2 ROWS ONLY) AS l2) AND test_blogs.deleted_at IS NULL',
            'sqlite' => $expectedSQL,
        ]);
        $rows = $query->get();
        $this->assertNotNull($rows);
    }
}

<?php

class JoinTest extends DriverTestCase
{
    public function testInnerJoin()
    {
        $query = Blog::innerJoin(
            'test_authors',
            'test_blogs.author_id',
            '=',
            'test_authors.id'
        )->select([
            'test_blogs.id',
            'test_authors.name'
        ]);
        $expectedSQL = 'SELECT test_blogs.id,test_authors.name FROM test_blogs INNER JOIN test_authors ON test_authors.id = test_blogs.author_id WHERE test_blogs.deleted_at IS NULL';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => $expectedSQL,
        ]);

        $rows = $query->get();

        $this->assertCount(6, $rows);
        $this->assertSame('John', $rows[0]->name);
    }

    public function testLeftJoin()
    {
        $query = Author::leftJoin(
            'test_blogs',
            'test_authors.id',
            '=',
            'test_blogs.author_id'
        );
        $expectedSQL = 'SELECT test_authors.* FROM test_authors LEFT JOIN test_blogs ON test_blogs.author_id = test_authors.id WHERE test_authors.deleted_at IS NULL';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => $expectedSQL,
        ]);

        $rows = $query->get();

        $this->assertCount(6, $rows);
    }

    public function testRightJoin()
    {
        $query = Author::rightJoin(
            'test_blogs',
            'test_authors.id',
            '=',
            'test_blogs.author_id'
        );
        $expectedSQL = 'SELECT test_authors.* FROM test_authors RIGHT JOIN test_blogs ON test_blogs.author_id = test_authors.id WHERE test_authors.deleted_at IS NULL';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => $expectedSQL,
        ]);

        $rows = $query->get();

        $this->assertCount(6, $rows);
    }

    public function testJoinWithWhere()
    {
        $query = Blog::innerJoin(
            'test_authors',
            'test_blogs.author_id',
            '=',
            'test_authors.id'
        )->where('test_authors.name', 'John');
        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs INNER JOIN test_authors ON test_authors.id = test_blogs.author_id WHERE test_authors.name = ? AND test_blogs.deleted_at IS NULL';

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

    public function testJoinWithWhereColumn()
    {
        $query = Blog::innerJoin(
            'test_authors',
            'test_blogs.author_id',
            '=',
            'test_authors.id'
        )->whereColumn(
            'test_blogs.author_id',
            'test_authors.id'
        );
        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs INNER JOIN test_authors ON test_authors.id = test_blogs.author_id WHERE test_blogs.author_id = test_authors.id AND test_blogs.deleted_at IS NULL';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => $expectedSQL,
        ]);

        $rows = $query->get();

        $this->assertCount(6, $rows);
    }
}

<?php

class WhereTest extends DriverTestCase
{
    public function testWhereEqual()
    {
        $query = Blog::where('status', 'published');
        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs WHERE status = ? AND test_blogs.deleted_at IS NULL';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => $expectedSQL,
        ]);

        $rows = $query->get();
        $this->assertCount(4, $rows);
    }

    public function testWhereExplicitEqual()
    {
        $query = Blog::where('status', '=', 'published');
        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs WHERE status = ? AND test_blogs.deleted_at IS NULL';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => $expectedSQL,
        ]);

        $this->assertCount(4, $query->get());
    }

    public function testWhereNotEqual()
    {
        $query = Blog::where('status', '!=', 'published');
        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs WHERE status != ? AND test_blogs.deleted_at IS NULL';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => $expectedSQL,
        ]);

        $this->assertCount(2, $query->get());
    }

    public function testWhereGreaterThan()
    {
        $query = Blog::where('views', '>', 100);
        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs WHERE views > ? AND test_blogs.deleted_at IS NULL';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => $expectedSQL,
        ]);

        $this->assertCount(3, $query->get());
    }

    public function testWhereGreaterThanOrEqual()
    {
        $query = Blog::where('views', '>=', 100);
        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs WHERE views >= ? AND test_blogs.deleted_at IS NULL';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => $expectedSQL,
        ]);

        $this->assertCount(4, $query->get());
    }

    public function testWhereLessThan()
    {
        $query = Blog::where('views', '<', 100);
        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs WHERE views < ? AND test_blogs.deleted_at IS NULL';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => $expectedSQL,
        ]);

        $this->assertCount(2, $query->get());
    }

    public function testWhereNullUsesIsNull()
    {
        $query = Blog::where('content', null);
        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs WHERE content IS NULL AND test_blogs.deleted_at IS NULL';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => $expectedSQL,
        ]);

        $this->assertCount(1, $query->get());
    }

    public function testWhereNotNullUsesIsNotNull()
    {
        $query = Blog::where('content', '!=', null);
        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs WHERE content IS NOT NULL AND test_blogs.deleted_at IS NULL';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => $expectedSQL,
        ]);

        $this->assertCount(5, $query->get());
    }

    public function testMultipleWhereConditions()
    {
        $query = Blog::where('status', 'published')->where('views', '>', 100);
        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs WHERE status = ? AND views > ? AND test_blogs.deleted_at IS NULL';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => $expectedSQL,
        ]);

        $this->assertCount(3, $query->get());
    }

    public function testArrayParameterNormalization()
    {
        $query = Blog::where(['status', 'published']);
        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs WHERE status = ? AND test_blogs.deleted_at IS NULL';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => $expectedSQL,
        ]);

        $this->assertCount(4, $query->get());
    }

    public function testWhereValueWithSqlInjectionPayloadIsTreatedAsValue()
    {
        $payload = "' OR 1=1 --";
        $query = Blog::where('title', $payload);
        $expectedSQL = 'SELECT test_blogs.* FROM test_blogs WHERE title = ? AND test_blogs.deleted_at IS NULL';

        $this->assertExactSql($query, [
            'mysql' => $expectedSQL,
            'pgsql' => $expectedSQL,
            'sqlsrv' => $expectedSQL,
            'sqlite' => $expectedSQL,
        ]);

        $this->assertCount(0, $query->get());
    }
}

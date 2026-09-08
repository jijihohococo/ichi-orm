<?php

use IchiORMTests\Fixtures\Blog;

class WhereTest extends DriverTestCase
{
    public function testWhereEqual()
    {
        $this->assertCount(4, Blog::where('status', 'published')->get());
    }

    public function testWhereExplicitEqual()
    {
        $this->assertCount(4, Blog::where('status', '=', 'published')->get());
    }

    public function testWhereNotEqual()
    {
        $this->assertCount(2, Blog::where('status', '!=', 'published')->get());
    }

    public function testWhereNotEqualAlternateOperator()
    {
        $this->assertCount(2, Blog::where('status', '<>', 'published')->get());
    }

    public function testWhereGreaterThan()
    {
        $this->assertCount(3, Blog::where('views', '>', 100)->get());
    }

    public function testWhereGreaterThanOrEqual()
    {
        $this->assertCount(4, Blog::where('views', '>=', 100)->get());
    }

    public function testWhereLessThan()
    {
        $this->assertCount(2, Blog::where('views', '<', 100)->get());
    }

    public function testWhereLessThanOrEqual()
    {
        $this->assertCount(3, Blog::where('views', '<=', 100)->get());
    }

    public function testWhereLike()
    {
        $this->assertCount(2, Blog::where('title', 'like', '%ORM%')->get());
    }

    public function testWhereNotLike()
    {
        $this->assertCount(4, Blog::where('title', 'not like', '%ORM%')->get());
    }

    public function testWhereNullUsesIsNull()
    {
        $this->assertCount(1, Blog::where('content', null)->get());
    }

    public function testWhereNotNullUsesIsNotNull()
    {
        $this->assertCount(5, Blog::where('content', '!=', null)->get());
    }

    public function testMultipleWhereConditions()
    {
        $rows = Blog::where('status', 'published')
            ->where('views', '>', 100)
            ->get();

        $this->assertCount(2, $rows);
    }

    public function testArrayParameterNormalization()
    {
        $rows = Blog::where(['status', 'published'])->get();
        $this->assertCount(3, $rows);
    }

    public function testWhereValueWithSqlInjectionPayloadIsTreatedAsValue()
    {
        $payload = "' OR 1=1 --";
        $rows = Blog::where('title', $payload)->get();
        $this->assertCount(0, $rows);
    }
}

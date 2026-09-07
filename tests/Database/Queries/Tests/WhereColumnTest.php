<?php

use IchiORMTests\Fixtures\Blog;

class WhereColumnTest extends DriverTestCase
{
    public function testWhereColumnSameValues()
    {
        $rows = Blog::whereColumn('test_blogs.author_id', 'test_blogs.author_id')->get();
        $this->assertCount(6, $rows);
    }

    public function testWhereColumnGreaterThan()
    {
        $rows = Blog::whereColumn('test_blogs.id', 'test_blogs.author_id')->get();
        $this->assertCount(3, $rows);
    }

    public function testWhereColumnQualifiedNames()
    {
        $rows = Blog::whereColumn('test_blogs.author_id', 'test_blogs.id')->get();
        $this->assertCount(3, $rows);
    }

    public function testWhereColumnSubquery()
    {
        $rows = Blog::whereColumn('test_blogs.id', '>', function ($query) {
            return $query->select(['id'])
                ->where('title', 'PHP ORM')
                ->get();
        })->get();

        $this->assertNotNull($rows);
    }
}

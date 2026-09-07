<?php

use IchiORMTests\Fixtures\Blog;

class OrWhereTest extends DriverTestCase
{
    public function testOrWhere()
    {
        $rows = Blog::where('status', 'draft')
            ->orWhere('status', 'published')
            ->get();

        $this->assertCount(6, $rows);
    }

    public function testOrWhereWithOperator()
    {
        $rows = Blog::where('views', '<', 20)
            ->orWhere('views', '>', 350)
            ->get();

        $this->assertCount(2, $rows);
    }

    public function testMultipleOrWhere()
    {
        $rows = Blog::where('title', 'PHP ORM')
            ->orWhere('title', 'Laravel ORM')
            ->orWhere('title', 'Architecture')
            ->get();

        $this->assertCount(3, $rows);
    }
}

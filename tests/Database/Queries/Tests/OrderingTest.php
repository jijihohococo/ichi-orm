<?php

use IchiORMTests\Fixtures\Blog;

class OrderingTest extends DriverTestCase
{
    public function testOrderAscending()
    {
        $rows = Blog::orderBy('views', 'ASC')->get();
        $this->assertSame(10, (int) $rows[0]->views);
        $this->assertSame(400, (int) $rows[count($rows) - 1]->views);
    }

    public function testOrderDescending()
    {
        $rows = Blog::orderBy('views', 'DESC')->get();
        $this->assertSame(400, (int) $rows[0]->views);
        $this->assertSame(10, (int) $rows[count($rows) - 1]->views);
    }

    public function testLatest()
    {
        $rows = Blog::latest('views')->get();
        $this->assertSame(400, (int) $rows[0]->views);
    }

    public function testInvalidSortTextMustNotBeAcceptedByFutureHardening()
    {
        $sql = Blog::orderBy('views', 'DESC')->toSQL()->get();
        $this->assertStringContains('ORDER BY views DESC', $sql);
    }
}

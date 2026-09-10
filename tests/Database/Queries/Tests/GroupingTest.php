<?php

use IchiORMTests\Fixtures\Blog;

class GroupingTest extends DriverTestCase
{
    public function testGroupBy()
    {
        $rows = Blog::select(['author_id'])
            ->groupBy('author_id')
            ->get();

        $this->assertCount(3, $rows);
    }

    public function testGroupByAndHaving()
    {
        $rows = Blog::select(['author_id'])
            ->groupBy('author_id')
            ->having('COUNT(id)', '>', 1)
            ->get();

        $this->assertNotEmpty($rows);
    }

    public function testMultipleHavingConditions()
    {
        $rows = Blog::select(['author_id'])
            ->groupBy('author_id')
            ->having('COUNT(id)', '>', 0)
            ->having('COUNT(id)', '<', 10)
            ->get();

        $this->assertNotEmpty($rows);
    }

    public function testHavingNumericLiteral()
    {
        $rows = Blog::select(['author_id'])
            ->groupBy('author_id')
            ->having('COUNT(id)', '>=', 2)
            ->get();

        $this->assertNotEmpty($rows);
    }
}

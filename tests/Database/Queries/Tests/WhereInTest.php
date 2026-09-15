<?php

use IchiORMTests\Fixtures\Blog;
use IchiORMTests\Fixtures\Author;

class WhereInTest extends DriverTestCase
{
    public function testWhereInMultipleIntegers()
    {
        $rows = Blog::whereIn('id', [1, 2, 3])->get();
        $this->assertCount(3, $rows);
    }

    public function testWhereInSingleInteger()
    {
        $rows = Blog::whereIn('id', [1])->get();
        $this->assertCount(1, $rows);
    }

    public function testWhereInStringValues()
    {
        $rows = Blog::whereIn('status', ['published'])->get();
        $this->assertCount(4, $rows);
    }

    public function testWhereInEmptyArrayProducesNoRows()
    {
        $rows = Blog::whereIn('id', [])->get();
        $this->assertCount(0, $rows);
    }

    public function testWhereInValuesAreBound()
    {
        $payload = "1 OR 1=1";
        $rows = Blog::whereIn('id', [$payload])->get();
        $this->assertCount(0, $rows);
    }

    public function testWhereNotInMultipleIntegers()
    {
        $rows = Blog::whereNotIn('id', [1, 2])->get();
        $this->assertCount(4, $rows);
    }

    public function testWhereNotInEmptyArrayProducesNoRowsAccordingToCurrentContract()
    {
        $rows = Blog::whereNotIn('id', [])->get();
        $this->assertCount(0, $rows);
    }

    public function testWhereInSubquery()
    {
        $rows = Blog::whereIn('author_id', function ($query) {
            return $query->from(Author::class)
                ->select(['id'])
                ->where('name', 'John')
                ->get();
        })->get();

        $this->assertCount(2, $rows);
    }

    public function testWhereNotInSubquery()
    {
        $rows = Blog::whereNotIn('author_id', function ($query) {
            return $query->from(Author::class)
                ->select(['id'])
                ->where('name', 'John')
                ->get();
        })->get();

        $this->assertCount(4, $rows);
    }
}

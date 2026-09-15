<?php

use IchiORMTests\Fixtures\Author;
use IchiORMTests\Fixtures\Blog;

class JoinTest extends DriverTestCase
{
    public function testInnerJoin()
    {
        $rows = Blog::innerJoin(
            'test_authors',
            'test_blogs.author_id',
            '=',
            'test_authors.id'
        )->select([
            'test_blogs.id',
            'test_authors.name'
        ])->get();

        $this->assertCount(6, $rows);
        $this->assertSame('John', $rows[0]->name);
    }

    public function testLeftJoin()
    {
        $rows = Author::leftJoin(
            'test_blogs',
            'test_authors.id',
            '=',
            'test_blogs.author_id'
        )->get();

        $this->assertCount(6, $rows);
    }

    public function testRightJoin()
    {
        $rows = Author::rightJoin(
            'test_blogs',
            'test_authors.id',
            '=',
            'test_blogs.author_id'
        )->get();

        $this->assertCount(6, $rows);
    }

    public function testJoinWithWhere()
    {
        $rows = Blog::innerJoin(
            'test_authors',
            'test_blogs.author_id',
            '=',
            'test_authors.id'
        )->where('test_authors.name', 'John')->get();

        $this->assertCount(2, $rows);
    }

    public function testJoinWithWhereColumn()
    {
        $rows = Blog::innerJoin(
            'test_authors',
            'test_blogs.author_id',
            '=',
            'test_authors.id'
        )->whereColumn(
            'test_blogs.author_id',
            'test_authors.id'
        )->get();

        $this->assertCount(6, $rows);
    }
}

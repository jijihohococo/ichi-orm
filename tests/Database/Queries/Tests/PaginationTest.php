<?php

use IchiORMTests\Fixtures\Blog;
use JiJiHoHoCoCo\IchiORM\Pagination\ArrayPaginate;

class PaginationTest extends DriverTestCase
{
    public function setUp()
    {
        parent::setUp();
        $_SERVER['REQUEST_URI'] = '/test.php';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_GET = [];
    }

    public function testDatabasePaginationPageOne()
    {
        $result = Blog::orderBy('id', 'ASC')->paginate(2);

        $this->assertIsArray($result);
        $this->assertSame(1, $result['current_page']);
        $this->assertSame(2, $result['per_page']);
        $this->assertSame(6, $result['total']);
        $this->assertCount(2, $result['data']);
        $this->assertSame(1, (int) $result['data'][0]->id);
        $this->assertSame(2, (int) $result['data'][1]->id);
    }

    public function testDatabasePaginationPageTwo()
    {
        $_GET['page'] = 2;
        $result = Blog::orderBy('id', 'ASC')->paginate(2);

        $this->assertSame(2, $result['current_page']);
        $this->assertCount(2, $result['data']);
        $this->assertSame(3, (int) $result['data'][0]->id);
        $this->assertSame(4, (int) $result['data'][1]->id);
    }

    public function testArrayPaginationPageOne()
    {
        $result = (new ArrayPaginate())->paginate(['A', 'B', 'C', 'D', 'E'], 2);

        $this->assertSame(1, $result['current_page']);
        $this->assertSame(5, $result['total']);
        $this->assertEquals(['A', 'B'], $result['data']);
    }

    public function testArrayPaginationPageTwo()
    {
        $_GET['page'] = 2;
        $result = (new ArrayPaginate())->paginate(['A', 'B', 'C', 'D', 'E'], 2);

        $this->assertSame(2, $result['current_page']);
        $this->assertEquals(['C', 'D'], $result['data']);
    }
}

<?php

use IchiORMTests\Fixtures\Author;
use IchiORMTests\Fixtures\Blog;
use JiJiHoHoCoCo\IchiORM\QueryBuilder\QueryBuilder;

class CoreQueryTest extends DriverTestCase
{
    public function testQueryBuilderCalledClass()
    {
        $builder = new QueryBuilder();
        $builder->setCalledClass(Blog::class);

        $this->assertSame(Blog::class, $builder->getCalledClass());
        $this->assertSame('test_blogs', $builder->getTable());
        $this->assertSame('id', $builder->getID());
        $this->assertTrue($builder->autoIncrementId());
    }

    public function testGetReturnsModels()
    {
        $rows = Blog::get();
        $this->assertCount(6, $rows);
        $this->assertInstanceOf(Blog::class, $rows[0]);
    }

    public function testToArrayReturnsAssociativeArrays()
    {
        $rows = Blog::where('id', 1)->toArray();
        $this->assertCount(1, $rows);
        $this->assertIsArray($rows[0]);
        $this->assertSame('PHP ORM', $rows[0]['title']);
    }

    public function testSelectSingleColumn()
    {
        $row = Blog::select(['title'])->where('id', 1)->get()[0];
        $this->assertSame('PHP ORM', $row->title);
        $this->assertFalse(isset($row->content));
    }

    public function testSelectMultipleColumns()
    {
        $row = Blog::select(['id', 'title', 'status'])->where('id', 1)->get()[0];
        $this->assertSame(1, (int) $row->id);
        $this->assertSame('PHP ORM', $row->title);
        $this->assertSame('published', $row->status);
    }

    public function testQualifiedSelectColumns()
    {
        $row = Blog::select(['test_blogs.id', 'test_blogs.title'])->where('test_blogs.id', 1)->get()[0];
        $this->assertSame(1, (int) $row->id);
        $this->assertSame('PHP ORM', $row->title);
    }

    public function testToSQLReturnsGeneratedSql()
    {
        $sql = Blog::where('status', 'published')->toSQL()->get();
        $this->assertIsString($sql);
        $this->assertStringContains('SELECT', $sql);
        $this->assertStringContains('WHERE', $sql);
        $this->assertStringContains('?', $sql);
    }

    public function testLatestDefaultsToPrimaryKey()
    {
        $rows = Blog::latest()->get();
        $this->assertCount(6, $rows);
        $this->assertSame(6, (int) $rows[0]->id);
    }

    public function testLatestAcceptsField()
    {
        $rows = Blog::latest('views')->get();
        $this->assertSame(400, (int) $rows[0]->views);
    }

    public function testLimit()
    {
        $rows = Blog::limit(2)->get();
        $this->assertCount(2, $rows);
    }

    public function testOffset()
    {
        $rows = Blog::orderBy('id', 'ASC')->limit(2)->offset(2)->get();
        $this->assertCount(2, $rows);
        $this->assertSame(3, (int) $rows[0]->id);
    }

    public function testFromIsAvailableInsideSubquery()
    {
        $rows = Blog::whereIn('author_id', function ($query) {
            return $query->from(Author::class)
                ->select(['id'])
                ->where('name', 'John')
                ->get();
        })->get();

        $this->assertCount(2, $rows);
    }
}

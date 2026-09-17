<?php

class QueryBuilderReuseTest extends DriverTestCase
{
    public function testWhereQueryIsReusable()
    {
        $blog = Blog::where('status', 'published');

        $first = $blog->where('id', 1)->get();
        $second = $blog->where('id', 2)->get();

        $this->assertCount(1, $first);
        $this->assertCount(1, $second);
        $this->assertSame(1, (int) $first[0]->id);
        $this->assertSame(2, (int) $second[0]->id);
    }

    public function testWhereColumnQueryIsReusable()
    {
        $blog = Blog::where('status', 'published');

        $first = $blog->whereColumn(
            'test_blogs.id',
            '>',
            'test_blogs.author_id'
        )->get();

        $second = $blog->whereColumn(
            'test_blogs.id',
            '<=',
            'test_blogs.author_id'
        )->get();

        $this->assertNotNull($first);
        $this->assertNotNull($second);
    }

    public function testWhereInQueryIsReusable()
    {
        $blog = Blog::where('status', 'published');

        $first = $blog->whereIn('id', [1, 2])->get();
        $second = $blog->whereIn('id', [3, 4])->get();

        $this->assertCount(2, $first);
        $this->assertCount(1, $second);
    }

    public function testWhereNotInQueryIsReusable()
    {
        $blog = Blog::where('status', 'published');

        $first = $blog->whereNotIn('id', [1, 2])->get();
        $second = $blog->whereNotIn('id', [3, 4])->get();

        $this->assertNotNull($first);
        $this->assertNotNull($second);
    }

    public function testOrWhereQueryIsReusable()
    {
        $blog = Blog::where('status', 'published');

        $first = $blog->orWhere('id', 1)->get();
        $second = $blog->orWhere('id', 2)->get();

        $this->assertNotNull($first);
        $this->assertNotNull($second);
    }

    public function testOrderByQueryIsReusable()
    {
        $blog = Blog::where('status', 'published');

        $first = $blog->orderBy('id', 'ASC')->get();
        $second = $blog->orderBy('title', 'ASC')->get();

        $this->assertCount(4, $first);
        $this->assertCount(4, $second);
    }

    public function testLatestQueryIsReusable()
    {
        $blog = Blog::where('status', 'published');

        $first = $blog->latest('id')->get();
        $second = $blog->latest('views')->get();

        $this->assertCount(4, $first);
        $this->assertCount(4, $second);
    }

    public function testSelectQueryIsReusable()
    {
        $blog = Blog::where('status', 'published');

        $first = $blog->select(['id'])->get();
        $second = $blog->select(['title'])->get();

        $this->assertTrue(isset($first[0]->id));
        $this->assertFalse(isset($first[0]->title));

        $this->assertTrue(isset($second[0]->title));
        $this->assertFalse(isset($second[0]->id));
    }

    public function testLimitQueryIsReusable()
    {
        $blog = Blog::where('status', 'published');

        $first = $blog->limit(1)->get();
        $second = $blog->limit(2)->get();

        $this->assertCount(1, $first);
        $this->assertCount(2, $second);
    }

    public function testOffsetQueryIsReusable()
    {
        $blog = Blog::where('status', 'published')
            ->orderBy('id', 'ASC');

        $first = $blog->offset(0)->limit(1)->get();
        $second = $blog->offset(1)->limit(1)->get();

        $this->assertSame(1, (int) $first[0]->id);
        $this->assertSame(2, (int) $second[0]->id);
    }

    public function testGroupByQueryIsReusable()
    {
        $blog = Blog::where('status', 'published');

        $first = $blog->select(['status'])->groupBy('status')->get();
        $second = $blog->select(['author_id'])->groupBy('author_id')->get();

        $this->assertNotNull($first);
        $this->assertNotNull($second);
    }

    public function testMultipleGroupByQueryIsReusable()
    {
        $blog = Blog::where('status', 'published');

        $first = $blog->select(['status', 'author_id'])->groupBy('status', 'author_id')->get();

        $second = $blog->select(['author_id'])->groupBy('author_id')->get();

        $this->assertNotNull($first);
        $this->assertNotNull($second);
    }

    public function testHavingQueryIsReusable()
    {
        $blog = Blog::select(['author_id'])
            ->groupBy('author_id');

        $first = $blog->having('author_id', '>', 0)->get();
        $second = $blog->having('author_id', '=', 1)->get();

        $this->assertNotNull($first);
        $this->assertNotNull($second);
    }

    public function testWithTrashedQueryIsReusable()
    {
        $blog = Blog::where('status', 'published');

        $first = $blog->withTrashed()->get();
        $second = $blog->get();

        $this->assertNotNull($first);
        $this->assertNotNull($second);
    }

    public function testSubqueryQueryIsReusable()
    {
        $blog = Blog::whereIn('author_id', function ($query) {
            return $query
                ->from(Author::class)
                ->select(['id'])
                ->where('name', 'John')
                ->get();
        });

        $first = $blog->get();
        $second = $blog->get();

        $this->assertCount(2, $first);
        $this->assertCount(2, $second);
    }

    public function testUnionQueryIsReusable()
    {
        $base = Blog::where('status', 'published');

        $first = $base
            ->union(function ($query) {
                return $query
                    ->select(['id'])
                    ->where('id', 5)
                    ->get();
            })
            ->get();

        $second = $base
            ->union(function ($query) {
                return $query
                    ->select(['id'])
                    ->where('id', 6)
                    ->get();
            })
            ->get();

        $this->assertNotNull($first);
        $this->assertNotNull($second);
    }

    public function testUnionAllQueryIsReusable()
    {
        $base = Blog::where('status', 'published');

        $first = $base
            ->unionAll(function ($query) {
                return $query
                    ->where('id', 5)
                    ->get();
            })
            ->get();

        $second = $base
            ->unionAll(function ($query) {
                return $query
                    ->where('id', 6)
                    ->get();
            })
            ->get();

        $this->assertNotNull($first);
        $this->assertNotNull($second);
    }

    public function testToSQLDoesNotDestroyQuery()
    {
        $blog = Blog::where('status', 'published');

        $sql1 = $blog->orderBy('id')->toSQL()->get();
        $sql2 = $blog->orderBy('title')->toSQL()->get();

        $this->assertTrue(stripos($sql1, 'ORDER BY id') !== false);
        $this->assertTrue(stripos($sql2, 'ORDER BY title') !== false);
    }

    public function testSameBaseQueryCanBeReused()
    {
        $blog = Blog::where('title', 'PHP ORM');

        $first = $blog->orderBy('id', 'ASC')->get();
        $second = $blog->orderBy('title', 'ASC')->get();

        $this->assertCount(1, $first);
        $this->assertCount(1, $second);

        $this->assertSame(1, (int) $first[0]->id);
        $this->assertSame('PHP ORM', $second[0]->title);
    }
}

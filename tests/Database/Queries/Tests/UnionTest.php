<?php

use IchiORMTests\Fixtures\Blog;

class UnionTest extends DriverTestCase
{
    public function testUnionWithConditionsOnBothQueries()
    {
        $rows = Blog::where('status', 'published')
            ->where('views', '>=', 100)
            ->union(function () {
                return Blog::where('status', 'draft')
                    ->where('views', '>=', 50)
                    ->toSQL()
                    ->get();
            })
            ->get();

        $this->assertCount(5, $rows);
    }

    public function testUnionAllWithConditionsOnBothQueries()
    {
        $rows = Blog::where('status', 'published')
            ->where('views', '>=', 100)
            ->unionAll(function () {
                return Blog::where('status', 'published')
                    ->where('views', '>=', 100)
                    ->toSQL()
                    ->get();
            })
            ->get();

        $this->assertCount(8, $rows);
    }

    public function testUnionInsideWhereInSubqueryFromReadme()
    {
        $rows = Blog::whereIn('id', function ($query) {
            return $query->select(['id'])
                ->where('id', 1)
                ->union(function ($query) {
                    return $query
                        ->select(['id'])
                        ->where('id', 2)
                        ->get();
                })
                ->get();
        })->get();

        $this->assertCount(2, $rows);
    }

    public function testUnionInsideWhereInSubqueryWithAdditionalConditions()
    {
        $rows = Blog::whereIn('id', function ($query) {
            return $query
                ->select(['id'])
                ->where('status', 'published')
                ->where('views', '>=', 100)
                ->union(function ($query) {
                    return $query
                        ->select(['id'])
                        ->where('status', 'draft')
                        ->where('views', '>=', 50)
                        ->get();
                })
                ->get();
        })->get();

        // Published blogs with views >= 100: 1, 2, 4, 6
        // Draft blogs with views >= 50: 3
        $this->assertCount(5, $rows);
    }

    public function testUnionInsideWhereInSubqueryWithOuterCondition()
    {
        $rows = Blog::where('status', 'published')
            ->whereIn('id', function ($query) {
                return $query
                    ->select(['id'])
                    ->where('id', 1)
                    ->union(function ($query) {
                        return $query
                            ->select(['id'])
                            ->where('id', 2)
                            ->get();
                    })
                    ->get();
            })
            ->get();

        $this->assertCount(2, $rows);
    }

    public function testUnionSubqueryCanContainMultipleWhereConditions()
    {
        $rows = Blog::whereIn('id', function ($query) {
            return $query
                ->select(['id'])
                ->where('author_id', 1)
                ->where('status', 'published')
                ->where('views', '>', 100)
                ->union(function ($query) {
                    return $query
                        ->select(['id'])
                        ->where('author_id', 2)
                        ->where('status', 'published')
                        ->where('views', '>', 100)
                        ->get();
                })
                ->get();
        })->get();

        // id 2 (author 1, published, > 100) and id 4 (author 2, published, > 100).
        $this->assertCount(2, $rows);
    }

    public function testUnionSubqueryWithOrWhereCondition()
    {
        $rows = Blog::whereIn('id', function ($query) {
            return $query
                ->select(['id'])
                ->where('status', 'published')
                ->orWhere('title', 'Database Design')
                ->union(function ($query) {
                    return $query
                        ->select(['id'])
                        ->where('title', 'PostgreSQL Guide')
                        ->get();
                })
                ->get();
        })->get();

        // First query returns published blogs plus Database Design.
        // UNION adds PostgreSQL Guide.
        $this->assertCount(6, $rows);
    }

    public function testMultipleUnions()
    {
        $rows = Blog::whereIn('id', function ($query) {
            return $query
                ->select(['id'])
                ->where('id', 1)
                ->union(function ($query) {
                    return $query
                        ->select(['id'])
                        ->where('id', 2)
                        ->get();
                })
                ->union(function ($query) {
                    return $query
                        ->select(['id'])
                        ->where('id', 3)
                        ->get();
                })
                ->union(function ($query) {
                    return $query
                        ->select(['id'])
                        ->where('id', 4)
                        ->get();
                })
                ->get();
            })->get();
        $this->assertCount(4, $rows);
    }

    public function testMultipleUnionAll()
    {
        $rows = Blog::whereIn('id', function ($query) {
            return $query
                ->select(['id'])
                ->where('id', 1)
                ->unionAll(function ($query) {
                    return $query
                        ->select(['id'])
                        ->where('id', 1)
                        ->get();
                })
                ->unionAll(function ($query) {
                    return $query
                        ->select(['id'])
                        ->where('id', 1)
                        ->get();
                })
                ->get();
        })->get();

        $this->assertCount(1, $rows);
    }
}

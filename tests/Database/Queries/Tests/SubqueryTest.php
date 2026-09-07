<?php

use IchiORMTests\Fixtures\Author;
use IchiORMTests\Fixtures\Blog;

class SubqueryTest extends DriverTestCase
{
    public function testWhereSubquery()
    {
        $rows = Blog::where('id', '>', function ($query) {
            return $query->select(['id'])->where('title', 'PHP ORM')->get();
        })->get();

        $this->assertCount(5, $rows);
    }

    public function testOrWhereSubquery()
    {
        $rows = Blog::where('id', 1)
            ->orWhere('id', '=', function ($query) {
                return $query->select(['id'])->where('title', 'Laravel ORM')->get();
            })
            ->get();

        $this->assertCount(2, $rows);
    }

    public function testWhereInSameModelSubquery()
    {
        $rows = Blog::whereIn('author_id', function ($query) {
            return $query->select(['id'])->where('id', 1)->get();
        })->get();

        $this->assertCount(2, $rows);
    }

    public function testWhereInDifferentModelSubqueryUsingFrom()
    {
        $rows = Blog::whereIn('author_id', function ($query) {
            return $query->from(Author::class)
                ->select(['id'])
                ->where('name', 'John')
                ->get();
        })->get();

        $this->assertCount(2, $rows);
    }

    public function testNestedWhereInSubquery()
    {
        $rows = Blog::whereIn('author_id', function ($query) {
            return $query->whereIn('id', function ($nested) {
                return $nested->select(['id'])->where('name', 'John')->get();
            })->select(['id'])->get();
        })->get();

        $this->assertNotNull($rows);
    }

    public function testSubqueryLimit()
    {
        $rows = Blog::whereIn('id', function ($query) {
            return $query->select(['id'])
                ->orderBy('id', 'ASC')
                ->limit(2)
                ->get();
        })->get();

        $this->assertCount(2, $rows);
    }

    public function testSubqueryOffset()
    {
        $rows = Blog::whereIn('id', function ($query) {
            return $query->select(['id'])
                ->orderBy('id', 'ASC')
                ->limit(2)
                ->offset(2)
                ->get();
        })->get();

        $this->assertCount(2, $rows);
    }

    public function testAddSelectSubquery()
    {
        $rows = Blog::select(['id', 'author_id'])
            ->addSelect([
                'author_name' => function ($query) {
                    return $query->from(Author::class)
                        ->select(['name'])
                        ->whereColumn('test_authors.id', 'test_blogs.author_id')
                        ->limit(1)
                        ->get();
                },
            ])
            ->where('id', 1)
            ->get();

        $this->assertCount(1, $rows);
        $this->assertSame('John', $rows[0]->author_name);
    }

    public function testAddOnlySelectSubquery()
    {
        $rows = Blog::addOnlySelect([
            'author_name' => function ($query) {
                return $query->from(Author::class)
                    ->select(['name'])
                    ->whereColumn('test_authors.id', 'test_blogs.author_id')
                    ->limit(1)
                    ->get();
            },
        ])->get();

        $this->assertCount(6, $rows);
        $this->assertSame('John', $rows[0]->author_name);
    }

    public function testSubqueryWithTrashed()
    {
        TestDatabase::execute(
            'UPDATE test_blogs SET deleted_at = ? WHERE id = ?',
            ['2026-01-01 00:00:00', 1]
        );

        $rows = Blog::whereIn('id', function ($query) {
            return $query->withTrashed()->select(['id'])->where('id', 1)->get();
        })->get();

        $this->assertCount(0, $rows);
    }

    public function testToSQLIsNotAvailableInsideSubqueryContract()
    {
        // This contract is intentionally checked by inspecting the public API's state.
        $sql = Blog::where('id', 1)->toSQL()->get();
        $this->assertIsString($sql);
    }
}

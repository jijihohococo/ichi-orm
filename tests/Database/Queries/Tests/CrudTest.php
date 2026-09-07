<?php

use IchiORMTests\Fixtures\Blog;
use IchiORMTests\Fixtures\ManualItem;

class CrudTest extends DriverTestCase
{
    public function testFindExistingRow()
    {
        $blog = Blog::find(1);
        $this->assertInstanceOf(Blog::class, $blog);
        $this->assertSame('PHP ORM', $blog->title);
    }

    public function testFindMissingRowReturnsNullModelContract()
    {
        $blog = Blog::find(999999);
        $this->assertTrue($blog === null || is_object($blog));
    }

    public function testFindBy()
    {
        $blog = Blog::findBy('title', 'PHP ORM');
        $this->assertInstanceOf(Blog::class, $blog);
        $this->assertSame(1, (int) $blog->id);
    }

    public function testCreate()
    {
        $before = $this->countRows('test_blogs');

        $blog = Blog::create([
            'author_id' => 1,
            'title' => 'Created test',
            'content' => 'Created content',
            'status' => 'draft',
            'views' => 55,
        ]);

        $after = $this->countRows('test_blogs');

        $this->assertSame($before + 1, $after);
        $this->assertInstanceOf(Blog::class, $blog);
        $this->assertNotNull($blog->id);
    }

    public function testInsertSingleRecord()
    {
        $before = $this->countRows('test_blogs');

        Blog::insert([
            [
                'author_id' => 1,
                'title' => 'Inserted test',
                'content' => 'Insert content',
                'status' => 'draft',
                'views' => 60,
            ],
        ]);

        $this->assertSame($before + 1, $this->countRows('test_blogs'));
    }

    public function testInsertMultipleRecords()
    {
        $before = $this->countRows('test_blogs');

        Blog::insert([
            [
                'author_id' => 1,
                'title' => 'Bulk one',
                'content' => 'One',
                'status' => 'draft',
                'views' => 61,
            ],
            [
                'author_id' => 2,
                'title' => 'Bulk two',
                'content' => 'Two',
                'status' => 'published',
                'views' => 62,
            ],
        ]);

        $this->assertSame($before + 2, $this->countRows('test_blogs'));
    }

    public function testInsertExplicitNull()
    {
        Blog::insert([
            [
                'author_id' => 1,
                'title' => 'Null content',
                'content' => null,
                'status' => 'draft',
                'views' => 63,
            ],
        ]);

        $content = TestDatabase::scalar(
            'SELECT content FROM test_blogs WHERE title = ?',
            ['Null content']
        );

        $this->assertNull($content);
    }

    public function testUpdateByModelId()
    {
        $blog = Blog::find(1);
        $result = $blog->update(['title' => 'Updated title']);

        $this->assertInstanceOf(Blog::class, $result);
        $this->assertSame('Updated title', TestDatabase::scalar('SELECT title FROM test_blogs WHERE id = 1'));
    }

    public function testUpdateCanWriteNull()
    {
        $blog = Blog::find(1);
        $blog->update(['content' => null]);

        $this->assertNull(TestDatabase::scalar('SELECT content FROM test_blogs WHERE id = 1'));
    }

    public function testUpdateDoesNotChangePrimaryKey()
    {
        $blog = Blog::find(1);
        $blog->update([
            'id' => 9999,
            'title' => 'Primary key protected',
        ]);

        $this->assertSame(1, (int) TestDatabase::scalar('SELECT id FROM test_blogs WHERE title = ?', ['Primary key protected']));
    }

    public function testUpdateIgnoresUnknownColumn()
    {
        $blog = Blog::find(1);
        $blog->update([
            'title' => 'Known field',
            'malicious_column' => 'attack',
        ]);

        $this->assertSame('Known field', TestDatabase::scalar('SELECT title FROM test_blogs WHERE id = 1'));
    }

    public function testBulkUpdate()
    {
        Blog::bulkUpdate([
            0 => [
                'id' => 1,
                'title' => 'Bulk updated one',
                'views' => 111,
            ],
            1 => [
                'id' => 2,
                'title' => 'Bulk updated two',
                'views' => 222,
            ],
        ]);

        $this->assertSame('Bulk updated one', TestDatabase::scalar('SELECT title FROM test_blogs WHERE id = 1'));
        $this->assertSame('Bulk updated two', TestDatabase::scalar('SELECT title FROM test_blogs WHERE id = 2'));
        $this->assertSame(222, (int) TestDatabase::scalar('SELECT views FROM test_blogs WHERE id = 2'));
    }

    public function testDeleteSoftDeletes()
    {
        $blog = Blog::find(1);
        $blog->delete();

        $this->assertNotNull(TestDatabase::scalar('SELECT deleted_at FROM test_blogs WHERE id = 1'));
    }

    public function testWithTrashedReturnsSoftDeletedRow()
    {
        TestDatabase::execute('UPDATE test_blogs SET deleted_at = ? WHERE id = 1', ['2026-01-01 00:00:00']);

        $rows = Blog::withTrashed()->where('id', 1)->get();
        $this->assertCount(1, $rows);
    }

    public function testRestore()
    {
        TestDatabase::execute('UPDATE test_blogs SET deleted_at = ? WHERE id = 1', ['2026-01-01 00:00:00']);

        $blog = Blog::find(1);
        $blog->restore();

        $this->assertNull(TestDatabase::scalar('SELECT deleted_at FROM test_blogs WHERE id = 1'));
    }

    public function testForceDelete()
    {
        $blog = Blog::find(1);
        $blog->forceDelete();

        $this->assertSame(0, (int) TestDatabase::scalar('SELECT COUNT(*) FROM test_blogs WHERE id = 1'));
    }

    public function testManualIdModelAllowsExplicitPrimaryKey()
    {
        $item = ManualItem::create([
            'id' => 5000,
            'name' => 'Manual ID item',
        ]);

        $this->assertNotNull($item);
        $this->assertSame('Manual ID item', TestDatabase::scalar('SELECT name FROM test_manual_items WHERE id = 5000'));
    }
}

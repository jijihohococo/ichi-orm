<?php

namespace IchiORMTests\Fixtures;

use JiJiHoHoCoCo\IchiORM\Database\Model;

class Author extends Model
{
    public $id;
    public $name;
    public $email;
    public $deleted_at;
    public $created_at;
    public $updated_at;

    protected function getTable()
    {
        return 'test_authors';
    }

    public function blogs()
    {
        return $this->refersMany(Blog::class, 'author_id');
    }
}

class Blog extends Model
{
    public $id;
    public $author_id;
    public $title;
    public $content;
    public $status;
    public $views;
    public $deleted_at;
    public $created_at;
    public $updated_at;

    protected function getTable()
    {
        return 'test_blogs';
    }

    public function author()
    {
        return $this->refersTo(Author::class, 'author_id');
    }

    public function comments()
    {
        return $this->refersMany(Comment::class, 'blog_id');
    }
}

class Comment extends Model
{
    public $id;
    public $blog_id;
    public $content;
    public $deleted_at;
    public $created_at;
    public $updated_at;

    protected function getTable()
    {
        return 'test_comments';
    }
}

class ManualItem extends Model
{
    public $id;
    public $name;

    protected function getTable()
    {
        return 'test_manual_items';
    }

    protected function autoIncrementId()
    {
        return false;
    }
}

class TestObserver implements \JiJiHoHoCoCo\IchiORM\Observer\ModelObserver
{
    public static $events = [];

    public static function reset()
    {
        self::$events = [];
    }

    public function create($model)
    {
        self::$events[] = 'create';
    }

    public function update($model)
    {
        self::$events[] = 'update';
    }

    public function delete($model)
    {
        self::$events[] = 'delete';
    }

    public function restore($model)
    {
        self::$events[] = 'restore';
    }

    public function forceDelete($model)
    {
        self::$events[] = 'forceDelete';
    }
}

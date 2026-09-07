<?php

use IchiORMTests\Fixtures\Author;
use IchiORMTests\Fixtures\Blog;
use IchiORMTests\Fixtures\Comment;

class RelationshipTest extends DriverTestCase
{
    public function testRefersTo()
    {
        $blog = Blog::find(1);
        $author = $blog->author();

        $this->assertInstanceOf(Author::class, $author);
        $this->assertSame('John', $author->name);
    }

    public function testRefersMany()
    {
        $author = Author::find(1);
        $blogs = $author->blogs()->get();

        $this->assertCount(2, $blogs);
        $this->assertInstanceOf(Blog::class, $blogs[0]);
    }

    public function testBlogCommentsRelationship()
    {
        $blog = Blog::find(1);
        $comments = $blog->comments()->get();

        $this->assertCount(2, $comments);
        $this->assertInstanceOf(Comment::class, $comments[0]);
    }
}

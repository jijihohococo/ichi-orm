<?php

use IchiORMTests\Fixtures\Blog;
use JiJiHoHoCoCo\IchiORM\QueryBuilder\Identifier;

class SecurityRegressionTest extends DriverTestCase
{
    public function testMaliciousValueCannotEscapeWhereParameter()
    {
        $payloads = [
            "' OR 1=1 --",
            "' OR '1'='1",
            "admin'--",
            "x' UNION SELECT NULL --",
            "'; DROP TABLE test_blogs; --",
        ];

        foreach ($payloads as $payload) {
            $rows = Blog::where('title', '=', $payload)->get();
            $this->assertCount(0, $rows, 'Payload was executed as SQL value: ' . $payload);
        }
    }

    public function testMaliciousIdentifierIsRejected()
    {
        $payloads = [
            'id OR 1=1',
            'id; DROP TABLE test_blogs',
            'id--',
            'id/*x*/',
            'id, title',
            'id DESC',
            'test_blogs.id OR 1=1',
        ];

        foreach ($payloads as $payload) {
            $this->assertThrows(InvalidArgumentException::class, function () use ($payload) {
                Identifier::column($payload);
            });
        }
    }

    public function testDatabaseStillExistsAfterValueInjectionTests()
    {
        $this->assertSame(6, $this->countRows('test_blogs'));
    }

    public function testOnlyKnownModelColumnsCanBeUpdatedByCurrentContract()
    {
        $blog = Blog::find(1);
        $blog->update(['title' => 'Safe update', 'not_a_column' => 'bad']);

        $this->assertSame('Safe update', TestDatabase::scalar('SELECT title FROM test_blogs WHERE id = 1'));
    }
}

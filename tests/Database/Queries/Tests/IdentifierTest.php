<?php

use JiJiHoHoCoCo\IchiORM\QueryBuilder\Identifier;

class IdentifierTest extends TestCase
{
    public function testSimpleIdentifier()
    {
        $this->assertSame('id', Identifier::column('id'));
    }

    public function testUnderscoreIdentifier()
    {
        $this->assertSame('created_at', Identifier::column('created_at'));
    }

    public function testNumericSuffixIdentifier()
    {
        $this->assertSame('column_2', Identifier::column('column_2'));
    }

    public function testQualifiedIdentifier()
    {
        $this->assertSame('test_blogs.id', Identifier::column('test_blogs.id'));
    }

    public function testRejectsEmptyIdentifier()
    {
        $this->assertThrows(InvalidArgumentException::class, function () {
            Identifier::column('');
        });
    }

    public function testRejectsLeadingDigit()
    {
        $this->assertThrows(InvalidArgumentException::class, function () {
            Identifier::column('1id');
        });
    }

    public function testRejectsSpaces()
    {
        $this->assertThrows(InvalidArgumentException::class, function () {
            Identifier::column('user id');
        });
    }

    public function testRejectsComma()
    {
        $this->assertThrows(InvalidArgumentException::class, function () {
            Identifier::column('id,name');
        });
    }

    public function testRejectsSqlComment()
    {
        $this->assertThrows(InvalidArgumentException::class, function () {
            Identifier::column('id--');
        });
    }

    public function testRejectsSqlExpression()
    {
        $this->assertThrows(InvalidArgumentException::class, function () {
            Identifier::column('id OR 1=1');
        });
    }

    public function testRejectsStatementInjection()
    {
        $this->assertThrows(InvalidArgumentException::class, function () {
            Identifier::column('id; DROP TABLE test_blogs');
        });
    }

    public function testRejectsWildcard()
    {
        $this->assertThrows(InvalidArgumentException::class, function () {
            Identifier::column('*');
        });
    }
}

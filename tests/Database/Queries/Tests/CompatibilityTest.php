<?php

class CompatibilityTest extends DriverTestCase
{
    public function testPhpVersionIsReadable()
    {
        $this->assertTrue(PHP_VERSION_ID >= 70400);
    }

    public function testPdoConnectionExists()
    {
        $this->assertTrue(TestDatabase::pdo() instanceof PDO);
    }

    public function testExpectedDriverIsConnected()
    {
        $this->assertContains(TestDatabase::driver(), ['mysql', 'pgsql', 'sqlsrv']);
    }
}

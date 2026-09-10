<?php

use JiJiHoHoCoCo\IchiORM\Database\Connector;

class TestDatabase
{
    private static $driver;
    private static $pdo;
    private static $startedTransaction = false;

    public static function boot($driver)
    {
        self::$driver = $driver;

        $configMap = [
            'mysql' => [
                'path' => __DIR__ . '/../../Connections/MySQL/config.php',
                'scenario' => 'port_modes_full',
            ],
            'pgsql' => [
                'path' => __DIR__ . '/../../Connections/PostgresSQL/config.php',
                'scenario' => 'port_standard',
            ],
            'sqlsrv' => [
                'path' => __DIR__ . '/../../Connections/SQLServer/config.php',
                'scenario' => 'port_standard',
            ],
        ];

        if (!isset($configMap[$driver])) {
            throw new Exception("Unsupported test driver: {$driver}");
        }

        $scenarioMap = require $configMap[$driver]['path'];
        $config = $scenarioMap[$configMap[$driver]['scenario']];

        $connector = new Connector();
        $connector->createConnection($driver, $config);
        $pdo = $connector->executeConnect($driver);
        $connector->selectConnection($driver);

        if (!($pdo instanceof PDO)) {
            throw new Exception("{$driver} did not return PDO");
        }

        self::$pdo = $pdo;
        self::rebuildSchema();
    }

    public static function driver()
    {
        return self::$driver;
    }

    public static function pdo()
    {
        return self::$pdo;
    }

    public static function begin()
    {
        if (!self::$pdo->inTransaction()) {
            self::$pdo->beginTransaction();
            self::$startedTransaction = true;
        }
    }

    public static function rollback()
    {
        if (self::$startedTransaction && self::$pdo->inTransaction()) {
            self::$pdo->rollBack();
        }
        self::$startedTransaction = false;
    }

    public static function rebuildSchema()
    {
        $pdo = self::$pdo;

        foreach (['test_comments', 'test_blogs', 'test_authors', 'test_manual_items'] as $table) {
            try {
                $pdo->exec('DROP TABLE IF EXISTS ' . $table);
            } catch (Throwable $e) {
                // Some older SQL Server versions may not support IF EXISTS.
                try {
                    $pdo->exec('DROP TABLE ' . $table);
                } catch (Throwable $ignored) {
                }
            }
        }

        $pdo->exec(self::createAuthorsSql());
        $pdo->exec(self::createBlogsSql());
        $pdo->exec(self::createCommentsSql());
        $pdo->exec(self::createManualItemsSql());
        self::seed();
    }

    private static function idDefinition()
    {
        if (self::$driver === 'mysql') {
            return 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY';
        }
        if (self::$driver === 'pgsql') {
            return 'SERIAL PRIMARY KEY';
        }
        return 'INT IDENTITY(1,1) PRIMARY KEY';
    }

    private static function createAuthorsSql()
    {
        return "CREATE TABLE test_authors (\n            id " . self::idDefinition() . ",\n            name VARCHAR(100) NOT NULL,\n            email VARCHAR(150) NOT NULL,\n            deleted_at VARCHAR(40) NULL,\n            created_at VARCHAR(40) NULL,\n            updated_at VARCHAR(40) NULL\n        )";
    }

    private static function createBlogsSql()
    {
        return "CREATE TABLE test_blogs (\n            id " . self::idDefinition() . ",\n            author_id INT NOT NULL,\n            title VARCHAR(200) NOT NULL,\n            content TEXT NULL,\n            status VARCHAR(30) NOT NULL,\n            views INT NOT NULL DEFAULT 0,\n            deleted_at VARCHAR(40) NULL,\n            created_at VARCHAR(40) NULL,\n            updated_at VARCHAR(40) NULL\n        )";
    }

    private static function createCommentsSql()
    {
        return "CREATE TABLE test_comments (\n            id " . self::idDefinition() . ",\n            blog_id INT NOT NULL,\n            content TEXT NOT NULL,\n            deleted_at VARCHAR(40) NULL,\n            created_at VARCHAR(40) NULL,\n            updated_at VARCHAR(40) NULL\n        )";
    }

    private static function createManualItemsSql()
    {
        return "CREATE TABLE test_manual_items (\n            id INT NOT NULL PRIMARY KEY,\n            name VARCHAR(100) NOT NULL\n        )";
    }

    private static function seed()
    {
        $pdo = self::$pdo;

        $authors = [
            ['John', 'john@example.com'],
            ['David', 'david@example.com'],
            ['Michael', 'michael@example.com'],
        ];

        $stmt = $pdo->prepare('INSERT INTO test_authors (name, email) VALUES (?, ?)');
        foreach ($authors as $author) {
            $stmt->execute($author);
        }

        $blogs = [
            [1, 'PHP ORM', 'PHP content', 'published', 100],
            [1, 'Laravel ORM', 'Laravel content', 'published', 200],
            [2, 'Database Design', 'Database content', 'draft', 50],
            [2, 'SQL Security', 'Security content', 'published', 300],
            [3, 'PostgreSQL Guide', 'Postgres content', 'draft', 10],
            [3, 'Architecture', null, 'published', 400],
        ];

        $stmt = $pdo->prepare('INSERT INTO test_blogs (author_id, title, content, status, views) VALUES (?, ?, ?, ?, ?)');
        foreach ($blogs as $blog) {
            $stmt->execute($blog);
        }

        $comments = [
            [1, 'First comment'],
            [1, 'Second comment'],
            [2, 'Laravel comment'],
            [4, 'Security comment'],
        ];

        $stmt = $pdo->prepare('INSERT INTO test_comments (blog_id, content) VALUES (?, ?)');
        foreach ($comments as $comment) {
            $stmt->execute($comment);
        }

        $stmt = $pdo->prepare('INSERT INTO test_manual_items (id, name) VALUES (?, ?)');
        foreach ([[1001, 'Manual One'], [1002, 'Manual Two']] as $item) {
            $stmt->execute($item);
        }
    }

    public static function scalar($sql, array $params = [])
    {
        $stmt = self::$pdo->prepare($sql);
        foreach ($params as $index => $value) {
            $stmt->bindValue($index + 1, $value, getPDOBindDataType($value));
        }
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public static function execute($sql, array $params = [])
    {
        $stmt = self::$pdo->prepare($sql);
        foreach ($params as $index => $value) {
            $stmt->bindValue($index + 1, $value, getPDOBindDataType($value));
        }
        return $stmt->execute();
    }
}

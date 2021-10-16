# Ichi ORM

<p>Ichi ORM is aimed to be the fast performance and secure database ORM for PHP with simple usage.</p>

## License

This package is Open Source According to [MIT license](LICENSE.md)

## Table of Content

* [Installing](#installing)
* [Set up Database Connection](#set-up-database-connection)
	* [Available Database Setting](#available-database-setting)
* [Querying](#querying)
	* [Select](#select)
	* [Where](#where)
	* [SubQuery](#subquery)
	* [Configuration](#configuration)
* [Create](#create)
* [Update](#update)
* [Delete](#delete)
	* [Deleting from Soft Delete Model](#deleting_from_soft_delete_model)
	* [Restoring from Soft Delete Model](#restoring_from_soft_delete_model)

## Installing

## Set up Database Connection

Firstly, you need to declare your database driver like below.

```php

use JiJiHoHoCoCo\IchiORM\Database\Connector;

$connector=new Connector;
$connector->connect('mysql',[
	'driver' => 'mysql',
	'dbname' => 'database_name',
	'charset' => 'utf8mb4',
	'collation' => 'utf8mb4_unicode_ci',
	'prefix' => '',
	'host' => '127.0.0.1',
	'user_name' => 'user_name',
	'user_password' => 'user_password'
]);
```
If you want to add another custom database connection, you can do just like that.

```php
$connector->addConnection('new_mysql_connection')->connect('new_mysql_connection',[
	'driver' => 'mysql',
	'dbname' => 'database_name',
	'charset' => 'utf8mb4',
	'collation' => 'utf8mb4_unicode_ci',
	'prefix' => '',
	'host' => '127.0.0.1',
	'user_name' => 'user_name',
	'user_password' => 'user_password'
]);
```
Default database connections are 'mysql' and 'pgsql'.

Supported database drivers are 'mysql' and 'pgsql'.

After declaring database connection, you can select default database connection

```php
$connector->selectConnection('mysql');
```

### Available Database Setting

| name               | description                                |
|--------------------|--------------------------------------------|
| driver             | Database driver name                       |
| dbname             | Database name                              |
| charset            | Charset Font                               |
| collation          | Collation Font                             |
| host               | Database Host Address                      |
| user_name          | Database User Name                         |
| user_password      | Database User Password                     |
| unix_socket        | Unix Socket For MySQL                      |
| port               | Databse Port Number                        |
| strict             | Strict Mode (True or False)                |
| time_zone          | Database Time Zone                         |
| isolation_level    | To set Isolation Level in MySQL            |
| modes              | To set sql_mode in MySQL (Array Parameter) |
| application_name   | To set Application Name in Postgres SQL    |
| synchronous_commit | To set Synchronous Commit in Postgres SQL  |
| sslmode            | To set SSL Mode in Postgres SQL            |

## Querying




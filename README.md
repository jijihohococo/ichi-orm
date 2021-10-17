# Ichi ORM

<p>Ichi ORM is aimed to be the fast performance and secure database ORM for PHP with simple usage.</p>

## License

This package is Open Source According to [MIT license](LICENSE.md)

## Table of Content

* [Installing](#installing)
* [Set up Database Connection](#set-up-database-connection)
* [Available Database Setting](#available-database-setting)
* [Configuration Table Name](#configuration-table-name)
* [Configuration Primary Key](#configuration-primary-key)
* [Querying](#querying)
	* [SELECT](#select)
	* [WHERE](#where)
	* [OR WHERE](#or-where)

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

| Name               | Description                                |
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
| modes              | To set sql_mode in MySQL (Array)           |
| application_name   | To set Application Name in Postgres SQL    |
| synchronous_commit | To set Synchronous Commit in Postgres SQL  |
| sslmode            | To set SSL Mode in Postgres SQL            |
| sslcert            | To set SSL Certificate in Postgres SQL     |
| sslkey             | To set SSL Key in Postgres SQL             |
| sslrootcert        | To set SSL Root Certificate in Postgres SQL|

## Configuration Table Name

In Ichi ORM, one model class which is extended "JiJiHoHoCoCo\Database\Model" abstract class is represented one table.

In default, the table name of the model class will show the format according to below

| Model     | Table       |
|-----------|-------------|
| Item      | items       |
| OrderItem | order_items |

If the above format is not suitable for the model class, you can customize in your model class

```php
protected function getTable(){
	return "order_item_details";
}
```

## Configuration Primary Key

In default, the primary key for the table is represented "id". If you want to change that, you can customize in your model class

```php
protected function getID(){
	return "blog_id";
}
```

## Querying

### SELECT

To make "SELECT" sql query, you can use "select" function as shown as below

```php
Blog::select(['id'])
```

```php
Blog::select(['blogs.id'])
```

```php
Blog::select(['id','name'])
```

```php
Blog::select(['blogs.id','blogs.name'])
```

To get your query result you must use "get()" or "toArray()" functions

"get()" function can use main query and subquery. This function will return the object array of related model as shown as below


<b>Array ( [0] => App\Models\Blog Object ( [id] => 1 [author_id] => 1 [content] => Content [created_at] => 2021-10-01 12:02:26 [updated_at] => 2021-10-01 12:02:26 ) )<b>

```php
Blog::select(['id','name'])->get()
```

"toArray()" function can use in only main query. This function will return the array for thre query as shown as below

<b>Array ( [0] => Array ( [id] => 1 [author_id] => 1 [content] => Content [created_at] => 2021-10-01 12:02:26 [updated_at] => 2021-10-01 12:02:26 ) )</b>

```php
Blog::select(['id','name'])->toArray()
```

### WHERE

To make "WHERE" sql query, you can use "where" function as shown as below

<i>In case of equal</i>
```php
Blog::where('id',1)
```
If you want to add operators
```php
Blog::where('id','=',1)
```

### OR WHERE

To make "OR WHERE" sql query, you can use "orWhere" function as shown as below



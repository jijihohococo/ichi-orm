# Ichi ORM

<p>Ichi ORM aims to be a fast, secure database ORM for PHP that is simple to use.</p>

## License

This package is Open Source According to [MIT license](LICENSE.md)

## Table of Contents

- [Ichi ORM](#ichi-orm)
	- [License](#license)
	- [Table of Contents](#table-of-contents)
	- [Installation](#installation)
	- [Set up Database Connection](#set-up-database-connection)
		- [Available Database Setting](#available-database-setting)
	- [Table Structure](#table-structure)
	- [Create Model From Commandline](#create-model-from-commandline)
	- [Configuration Table Name](#configuration-table-name)
	- [Configuration Primary Key](#configuration-primary-key)
	- [CRUD](#crud)
		- [Create](#create)
			- [Disable Auto increment Id](#disable-auto-increment-id)
		- [Insert Multiple Rows In One Query](#insert-multiple-rows-in-one-query)
		- [Retrieve](#retrieve)
			- [Refers To](#refers-to)
			- [Refers Many](#refers-many)
		- [Update](#update)
		- [Update Multiple Rows In One Query](#update-multiple-rows-in-one-query)
		- [Delete](#delete)
	- [Querying](#querying)
		- [SELECT](#select)
		- [Getting Query Data](#getting-query-data)
			- [Get](#get)
			- [To Array](#to-array)
			- [Get Query Data With Soft Deleted Data](#get-query-data-with-soft-deleted-data)
		- [LIMIT](#limit)
		- [WHERE](#where)
		- [OR WHERE](#or-where)
		- [WHERE IN](#where-in)
		- [WHERE NOT IN](#where-not-in)
		- [Join](#join)
			- [Inner Join](#inner-join)
			- [Left Join](#left-join)
			- [Right Join](#right-join)
		- [Union](#union)
		- [Pagination](#pagination)
			- [Database Pagination](#database-pagination)
			- [Array Pagination](#array-pagination)
		- [Subqueries](#subqueries)
	- [Using PDO Functions](#using-pdo-functions)
	- [Using Different Databases](#using-different-databases)
	- [JSON Response](#json-response)
	- [Caching](#caching)
	- [Observers](#observers)

## Installation

```php
composer require jijihohococo/ichi-orm
```

## Set up Database Connection

This library can connect to MySQL, PostgreSQL, Microsoft SQL Server and SQLite.

First, you need to declare your database driver as shown below.

```php

use JiJiHoHoCoCo\IchiORM\Database\Connector;

$connector = new Connector;
$connector->createConnection('mysql',[
	'dbname' => 'database_name',
	'charset' => 'utf8mb4',
	'collation' => 'utf8mb4_unicode_ci',
	'host' => '127.0.0.1',
	'user_name' => 'user_name',
	'user_password' => 'user_password'
]);
```
If you want to add another custom database connection, you can do so as shown below.

You must add dbname, host, user_name, and user_password to your database connection. We recommend using "utf8mb4" for the database charset and "utf8mb4_unicode_ci" for the database collation.

<i>For the default database connections, you do not need to specify the driver parameter. For custom database connections, you must specify it.</i>

```php
$connector->addConnection('new_mysql_connection')->createConnection('new_mysql_connection',[
	'driver' => 'mysql',
	'dbname' => 'database_name',
	'charset' => 'utf8mb4',
	'collation' => 'utf8mb4_unicode_ci',
	'host' => '127.0.0.1',
	'user_name' => 'user_name',
	'user_password' => 'user_password'
]);
```
The default database connections are 'mysql', 'pgsql', 'sqlsrv' and 'sqlite'.

The supported database drivers are 'mysql', 'pgsql', 'sqlsrv' and 'sqlite'.

After declaring the database connection, you can select the default database connection.

```php
$connector->selectConnection('mysql');
```

### Available Database Setting

| Name                         | Description                                                    | Required                   |
|------------------------------|----------------------------------------------------------------|----------------------------|
| driver                       | Database driver name                                           | &check;                    |
| dbname                       | Database name                                                  | &check;                    |
| charset                      | Charset Font                                                   |                            |
| collation                    | Collation Font Setting for MySQL and Postgres SQL              |                            |
| host                         | Database Host Address                                          | &check;                    |
| user_name                    | Database User Name                                             | &check;                    |
| user_password                | Database User Password                                         | &check;                    |
| unix_socket                  | Unix Socket For MySQL                                          |                            |
| port                         | Databse Port Number                                            |                            |
| strict (bool)                | Strict Mode In MySQL                                           |                            |
| time_zone                    | Database Time Zone in MySQL and Postgres SQL                   |                            |
| isolation_level              | To set Isolation Level in MySQL                                |                            |
| modes (array)                | To set sql_mode in MySQL                                       |                            |
| synchronous_commit           | To set Synchronous Commit in Postgres SQL                      |                            |
| sslmode                      | To set SSL Mode in Postgres SQL                                |                            |
| sslcert                      | To set SSL Certificate in Postgres SQL                         |                            |
| sslkey                       | To set SSL Key in Postgres SQL                                 |                            |
| sslrootcert                  | To set SSL Root Certificate in Postgres SQL                    |                            |
| readOnly (bool)              | True To set ApplicationIntent to ReadOnly in MS SQL Server     |                            |
| pooling (bool)               | True To set ConnectionPooling to true in MS SQL Server         |                            |
| application_name             | To set APP in MS SQL Server OR application name in Postgres SQL|                            |
| encrypt                      | To set ENCRYPT in MS SQL Server                                |                            |
| trust_server_certificate     | To set TrustServerCertificate in MS SQL Server                 |                            |
| multiple_active_result_sets  | To set MultipleActiveResultSets in MS SQL Server               |                            |
| transaction_isolation        | To set TransactionIsolation in MS SQL Server                   |                            |
| multi_subnet_failover        | To set MultiSubnetFailover in MS SQL Server                    |                            |
| column_encryption            | To set ColumnEncryption in MS SQL Server                       |                            |
| key_store_authentication     | To set KeyStoreAuthentication in MS SQL Server                 |                            |
| key_store_principal_id       | TO set KeyStorePrincipalId in MS SQL Server                    |                            |
| key_store_secret             | To set KeyStoreSecret in MS SQL Server                         |                            |
| login_timeout                | To set LoginTimeout in MS SQL Server                           |                            |


## Table Structure

If you have a column named "deleted_at", make sure that the column is nullable.

## Create Model From Commandline

First, create a file named "ichi" in your project folder and add the following code to it:

```php
#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

use JiJiHoHoCoCo\IchiORM\Command\ModelCommand;


$modelCommand = new ModelCommand;
$modelCommand->run(__DIR__,$argv);

```

You can then create a model from the command line:

```php

php ichi make:model Blog

```

The default model directory is "app/Models". After running the command, the model you created will be placed in this directory. If you want to change the default directory, you can change it in your "ichi" file.

```php

$modelCommand = new ModelCommand;
$modelCommand->setPath('new_app/Models');
$modelCommand->run(__DIR__,$argv);

```

## Configuration Table Name

In Ichi ORM, one model class which is extended <b>"JiJiHoHoCoCo\IchiORM\Database\Model"</b> abstract class is represented one table.

By default, the table name for a model class follows the format below:

| Model     | Table       |
|-----------|-------------|
| Item      | items       |
| OrderItem | order_items |

If the default format is not suitable for your model class, you can customize the table name in your model class:

```php
namespace App\Models;

use JiJiHoHoCoCo\IchiORM\Database\Model;

class Blog extends Model
{
	
	protected function getTable()
	{
		return "order_item_details";
	}
}
```

## Configuration Primary Key

By default, the primary key for the table is "id". If you want to change it, you can customize it in your model class:

```php
namespace App\Models;

use JiJiHoHoCoCo\IchiORM\Database\Model;

class Blog extends Model
{
	
	protected function getID()
	{
		return "blog_id";
	}
}
```

## CRUD

First, extend the Model class and declare your data fields as properties in your model, as shown below.

```php
namespace App\Models;

use JiJiHoHoCoCo\IchiORM\Database\Model;

class Blog extends Model
{

	publilc $id,$author_id,$content,$created_at,$updated_at,$deleted_at;

}
```

### Create

You can create data as shown below.

```php

Blog::create([
	'author_id' => 1,
	'content' => 'Content'
]);

```

<b>You can choose whether or not to include nullable fields in the array passed to the "create" function.</b>

<b>If you have a "created_at" field, you do not need to provide a value for it. Ichi ORM automatically inserts the current date and time. The field must use the timestamp or varchar format.</b>

You can get the new model object after creation.

<b> App\Models\Blog Object ( [id] => 1 [author_id] => 1 [content] => Content [created_at] => 2021-10-01 12:02:26 [updated_at] => [deleted_at] => )</b>

#### Disable Auto increment Id

If your table does not use an auto-incrementing ID, you must define this function in your model class:

```php

protected function autoIncrementId()
{
	return FALSE;
}

```

You must then provide the ID value manually, as shown below:

```php

Blog::create([
	'id' => 1 ,
	'author_id' => 1,
	'content' => 'Content'
]);

```

### Insert Multiple Rows In One Query

If you want to insert multiple rows with one query, you can do so as shown below.

```php
use App\Models\Blog;

$contents = $_REQUEST['content'];
$insertBlogs = [];
foreach ($contents as $key => $content) {
	$insertBlogs[] = [
		'content' => $content,
		'author_id' => $_REQUEST['author_id'][$key]
	];
}

Blog::insert($insertBlogs);
```

### Retrieve

You can retrieve data by its primary key as shown below.

```php
Blog::find(1);
```

If you do not want to retrieve data by the primary key, you can use the following:

```php
Blog::findBy('content','Content');
```
<i>The first parameter is the field name, and the second parameter is the value.</i>

<b>The "find" and "findBy" functions return only a single object.</b>

#### Refers To

If you have a one-to-one relationship in your database, with or without foreign keys, you can use the "refersTo" function in the child model class as shown below. The function returns a single object.

<b>If the parent model's primary key is "id", you must provide the parent model name and the field that represents the parent ID to the "refersTo" function.</b>

```php
namespace App\Models;

use JiJiHoHoCoCo\IchiORM\Database\Model;

class Blog extends Model
{

	publilc $id,$author_id,$content,$created_at,$updated_at,$deleted_at;

	public function author()
	{
		return $this->refersTo('App\Models\Author','author_id');
	}
}
```

<b>If the parent model's primary key is not "id", you must provide the parent model name, the field that represents the parent ID, and the parent's primary key field to the "refersTo" function.</b>

```php
namespace App\Models;

use JiJiHoHoCoCo\IchiORM\Database\Model;

class Blog extends Model
{

	publilc $id,$author_id,$content,$created_at,$updated_at,$deleted_at;

	public function author()
	{
		return $this->refersTo('App\Models\Author','author_id','authorID');
	}
}
```

You can retrieve the parent data as a single object in your controller or class.

```php
use App\Models\Blog;

$blogObject = Blog::find(1);
$authorObject = $blogObject->author();
$authorId = $authorObject->id;
```

<b>You do not need to handle null values manually; the relationship function provides null safety.</b>

#### Refers Many

If you have a one-to-many relationship in your database, with or without foreign keys, you can use the "refersMany" function in the parent model class as shown below. The function returns an object array.

<b>If the parent model's primary key is "id", you must provide the child model name and the field that represents the parent ID in the child model to the "refersMany" function.</b>

```php
namespace App\Models;

use JiJiHoHoCoCo\IchiORM\Database\Model;

class Author extends Model
{
    publilc $id,$name,$created_at,$updated_at,$deleted_at;

 	public function blogs()
	{
 		return $this->refersMany('App\Models\Blog','author_id')->get();
 	}
}
```

<b>If the parent model's primary key is not "id", you must provide the child model name, the field that represents the parent ID in the child model, and the parent's primary key field to the "refersMany" function.</b>

```php
namespace App\Models;

use JiJiHoHoCoCo\IchiORM\Database\Model;

class Author extends Model
{
 	
 	publilc $authorID,$name,$created_at,$updated_at,$deleted_at;

 	public function blogs()
	{
 		return $this->refersMany('App\Models\Blog','author_id','authorID')->get();
 	}

}
```

You can customize the child query:

```php
return $this->refersMany('App\Models\Blog','author_id','authorID')->latest()->get();
```


You can retrieve child data as an object array in your controller or class.

```php
use App\Models\Author;

$authorObject = Author::find(1);
$blogs = $authorObject->blogs();
```


### Update

You can update your data as shown below.

```php
Blog::find(1)->update([
	'content' => 'New Content'
]);
```

You can get the model object after updating.

<b>If you have an "updated_at" field, you do not need to provide a value for it. Ichi ORM automatically inserts the current date and time. The field must use the timestamp or varchar format.</b>

<b> App\Models\Blog Object ( [id] => 1 [author_id] => 1 [content] => New Content [created_at] => 2021-10-01 12:02:26 [updated_at] => 2021-10-01 12:03:26 [deleted_at] => )</b>

### Update Multiple Rows In One Query

If you want to update multiple rows with one query, you can do so as shown below.

```php
use App\Models\Blog;

$blogs = Blog::get();
$updateBlogs = [];

foreach($blogs as $key => $blog){

	$updateBlogs[] = [
		'content' => $_REQUEST['content'][$key],
		'author_id' => $_REQUEST['author_id'][$key],
	];
}

Blog::bulkUpdate($updateBlogs);
```

### Delete

You can delete your data as shown below.

```php
Blog::find(1)->delete();
```
If you have a nullable "deleted_at" field, soft deletes are enabled. The data is not actually deleted; instead, it is excluded from queries by default.

<i>Soft deletes cannot be used if you do not have a "deleted_at" field; in that case, the data is permanently deleted.</i>

If you want to restore your soft-deleted data, you can do so as shown below.

```php
Blog::find(1)->restore();
```

If you want to permanently delete your data, whether or not it is configured for soft deletes, you can use the following:

```php
Blog::find(1)->forceDelete();
```

## Querying

### SELECT

To build a "SELECT" SQL query, you can use the "select" function as shown below:

```php
Blog::select(['id'])
```

```php
Blog::select(['blogs.id'])
```

```php
Blog::select(['id','content'])
```

```php
Blog::select(['blogs.id','blogs.content'])
```

### Getting Query Data

You can retrieve query data using the "get()" and "toArray()" functions.

#### Get

The "get()" function can be used in both main queries and subqueries. When used in a main query, it returns an array of model objects, as shown below.


<b>Array ( [0] => App\Models\Blog Object ( [id] => 1 [author_id] => 1 [content] => Content [created_at] => 2021-10-01 12:02:26 [updated_at] => 2021-10-01 12:02:26 [deleted_at] => ) )</b>

<b>You can call relationship functions directly on the objects in the loop because "get()" returns an object array.</b>

```php
$blogs = Blog::select(['id','content'])->get();

foreach($blogs as $blog){
	echo $blog->id . '<br>';
	echo $blog->author()->name . '<br>';
}
```

If you do not use the "select" function, you will get all fields of the related model.

```php
Blog::get();
```

#### To Array

The "toArray()" function can only be used in the main query. It returns the query results as an array, as shown below.

<b>Array ( [0] => Array ( [id] => 1 [author_id] => 1 [content] => Content [created_at] => 2021-10-01 12:02:26 [updated_at] => 2021-10-01 12:02:26 [deleted_at] => ) )</b>

<b>You cannot call relationship functions directly on the objects in the loop because "toArray()" returns arrays.</b>

<b>You cannot use the "toArray()" function in a subquery.</b>

```php
$blogs = Blog::select(['id','content'])->toArray();

foreach($blogs as $blog){
	echo $blog['id'] . '<br>';
}
```

If you do not use the "select" function, you will get all fields of the related model.

```php
Blog::toArray();
```
#### Get Query Data With Soft Deleted Data

If you have soft-deleted rows, they are not included in your arrays or object arrays by default. To include soft-deleted rows, use the "withTrashed()" function as shown below.

```php
Blog::withTrashed()->select(['id','content'])->get();

Blog::withTrashed()->select(['id','content'])->toArray();
```

If you do not use the "select" function, you will get all fields of the related model. You will also get soft deleted data rows if you use "withTrashed()" function.

```php
Blog::withTrashed()->get();

Blog::withTrashed()->toArray();
```

### LIMIT

To add a LIMIT clause to a SQL query, use the "limit" function and pass an integer to it, as shown below.

In a main query:
```php
Blog::limit(1)->get();

Blog::limit(1)->toArray();
```

In subquery:
```php
Blog::whereIn('id',function($query){
	return $query->select(['id'])->limit(1)->get();
})->get();

Blog::whereIn('id',function($query){
	return $query->select(['id'])->limit(1)->get();
})->toArray();
```

### WHERE

To add a "WHERE" clause to a SQL query, use the "where" function as shown below:

<i>For the "=" operator:</i>

```php
Blog::where('id',1)->get();
```
<i>To specify an operator:</i>

```php
Blog::where('id','=',1)->get();

Blog::where('content','like','%Content%')->get();
```

### OR WHERE

To add an "OR WHERE" clause to a SQL query, use the "orWhere" function as shown below:

<i>For the "=" operator:</i>

```php
Blog::where('id',1)->orWhere('content','Content')->get();
```

<i>To specify an operator:</i>

```php
Blog::where('id',1)->orWhere('content','=','Content')->get();

Blog::where('id',1)->orWhere('content','like','%Content%')->get();
```

### WHERE IN

To add a "WHERE IN" clause to a SQL query, use the "whereIn" function as shown below:

```php
Blog::whereIn('id',[1,2])->get();
```


### WHERE NOT IN

To add a "WHERE NOT IN" clause to a SQL query, use the "whereNotIn" function as shown below:

```php
Blog::whereNotIn('id',[1,2])->get();
```

### Join

The syntax and behavior follow standard SQL JOIN syntax.

#### Inner Join

Single SQL query:
```php
Author::innerJoin('blogs','authors.id','=','blogs.author_id')
->select(['authors.*','blogs.id AS blog_id'])
->get();
```

Subquery:
```php
Blog::where('id',function($query){
	return $query->from('App\Models\Author')
	->innerJoin('blogs','authors.id','=','blogs.author_id')
	->select(['blogs.id AS blog_id'])
	->get();
})->get();
``` 

#### Left Join

Single SQL query:
```php
Author::leftJoin('blogs','authors.id','=','blogs.author_id')
->select(['authors.*','blogs.id AS blog_id'])
->get();
```

Subquery:
```php
Blog::where('id',function($query){
	return $query->from('App\Models\Author')
	->leftJoin('blogs','authors.id','=','blogs.author_id')
	->select(['blogs.id AS blog_id'])
	->get();
})->get();
``` 

#### Right Join

Single SQL query:
```php
Author::rightJoin('blogs','authors.id','=','blogs.author_id')
->select(['authors.*','blogs.id AS blog_id'])
->get();
```

Subquery:
```php
Blog::where('id',function($query){
	return $query->from('App\Models\Author')
	->rightJoin('blogs','authors.id','=','blogs.author_id')
	->select(['blogs.id AS blog_id'])
	->get();
})->get();
```

### Union

You can use the "union" function in queries.

```php
Blog::where('id',1)->union(function(){
	return Blog::where('id',2)->toSQL()->get();
})->get();
```

You can also use the "union" function in subqueries.

```php
Blog::whereIn('id', function($query) {
	return $query->select(['id'])->where('id',1)->union(function($query){
		return $query->select(['id'])->where('id',2)->get();
	})->get();
} )->get();
```

### Pagination

This library supports two types of pagination:

1. Database Pagination
2. Array Pagination

The default number of items per page is 10. You can customize this number.
The pagination functions return an array in the following format.
You can use this array data for server-side pagination in your frontend application, such as Vue or React.

```php
[
	'current_page' => 'current page number',
	'data' => 'paginated data',
	'first_page_url' => 'first page url',
	'from' => 'The number of paginated data which starts to show in current page',
	'last_page' => 'The last page number',
	'last_page_url' => 'The last page url',
	'next_page_url' => 'The next page url',
	'path' => 'the current page url',
	'per_page' => 'The number of how many data will be shown per page',
	'prev_page_url' => 'The previous page url',
	'to' => 'The number of paginated data which is last data to show in current page',
	'total' => 'The total number of paginated data in current page'
]
```

#### Database Pagination

You can paginate your query results as shown below:

```php
$paginatedBlogs = Blog::whereIn('id',[1,2,3,4,5])->paginate();
```
You can customize the number of items per page:
```php
$paginatedBlogs = Blog::whereIn('id',[1,2,3,4,5])->paginate(12);
```
You can access the paginated data as shown below. The value of the "data" key is an object array.

```php
foreach($paginatedBlogs['data'] as $blog){
	echo $blog->id.'<br>';
	echo $blog->author()->name . '<br>';
}
```
<b>You can call relationship functions directly on the objects in the loop.</b>

You can use the pagination UI in your frontend PHP file as follows:

```php
(new  JiJiHoHoCoCo\IchiORM\UI\Pagination)->paginate($paginatedBlogs);
```

You can customize the pagination UI color:

```php
(new JiJiHoHoCoCo\IchiORM\UI\Pagination)->paginate($paginatedBlogs,'#000000');
```

#### Array Pagination

You can paginate an array as shown below.

```php
use JiJiHoHoCoCo\IchiORM\Pagination\ArrayPagination;

$blogs = ['Blog One','Blog Two','Blog Three','Blog Four','Blog Five'];

$paginatedBlogs = (new ArrayPagination)->paginate($blogs);

```

You can also use a multidimensional array:

```php
use JiJiHoHoCoCo\IchiORM\Pagination\ArrayPagination;

$blogs = [
	[
		'content' => 'Blog One',
		'author_name' => 'John Doe'
	],
	[
		'content' => 'Blog Two',
		'author_name' => 'Joe Blow'
	],
	[
		'content' => 'Blog Three',
		'author_name' => 'Everyman'
	],
	[
		'content' => 'Blog Four',
		'author_name' => 'John Doe'
	],
	[
		'content' => 'Blog Five',
		'author_name' => 'John Doe'
	]
];

$paginatedBlogs = (new ArrayPagination)->paginate($blogs);

```

You can customize the number of items per page:

```php
$paginatedBlogs = (new ArrayPagination)->paginate($blogs,2);
```

You can use the pagination UI in your frontend PHP file as follows:

```php
(new  JiJiHoHoCoCo\IchiORM\UI\Pagination)->paginate($paginatedBlogs);
```

You can customize the pagination UI color:

```php
(new JiJiHoHoCoCo\IchiORM\UI\Pagination)->paginate($paginatedBlogs,'#000000');
```


### Subqueries

If you want to use a subquery within the same table, you can do so as shown below.

<i>You can use subqueries in the "where", "orWhere", and "whereIn" functions as shown below.</i>

```php
Blog::whereIn('author_id',function($query){
return $query->select(['id'])->where('id',1)->get();
})->get();
```

If you want to use a subquery from a different table, you can do so as shown below.

```php
Blog::whereIn('author_id',function($query){
	return $query->from('App\Models\Author')
	->select(['id'])
	->where('id',1)
	->get();
})->get();
```
You can use the "from" function only in subqueries. You need to provide the model class name that represents the other table to the "from" function.

If you want to use a subquery in SELECT, you can use the "addSelect" and "addOnlySelect" functions.

The "addSelect" function adds a subquery to the SELECT query.

It selects the data returned by its function in addition to the data selected by the "select" function.

If you do not use the "select" function, it selects the data returned by its function in addition to all fields from the selected table.

```php
Blog::select(['id','author_id'])
->addSelect(['autor_name' => function($query){
	return $query->from(['App\Models\Author'])
	->whereColumn('authors.id','blogs.author_id')
	->limit(1)
	->get();
}])->get();
```
<b>You can't use "addSelect" function in subqueries</b>


The "addOnlySelect" function adds a subquery to the SELECT query.
It selects only the data returned by its function.
You cannot use the other SELECT functions ("select" and "addSelect") when using "addOnlySelect".

```php
Blog::addOnlySelect(['autor_name' => function($query){
	return $query->from(['App\Models\Author'])
	->whereColumn('authors.id','blogs.author_id')
	->limit(1)
	->get();
}])->get();
```
<b>You can use "addOnlySelect" function in subqueries</b>

## Using PDO Functions

You can use PDO functions as shown below. You can use all PDO functions provided by 
https://www.php.net/manual/en/class.pdo.php

<i>To use the default database connection with a PDO object:</i>

```php
$pdo = connectPDO();

```

<i>To use the selected database connection with a PDO object:</i>

```php
use JiJiHoHoCoCo\IchiORM\Database\Connector;

$pdo = Connector::getInstance()->executeConnect('new_mysql_connection');

```

## Using Different Databases

If a model uses a different database connection, you can configure it as follows:

```php
namespace App\Models;

use JiJiHoHoCoCo\IchiORM\Database\Model;
use JiJiHoHoCoCo\IchiORM\Database\Connector;

class Author extends Model
{

	protected function connectDatabase()
	{
		return Connector::getInstance()->executeConnect('new_mysql_connection');
	}
}
```

## JSON Response

When you want to return JSON data for your API, you can simply do the following:

```php
return jsonResponse([
	'blogs' => Blog::get()
]);
```
You can customize the HTTP response code for a JSON response. The default HTTP response code is 200.

```php
return jsonResponse([
	'blogs' => Blog::get()
],202);
```

If you want to customize your JSON data, first create a resource collection class.

<i>You must extend "JiJiHoHoCoCo\IchiORM\Resource\ResourceCollection" abstract class and declare "getSelectedResource()" function for your all resource collection classes.</i>

```php
namespace App\Resources;

use JiJiHoHoCoCo\IchiORM\Resource\ResourceCollection;

class BlogResourceCollection extends ResourceCollection
{
	
	public function getSelectedResource($data)
	{
		return [
			'id' => $data->id,
			'author_id' => $data->author_id,
			'content' => $data->content,
			'created_at' => $data->created_at,
			'updated_at' => $data->updated_at
		];
	}
} 
```

You can create the resource class via terminal after creating "ichi" file as we mentioned in [Create Model From Commandline](#create-model-from-commandline)


```php

php ichi make:resource BlogResourceCollection

```

The default path for observer is "app/Resources". You can also change this in "ichi" file.

```php

$modelCommand = new ModelCommand;
$modelCommand->setResourcePath('new_app/Resources');
$modelCommand->run(__DIR__,$argv);

```

You can then return your custom JSON resource as shown below.

<b>For an object array:</b>

```php
return jsonResponse([
	'blogs' => (new BlogResourceCollection)->collection( Blog::get() ) 
]);
```

<b>For a single object:</b>

```php
return jsonResponse([
	'blog' => (new BlogResourceCollection)->singleCollection( Blog::find(1) )
]);
```

You can define relationships in your resource collection class (for both "refersTo" and "refersMany").

```php
namespace App\Resources;

use JiJiHoHoCoCo\IchiORM\Resource\ResourceCollection;

class BlogResourceCollection extends ResourceCollection
{
	
	public function getSelectedResource($data)
	{
		return [
			'id' => $data->id,
			'author' => $data->author(),
			'content' => $data->content,
			'created_at' => $data->created_at,
			'updated_at' => $data->updated_at
		];
	}
}
```

You can use another resource collection in your resource collection class, depending on whether the related data is a single object or an object array.


```php
namespace App\Resources;

use JiJiHoHoCoCo\IchiORM\Resource\ResourceCollection;
use App\Resources\AuthorResourceCollection;

class BlogResourceCollection extends ResourceCollection
{
	
	public function getSelectedResource($data)
	{
		return [
			'id' => $data->id,
			'author_id' => $data->author_id,
			'author' => (new AuthorResourceCollection)->singleCollection($data->author()),
			'content' => $data->content,
			'created_at' => $data->created_at,
			'updated_at' => $data->updated_at
		];
	}
}
```

```php
namespace App\Resources\AuthorResourceCollection;

use JiJiHoHoCoCo\IchiORM\Resource\ResourceCollection;

class AuthorResourceCollection extends ResourceCollection
{

	public function getSelectedResource($data)
	{
		return [
			'id' => $data->id,
			'name' => $data->name
		];
	}

}
```
## Caching

You can cache your query data with <a href="https://github.com/phpredis/phpredis">redis</a> or <a href="https://pecl.php.net/package/memcached">memcached</a> extensions in this library.

First, pass the Redis or Memcached object to the "setCacheObject" static function of "JiJiHoHoCoCo\IchiORM\Cache\CacheModel", as shown below.

<i>With Redis</i>

```php
use JiJiHoHoCoCo\IchiORM\Cache\CacheModel;
use Redis;

$redis = new Redis();
$redis->connect('127.0.0.1', 6379);
CacheModel::setCacheObject($redis);
```

<i>With Memcached</i>

```php
use JiJiHoHoCoCo\IchiORM\Cache\CacheModel;
use Memcached;

$memcached = new Memcached();
$memcached->addServer('127.0.0.1',11211);
CacheModel::setCacheObject($memcached);
```

<b>The connection method for Redis or Memcached may vary depending on your security requirements and available ports. The important point is that you must pass the Redis or Memcached object to the "setCacheObject" static function of "JiJiHoHoCoCo\IchiORM\Cache\CacheModel".</b>

You can then use the cache functions to store and retrieve data.

```php
use JiJiHoHoCoCo\IchiORM\Cache\CacheModel;
use App\Models\Blog;

$blogs = CacheModel::remember('blogs',function(){
		return Blog::whereIn('author_id',[1,2,3])->get();
	},100);
```

In the "remember" function, you must specify the cache key, the query or data to store, and the expiration time in seconds. The expiration time is optional; if you omit it, the data is stored indefinitely. If the specified key does not exist in the cache server, the function stores the data and returns it. If the key already exists, the function retrieves the cached data.

<b>The default expiration time is unlimited, so you should specify an expiration time when appropriate.</b>

To delete a cached key, use:

```php
use JiJiHoHoCoCo\IchiORM\Cache\CacheModel;

CacheModel::remove('blogs');
```

You can save data directly to the cache:

```php
use JiJiHoHoCoCo\IchiORM\Cache\CacheModel;

$blogs = CacheModel::save('blogs',function(){
		return  Blog::whereIn('author_id',[1,2,3])->get();
	},100);
```

To retrieve cached data:

```php
use JiJiHoHoCoCo\IchiORM\Cache\CacheModel;

$cachedBlogs = CacheModel::get('blogs');

```

You can retrieve the Redis object and use the functions provided by the Redis extension.

```php
use JiJiHoHoCoCo\IchiORM\Cache\CacheModel;

$redisObject = CacheModel::getRedis();
```

You can also retrieve the Memcached object and use the functions provided by Memcached.

```php
use JiJiHoHoCoCo\IchiORM\Cache\CacheModel;

$memcachedObject = CacheModel::getMemcached();
```

## Observers

To make observers firstly you need to create the observer class which implements <b>"JiJiHoHoCoCo\IchiORM\Observer\ModelObserver"</b> interface.

In this class, you must define the following functions:

```php
namespace App\Observers;

use JiJiHoHoCoCo\IchiORM\Observer\ModelObserver;
use App\Models\Blog;

class BlogObserver implements ModelObserver
{

	public function create($blog)
	{
		
	}

	public function update($blog)
	{
		
	}

	public function delete($blog)
	{

	}

	public function restore($blog)
	{

	}

	public function forceDelete($blog)
	{

	}

}

```

1. The "create" function is called after creating the blog model data.
2. The "update" function is called after updating the blog model data.
3. The "delete" function is called after deleting the blog model data.
4. The "restore" function is called after restoring the soft-deleted blog model data.
5. The "forceDelete" function is called after permanently deleting the blog model data.

You can create the observer via terminal after creating "ichi" file as we mentioned in [Create Model From Commandline](#create-model-from-commandline)

```php

php ichi make:observer BlogObserver

```

The default directory for observers is "app/Observers". You can also change this in the "ichi" file.

```php

$modelCommand = new ModelCommand;
$modelCommand->setObserverPath('new_app/Observers');
$modelCommand->run(__DIR__,$argv);

```


After creating observer, you must do

```php
use App\Models\Blog;
use App\Observers\BlogObserver;

Blog::observe(new BlogObserver);
```

You can also add many observers for one model

```php
use App\Models\Blog;
use App\Observers\BlogObserver;
use App\Observers\BlogDataObserver;

Blog::observe(new BlogObserver);
Blog::observe(new BlogDataObserver);
```
Observer functions are called sequentially.

If you want to observe a custom function:

<i>In model</i>

```php
namespace App\Models;
use JiJiHoHoCoCo\IchiORM\Database\Model;

class Blog extends Model
{

	publilc $id,$author_id,$content,$created_at,$updated_at,$deleted_at;

	public function customFunction()
	{
		/*----- your business logic -----*/
		
		//--- Example to pass one parameter into observer function ---//
		$currentObject = $this;
		self::getObserverSubject()->use(get_class($this),'customFunction',$currentObject);
	}

}

```

<i>In observer</i>

```php
namespace App\Observers;

use JiJiHoHoCoCo\IchiORM\Observer\ModelObserver;
use App\Models\Blog;

class BlogObserver implements ModelObserver
{

	public function customFunction($blog)
	{

	}
}
```

If you need to pass multiple parameters to an observer function:

<i>In model</i>

```php
namespace App\Models;

use JiJiHoHoCoCo\IchiORM\Database\Model;

class Blog extends Model
{

	publilc $id,$author_id,$content,$created_at,$updated_at,$deleted_at;

	public function author()
	{
		return $this->refersTo('App\Models\Author','author_id');
	}

	public function customFunction()
	{
		/*----- your business logic -----*/
		
		//--- Example to pass multiple parameter into observer function ---//
		$currentObject = $this;
		$author = $this->author();
		self::getObserverSubject()->use(get_class($this),'customFunction',[$currentObject,$author]);
	}

}

```
<i>In observer</i>

```php
namespace App\Observers;

use JiJiHoHoCoCo\IchiORM\Observer\ModelObserver;
use App\Models\Blog;
use App\Models\Author;

class BlogObserver implements ModelObserver
{

	public function customFunction($blog,$author)
	{
	
	}
}
```
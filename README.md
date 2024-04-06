# Ichi ORM

<p>Ichi ORM is aimed to be the fast performance and secure database ORM for PHP with simple usage.</p>

## License

This package is Open Source According to [MIT license](LICENSE.md)

## Table of Contents

* [Installation](#installation)
* [Set up Database Connection](#set-up-database-connection)
* [Available Database Setting](#available-database-setting)
* [Table Structure](#table-structure)
* [Create Model From Commandline](#create-model-from-commandline)
* [Configuration Table Name](#configuration-table-name)
* [Configuration Primary Key](#configuration-primary-key)
* [CRUD](#crud)
	* [Create](#create)
		* [Disable Auto increment Id](#disable-auto-increment-id)
	* [Insert Multiple Rows In One Query](#insert-multiple-rows-in-one-query)
	* [Retrieve](#retrieve)
		* [Refers To](#refers-to)
		* [Refers Many](#refers-many)
	* [Update](#update)
	* [Update Multiple Rows In One Query](#update-multiple-rows-in-one-query)
	* [Delete](#delete)
* [Querying](#querying)
	* [SELECT](#select)
	* [Getting Query Data](#getting-query-data)
		* [Get](#get)
		* [To Array](#to-array)
		* [Get Query Data With Soft Deleted Data](#get-query-data-with-soft-deleted-data)
	* [LIMIT](#limit)
	* [WHERE](#where)
	* [OR WHERE](#or-where)
	* [WHERE IN](#where-in)
	* [WHERE NOT IN](#where-not-in)
	* [Join](#join)
		* [Inner Join](#inner-join)
		* [Left Join](#left-join)
		* [Right Join](#right-join)
	* [Union](#union)
	* [Pagination](#pagination)
		* [Database Pagination](#database-pagination)
		* [Array Pagination](#array-pagination)
	* [Subqueries](#subqueries)
* [Using PDO Functions](#using-pdo-functions)
* [Using Different Databases](#using-different-databases)
* [JSON Response](#json-response)
* [Caching](#caching)
* [Observers](#observers)

## Installation

```php
composer require jijihohococo/ichi-orm
```

## Set up Database Connection

This library can connect MySQL, Postgres and MS SQL Server.

Firstly, you need to declare your database driver like below.

```php

use JiJiHoHoCoCo\IchiORM\Database\Connector;

$connector=new Connector;
$connector->createConnection('mysql',[
	'dbname' => 'database_name',
	'charset' => 'utf8mb4',
	'collation' => 'utf8mb4_unicode_ci',
	'host' => '127.0.0.1',
	'user_name' => 'user_name',
	'user_password' => 'user_password'
]);
```
If you want to add another custom database connection, you can do just like that.

You must add dbname,host,user_name and user_password in your database connection. I recomend you to use "utf8mb4" for your database charset and "utf8mb4_unicode_ci" for your database collation.

<i>In defalt database connections, you don't need to add driver parameters but in your custom database connection you have to add driver parameters.</i>

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
Default database connections are 'mysql' , 'pgsql' and 'sqlsrv'.

Supported database drivers are 'mysql' , 'pgsql' and 'sqlsrv'.

After declaring database connection, you can select default database connection

```php
$connector->selectConnection('mysql');
```

### Available Database Setting

| Name                         | Description                                                    | Required |
|------------------------------|----------------------------------------------------------------|----------|
| driver                       | Database driver name                                           |   [x]    |
| dbname                       | Database name                                                  |   [x]    |
| charset                      | Charset Font                                                   |          |
| collation                    | Collation Font Setting for MySQL and Postgres SQL              |          |
| host                         | Database Host Address                                          |   [x]    |
| user_name                    | Database User Name                                             |   [x]    |
| user_password                | Database User Password                                         |   [x]    |
| unix_socket                  | Unix Socket For MySQL                                          |          |
| port                         | Databse Port Number                                            |          |
| strict (bool)                | Strict Mode In MySQL                                           |          |
| time_zone                    | Database Time Zone in MySQL and Postgres SQL                   |          |
| isolation_level              | To set Isolation Level in MySQL                                |          |
| modes (array)                | To set sql_mode in MySQL                                       |          |
| synchronous_commit           | To set Synchronous Commit in Postgres SQL                      |          |
| sslmode                      | To set SSL Mode in Postgres SQL                                |          |
| sslcert                      | To set SSL Certificate in Postgres SQL                         |          |
| sslkey                       | To set SSL Key in Postgres SQL                                 |          |
| sslrootcert                  | To set SSL Root Certificate in Postgres SQL                    |          |
| readOnly (bool)              | True To set ApplicationIntent to ReadOnly in MS SQL Server     |          |
| pooling (bool)               | True To set ConnectionPooling to true in MS SQL Server         |          |
| application_name             | To set APP in MS SQL Server OR application name in Postgres SQL|          |
| encrypt                      | To set ENCRYPT in MS SQL Server                                |          |
| trust_server_certificate     | To set TrustServerCertificate in MS SQL Server                 |          |
| multiple_active_result_sets  | To set MultipleActiveResultSets in MS SQL Server               |          |
| transaction_isolation        | To set TransactionIsolation in MS SQL Server                   |          |
| multi_subnet_failover        | To set MultiSubnetFailover in MS SQL Server                    |          |
| column_encryption            | To set ColumnEncryption in MS SQL Server                       |          |
| key_store_authentication     | To set KeyStoreAuthentication in MS SQL Server                 |          |
| key_store_principal_id       | TO set KeyStorePrincipalId in MS SQL Server                    |          |
| key_store_secret             | To set KeyStoreSecret in MS SQL Server                         |          |
| login_timeout                | To set LoginTimeout in MS SQL Server                           |          |


## Table Structure

If you have the column named "deleted_at", be sure that the column is NULLABLE column.

## Create Model From Commandline

Firstly you need to created the file named "ichi" under your project folder and use the below code in this file

```php
#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

use JiJiHoHoCoCo\IchiORM\Command\ModelCommand;


$modelCommand=new ModelCommand;
$modelCommand->run(__DIR__,$argv);

```

And then you can create the model in your commandline

```php

php ichi make:model Blog

```

The default file folder is "app/Models". So after making command, the model you created will be in the this default file folder. If you want to change the default folder path, you can change it in your "ichi" file.


```php

$modelCommand=new ModelCommand;
$modelCommand->setPath('new_app/Models');
$modelCommand->run(__DIR__,$argv);

```

## Configuration Table Name

In Ichi ORM, one model class which is extended <b>"JiJiHoHoCoCo\IchiORM\Database\Model"</b> abstract class is represented one table.

In default, the table name of the model class will show according to the format below

| Model     | Table       |
|-----------|-------------|
| Item      | items       |
| OrderItem | order_items |

If the above format is not suitable for the model class, you can customize in your model class

```php
namespace App\Models;

use JiJiHoHoCoCo\IchiORM\Database\Model;

class Blog extends Model{
	
	protected function getTable(){
		return "order_item_details";
	}
}
```

## Configuration Primary Key

In default, the primary key for the table is represented "id". If you want to change that, you can customize in your model class

```php
namespace App\Models;

use JiJiHoHoCoCo\IchiORM\Database\Model;

class Blog extends Model{
	
	protected function getID(){
		return "blog_id";
	}
}
```

## CRUD

Firstly, you need to extend Model Class from your class and declare your data fields as attributes in your model as shown as below.

```php
namespace App\Models;
use JiJiHoHoCoCo\IchiORM\Database\Model;
class Blog extends Model{

	publilc $id,$author_id,$content,$created_at,$updated_at,$deleted_at;

}
```

### Create

You can create the data as shown as below.

```php

Blog::create([
	'author_id' => 1,
	'content' => 'Content'
]);

```

<b>It is your choice to add or not to add the nullable field data into array in "create" function.</b>

<b>If you have "created_at" data field, you don't need to add any data for that data field. Ichi ORM will automatically insert current date time for this data field. The data field must be in the format of timestamp or varchar.</b>

You can get the new model object after creating.

<b> App\Models\Blog Object ( [id] => 1 [author_id] => 1 [content] => Content [created_at] => 2021-10-01 12:02:26 [updated_at] => [deleted_at] => )</b>

#### Disable Auto increment Id

If you don't use auto increment id in your table you must write this function in your model class

```php


protected function autoIncrementId(){
		return FALSE;
}

```

And you must add your ID Values from your side manually like this

```php

Blog::create([
	'id' => 1 ,
	'author_id' => 1,
	'content' => 'Content'
]);

```

### Insert Multiple Rows In One Query

If you want to insert multiple rows in one query you can do according to below coding flow.

```php
use App\Models\Blog;

$contents=$_REQUEST['content'];
$insertBlogs=[];
foreach ($contents as $key => $content) {
	$insertBlogs[]=[
		'content' => $content,
		'author_id' => $_REQUEST['author_id'][$key]
	];
}

Blog::insert($insertBlogs);
```

### Retrieve

You can get your data by your primary key as shown as below.

```php
Blog::find(1);
```

If you don't want to get your data by your primary key, you can do as shown as below.

```php
Blog::findBy('content','Content');
```
<i>First Parameter is field name and second parameter is value.</i>

<b>You can get only single object by using "find" and findBy" function.</b>

#### Refers To

If you have one to one relationship in your database (with foreign keys or without foreign keys), you can use "refersTo" function in child model class as shown as below. The function will output the single object.

<b>You must add parent model name, the field that represent parent id into "refersTo" function if parent model's primary key is "id".</b>

```php
namespace App\Models;

use JiJiHoHoCoCo\IchiORM\Database\Model;

class Blog extends Model{

	publilc $id,$author_id,$content,$created_at,$updated_at,$deleted_at;

	public function author(){
		return $this->refersTo('App\Models\Author','author_id');
	}
}
```

<b>You must add parent model name, the field name that represent parent id and parent primary key field into "refersTo" function if parent model's primary key is not "id".</b>

```php
namespace App\Models;

use JiJiHoHoCoCo\IchiORM\Database\Model;

class Blog extends Model{

	publilc $id,$author_id,$content,$created_at,$updated_at,$deleted_at;

	public function author(){
		return $this->refersTo('App\Models\Author','author_id','authorID');
	}
}
```

You can get parent data as single object in your controller or class.

```php
use App\Models\Blog;

$blogObject=Blog::find(1);
$authorObject=$blogObject->author();
$authorId=$authorObject->id;
```

<b>You don't need to worry about null. It has null safety.</b>

#### Refers Many

If you have one to many relationship in your database (with foreign keys or without foreign keys), you can use "refersMany" function in parent model class as shown as below. The function will output the object array.

<b>You must add child model name and the field name that represent parent id in child model into "refersMany" function if parent model's primary key is "id".</b>

```php
namespace App\Models;

use JiJiHoHoCoCo\IchiORM\Database\Model;

class Author extends Model{
 	
 	publilc $id,$name,$created_at,$updated_at,$deleted_at;

 	public function blogs(){
 		return $this->refersMany('App\Models\Blog','author_id')->get();
 	}

}
```

<b>You must add child model name, the field name that represent parent id in child model and parent primary key field into "refersMany" function if parent model's primary key is not "id".</b>

```php
namespace App\Models;

use JiJiHoHoCoCo\IchiORM\Database\Model;

class Author extends Model{
 	
 	publilc $authorID,$name,$created_at,$updated_at,$deleted_at;

 	public function blogs(){
 		return $this->refersMany('App\Models\Blog','author_id','authorID')->get();
 	}

}
```

You can customize the child query

```php
return $this->refersMany('App\Models\Blog','author_id','authorID')->latest()->get();
```


You can get child data as object array in your controller or class.

```php
use App\Models\Author;

$authorObject=Author::find(1);
$blogs=$authorObject->blogs();
```


### Update

You can update your data as shown as below.

```php
Blog::find(1)->update([
	'content' => 'New Content'
]);
```

You can get the model object after updating

<b>If you have "updated_at" data field, you don't need to add any data for that data field. Ichi ORM will automatically insert current date time for this data field. The data field must be in the format of timestamp or varchar.</b>

<b> App\Models\Blog Object ( [id] => 1 [author_id] => 1 [content] => New Content [created_at] => 2021-10-01 12:02:26 [updated_at] => 2021-10-01 12:03:26 [deleted_at] => )</b>

### Update Multiple Rows In One Query

If you want to update multiple rows in one query you can do according to below coding flow.

```php
use App\Models\Blog;

$blogs=Blog::get();
$updateBlogs=[];

foreach($blogs as $key => $blog){

	$updateBlogs[]=[
		'content' => $_REQUEST['content'][$key],
		'author_id' => $_REQUEST['author_id'][$key],
	];
}

Blog::bulkUpdate($updateBlogs);
```

### Delete

You can delete your data as shown as below.

```php
Blog::find(1)->delete();
```
If you have "deleted_at" data field and "deleted_at" data field is nullable, you have soft delete function. So, the data will not actually delete after deleting but this data will not be shown in querying in default.

<i>Soft Delete Functions can't be used if you don't have "delete_at" data field and the data will be deleted.</i>

If you want to restore your soft deleted data, you can do as shown as before.

```php
Blog::find(1)->restore();
```

If you want to force to delete your data (whatever it is able to be soft deleted or not), you can do as shown as before.

```php
Blog::find(1)->forceDelete();
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
Blog::select(['id','content'])
```

```php
Blog::select(['blogs.id','blogs.content'])
```

### Getting Query Data

You can get your query data with "get()" and "toArray()" functions.

#### Get

"get()" function can use in main query and subquery. This function will return the object array of related model when it is used in main query as shown as below.


<b>Array ( [0] => App\Models\Blog Object ( [id] => 1 [author_id] => 1 [content] => Content [created_at] => 2021-10-01 12:02:26 [updated_at] => 2021-10-01 12:02:26 [deleted_at] => ) )</b>

<b>You can call relationship functions directly with the object in the loop because "get()" function outputs the object array</b>

```php
$blogs=Blog::select(['id','content'])->get();

foreach($blogs as $blog){
	echo $blog->id . '<br>';
	echo $blog->author()->name . '<br>';
}
```

If you don't use select function, you will get all data fields of related model.

```php
Blog::get();
```

#### To Array

"toArray()" function can use in only main query. This function will return the array for thre query as shown as below.

<b>Array ( [0] => Array ( [id] => 1 [author_id] => 1 [content] => Content [created_at] => 2021-10-01 12:02:26 [updated_at] => 2021-10-01 12:02:26 [deleted_at] => ) )</b>

<b>You can't call relationship functions directly with the object in the loop because "toArray()" function outputs the array.</b>

<b>You can't use "toArray" function in subquery.</b>

```php
$blogs=Blog::select(['id','content'])->toArray();

foreach($blogs as $blog){
	echo $blog['id'] . '<br>';
}
```
If you don't use select function, you will get all data fields of related model.

```php
Blog::toArray();
```
#### Get Query Data With Soft Deleted Data

If you have soft deleted data rows, you can't see those in your array or data object array. If you want to see the array or data object array with soft deleted data rows, you must use "withTrashed()" function as shown as below.

```php
Blog::withTrashed()->select(['id','content'])->get();

Blog::withTrashed()->select(['id','content'])->toArray();
```

If you don't use select function, you will get all data fields of related model. You will also get soft deleted data rows if you use "withTrashed()" function.

```php
Blog::withTrashed()->get();

Blog::withTrashed()->toArray();
```

### LIMIT

To make limit sql query, you can use "limit" function and put the integer into this function as shown as below

In main query
```php
Blog::limit(1)->get();

Blog::limit(1)->toArray();
```

In subquery
```php
Blog::whereIn('id',function($query){
	return $query->select(['id'])->limit(1)->get();
})->get();

Blog::whereIn('id',function($query){
	return $query->select(['id'])->limit(1)->get();
})->toArray();
```

### WHERE

To make "WHERE" sql query, you can use "where" function as shown as below

<i>In case of '='</i>
```php
Blog::where('id',1)->get();
```
<i>If you want to add operators</i>

```php
Blog::where('id','=',1)->get();

Blog::where('content','like','%Content%')->get();
```


### OR WHERE

To make "OR WHERE" sql query, you can use "orWhere" function as shown as below


<i>In case of '=' </i>
```php
Blog::where('id',1)->orWhere('content','Content')->get();
```

<i>If you want to add operators</i>

```php
Blog::where('id',1)->orWhere('content','=','Content')->get();

Blog::where('id',1)->orWhere('content','like','%Content%')->get();
```

### WHERE IN

To make "WHERE IN" sql query, you can use "whereIn" function as shown as below

```php
Blog::whereIn('id',[1,2])->get();
```


### WHERE NOT IN

To make "WHERE NOT IN" sql query, you can use "whereNotIn" function as shown as below

```php
Blog::whereNotIn('id',[1,2])->get();
```

### Join

The rules and flows are same as SQL Join.

#### Inner Join

Single SQL Query
```php
Author::innerJoin('blogs','authors.id','=','blogs.author_id')
->select(['authors.*','blogs.id AS blog_id'])
->get();
```

Subquery
```php
Blog::where('id',function($query){
	return $query->from('App\Models\Author')
	->innerJoin('blogs','authors.id','=','blogs.author_id')
	->select(['blogs.id AS blog_id'])
	->get();
})->get();
``` 

#### Left Join

Single SQL Query
```php
Author::leftJoin('blogs','authors.id','=','blogs.author_id')
->select(['authors.*','blogs.id AS blog_id'])
->get();
```

Subquery
```php
Blog::where('id',function($query){
	return $query->from('App\Models\Author')
	->leftJoin('blogs','authors.id','=','blogs.author_id')
	->select(['blogs.id AS blog_id'])
	->get();
})->get();
``` 

#### Right Join

Single SQL Query
```php
Author::rightJoin('blogs','authors.id','=','blogs.author_id')
->select(['authors.*','blogs.id AS blog_id'])
->get();
```

Subquery
```php
Blog::where('id',function($query){
	return $query->from('App\Models\Author')
	->rightJoin('blogs','authors.id','=','blogs.author_id')
	->select(['blogs.id AS blog_id'])
	->get();
})->get();
```

### Union

You can use "union" function in queries.

```php
Blog::where('id',1)->union(function(){
	return Blog::where('id',2)->toSQL()->get();
})->get();
```

You can use "union" function in subqueries.

```php
Blog::whereIn('id', function($query) {
	return $query->select(['id'])->where('id',1)->union(function($query){
		return $query->select(['id'])->where('id',2)->get();
	})->get();
} )->get();
```

### Pagination

In this library, you can use two types of pagination.

1. Database Pagination
2. Array Pagination

The default paginated data per page is 10. You can customize that number.
Pagination functions will output the array according to the below format.
So, you can use server pagination into your frontend (like Vue and React) with that array data.

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

You can paginate your query result like that

```php
$paginatedBlogs=Blog::whereIn('id',[1,2,3,4,5])->paginate();
```
You can customize the number of paginated data by

```php
$paginatedBlogs=Blog::whereIn('id',[1,2,3,4,5])->paginate(12);
```
You can get paginated data like below. The data in "data" array key is object array.

```php
foreach($paginatedBlogs['data'] as $blog){
	echo $blog->id.'<br>';
	echo $blog->author()->name . '<br>';
}
```
<b>You can call relationship functions directly with the object in the loop.</b>

You can use pagination user interface in your frontend php file like

```php
(new  JiJiHoHoCoCo\IchiORM\UI\Pagination)->paginate($paginatedBlogs);
```

You can customize the pagination user interface color

```php
(new JiJiHoHoCoCo\IchiORM\UI\Pagination)->paginate($paginatedBlogs,'#000000');
```

#### Array Pagination

You can paginate your array like below.

```php
use JiJiHoHoCoCo\IchiORM\Pagination\ArrayPagination;

$blogs=['Blog One','Blog Two','Blog Three','Blog Four','Blog Five'];

$paginatedBlogs=(new ArrayPagination)->paginate($blogs);

```

You can also use multidimensional array

```php
use JiJiHoHoCoCo\IchiORM\Pagination\ArrayPagination;

$blogs=[
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

$paginatedBlogs=(new ArrayPagination)->paginate($blogs);

```

You can customize the number of paginated data by

```php
$paginatedBlogs=(new ArrayPagination)->paginate($blogs,2);
```

You can use pagination user interface in your frontend php file like

```php
(new  JiJiHoHoCoCo\IchiORM\UI\Pagination)->paginate($paginatedBlogs);
```

You can customize the pagination user interface color

```php
(new JiJiHoHoCoCo\IchiORM\UI\Pagination)->paginate($paginatedBlogs,'#000000');
```


### Subqueries

If you want to use subquery within one table you can do as shown as before.

<i>You can use subqueries as shown as below in "where","orWhere" and "whereIn" functions.</i>
```php
Blog::whereIn('author_id',function($query){
return $query->select(['id'])->where('id',1)->get();
})->get();
```

If you want to use subquery from different table you can do as shown as before.

```php
Blog::whereIn('author_id',function($query){
	return $query->from('App\Models\Author')
	->select(['id'])
	->where('id',1)
	->get();
})->get();
```
You can use "from" function in only subqueries. You need to add model class name which is represented the another table in "from" function.

If you want to use subquery in select, you can use "addSelect" and "addOnlySelect" functions.

"addSelect" function is making subquery in select query.
It will select the data within its function with the data from "select" function.
If you don't use "select" function, it will select the data within its function with the data of all fields' values of selected table.

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


"addOnlySelect" function is making subquery in select query.
It will select only the data within its function.
You can't use other select functions("select" and "addSelect") if you want to use "addOnlySelect" function. 

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

You can use PDO functions like that. You can use all PDO functions according to 
https://www.php.net/manual/en/class.pdo.php

<i>If you want to use default database connection with PDO object</i>
```php
$pdo=connectPDO();

```

<i>If you want to use selected database connection with PDO object</i>
```php
use JiJiHoHoCoCo\IchiORM\Database\Connector;

$pdo=Connector::getInstance()->executeConnect('new_mysql_connection');

```

## Using Different Databases

If you have the model which is from different database you can connect like that

```php
namespace App\Models;

use JiJiHoHoCoCo\IchiORM\Database\Model;
use JiJiHoHoCoCo\IchiORM\Database\Connector;
class Author extends Model{

	protected function connectDatabase(){
		return Connector::getInstance()->executeConnect('new_mysql_connection');
	}
}
```

## JSON Response

When you want to do json data of for your API you can simply do as shown as below.

```php
return jsonResponse([
	'blogs' => Blog::get()
]);
```
You can customize http response code for json response. Default http response code is 200.

```php
return jsonResponse([
	'blogs' => Blog::get()
],202);
```

If you want to customize your JSON data, firstly you need to create the class.

<i>You must extend "JiJiHoHoCoCo\IchiORM\Resource\ResourceCollection" abstract class and declare "getSelectedResource()" function for your all resource collection classes.</i>
```php
namespace App\Resources;

use JiJiHoHoCoCo\IchiORM\Resource\ResourceCollection;

class BlogResourceCollection extends ResourceCollection{
	
	public function getSelectedResource($data){
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

$modelCommand=new ModelCommand;
$modelCommand->setResourcePath('new_app/Resources');
$modelCommand->run(__DIR__,$argv);

```




And then, you can do to show to your custom JSON Resource as shown as below.

<b>For Object Array- </b>
```php
return jsonResponse([
	'blogs' => (new BlogResourceCollection)->collection( Blog::get() ) 
]);
```

<b>For Single Object- </b>
```php
return jsonResponse([
	'blog' => (new BlogResourceCollection)->singleCollection( Blog::find(1) )
]);
```

You can declare your relationship in your resource collection class (For refers to and refers many).

```php
namespace App\Resources;

use JiJiHoHoCoCo\IchiORM\Resource\ResourceCollection;

class BlogResourceCollection extends ResourceCollection{
	
	public function getSelectedResource($data){
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

You can declare another resource collection (according to the data is single object or object array) in your resource collection class.


```php
namespace App\Resources;

use JiJiHoHoCoCo\IchiORM\Resource\ResourceCollection;
use App\Resources\AuthorResourceCollection;

class BlogResourceCollection extends ResourceCollection{
	
	public function getSelectedResource($data){
		return [
			'id' => $data->id,
			'author_id' => $data->author_id,
			'author' => (new AuthorResourceCollection)->singleCollection( $data->author() )  ,
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

class AuthorResourceCollection extends ResourceCollection{

	public function getSelectedResource($data){
		return [
			'id' => $data->id,
			'name' => $data->name
		];
	}

}
```
## Caching

You can cache your query data with <a href="https://github.com/phpredis/phpredis">redis</a> or <a href="https://pecl.php.net/package/memcached">memcached</a> extensions in this library.

Firstly, you need to pass the object of redis or memcached into the "JiJiHoHoCoCo\IchiORM\Cache\CacheModel" static function "setCacheObject" like below.


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

<b>It might be different of connecting the way of redis or memcached to each other according to the security and ports' availabilities. The important thing is you must pass the redis or memcached object into the "setCacheObject" static function of "JiJiHoHoCoCo\IchiORM\Cache\CacheModel".</b>

And then, you can call the cache functions to store and get.

```php
use JiJiHoHoCoCo\IchiORM\Cache\CacheModel;
use App\Models\Blog;

$blogs=CacheModel::remember('blogs',function(){
		 	return  Blog::whereIn('author_id',[1,2,3])->get();
		 },100);
```

In "remember" function you must declare the cached key name,and the stored query or data and expired time in seconds. Without adding expired time is also ok but it will save the data into the unlimited time. This function will store the data if the declared cached key is not in the cached server and get the cached data if the declared cached key is in the cached server.

<b>The default stored time is unlimited. So you must declare the stored time for your cached server</b>


If you want to delete your cached key, you can do

```php
use JiJiHoHoCoCo\IchiORM\Cache\CacheModel;

CacheModel::remove('blogs');
```

You can just save your data in your cache

```php
use JiJiHoHoCoCo\IchiORM\Cache\CacheModel;

$blogs=CacheModel::save('blogs',function(){
		 	return  Blog::whereIn('author_id',[1,2,3])->get();
		 },100);
```

To get your cached data

```php
use JiJiHoHoCoCo\IchiORM\Cache\CacheModel;

$cachedBlogs=CacheModel::get('blogs');

```

You can get back your redis object to implement the functions of redis extension.

```php
use JiJiHoHoCoCo\IchiORM\Cache\CacheModel;

$redisObject=CacheModel::getRedis();
```

You can also get back your memcached object to implement the functions of memcached.

```php
use JiJiHoHoCoCo\IchiORM\Cache\CacheModel;

$memcachedObject=CacheModel::getMemcached();
```

## Observers

To make observers firstly you need to create the observer class which implements <b>"JiJiHoHoCoCo\IchiORM\Observer\ModelObserver"</b> interface.

In this created class, you must declare the functions as shown as below.

```php
namespace App\Observers;

use JiJiHoHoCoCo\IchiORM\Observer\ModelObserver;
use App\Models\Blog;
class BlogObserver implements ModelObserver{

	public function create($blog){
		
	}

	public function update($blog){
		
	}

	public function delete($blog){

	}

	public function restore($blog){

	}

	public function forceDelete($blog){

	}

}

```

1. "create" function will load after creating the data of blog model.
2. "update" function will load after updating the data of blog model.
3. "delete" function will load after deleting the data of blog model.
4. "restore" function will load after restoring the soft deleted data of blog model.
5. "forceDelete" function will load after force deleting the data of blog model.

You can create the observer via terminal after creating "ichi" file as we mentioned in [Create Model From Commandline](#create-model-from-commandline)

```php

php ichi make:observer BlogObserver

```

The default path for observer is "app/Observers". You can also change this in "ichi" file.

```php

$modelCommand=new ModelCommand;
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
use App\Observers\{BlogObserver,BlogDataObserver};

Blog::observe(new BlogObserver);
Blog::observe(new BlogDataObserver);
```
The observers' functions will load sequetly.

If you want to observe your custom function

<i>In model</i>
```php
namespace App\Models;
use JiJiHoHoCoCo\IchiORM\Database\Model;
class Blog extends Model{

	publilc $id,$author_id,$content,$created_at,$updated_at,$deleted_at;

	public function customFunction(){
		/*----- your business logic -----*/
		
		//--- Example to pass one parameter into observer function ---//
		$currentObject=$this;
		self::$observerSubject->use(get_class($this),'customFunction',$currentObject);
	}

}

```

<i>In observer</i>
```php
namespace App\Observers;

use JiJiHoHoCoCo\IchiORM\Observer\ModelObserver;
use App\Models\Blog;
class BlogObserver implements ModelObserver{

	public function customFunction($blog){

	}
}
```

If you need to pass multiple parameters in observer function.

<i>In model</i>
```php
namespace App\Models;
use JiJiHoHoCoCo\IchiORM\Database\Model;
class Blog extends Model{

	publilc $id,$author_id,$content,$created_at,$updated_at,$deleted_at;

	public function author(){
		return $this->refersTo('App\Models\Author','author_id');
	}

	public function customFunction(){
		/*----- your business logic -----*/
		
		//--- Example to pass multiple parameter into observer function ---//
		$currentObject=$this;
		$author=$this->author();
		self::$observerSubject->use(get_class($this),'customFunction',[$currentObject,$author]);
	}

}

```
<i>In observer</i>
```php
namespace App\Observers;

use JiJiHoHoCoCo\IchiORM\Observer\ModelObserver;
use App\Models\{Blog,Author};
class BlogObserver implements ModelObserver{

	public function customFunction($blog,$author){
	
	}
}
```
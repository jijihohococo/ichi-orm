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
* [CRUD](#crud)
	* [Create](#create)
	* [Insert Multiple Rows In One Query](#insert-multiple-rows-in-one-query)
	* [Retrieve](#retrieve)
		* [Refers To](#refers-to)
		* [Refers Many](#refers-many)
	* [Update](#update)
	* [Update Multiple Rows In One Query](#update-multiple-rows-in-one-query)
	* [Delete](#delete)
* [Querying](#querying)
	* [SELECT](#select)
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
	* [Subqueries](#subqueries)
* [Using PDO Functions](#using-pdo-functions)
* [Using Different Databases](#using-different-databases)
* [JSON Response](#json-response)
* [Caching](#caching)
* [Observers](#observers)

## Installing

## Set up Database Connection

Firstly, you need to declare your database driver like below.

```php

use JiJiHoHoCoCo\IchiORM\Database\Connector;

$connector=new Connector;
$connector->connect('mysql',[
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
| driver        (R)  | Database driver name                       |
| dbname        (R)  | Database name                              |
| charset            | Charset Font                               |
| collation          | Collation Font                             |
| host          (R)  | Database Host Address                      |
| user_name     (R)  | Database User Name                         |
| user_password (R)  | Database User Password                     |
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

<i>R means required</i>

## Configuration Table Name

In Ichi ORM, one model class which is extended "JiJiHoHoCoCo\IchiORM\Database\Model" abstract class is represented one table.

In default, the table name of the model class will show according to the format below

| Model     | Table       |
|-----------|-------------|
| Item      | items       |
| OrderItem | order_items |

If the above format is not suitable for the model class, you can customize in your model class

```php
namespace App\Models\Blog;

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
namespace App\Models\Blog;

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
namespace App\Models\Blog;
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

### Insert Multiple Rows In One Query

If you want to insert multiple rows in one query you can do according to below coding flow.

```php
use App\Models\Blog;

$contents=$_REQUEST['content'];
$insertBlogs=[];
foreach ($contents as $key => $content) {
	$insertBlogs[]=[
		'name' => $content,
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

If you have one to one relationship in your database (with foreign keys or without foreign keys), you can use "refersTo" function in child model class as shown as below

<i>You must add parent model name, the field that represent parent id into "refersTo" function if parent model's primary key is "id".</i>

```php
namespace App\Models\Blog;

use JiJiHoHoCoCo\IchiORM\Database\Model;

class Blog extends Model{

	publilc $id,$author_id,$content,$created_at,$updated_at,$deleted_at;

	public function author(){
		return $this->refersTo('App\Models\Author','author_id');
	}
}
```

<i>You must add parent model name, the field name that represent parent id and parent primary key field into "refersTo" function if parent model's primary key is not "id".</i>

```php
namespace App\Models\Blog;

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

If you have one to many relationship in your database (with foreign keys or without foreign keys), you can use "refersMany" function in parent model class as shown as below

<i>You must add child model name and the field name that represent parent id in child model into "refersMany" function if parent model's primary key is "id".</i>

```php
namespace App\Models\Author;

use JiJiHoHoCoCo\IchiORM\Database\Model;

class Author extends Model{
 	
 	publilc $id,$name,$created_at,$updated_at,$deleted_at;

 	public function blogs(){
 		return $this->refersMany('App\Models\Blog','author_id')->get();
 	}

}
```

<i>You must add child model name, the field name that represent parent id in child model and parent primary key field into "refersMany" function if parent model's primary key is not "id".</i>

```php
namespace App\Models\Author;

use JiJiHoHoCoCo\IchiORM\Database\Model;

class Author extends Model{
 	
 	publilc $authorID,$name,$created_at,$updated_at,$deleted_at;

 	public function blogs(){
 		return $this->refersMany('App\Models\Blog','author_id','authorID')->get();
 	}

}
```

<i>You can customize the child query</i>

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
Blog::select(['id','name'])
```

```php
Blog::select(['blogs.id','blogs.name'])
```

To get your query result you must use "get()" or "toArray()" functions

"get()" function can use in main query and subquery. This function will return the object array of related model when it is used in main query as shown as below.


<b>Array ( [0] => App\Models\Blog Object ( [id] => 1 [author_id] => 1 [content] => Content [created_at] => 2021-10-01 12:02:26 [updated_at] => 2021-10-01 12:02:26 [deleted_at] => ) )</b>

```php
Blog::select(['id','name'])->get();
```

You can use subqueries in select with two functions "addSelect" and "addOnlySelect".

<i>"addSelect" function is adding subqueries select</i>
```php
Blog::select(['id','author_id'])
->addSelect(['autor_name' => function($query){
	return $query->from(['App\Models\Author'])
	->whereColumn('authors.id','blogs.author_id')
	->limit(1)
	->get();
}])->get();
```

<i>"addOnlySelect" function is selecting only data from that function</i>
```php
Blog::addOnlySelect(['autor_name' => function($query){
	return $query->from(['App\Models\Author'])
	->whereColumn('authors.id','blogs.author_id')
	->limit(1)
	->get();
}])->get();
```

"toArray()" function can use in only main query. This function will return the array for thre query as shown as below.

<b>Array ( [0] => Array ( [id] => 1 [author_id] => 1 [content] => Content [created_at] => 2021-10-01 12:02:26 [updated_at] => 2021-10-01 12:02:26 [deleted_at] => ) )</b>

```php
Blog::select(['id','name'])->toArray();
```

If you have soft deleted data rows, you can't seee those in your array or data object array. If you want to see the array or data object array with soft deleted data rows, you must use "withTrashed()" function as shown as before.

```php
Blog::withTrashed()->select(['id','name'])->get();

Blog::withTrashed()->select(['id','name'])->toArray();
```

If you don't use select function, you will get all data fields of related model.(If you use "withTrashed()" function you will also get soft deleted data rows)

```php
Blog::get();

Blog::toArray();

Blog::withTrashed()->get();

Blog::withTrashed()->toArray();
```

### LIMIT

To make limit sql query, you can use "limit" function and put the integer into this function as shown as below

```php
Blog::limit(1)->get();

Blog::limit(1)->toArray();
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

To make "WHERE NOT IN" sql query, you can user "whereNotIn" function as shown as below

```php
Blog::whereNotIn('id',[1,2])->get();
```

### Join

The rules and flows are same as SQL Join.

#### Inner Join

Single SQL Query
```php
Author::innerJoin('blogs','authors.id','=','blogs.author_id')->select(['authors.*','blogs.id AS blog_id'])->get();
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
Author::leftJoin('blogs','authors.id','=','blogs.author_id')->select(['authors.*','blogs.id AS blog_id'])->get();
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
Author::rightJoin('blogs','authors.id','=','blogs.author_id')->select(['authors.*','blogs.id AS blog_id'])->get();
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
	});
} )->get();
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

If you have the model which is from different database you can like that

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
namespace App\Resource\BlogResourceCollection;

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
namespace App\Resource\BlogResourceCollection;

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
namespace App\Resource\BlogResourceCollection;

use JiJiHoHoCoCo\IchiORM\Resource\ResourceCollection;
use App\Resource\AuthorResourceCollection;

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
namespace App\Resource\AuthorResourceCollection;

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

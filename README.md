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
	* [Retrieve](#retrieve)
		* [Refers To](#refers-to)
		* [Refers Many](#refers-many)
	* [Update](#update)
	* [Delete](#delete)
* [Querying](#querying)
	* [SELECT](#select)
	* [WHERE](#where)
	* [OR WHERE](#or-where)
	* [WHERE IN](#where-in)
	* [WHERE NOT IN](#where-not-in)
	* [Join](#join)
		* [Inner Join](#inner-join)
		* [Left Join](#left-join)
		* [Right Join](#right-join)
	* [Subqueries](#subqueries)
* [Using PDO Functions](#using-pdo-functions)
* [Using Different Databases](#using-different-databases)
* [JSON Response](#json-response)

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

## CRUD

Firstly, you need to extend Model Class from your class and declare your data fields as attributes in your model as shown as below.

```php
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
<b>If you have "created_at" data field, you don't need to add any data for that data field. Ichi ORM will automatically insert current date time for this data field. The data field must be in the format of timestamp or varchar.</b>

You can get the new model object after creating.

<b> App\Models\Blog Object ( [id] => 1 [author_id] => 1 [content] => Content [created_at] => 2021-10-01 12:02:26 [updated_at] => [deleted_at] => )</b>

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

#### Inner Join

You can do inner join as shown as below.

```php

```

#### Left Join

You can do left join as shown as below.

```php

```

#### Right Join

You can do right join as shown as below.

```php

```

### Subqueries

If you want to use subquery within one table you can do as shown as before.

<i>You can use subqueries as shown as below in "where","orWhere" and "whereIn" functions.</i>
```php
Blog::whereIn('author_id',function($query){
$query->select(['id'])->where('id',1)->get();
})->get();
```

If you want to use subquery from different table you can do as shown as before.

```php
Blog::whereIn('author_id',function($query){
	$query->select(['id'])->where('id',1)->from('App\Models\Author')->get();
})->get();
```
You can use "from" function in only subqueries. You need to add model class name which is represented the another table in "from" function.

## Using PDO Functions

## Using Different Databases

## JSON Response

When you want to do json data of for your API you can simply do as shown as below.

```php
return jsonResponse([
	'blogs' => Blog::get()
]);
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

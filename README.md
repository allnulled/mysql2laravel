# mysql2laravel

Autogenerate Laravel routes, models and controllers for all the tables of your mysql database.

## Features

- Standarized and customizable response for all the methods.
- The `index` method:
	- The `items` parameter: how many items do you want per request?
	Example:
		`myEndpoint?items=5` (by default this is 20 always)
	- The `page` parameter: which page do you want to get?
	Example:
		`myEndpoint?page=1` (set 0 to get them all in one query)
	- The `search` parameter: do you want some text to appear in any (not hidden) column?
	Example:
		`myEndpoint?search=Some case-insensitive text`
	- The `sortBy` parameter: which columns (and asc/desc policy) you want the query to be ordered by?
	Example:
		`myEndpoint?sortBy=id/asc//name/desc`
	- The `where` parameter: which (`WHERE`) conditions you want to apply to the query?
	Examples:
		`myEndpoint?where=id/=/1` (with id set to 1)
		`myEndpoint?where=id/in/1/2/3` (with id in 1, 2 or 3)
		`myEndpoint?where=id/notin/1/2/3` (with id not in 1, 2 and 3)
		`myEndpoint?where=id/</10//name/like/%u%` (with id less than 10 AND containing text 'u')
	- The `whereSeparator` parameter: which separator token you want to use in the `where` parameter?
	By default, this is `/`.
- All the methods have their own (customizable) lifecycle (that follow the same decorator design pattern inside):
	- `index`:
		- `indexStepValidate`
		- `indexStepFields`
		- `indexStepJoins`
		- `indexStepWhere`
		- `indexStepSearch`
		- `indexStepSort`
		- `indexStepPage`
		- `indexStepQuery`
		- `indexStepRespond`
	- `show`:
		- `showStepQuery`
		- `showStepRespond`
	- `store`:
		- `storeStepValidate`
		- `storeStepFill`
		- `storeStepQuery`
		- `storeStepRespond`
	- `update`:
		- `updateStepValidate`
		- `updateStepFind`
		- `updateStepFill`
		- `updateStepQuery`
		- `updateStepRespond`
	- `destroy`:
		- `destroyStepFind`
		- `destroyStepQuery`
		- `destroyStepRespond`
- `store` and `update` methods with customizable validations and error messages out-of-the-box (also `index`).
- All HTTP status codes are available to be used in your JSON responses.
- Custom `jsonSuccess` and `jsonError` methods.

The idea is that you can get the focus on your database, and re-generate all the times you need the controllers, models and routes automatically, and customizing everything per each project.

## Why?

It makes non-sense to re-write in every project your controllers and models and routes, when they follow a very clear pattern, and you want them to be an exact representation of the tables of your database.

This is why I wanted to create this project: for anyone to take more time in the database design, and less in the back-end development and maintenance.

## Get started

These are the steps that you need to follow in order to autogenerate your Laravel endpoints (models, controllers and routes) from an existing mysql database.

### Install it

Download this project directly from GitHub:

`$ git clone https://github.com/allnulled/mysql2laravel.git`

### Run it against an existing mysql database

Run the script against your database:

`$ cd mysql2laravel`

`$ php run.php --database=MY_DB --user=MY_USER --password=MY_PASSWORD --host=MY_HOST`

### Copy the output into your project

This step is preferrably done manually, in order to avoid to override accidentally files
that your current project has already, and it should maintain.

### Done!

All your mysql tables will have their own endpoint.

- **No relationships**. No `with` method in the generated output!
- **No validations**. The validations are very basic, but they are fully customizable!
- **No redundant code**. The code that can be written once, is written once!
- **Clean models**. The models only have the code they need to work!
- **Clean controllers**. The controllers only have the code they need to work!
- **Fully customizable**. Each model and controller can be customized by their own! But the good thing is that you start from a very advanced point, in which you do not need to code the common stuff: just the specific things you need.

## 

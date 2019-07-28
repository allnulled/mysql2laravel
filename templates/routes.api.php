<?php

echo "<?php\n";

?>

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
 */

Route::middleware('auth:api')->get('/user', function (Request $request) {
	return $request->user();
});

Route::group(["prefix" => "/v1"], function () {
	$restMethods = ["index", "show", "store", "update", "destroy"];
	// General:
	Route::get("/", "ApiController@welcome");
	// Controller per each model:
<?php foreach ($info as $tableName => $table) {?>
	Route::resource("/<?=lcfirst($snakeToCamel($tableName));?>", "<?=$snakeToCamel($tableName);?>Controller")->only($restMethods);
<?php }?>
});





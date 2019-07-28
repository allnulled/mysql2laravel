<?php

$printHelp = function () {
	echo file_get_contents(__DIR__ . "/help.txt");
};
$printMessage = function ($message) {
	echo "[mysql2laravel] " . $message . "\n\n";
};
/////////////////////////////////////////////////////////

/////////////////////////////////////////////////////////
$dbDefaults = [
	"user" => "root",
	"password" => null,
	"host" => "localhost",
];
$dbOptions = getopt(null, [
	"user::",
	"password::",
	"host::",
	"database:",
]);
$defaultOptions = [
	"output" => __DIR__ . "/output",
];
$fileOptions = getopt(null, [
	"help::",
]);
$db = array_merge($dbDefaults, $dbOptions);
$options = array_merge($defaultOptions, $fileOptions);
if (!array_key_exists("database", $db)) {
	$printHelp();
	$printMessage("ERROR: --database parameter is required.");
	exit;
}
if (array_key_exists("help", $options)) {
	$printHelp();
	exit;
}
$options["output"] = realpath(".") . "/output";
/////////////////////////////////////////////////////////

/////////////////////////////////////////////////////////
$rimraf = function ($dir) {
	if (is_dir($dir)) {
		$objects = scandir($dir);
		foreach ($objects as $object) {
			if ($object != "." && $object != "..") {
				if (is_dir($dir . "/" . $object)) {
					rrmdir($dir . "/" . $object);
				} else {
					unlink($dir . "/" . $object);
				}

			}
		}
		rmdir($dir);
	}
};
$ensureFolders = function () use ($options) {
	$controllersFolder = $options["output"] . "/app/Http/Controllers";
	$routesFolder = $options["output"] . "/routes";
	echo "[*] Creating folder at:\n - $controllersFolder\n";
	mkdir($controllersFolder, 0777, true);
	echo "[*] Creating folder at:\n - $routesFolder\n";
	mkdir($routesFolder, 0777, true);
};
$snakeToCamel = function ($input, $separator = '_') {
	return str_replace($separator, '', ucwords($input, $separator));
};
$generateModel = function ($table, $tableData) use ($options, $snakeToCamel) {
	$model = $snakeToCamel($table);
	$file = $options["output"] . "/app/{$model}.php";
	ob_start();
	include __DIR__ . "/templates/Model.php";
	$modelContents = ob_get_contents();
	ob_end_clean();
	file_put_contents($file, $modelContents);
};
$generateController = function ($table, $tableData) use ($options, $snakeToCamel) {
	$model = $snakeToCamel($table);
	$file = $options["output"] . "/app/Http/Controllers/{$model}Controller.php";
	ob_start();
	include __DIR__ . "/templates/Controller.php";
	$controllerContents = ob_get_contents();
	ob_end_clean();
	file_put_contents($file, $controllerContents);
};
$dumpRoutes = function ($info) use ($options, $snakeToCamel) {
	ob_start();
	include __DIR__ . "/templates/routes.api.php";
	$routes = ob_get_contents();
	ob_end_clean();
	file_put_contents($options['output'] . "/routes/api.php", $routes);
};
$dumpRestController = function () use ($options) {
	$restControllerContents = file_get_contents(__DIR__ . "/templates/RestController.php");
	file_put_contents($options['output'] . "/app/Http/Controllers/RestController.php", $restControllerContents);
};
$dumpApiController = function () use ($options) {
	$apiControllerContents = file_get_contents(__DIR__ . "/templates/ApiController.php");
	file_put_contents($options['output'] . "/app/Http/Controllers/ApiController.php", $apiControllerContents);
};
/////////////////////////////////////////////////////////

/////////////////////////////////////////////////////////
$connection = new mysqli(
	$db["host"],
	$db["user"],
	$db["password"],
	"INFORMATION_SCHEMA"
);
$query = <<<MASTER_QUERY
SELECT DISTINCT
    TABLES.TABLE_SCHEMA AS 'DATABASE',
    TABLES.TABLE_NAME AS 'TABLE',
    COLUMNS.COLUMN_NAME AS 'COLUMN',
    COLUMNS.COLUMN_TYPE AS 'COLUMN TYPE',
    COLUMNS.IS_NULLABLE AS 'COLUMN NULLABLE',
    COLUMNS.COLUMN_DEFAULT AS 'COLUMN DEFAULT VALUE',
    COLUMNS.EXTRA AS 'COLUMN EXTRA INFORMATION',
    KEY_COLUMN_USAGE.CONSTRAINT_NAME AS 'BOUND CONSTRAINT',
    KEY_COLUMN_USAGE.REFERENCED_TABLE_NAME AS 'REFERENCED TABLE',
    KEY_COLUMN_USAGE.REFERENCED_COLUMN_NAME AS 'REFERENCED COLUMN'
FROM TABLES
LEFT JOIN COLUMNS ON
    COLUMNS.TABLE_SCHEMA = TABLES.TABLE_SCHEMA AND
    COLUMNS.TABLE_NAME = TABLES.TABLE_NAME
LEFT JOIN KEY_COLUMN_USAGE ON
    KEY_COLUMN_USAGE.TABLE_SCHEMA = TABLES.TABLE_SCHEMA AND
    KEY_COLUMN_USAGE.TABLE_NAME = TABLES.TABLE_NAME AND
    KEY_COLUMN_USAGE.COLUMN_NAME = COLUMNS.COLUMN_NAME
WHERE TABLES.TABLE_SCHEMA = '{$db["database"]}'
ORDER BY TABLES.TABLE_SCHEMA ASC, TABLES.TABLE_NAME ASC, COLUMNS.COLUMN_NAME ASC;
MASTER_QUERY;
/////////////////////////////////////////////////////////

/////////////////////////////////////////////////////////
$result = $connection->query($query) or die(mysqli_error($connection));
/////////////////////////////////////////////////////////

/////////////////////////////////////////////////////////
// $data = mysqli_fetch_all($result, MYSQLI_ASSOC);
// file_put_contents(__DIR__ . "/tmp/query.json", json_encode($data, JSON_PRETTY_PRINT));
$ensureFolders();
$info = [];
while ($row = $result->fetch_assoc()) {
	// var_dump($row);
	if (!isset($info[$row["TABLE"]])) {
		$info[$row["TABLE"]] = [];
	}
	$info[$row["TABLE"]][$row["COLUMN"]] = $row;
}
file_put_contents(__DIR__ . "/tmp/info.json", json_encode($info, JSON_PRETTY_PRINT));
foreach ($info as $table => $tableData) {
	$generateModel($table, $tableData);
	$generateController($table, $tableData);
}
$dumpRoutes($info);
$dumpRestController();
$dumpApiController();
/////////////////////////////////////////////////////////

/////////////////////////////////////////////////////////
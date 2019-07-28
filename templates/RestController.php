<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class RestController extends Controller {

	const httpCodes = [
		"200" => "Success",
		"400" => "Error",
		"404" => "Not Found",
		"500" => "Bad Request",
	];

	const ALLOWED_WHERE_OPERATORS = ["=", "<", ">", "<=", ">=", "<>", "!=", "like", "not like", "between", "ilike", "&", "|", "^", "<<", ">>", "rlike", "regexp", "not regexp"];

	public function getStandardResponse() {
		return [
			"app" => [
				"name" => "Custom REST API",
				"version" => "1.0",
			],
		];
	}

	public function jsonSuccess($data = [], $metadata = [], $status = [], $statusCode = 200) {
		return $this->jsonResponse($statusCode, $data, $metadata, $status);
	}

	public function jsonError($error = [], $metadata = [], $status = [], $statusCode = 500) {
		return $this->jsonResponse($statusCode, $error, $metadata, $status, true);
	}

	public function jsonSuccessDispatch(...$args) {
		$this->jsonSuccess(...$args);
		exit;
	}

	public function jsonErrorDispatch(...$args) {
		$this->jsonError(...$args);
		exit;
	}

	public function jsonDispatch(...$args) {
		$this->jsonResponse(...args);
		exit;
	}

	public function jsonResponse($statusCode, $data, $metadata, $status, $isError = false) {
		$responseData["status"] = array_merge([
			"code" => $statusCode,
			"message" => $this::httpCodes[$statusCode],
		], $status);
		$responseData[$isError ? "error" : "data"] = $data;
		$responseData = array_merge($this->getStandardResponse(), $responseData, $metadata);
		response()->json($responseData, $statusCode)->send();
	}

	////////////////////////////////////////////////////////////////////

	public function getIndexValidations() {
		return [
			"fields" => "nullable|string",
			"search" => "nullable|string",
			"where" => "nullable|string",
			"whereSeparator" => "nullable|string",
			"join" => "nullable|string",
			"sortBy" => "nullable|string",
			"page" => "nullable|integer|min:0",
			"items" => "nullable|integer|min:1",
		];
	}

	public function getIndexValidationMessages() {
		return null;
	}

	public function initializeIndexData($request) {
		return [
			"steps" => [],
			"request" => $request,
			"query" => null,
			"data" => null,
			"meta" => [],
			"pagination" => [],
		];
	}

	public function index(Request $request) {
		$data = $this->initializeIndexData(...func_get_args());
		$steps = $this->getIndexSteps($data);
		foreach ($steps as $step) {
			$this->$step($data);
		}
	}

	public function getIndexSteps(&$data) {
		return [
			"indexStepValidate",
			"indexStepFields",
			"indexStepJoins",
			"indexStepWhere",
			"indexStepSearch",
			"indexStepSort",
			"indexStepPage",
			"indexStepQuery",
			"indexStepRespond",
		];
	}

	public function indexStepValidate(&$data) {
		$validationArguments = [];
		array_push($validationArguments, $data["request"]->all(), $this->getIndexValidations());
		$validationMessages = $this->getIndexValidationMessages();
		if (isset($validationMessages)) {
			array_push($validationArguments, $validationMessages);
		}
		$validator = Validator::make(...$validationArguments);
		if ($validator->fails()) {
			return $this->jsonErrorDispatch($validator->errors());
		}
	}

	public function indexStepFields(&$data) {
		$fields = $data["request"]->input("fields", "*");
		if ($fields != "*") {
			$fields = explode(",", $fields);
		}
		$data["query"] = $this->modelClass::select($fields);
	}

	public function indexStepJoins(&$data) {
		// @TODO: join related tables
	}

	public function indexStepWhere(&$data) {
		$where = $data["request"]->input("where", null);
		$whereSeparator = $data["request"]->input("whereSeparator", "/");
		if (isset($where)) {
			$operations = explode($whereSeparator . $whereSeparator, $where, 10); // 10 conditions max
			foreach ($operations as $operation) {
				$operators = explode($whereSeparator, $operation, 3);
				if (sizeof($operators) == 3) {
					$subject = $operators[0];
					$operator = $operators[1];
					$object = $operators[2];
					// Custom handler for <in> and <not in>:
					if (in_array($operator, ["in", "notin"])) {
						$objects = explode($whereSeparator, $object);
						if ($operator == "in") {
							$data["query"]->whereIn($subject, $objects);
						} else {
							$data["query"]->whereNotIn($subject, $objects);
						}
					}
					// Handle other operators:
					else if (in_array($operator, self::ALLOWED_WHERE_OPERATORS)) {
						$data["query"]->where($subject, $operator, $object);
					}
				}
			}
		}
	}

	public function indexStepSearch(&$data) {
		$search = $data["request"]->input("search", null);
		if (!isset($search)) {
			return;
		}
		$modelSample = new $this->modelClass();
		$allColumns = Schema::getColumnListing($modelSample->table);
		$searcheableColumns = array_diff($allColumns, $modelSample->hidden);
		$data["query"]->where(function ($query) use ($data, $allColumns, $search) {
			foreach ($allColumns as $key => $column) {
				if ($key == 0) {
					$data["query"]->where($column, "like", "%$search%");
				} else {
					$data["query"]->orWhere($column, "like", "%$search%");
				}
			}
		});
	}

	public function indexStepPage(&$data) {
		$itemsPerPage = (int) $data["request"]->input("items", 20);
		$page = ((int) $data["request"]->input("page", 1));
		$total = $data["query"]->count();
		if ($page <= 0) {
			$data["pagination"] = [
				"page" => "(all in one)",
				"pages" => 1,
				"items" => $total,
				"total" => $total,
			];
			return;
		}
		$pages = $total / $itemsPerPage;
		$data["query"]->skip(($page - 1) * $itemsPerPage)->take($itemsPerPage);
		$data["pagination"] = [
			"page" => $page,
			"pages" => ceil($pages),
			"items" => $itemsPerPage,
			"total" => $total,
		];
	}

	public function indexStepSort(&$data) {
		$sortBy = $data["request"]->input("sortBy", null);
		if (isset($sortBy)) {
			$sortRules = explode("//", $sortBy);
			foreach ($sortRules as $sortRule) {
				$sortOperators = explode("/", $sortRule);
				$data["query"]->orderBy($sortOperators[0], (count($sortOperators) > 1) ? $sortOperators[1] : "ASC");
			}
		}
	}

	public function indexStepQuery(&$data) {
		$data["data"] = $data["query"]->get();
		$data["pagination"]["found"] = count($data["data"]);
		$data["meta"] = ["pagination" => $data["pagination"]];
	}

	public function indexStepRespond(&$data) {
		return $this->jsonSuccessDispatch($data["data"], $data["meta"], [
			"method" => "GET",
			"endpoint" => $data["request"]->url(),
			"operation" => "List items",
		]);
	}

	////////////////////////////////////////////////////////////////////

	public function initializeShowData($id, $request) {
		return [
			"id" => $id,
			"request" => $request,
			"item" => null,
			"data" => null,
			"meta" => [],
		];
	}

	public function getShowSteps(&$data) {
		return [
			"showStepQuery",
			"showStepRespond",
		];
	}

	public function show($id, Request $request) {
		$data = $this->initializeShowData(...func_get_args());
		$steps = $this->getShowSteps($data);
		foreach ($steps as $step) {
			$this->$step($data);
		}
	}

	public function showStepQuery(&$data) {
		$data["item"] = $this->modelClass::find($data["id"]);
		if (!isset($data["item"])) {
			$this->jsonErrorDispatch(["id" => "Item with id {$data['id']} was not found."], [], [], 404);
		}
	}

	public function showStepRespond(&$data) {
		return $this->jsonSuccessDispatch($data["item"], $data["meta"], [
			"method" => "GET",
			"endpoint" => $data["request"]->url(),
			"operation" => "Get item",
		]);
	}

	////////////////////////////////////////////////////////////////////

	public function initializeStoreData($request) {
		return [
			"request" => $request,
			"item" => null,
			"data" => null,
			"meta" => [],
		];
	}

	public function getStoreValidations() {
		return [];
	}

	public function getStoreValidationMessages() {
		return null;
	}

	public function getStoreSteps(&$data) {
		return [
			"storeStepValidate",
			"storeStepFill",
			"storeStepQuery",
			"storeStepRespond",
		];
	}

	public function store(Request $request) {
		$data = $this->initializeStoreData(...func_get_args());
		$steps = $this->getStoreSteps($data);
		foreach ($steps as $step) {
			$this->$step($data);
		}
	}

	public function storeStepValidate(&$data) {
		$validationArguments = [];
		array_push($validationArguments, $data["request"]->all(), $this->getStoreValidations());
		$validationMessages = $this->getStoreValidationMessages();
		if (isset($validationMessages)) {
			array_push($validationArguments, $validationMessages);
		}
		$validator = Validator::make(...$validationArguments);
		if ($validator->fails()) {
			return $this->jsonErrorDispatch($validator->errors());
		}
	}

	public function storeStepFill(&$data) {
		$data["item"] = new $this->modelClass();
		$data["item"]->fill($data["request"]->all());
	}

	public function storeStepQuery(&$data) {
		$data["item"]->save();
	}

	public function storeStepRespond(&$data) {
		return $this->jsonSuccessDispatch($data["item"], $data["meta"], [
			"method" => "POST",
			"endpoint" => $data["request"]->url(),
			"operation" => "Create item",
		]);
	}

	////////////////////////////////////////////////////////////////////

	public function initializeUpdateData($id, $request) {
		return [
			"id" => $id,
			"request" => $request,
			"item" => null,
			"data" => null,
			"meta" => [],
		];
	}

	public function getUpdateValidations() {
		return [];
	}

	public function getUpdateValidationMessages() {
		return null;
	}

	public function getUpdateSteps(&$data) {
		return [
			"updateStepValidate",
			"updateStepFind",
			"updateStepFill",
			"updateStepQuery",
			"updateStepRespond",
		];
	}

	public function update($id, Request $request) {
		$data = $this->initializeUpdateData(...func_get_args());
		$steps = $this->getUpdateSteps($data);
		foreach ($steps as $step) {
			$this->$step($data);
		}
	}

	public function updateStepValidate(&$data) {
		$validationArguments = [];
		array_push($validationArguments, $data["request"]->all(), $this->getUpdateValidations());
		$validationMessages = $this->getUpdateValidationMessages();
		if (isset($validationMessages)) {
			array_push($validationArguments, $validationMessages);
		}
		$validator = Validator::make(...$validationArguments);
		if ($validator->fails()) {
			return $this->jsonErrorDispatch($validator->errors());
		}
	}

	public function updateStepFind(&$data) {
		$data["item"] = $this->modelClass::find($data["id"]);
		if (!isset($data["item"])) {
			$this->jsonErrorDispatch(["id" => "Item with id {$data['id']} was not found."], [], [], 404);
		}
	}

	public function updateStepFill(&$data) {
		$data["item"]->fill($data["request"]->all());
	}

	public function updateStepQuery(&$data) {
		$data["item"]->save();
	}

	public function updateStepRespond(&$data) {
		return $this->jsonSuccessDispatch($data["item"], $data["meta"], [
			"method" => "PUT",
			"endpoint" => $data["request"]->url(),
			"operation" => "Update item",
		]);
	}

	////////////////////////////////////////////////////////////////////

	public function initializeDestroyData($id, $request) {
		return [
			"id" => $id,
			"request" => $request,
			"item" => null,
			"data" => null,
			"meta" => [],
		];
	}

	public function getDestroySteps(&$data) {
		return [
			"destroyStepFind",
			"destroyStepQuery",
			"destroyStepRespond",
		];
	}

	public function destroy($id, Request $request) {
		$data = $this->initializeDestroyData(...func_get_args());
		$steps = $this->getDestroySteps($data);
		foreach ($steps as $step) {
			$this->$step($data);
		}
	}

	public function destroyStepFind(&$data) {
		$data["item"] = $this->modelClass::find($data["id"]);
		if (!isset($data["item"])) {
			$this->jsonErrorDispatch(["id" => "Item with id {$data['id']} was not found."], [], [], 404);
		}
	}

	public function destroyStepQuery(&$data) {
		$data["item"]->delete();
	}

	public function destroyStepRespond(&$data) {
		return $this->jsonSuccessDispatch($data["item"], $data["meta"], [
			"method" => "DELETE",
			"endpoint" => $data["request"]->url(),
			"operation" => "Delete item",
		]);
	}

}

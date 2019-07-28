<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Route;

class ApiController extends RestController {
	public function welcome() {
		$routes = Route::getRoutes();
		$data = [];
		foreach ($routes as $route) {
			$data[] = $route->uri();
		}
		$this->jsonSuccessDispatch(["app" => ["routes" => $data]]);
	}
}

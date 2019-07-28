<?php

echo "<?php";

?>


namespace App\Http\Controllers;

use App\<?=$model;?>;

class <?=$model;?>Controller extends RestController {

	public $modelClass = <?=$model;?>::class;

	public function getStoreValidations() {
		return [
			// @TODO: store validations...
		];
	}

	public function getStoreValidationMessages() {
		return null;
	}

	public function getUpdateValidations() {
		return [
			// @TODO: update validations...
		];
	}

	public function getUpdateValidationMessages() {
		return null;
	}

}

<?php

echo "?>";

?>
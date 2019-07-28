<?php

echo "<?php";

?>


namespace App;

use Illuminate\Database\Eloquent\Model;

class <?=$model?> extends Model {

	public $table = "<?=$table;?>";

	public $fillable = [
<?php
foreach ($tableData as $column => $columnData) {
	if (!in_array($column, ["created_at", "updated_at", "deleted_at"])) {
		?>
		<?=json_encode($column);?>,
<?php }
}
?>
	];

	public $timestamps = [
<?php
foreach ($tableData as $column => $columnData) {
	if (in_array($column, ["created_at", "updated_at", "deleted_at"])) {
		?>
		<?=json_encode($column);?>,
<?php }
}
?>
	];

	public $hidden = [
		// @TODO: add hidden fields here...
	];

}

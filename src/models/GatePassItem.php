<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use \Venturecraft\Revisionable\RevisionableTrait;
class GatePassItem extends Model {
	use SeederTrait;
	use SoftDeletes;
	use RevisionableTrait;
	protected $table = 'gate_pass_items';
	public $timestamps = true;
	protected $fillable = [
		"gate_pass_id",
		"name",
		"item_description",
		"item_make",
		"item_model",
		"item_serial_no",
		"qty",
		"return_qty",
		"status_id",
		"remarks",
	];
	protected $revisionCreationsEnabled = true;
	protected $revisionForceDeleteEnabled = true;
	
	public function attachment() {
		return $this->hasMany('App\Attachment', 'entity_id', 'id')->where('attachment_of_id', 231)->where('attachment_type_id', 238);
	}

	public function getQtyAttribute($value) {
		return empty($value) ? '' : round($value);
	}
}

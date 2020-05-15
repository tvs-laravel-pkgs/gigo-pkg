<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GatePassItem extends Model {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'gate_pass_items';
	public $timestamps = true;
	protected $fillable =
		["gate_pass_id", "item_description", "item_make", "item_model", "item_serial_no", "qty", "remarks"]
	;
}

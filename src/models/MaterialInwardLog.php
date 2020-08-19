<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaterialInwardLog extends Model {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'material_inward_logs';
	public $timestamps = false;
	protected $fillable = [
		"gass_pass_item_id",
		"qty",
		"created_by_id",
	];
}

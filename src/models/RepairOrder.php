<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\Company;
use App\Config;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RepairOrder extends Model {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'repair_orders';
	public $timestamps = true;
	protected $fillable = [
		'type_id',
		'code',
		'alt_code',
		'name',
		'skill_level_id',
		'hours',
		'amount',
		'tax_code_id',
	];

}

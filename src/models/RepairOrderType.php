<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\Company;
use App\Config;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RepairOrderType extends Model {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'repair_order_types';
	public $timestamps = true;
	protected $fillable = [
		'short_name',
		'name',
		'description',
		'company_id',
	];

}

<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\Company;
use App\Config;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ModelType extends Model {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'models';
	public $timestamps = true;
	protected $fillable = [
		'company_id',
		'business_id',
		'vehicle_make_id',
		'model_name',
		'model_number',
	];

}

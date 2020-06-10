<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\Company;
use App\Config;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Fault extends Model {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'faults';
	public $timestamps = true;
	protected $fillable =[
		"id",
		"company_id",
		"code",
		"name"
	];
}

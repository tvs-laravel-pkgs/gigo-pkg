<?php

namespace Abs\GigoPkg;
use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;

class AggregateWork extends BaseModel {
	use SeederTrait;
	public $timestamps = false;

	protected $table = 'amc_aggregate_works';
	protected $fillable = [
		"amc_policy_id",
		"aggregate_work_id",	
	];
}

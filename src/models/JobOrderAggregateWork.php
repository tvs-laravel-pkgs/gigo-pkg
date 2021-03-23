<?php

namespace Abs\GigoPkg;
use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;

class JobOrderAggregateWork extends BaseModel {
	use SeederTrait;
	public $timestamps = false;

	protected $table = 'job_order_aggregate_works';
	protected $fillable = [
		"job_order_id",
		"aggregate_work_id",
		"amount",	
	];
}

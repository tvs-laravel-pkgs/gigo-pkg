<?php

namespace Abs\GigoPkg;
use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class AggregateWork extends BaseModel {
	use SeederTrait;
	use SoftDeletes;

	protected $table = 'aggregate_works';
	protected $fillable = [
		"name",	
	];

	public function amcAggregateWork() {
		return $this->belongsToMany('Abs\AmcPkg\AmcPolicy', 'amc_aggregate_works', 'amc_policy_id', 'aggregate_work_id');
	}
}

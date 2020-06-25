<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobOrderCampaignChassisNumber extends BaseModel {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'job_order_campaign_chassis_numbers';
	public $timestamps = true;
	protected $fillable = [
		"job_order_id",
		"campaign_id",
		"chassis_number",
	];

}

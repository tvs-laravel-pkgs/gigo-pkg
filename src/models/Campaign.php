<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campaign extends Model {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'compaigns';
	public $timestamps = true;
	protected $fillable =
		["company_id", "authorisation_no", "complaint_code", "fault_code", "claim_type_id"]
	;

	public function campaignLabours() {
		return $this->belongsToMany('Abs\GigoPkg\RepairOrder', 'compaign_repair_order', 'compaign_id', 'repair_order_id')->withPivot(['amount']);
	}

	public function campaignParts() {
		return $this->belongsToMany('Abs\PartPkg\Part', 'compaign_part', 'compaign_id', 'part_id');
	}

}

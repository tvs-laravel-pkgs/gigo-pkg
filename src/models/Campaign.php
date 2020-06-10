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
		["company_id", "authorisation_no", "complaint_id", "fault_id", "claim_type_id", "manufacture_date", "vehicle_model_id"]
	;

	public function getManufactureDateAttribute($value) {
		return empty($value) ? '' : date('d-m-Y', strtotime($value));
	}

	public function setManufactureDateAttribute($date) {
		return $this->attributes['manufacture_date'] = empty($date) ? NULL : date('Y-m-d', strtotime($date));
	}

	public function vehicleModel() {
		return $this->belongsTo('App\VehicleModel', 'vehicle_model_id');
	}

	public function campaignLabours() {
		return $this->belongsToMany('Abs\GigoPkg\RepairOrder', 'compaign_repair_order', 'compaign_id', 'repair_order_id')->withPivot(['amount']);
	}

	public function campaignParts() {
		return $this->belongsToMany('Abs\PartPkg\Part', 'compaign_part', 'compaign_id', 'part_id');
	}

}

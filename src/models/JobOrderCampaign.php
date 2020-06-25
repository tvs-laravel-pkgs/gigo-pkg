<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobOrderCampaign extends BaseModel {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'job_order_campaigns';
	public $timestamps = true;
	protected $fillable = [
		"job_order_id",
		"campaign_id",
		"authorisation_no",
		"complaint_id",
		"fault_id",
		"claim_type_id",
		"campaign_type",
		"vehicle_model_id",
		"manufacture_date",
	];

	public function getManufactureDateAttribute($value) {
		return empty($value) ? '' : date('d-m-Y', strtotime($value));
	}

	public function setManufactureDateAttribute($date) {
		return $this->attributes['manufacture_date'] = empty($date) ? NULL : date('Y-m-d', strtotime($date));
	}

	public function vehicleModel() {
		return $this->belongsTo('App\VehicleModel', 'vehicle_model_id');
	}

	public function claimType() {
		return $this->belongsTo('App\Config', 'claim_type_id');
	}

	public function faultType() {
		return $this->belongsTo('Abs\GigoPkg\Fault', 'fault_id');
	}

	public function complaintType() {
		return $this->belongsTo('Abs\GigoPkg\Complaint', 'fault_id');
	}

	public function campaignLabours() {
		return $this->belongsToMany('Abs\GigoPkg\RepairOrder', 'job_order_campaign_repair_order', 'job_order_campaign_id', 'repair_order_id')->withPivot(['amount']);
	}

	public function campaignParts() {
		return $this->belongsToMany('Abs\PartPkg\Part', 'job_order_campaign_part', 'job_order_campaign_id', 'part_id');
	}

	public function chassisNumbers() {
		return $this->hasMany('Abs\GigoPkg\JobOrderCampaignChassisNumber', 'job_order_campaign_id', 'id');
	}

}

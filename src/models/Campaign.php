<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use \Venturecraft\Revisionable\RevisionableTrait;
class Campaign extends BaseModel {
	use SeederTrait;
	use SoftDeletes;
	use RevisionableTrait;
	protected $table = 'compaigns';
	public $timestamps = true;
	protected $fillable = [
		"company_id",
		"authorisation_no",
		"complaint_id",
		"fault_id",
		"claim_type_id",
		"manufacture_date",
		"vehicle_model_id",
		"campaign_type",
	];

	protected $revisionCreationsEnabled = true;
    protected $revisionForceDeleteEnabled = true;

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
		return $this->belongsToMany('Abs\GigoPkg\RepairOrder', 'compaign_repair_order', 'compaign_id', 'repair_order_id')->withPivot(['amount']);
	}

	public function campaignParts() {
		return $this->belongsToMany('Abs\PartPkg\Part', 'compaign_part', 'compaign_id', 'part_id');
	}

	public function chassisNumbers() {
		return $this->hasMany('Abs\GigoPkg\CampaignChassisNumber', 'campaign_id', 'id');
	}

}

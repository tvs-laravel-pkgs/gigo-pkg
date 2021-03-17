<?php

namespace Abs\GigoPkg;
use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;

class OnSiteOrder extends BaseModel {
	use SeederTrait;
	protected $table = 'on_site_orders';
	protected $fillable = [
		"company_id",
		"outlet_id",
		"on_site_visit_user_id",
		"number",
		"customer_id",
		"job_card_number",
		"service_type_id",
		"planned_visit_date",
		"actual_visit_date",
		"customer_remarks",
		"se_remarks	",
	];

	public function getCreatedAtAttribute($value) {
		return empty($value) ? '' : date('d-m-Y h:i A', strtotime($value));
	}

	public function getPlannedVisitDateAttribute($value) {
		return empty($value) ? '' : date('d-m-Y', strtotime($value));
	}

	public function getActualVisitDateAttribute($value) {
		return empty($value) ? '' : date('d-m-Y', strtotime($value));
	}

	public function company() {
		return $this->belongsTo('App\Company', 'company_id');
	}

	public function outlet() {
		return $this->belongsTo('App\Outlet', 'outlet_id');
	}

	public function onSiteVisitUser() {
		return $this->belongsTo('App\User', 'on_site_visit_user_id');
	}

	public function customer() {
		return $this->belongsTo('App\Customer', 'customer_id');
	}

	public function status() {
		return $this->belongsTo('App\OnSiteOrderStatus', 'status_id');
	}

	public function onSiteOrderRepairOrders() {
		return $this->hasMany('App\OnSiteOrderRepairOrder', 'on_site_order_id');
	}

	public function onSiteOrderParts() {
		return $this->hasMany('App\OnSiteOrderPart', 'on_site_order_id');
	}

	public function onSiteOrderTravelLogs() {
		return $this->hasMany('App\OnSiteOrderTimeLog', 'on_site_order_id')->where('work_log_type_id', 1);
	}

	public function onSiteOrderWorkLogs() {
		return $this->hasMany('App\OnSiteOrderTimeLog', 'on_site_order_id')->where('work_log_type_id', 2);
	}

	public function photos() {
		return $this->hasMany('App\Attachment', 'entity_id')->where('attachment_of_id', 9124);
	}
}

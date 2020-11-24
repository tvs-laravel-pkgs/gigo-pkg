<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;

class GatePassUser extends BaseModel {
	use SeederTrait;
	protected $table = 'gate_pass_users';
	public $timestamps = false;
	protected $fillable = ["id", "gate_pass_id", "user_id", "gate_out_date_time", "gate_in_date_time"];

	public function getGateInDateTimeAttribute($date) {
		return empty($date) ? '' : date('d-m-Y H:i A', strtotime($date));
	}

	public function getGateOutDateTimeAttribute($date) {
		return empty($date) ? '' : date('d-m-Y H:i A', strtotime($date));
	}

	public function user() {
		return $this->belongsTo('App\User', 'user_id');
	}
}

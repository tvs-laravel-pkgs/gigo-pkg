<?php

namespace Abs\GigoPkg;
use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use \Venturecraft\Revisionable\RevisionableTrait;
class RoadTestGatePass extends BaseModel {
	use SeederTrait;
	use SoftDeletes;
	use RevisionableTrait;
	protected $table = 'road_test_gate_pass';
	public $timestamps = true;
	protected $fillable = [
		"company_id",
		"job_order_id",
		"number",
		"gate_in_date",
		"gate_in_remarks",
		"gate_out_date",
		"gate_out_remarks",
	];
	protected $revisionCreationsEnabled = true;
    protected $revisionForceDeleteEnabled = true;
	// Getters --------------------------------------------------------------

	//APPEND - INBETWEEN REGISTRATION NUMBER
	public function getRegistrationNumberAttribute($value) {
		$registration_number = '';

		if ($value) {
			$value = str_replace('-', '', $value);
			$reg_number = str_split($value);

			$last_four_numbers = substr($value, -4);

			$registration_number .= $reg_number[0] . $reg_number[1] . '-' . $reg_number[2] . $reg_number[3] . '-';

			if (is_numeric($reg_number[4])) {
				$registration_number .= $last_four_numbers;
			} else {
				$registration_number .= $reg_number[4];
				if (is_numeric($reg_number[5])) {
					$registration_number .= '-' . $last_four_numbers;
				} else {
					$registration_number .= $reg_number[5] . '-' . $last_four_numbers;
				}
			}
		}
		return $this->attributes['registration_number'] = $registration_number;
	}

	public function getGateInDateAttribute($date) {
		return empty($date) ? '' : date('d-m-Y h:i A ', strtotime($date));
	}

	public function getGateOutDateAttribute($date) {
		return empty($date) ? '' : date('d-m-Y h:i A ', strtotime($date));
	}

	public function jobOrder() {
		return $this->belongsTo('App\JobOrder', 'job_order_id');
	}

	public function tradePlateNumber() {
		return $this->belongsTo('App\TradePlateNumber', 'trade_plate_number_id');
	}

	public function roadTestDoneBy() {
		return $this->belongsTo('App\Config', 'road_test_done_by_id');
	}

	public function roadTestPreferedBy() {
		return $this->belongsTo('App\User', 'road_test_performed_by_id');
	}

	public function status() {
		return $this->belongsTo('App\Config', 'status_id');
	}
}

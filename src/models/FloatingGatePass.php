<?php

namespace Abs\GigoPkg;
use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class FloatingGatePass extends BaseModel {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'floating_stock_logs';
	public $timestamps = true;
	protected $fillable = [
		"company_id",
		"outlet_id",
		"number",
		"job_card_id",
	];

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

	public function getOutwardDateAttribute($date) {
		return empty($date) ? '' : date('d-m-Y h:i A ', strtotime($date));
	}

	public function getInwardDateAttribute($date) {
		return empty($date) ? '' : date('d-m-Y h:i A ', strtotime($date));
	}

	public function jobCard() {
		return $this->belongsTo('App\JobCard', 'job_card_id');
	}

	public function status() {
		return $this->belongsTo('App\Config', 'status_id');
	}

	public function floatStock() {
		return $this->belongsTo('App\FloatStock', 'floating_stock_id');
	}
}

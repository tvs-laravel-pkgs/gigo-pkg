<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\Company;
use App\Config;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GatePass extends Model {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'gate_passes';
	public $timestamps = true;
	protected $fillable = [
		"company_id",
		"type_id",
		"number",
		"status_id",
		"job_order_id",
		"job_card_id",
		"gate_in_date",
		"gate_out_date",
		"gate_in_remarks",
		"gate_out_remarks",
	];

	protected $appends = [
		'created_on',
	];

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

	public function getCreatedAtAttribute($value) {
		return empty($value) ? '' : date('d-m-Y h:i A', strtotime($value));
	}

	public function getCreatedOnAttribute() {
		return empty($this->attributes['created_at']) ? '' : date('d/m/Y', strtotime($this->attributes['created_at']));
	}

	public function getGateInDateAttribute($value) {
		return empty($value) ? '' : date('d-m-Y h:i A', strtotime($value));
	}
	public function getGateOutDateAttribute($value) {
		return empty($value) ? '' : date('d-m-Y h:i A', strtotime($value));
	}

	public function setDateOfJoinAttribute($date) {
		return $this->attributes['date_of_join'] = empty($date) ? NULL : date('Y-m-d', strtotime($date));
	}

	public function type() {
		return $this->belongsTo('App\Config', 'type_id');
	}

	public function gatePassDetail() {
		return $this->hasOne('App\GatePassDetail', 'gate_pass_id');
	}

	public function status() {
		return $this->belongsTo('App\Config', 'status_id');
	}
	public function jobCard() {
		return $this->belongsTo('App\JobCard', 'job_card_id');
	}

	public function jobOrder() {
		return $this->belongsTo('App\JobOrder', 'job_order_id');
	}

	public function gatePassItems() {
		return $this->hasMany('App\GatePassItem', 'gate_pass_id');
	}
	public function gatePassItemsCount() {
		return $this->hasMany('App\GatePassItem', 'gate_pass_id')->count();
	}

	public function gatePassGatePassItems() {
		return $this->belongsToMany('App\GatePassItem', 'gate_pass_gate_pass_item', 'gate_pass_id', 'gate_pass_item_id')->withPivot(['return_qty', 'gate_in_date']);
	}

	public static function createFromObject($record_data) {

		$errors = [];
		$company = Company::where('code', $record_data->company)->first();
		if (!$company) {
			dump('Invalid Company : ' . $record_data->company);
			return;
		}

		$admin = $company->admin();
		if (!$admin) {
			dump('Default Admin user not found');
			return;
		}

		$type = Config::where('name', $record_data->type)->where('config_type_id', 89)->first();
		if (!$type) {
			$errors[] = 'Invalid Tax Type : ' . $record_data->type;
		}

		if (count($errors) > 0) {
			dump($errors);
			return;
		}

		$record = self::firstOrNew([
			'company_id' => $company->id,
			'name' => $record_data->tax_name,
		]);
		$record->type_id = $type->id;
		$record->created_by_id = $admin->id;
		$record->save();
		return $record;
	}

	public static function getList($params = [], $add_default = true, $default_text = 'Select Gate Pass') {
		$list = Collect(Self::select([
			'id',
			'name',
		])
				->orderBy('name')
				->get());
		if ($add_default) {
			$list->prepend(['id' => '', 'name' => $default_text]);
		}
		return $list;
	}

}

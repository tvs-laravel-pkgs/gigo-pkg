<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\Company;
use App\Config;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GateLog extends Model {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'gate_logs';
	public $timestamps = true;
	protected $fillable =
		["company_id", "number", "date", "driver_name", "contact_number", "vehicle_id", "km_reading", "reading_type_id", "gate_in_remarks", "gate_out_date", "gate_out_remarks", "gate_pass_id", "status_id"]
	;

	public function getDateOfJoinAttribute($value) {
		return empty($value) ? '' : date('d-m-Y', strtotime($value));
	}

	public function setDateOfJoinAttribute($date) {
		return $this->attributes['date_of_join'] = empty($date) ? NULL : date('Y-m-d', strtotime($date));
	}

	public function vehicle() {
		return $this->belongsTo('App\Vehicle', 'vehicle_id');
	}

	//issue : naming
	// public function vehicleDetail() {
	// 	return $this->belongsTo('App\Vehicle', 'vehicle_id');
	// }

	public function gatePass() {
		return $this->belongsTo('App\GatePass', 'gate_pass_id');
	}

	public function jobOrder() {
		return $this->hasOne('App\JobOrder', 'gate_log_id');
	}
	public function status() {
		return $this->belongsTo('App\Config', 'status_id');
	}

	public function driverAttachment() {
		return $this->hasMany('App\Attachment', 'entity_id', 'id')->where('attachment_of_id', 225)->where('attachment_type_id', 249);
	}
	public function kmAttachment() {
		return $this->hasMany('App\Attachment', 'entity_id', 'id')->where('attachment_of_id', 225)->where('attachment_type_id', 248);
	}
	public function vehicleAttachment() {
		return $this->hasMany('App\Attachment', 'entity_id', 'id')->where('attachment_of_id', 225)->where('attachment_type_id', 247);
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

	public static function getList($params = [], $add_default = true, $default_text = 'Select Gate Log') {
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

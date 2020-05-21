<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\Company;
use App\Config;
use App\VehicleOwner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'vehicles';
	public $timestamps = true;
	protected $fillable =
		["company_id", "engine_number", "chassis_number", "model_id", "is_registered", "registration_number", "vin_number", "sold_date", "warranty_member_id", "ewp_expiry_date"]
	;

	public function getDateOfJoinAttribute($value) {
		return empty($value) ? '' : date('d-m-Y', strtotime($value));
	}

	public function setDateOfJoinAttribute($date) {
		return $this->attributes['date_of_join'] = empty($date) ? NULL : date('Y-m-d', strtotime($date));
	}
	public function vehicleOwner() {
		return $this->hasMany('App\VehicleOwner', 'vehicle_id', 'id');
	}
	public function vehicleCurrentOwner() {
		return $this->hasMany('App\VehicleOwner', 'vehicle_id')->orderBy('from_date', 'DESC')->limit(1);
	}

	public function vehicleModel() {
		return $this->belongsTo('App\vehicleModel', 'model_id');
	}

	public function status() {
		return $this->belongsTo('App\Config', 'status_id');
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

	public static function getList($params = [], $add_default = true, $default_text = 'Select Vehicle') {
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

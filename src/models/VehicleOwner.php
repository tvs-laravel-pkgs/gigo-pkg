<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\Company;
use App\Config;
use App\Customer;
use Illuminate\Database\Eloquent\Model;

// use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleOwner extends Model {
	use SeederTrait;
	//use SoftDeletes;
	protected $table = 'vehicle_owners';
	// public $timestamps = true;
	public $timestamps = false;
	protected $fillable =
		["vehicle_id", "customer_id", "from_date", "ownership_id"]
	;

	public function getFromDateAttribute($value) {
		return empty($value) ? '' : date('d-m-Y', strtotime($value));
	}

	public function setFromDateAttribute($date) {
		return $this->attributes['from_date'] = empty($date) ? NULL : date('Y-m-d', strtotime($date));
	}

	public function customer() {
		return $this->belongsTo('App\Customer', 'customer_id');
	}

	//issue : naming
	// public function CustomerDetail() {
	// 	return $this->belongsTo('App\Customer', 'customer_id');
	// }

	public function ownershipType() {
		//issue : wrong relationship
		return $this->belongsTo('App\Config', 'ownership_id');
	}

	//issue : naming
	// public function ownerShipDetail() {
	// 	return $this->belongsTo('App\Config', 'ownership_id');
	// }

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

	public static function getList($params = [], $add_default = true, $default_text = 'Select Vehicle Owner') {
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

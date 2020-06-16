<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;
use App\Company;
use App\Config;
use Auth;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceType extends BaseModel {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'service_types';
	public $timestamps = true;
	protected $fillable =
		["company_id", "code", "name"]
	;

	public function getDateOfJoinAttribute($value) {
		return empty($value) ? '' : date('d-m-Y', strtotime($value));
	}

	public function setDateOfJoinAttribute($date) {
		return $this->attributes['date_of_join'] = empty($date) ? NULL : date('Y-m-d', strtotime($date));
	}

	public function serviceTypeLabours() {
		return $this->belongsToMany('Abs\GigoPkg\RepairOrder', 'repair_order_service_type', 'service_type_id', 'repair_order_id')->withPivot(['is_free_service']);
	}

	public function serviceTypeParts() {
		return $this->belongsToMany('Abs\PartPkg\Part', 'part_service_type', 'service_type_id', 'part_id')->withPivot(['quantity', 'amount', 'is_free_service']);
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

	public static function getDropDownList($params = [], $add_default = true, $default_text = 'Select Service Type') {
		$list = Collect(Self::select([
			'id',
			'name',
		])
				->orderBy('name')
				->where('company_id', Auth::user()->company_id)
				->get());
		if ($add_default) {
			$list->prepend(['id' => '', 'name' => $default_text]);
		}
		return $list;
	}

}

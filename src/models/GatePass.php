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
	protected $fillable =
		["company_id", "type_id", "name"]
	;

	public function getCreatedAtAttribute($value) {
		return empty($value) ? '' : date('d-m-Y H:i a', strtotime($value));
	}
	public function getGateInDateAttribute($value) {
		return empty($value) ? '' : date('d-m-Y H:i a', strtotime($value));
	}
	public function getGateOutDateAttribute($value) {
		return empty($value) ? '' : date('d-m-Y H:i a', strtotime($value));
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
		return $this->belongsTo('App\Config', 'type_id');
	}
	public function jobCard() {
		return $this->belongsTo('App\JobCard');
	}

	public function gatePassItems() {
		return $this->hasMany('App\GatePassItem', 'gate_pass_id');
	}
	public function gatePassItemsCount() {
		return $this->hasMany('App\GatePassItem', 'gate_pass_id')->count();
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

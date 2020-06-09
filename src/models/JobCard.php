<?php

namespace Abs\GigoPkg;
use Abs\GigoPkg\JobOrder;
use Abs\HelperPkg\Traits\SeederTrait;
use App\Company;
use App\Config;
use Auth;
use Illuminate\Database\Eloquent\Model;

// use Illuminate\Database\Eloquent\SoftDeletes;

class JobCard extends Model {
	use SeederTrait;
	// use SoftDeletes;
	protected $table = 'job_cards';
	public $timestamps = true;
	protected $fillable = [
		"company_id",
		"job_card_number",
		"dms_job_card_number",
		"date",
		"created_by",
		"job_order_id",
		"number",
		"order_number",
		"floor_supervisor_id",
		"status_id",
	];

	public function getDateOfJoinAttribute($value) {
		return empty($value) ? '' : date('d-m-Y', strtotime($value));
	}

	public function setDateOfJoinAttribute($date) {
		return $this->attributes['date_of_join'] = empty($date) ? NULL : date('Y-m-d', strtotime($date));
	}

	public function jobOrder() {
		return $this->belongsTo('Abs\GigoPkg\JobOrder', 'job_order_id');
	}

	public function outlet() {
		return $this->belongsTo('App\Outlet', 'outlet_id')->where('company_id', Auth::user()->company_id);
	}

	public function company() {
		return $this->belongsTo('App\Company', 'company_id');
	}

	public function workOrders() {
		return $this->hasMany('App\WorkOrder');
	}

	public function business() {
		return $this->belongsTo('App\Business', 'business_id')->where('company_id', Auth::user()->company_id);
	}

	public function sacCode() {
		return $this->belongsTo('App\Entity', 'sac_code_id')->where('company_id', Auth::user()->company_id);
	}

	public function model() {
		return $this->belongsTo('App\Entity', 'model_id')->where('company_id', Auth::user()->company_id);
	}

	public function segment() {
		return $this->belongsTo('App\Entity', 'segment_id')->where('company_id', Auth::user()->company_id);
	}

	public function bay() {
		return $this->belongsTo('App\Bay', 'bay_id');
	}

	public function status() {
		return $this->belongsTo('App\Config', 'status_id');
	}

	public function gatePasses() {
		return $this->hasMany('App\GatePass', 'job_card_id', 'id');
	}

	public function jobCardReturnableItems() {
		return $this->hasMany('Abs\GigoPkg\JobCardReturnableItem');
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

	public static function getList($params = [], $add_default = true, $default_text = 'Select Job Card') {
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

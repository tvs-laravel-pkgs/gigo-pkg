<?php

namespace Abs\GigoPkg;
use Abs\GigoPkg\JobOrder;
use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;
use App\Company;
use App\Config;
use Auth;
use Illuminate\Database\Eloquent\Model;

// use Illuminate\Database\Eloquent\SoftDeletes;

class JobCard extends BaseModel {
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

	//APPEND - INBETWEEN REGISTRATION NUMBER
	public function getRegistrationNumberAttribute($value) {
		$value = str_replace('-', '', $value);
		$registration_number = str_split($value);
		$registration_number_new = '';

		$registration_number_new .= $registration_number[0] . $registration_number[1] . '-' . $registration_number[2] . $registration_number[3] . '-';

		if (preg_match('/^[A-Z]+$/', $registration_number[4]) && preg_match('/^[A-Z]+$/', $registration_number[5])) {
			$registration_number_new .= $registration_number[4] . $registration_number[5] . '-' . $registration_number[6] . $registration_number[7] . $registration_number[8] . $registration_number[9];
		} elseif (preg_match('/^[A-Z]+$/', $registration_number[4]) && preg_match('/^[0-9]+$/', $registration_number[5])) {
			$registration_number_new .= $registration_number[4] . '-' . $registration_number[5] . $registration_number[6] . $registration_number[7] . $registration_number[8];
		} else {
			$registration_number_new .= $registration_number[4] . $registration_number[5] . $registration_number[6] . $registration_number[7];
		}

		return $this->attributes['registration_number'] = $registration_number_new;
	}

	public function getDateAttribute($value) {
		return empty($value) ? '' : date('d-m-Y', strtotime($value));
	}

	public function getCreatedAtAttribute($value) {
		return empty($value) ? '' : date('d-m-Y h:i A', strtotime($value));
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

	public function gigoInvoices() {
		return $this->hasMany('Abs\GigoPkg\GigoInvoice', 'entity_id', 'id');
	}

	public function attachment() {
		return $this->hasOne('App\Attachment', 'entity_id', 'id')->where('attachment_of_id', 228)->where('attachment_type_id', 255);
	}

	// Query Scopes --------------------------------------------------------------

	public function scopeFilterSearch($query, $term) {
		if (strlen($term)) {
			$query->where(function (Builder $query) use ($term) {
				$query->orWhereRaw("TRIM(CONCAT(full_name, ' ', surname)) LIKE ?", [
					"%{$term}%",
				]);
				$query->orWhere('additional_name', 'LIKE', '%' . $term . '%');
				$query->orWhere('alias', 'LIKE', '%' . $term . '%');
				$query->orWhere('end_date', 'LIKE', '%' . $term . '%');
				$query->orWhere('address_1', 'LIKE', '%' . $term . '%');
				$query->orWhere('city', 'LIKE', '%' . $term . '%');
				$query->orWhere('county', 'LIKE', '%' . $term . '%');
				$query->orWhereRaw("REPLACE(postcode, ' ', '') LIKE ?", ['%' . str_replace(' ', '', $term) . '%']);
				$query->orWhere('tel_h', 'LIKE', '%' . $term . '%');
				$query->orWhere('tel_m', 'LIKE', '%' . $term . '%');
				$query->orWhere('email', 'LIKE', '%' . $term . '%');
			});
		}
	}

	// Operations --------------------------------------------------------------

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

<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends BaseModel {
	use SeederTrait;
	use SoftDeletes;

	protected $fillable = [
		'company_id',
		'code',
		'name',
		'internal_outlet_id',
		'gstin',
		'type_id',
		// 'category_id',
		'created_by',
	];

	public function business() {
		return $this->belongsToMany('App\Business', 'business_vendor', 'vendor_id', 'business_id')->withPivot('dms_vendor_code');
	}

	public function businesses() {
		return $this->belongsToMany('App\Business', 'business_vendor', 'vendor_id', 'business_id')->withPivot('dms_vendor_code');
	}

	public function outlets() {
		return $this->belongsToMany('App\Outlet', 'vendor_outlet', 'vendor_id', 'outlet_id')->withPivot('ax_code');
	}
	public function user() {
		return $this->hasOne('App\User', 'entity_id')->where('user_type_id', 2)->withTrashed();
	}

	public function regions() {
		return $this->belongsToMany('App\Region', 'vendor_region');
	}

	public function roles() {
		return $this->belongsToMany('App\Role', 'role_user', 'user_id', 'role_id');
	}

	public function workOrders() {
		return $this->hasMany('App\WorkOrder');
	}

	public function addresses() {
		return $this->hasMany('App\Address', 'entity_id')->where('address_of_id', 21);
	}

	public function primaryAddress() {
		return $this->hasOne('App\Address', 'entity_id')->where('address_of_id', 21)->where('address_type_id', 40);
	}

	public static function validate_import_record($index, $import_record, $mandatory_columns, $job) {
		$skip = false;
		$success = true;
		$record_errors = [];

		$company_id = $job->createdBy->company_id;

		if (empty($import_record[$mandatory_columns['Company']->excel_column_name])) {
			$record_errors[] = 'Company is empty';
			$skip = true;
		}
		if (empty($import_record[$mandatory_columns['Vendor']->excel_column_name])) {
			$record_errors[] = 'Vendor Code is empty';
			$skip = true;
		}
		if (empty($import_record[$mandatory_columns['Name']->excel_column_name])) {
			$record_errors[] = 'Vendor Name is empty';
			$skip = true;
		}
		$vendor_gstin = NULL;
		if ($import_record[$mandatory_columns['Vendor']->excel_column_name]) {

			//DUPLICATE Vendor NUMBER CHECK
			$vendor = Vendor::where('code', $import_record[$mandatory_columns['Vendor']->excel_column_name])->where('company_id', $company_id)
				->first();
			$vendor_code = $import_record[$mandatory_columns['Vendor']->excel_column_name];
			if ($vendor) {
				$outlet_id = DB::table('business_outlet')
					->where('business_id', $job->business_id)
					->where('outlet_code', $import_record[$mandatory_columns['Plant']->excel_column_name])
					->pluck('outlet_id')
					->first();

				$attachment_invoice_user = DB::table('vendor_outlet')->updateOrInsert(['vendor_id' => $vendor->id, 'outlet_id' => $outlet_id], ['outlet_id' => $outlet_id]);

				$record_errors[] = 'This Vendor Already been used -' . $import_record[$mandatory_columns['Vendor']->excel_column_name];
				$skip = true;
			}
		}
		if ($import_record[$mandatory_columns['Vendor GSTIN NO']->excel_column_name]) {

			//DUPLICATE GST NUMBER CHECK
			$vendor = Vendor::where('gstin', $import_record[$mandatory_columns['Vendor GSTIN NO']->excel_column_name])->where('company_id', $company_id)
				->where('code', '!=', $import_record[$mandatory_columns['Vendor']->excel_column_name])
				->first();
			$vendor_gstin = $import_record[$mandatory_columns['Vendor GSTIN NO']->excel_column_name];
			if ($vendor) {
				$record_errors[] = 'This GSTIN Already been used -' . $import_record[$mandatory_columns['Vendor GSTIN NO']->excel_column_name];
				$skip = true;
			}
		}

		if (empty($import_record[$mandatory_columns['Account Group Name']->excel_column_name])) {
			$record_errors[] = 'Account Group Name is empty';
			$skip = true;
		} else {
			//VALID VENDOR TYPE CHECK
			$vendor_type = Config::where('name', $import_record[$mandatory_columns['Account Group Name']->excel_column_name])
				->where('config_type_id', 7)
				->first();
			if (!$vendor_type) {
				$record_errors[] = 'Vendor Type Not Found' . $import_record[$mandatory_columns['Account Group Name']->excel_column_name];
				$skip = true;
			}
		}

		//Outlet
		// if (empty($import_record[$mandatory_columns['Plant Name']->excel_column_name])) {
		// 	$record_errors[] = 'Outlet is empty';
		// 	$skip = true;
		// } else {
		// 	$outlet = Outlet::where('name', $import_record[$mandatory_columns['Plant Name']->excel_column_name])->where('company_id', $company_id)->first();
		// 	if (!$outlet) {
		// 		$record_errors[] = 'Invalid Outlet -' . $import_record[$mandatory_columns['Plant Name']->excel_column_name];
		// 		$skip = true;
		// 	}
		// }

		//Internal Outlet

		if (empty($import_record[$mandatory_columns['Plant']->excel_column_name])) {

			$record_errors[] = 'Plant is empty';
			$skip = true;
		} else {
			//$outlet = Outlet::where('name', $import_record[$mandatory_columns['Plant Name']->excel_column_name])->where('company_id', $company_id)->first();
			$internal_outlet = DB::table('business_outlet')
				->where('business_id', $job->business_id)
				->where('outlet_code', $import_record[$mandatory_columns['Plant']->excel_column_name])
				->select('outlet_id')
				->first();
			if (!$internal_outlet) {
				$record_errors[] = 'Invalid Outlet -' . $import_record[$mandatory_columns['Plant']->excel_column_name];
				$skip = true;
			}
		}

		// External Outlet
		if (empty($import_record[$mandatory_columns['External Plant']->excel_column_name])) {

			$record_errors[] = 'External Plant is empty';
			$skip = true;
		} else {
			// $outlet = [];
			// dd($import_record[$mandatory_columns['External Plant']->excel_column_name]);
			$outlets = explode(',', $import_record[$mandatory_columns['External Plant']->excel_column_name]);
			foreach ($outlets as $key => $outlet) {
				$external_outlet[] = DB::table('business_outlet')
					->where('business_id', $job->business_id)
					->where('outlet_code', $outlet)
					->pluck('outlet_id')
					->first();

				if (!$external_outlet) {
					$record_errors[] = 'Invalid Outlet -' . $import_record[$mandatory_columns['External Plant']->excel_column_name];
					$skip = true;
				}
			}
			// dd($external_outlet);

			//$outlet = Outlet::where('name', $import_record[$mandatory_columns['Plant Name']->excel_column_name])->where('company_id', $company_id)->first();

		}
		// dd('test', $external_outlet);

		//Region
		// if (empty($import_record[$mandatory_columns['Region Name']->excel_column_name])) {
		// 	$record_errors[] = 'Region is empty';
		// 	$skip = true;
		// } else {
		// 	$region = Region::where('name', $import_record[$mandatory_columns['Region Name']->excel_column_name])->where('company_id', $company_id)->first();
		// 	if (!$region) {
		// 		$record_errors[] = 'Invalid Region -' . $import_record[$mandatory_columns['Region Name']->excel_column_name];
		// 		$skip = true;
		// 	}
		// }

		//VALID COMPANY CHECK
		if ($job->business->company_code != $import_record[$mandatory_columns['Company']->excel_column_name]) {
			$record_errors[] = 'Invalid Company -' . $import_record[$mandatory_columns['Company']->excel_column_name];
			$skip = true;
		}

		if ($skip) {
			$status['skip'] = $skip;
			$status['errors'] = $record_errors;
			return $status;
		}

		$import_record['company_id'] = $company_id;
		$import_record['code'] = $import_record[$mandatory_columns['Vendor']->excel_column_name];
		$import_record['name'] = $import_record[$mandatory_columns['Name']->excel_column_name];
		$import_record['gstin'] = $vendor_gstin;
		$import_record['internal_outlet_id'] = $internal_outlet->outlet_id;
		// dd($external_outlet);
		$import_record['external_outlet_id'] = $external_outlet;
		$import_record['type_id'] = $vendor_type->id;
		$import_record['category_id'] = '141';
		$import_record['business_id'] = $job->business_id;
		$import_record['created_by'] = $job->created_by;
		$status['skip'] = $skip;
		$status['data'] = $import_record;
		return $status;
	}

	public static function getFormData() {

		$data = [];
		$company_id = Auth::user()->company_id;

		$country_option = new Country;
		$country_option->name = 'Select Country';
		$country_option->id = NULL;
		$data['country_list'] = $countries = Country::select('id', 'name')->get();
		$data['country_list'] = $countries->prepend($country_option);

		$data['region_list'] = Region::select(
			'code as name',
			'id'
		)
			->where('company_id', $company_id)
			->get()
			->keyBy('id')
		;

		$data['business_list'] = Business::select(
			'name',
			'id'
		)
			->where('company_id', $company_id)
			->get()
			->keyBy('id')
		;

		$data['role_list'] = Role::select('name', 'id')
			->where('company_id', $company_id)
			->orWhere('company_id', NULL)->get()->keyBy('id');

		$type_option = new Config;
		$type_option->name = 'Select Vendor Type';
		$type_option->id = NULL;
		$data['type'] = $type = Config::select('id', 'name')->whereIn('config_type_id', [7, 8])->get();
		$data['type'] = $type->prepend($type_option);

		$vendor_option = new Vendor;
		$vendor_option->name = 'Select Main Vendor';
		$vendor_option->id = NULL;
		$data['vendor_list_name'] = $vendor = Vendor::withTrashed()->select('id', 'name', 'code')->get();
		$data['vendor_list_name'] = $vendor->prepend($vendor_option);

		$option = new Outlet;
		$option->name = 'Select Internal Outlet';
		$option->id = NULL;
		$internal_outlet_list = Outlet::select(DB::raw('concat(code," / ",name) as name'), 'id')->where('company_id', Auth::user()->company_id)->get();
		$data['internal_outlet_list'] = $internal_outlet_list->prepend($option);

		// $category_option = new Config;
		// $category_option->name = 'Select Vendor Category';
		// $category_option->id = NULL;
		// $data['category'] = $category = Config::select('id', 'name')->where('config_type_id', 8)->get();
		// $data['category'] = $category->prepend($category_option);

		return $data;
	}

	public static function validateVendorImportRecord($index, $import_record, $mandatory_columns, $job) {
		$skip = false;
		$success = true;
		$record_errors = [];
		$company_id = $job->createdBy->company_id;

		//MANDATORY
		if (empty($import_record[$mandatory_columns['Company Code']->excel_column_name])) {
			$record_errors[] = 'Company code is empty';
			$skip = true;
		}

		if (empty($import_record[$mandatory_columns['Plant Code']->excel_column_name])) {
			$record_errors[] = 'plant code is empty';
			$skip = true;
		}

		if (empty($import_record[$mandatory_columns['Vendor Address']->excel_column_name])) {
			$record_errors[] = 'Vendor Address is empty';
			$skip = true;
		}
		if (empty($import_record[$mandatory_columns['External /Internal']->excel_column_name])) {
			$record_errors[] = 'External /Internal is empty';
			$skip = true;
		} else {
			//VALID VENDOR TYPE CHECK
			$vendor_type = Config::where('name', 'like', '%' . $import_record[$mandatory_columns['External /Internal']->excel_column_name] . '%')
				->where('config_type_id', 7)
				->first();

			if (!$vendor_type) {
				$record_errors[] = 'Vendor Type Not Found' . $import_record[$mandatory_columns['External /Internal']->excel_column_name];
				$skip = true;
			}

		}

		if (empty($import_record[$mandatory_columns['Supplier Code']->excel_column_name])) {
			$record_errors[] = 'supplier code is empty';
			$skip = true;
		}
		if (empty($import_record[$mandatory_columns['Supplier Name']->excel_column_name])) {
			$record_errors[] = 'supplier name is empty';
			$skip = true;
		}

		$vendor_gstin = NULL;
		//DUPLICATE CHECKSS
		if ($import_record[$mandatory_columns['Supplier Code']->excel_column_name]) {
			$vendor_code = $import_record[$mandatory_columns['Supplier Code']->excel_column_name];

			if ($import_record[$mandatory_columns['AX12 Code']->excel_column_name]) {
				$ax_code = $import_record[$mandatory_columns['AX12 Code']->excel_column_name];
			} else {
				$ax_code = NULL;
			}

		}
		//check outlet
		$outlet_id = DB::table('business_outlet')
			->where('business_id', $job->business_id)
			->where('outlet_code', $import_record[$mandatory_columns['Plant Code']->excel_column_name])
			->pluck('outlet_id')
			->first();

		if ($outlet_id) {
			$outlet_id = $outlet_id;

		} else {
			$record_errors[] = 'Outlet Id Not Found -' . $import_record[$mandatory_columns['Plant Code']->excel_column_name];
			$skip = true;
			$outlet_id = NULL;

		}

		//VENDOR GSTIN
		if ($import_record[$mandatory_columns['Vendor GSTN']->excel_column_name]) {

			//DUPLICATE GST NUMBER CHECK
			$vendor = Vendor::where('gstin', $import_record[$mandatory_columns['Vendor GSTN']->excel_column_name])->where('company_id', $company_id)
				->where('code', '!=', $import_record[$mandatory_columns['Supplier Code']->excel_column_name])
				->first();
			$vendor_gstin = $import_record[$mandatory_columns['Vendor GSTN']->excel_column_name];

		}

		//VALID COMPANY CHECK
		if ($job->business->company_code != $import_record[$mandatory_columns['Company Code']->excel_column_name]) {
			$record_errors[] = 'Invalid Company -' . $import_record[$mandatory_columns['Company Code']->excel_column_name];
			$skip = true;
		}

		if ($skip) {
			$status['skip'] = $skip;
			$status['errors'] = $record_errors;
			return $status;
		}
		$import_record['company_id'] = $company_id;
		$import_record['code'] = $import_record[$mandatory_columns['Supplier Code']->excel_column_name];
		$import_record['name'] = $import_record[$mandatory_columns['Supplier Name']->excel_column_name];
		$import_record['Address'] = $import_record[$mandatory_columns['Vendor Address']->excel_column_name];
		$import_record['gstin'] = $vendor_gstin;
		// $import_record['internal_outlet_id'] = $internal_outlet;

		$import_record['category_id'] = '141';
		$import_record['business_id'] = $job->business_id;
		$import_record['created_by'] = $job->created_by;
		$import_record['External_Internal'] = $import_record[$mandatory_columns['External /Internal']->excel_column_name];
		$import_record['type_id'] = $vendor_type->id;

		$status['skip'] = $skip;
		$import_record['outlet_id'] = $outlet_id;
		$import_record['ax_code'] = $ax_code;
		$status['data'] = $import_record;
		// dd($status);

		return $status;
	}

	public static function searchVendorCode($r) {
		$key = $r->key;
		// dd($r->type_id);
		$list = self::where('company_id', Auth::user()->company_id)
			->select(
				'id',
				'code', 'mobile_no', 'type_id', 'name'
			)
			->where(function ($q) use ($key) {
				$q->where('code', 'like', $key . '%')
					->orWhere('name', 'like', '%' . $key . '%')
				;
			})
			->where(function ($q) use ($r) {
				if ($r->type_id) {
					$q->where('type_id', $r->type_id);
				}
			})
			->get();
		return response()->json($list);
	}

}

<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Validator;
use App\BaseModel;

class ComplaintGroup extends BaseModel {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'complaint_groups';
	public $timestamps = true;
	protected $fillable =[
		"id", 
		"company_id", 
		"code", 
		"name", 
	];

	// Getter & Setters --------------------------------------------------------------

	// Static operations --------------------------------------------------------------

	public static function validate($data, $user) {
		$error_messages = [
			'code.required' => 'Code is Required',
			'code.unique' => 'Code already taken',
			'code.min' => 'Code should have minimum 3 Charachers',
			'code.max' => 'Code should have maximum 32 Charachers',
			'name.required' => 'Name is Required',
			'name.unique' => 'Name already taken',
			'name.min' => 'Name should have minimum 3 Charachers',
			'name.max' => 'Name should have maximum 191 Charachers',
		];
		$validator = Validator::make($data, [
			'code' => [
				'required:true',
				'min:3',
				'max:32',
			],
			'name' => [
				'required:true',
				'min:3',
				'max:191',
			],
		], $error_messages);
		if ($validator->fails()) {
			return [
				'success' => false,
				'errors' => $validator->errors()->all(),
			];
		}
		return [
			'success' => true,
		];
	}

	// public static function createFromObject($record_data) {
	// 	$errors = [];
	// 	$company = Company::where('code', $record_data->company_code)->first();
	// 	if (!$company) {
	// 		return [
	// 			'success' => false,
	// 			'errors' => ['Invalid Company : ' . $record_data->company],
	// 		];
	// 	}

	// 	$admin = $company->admin();
	// 	if (!$admin) {
	// 		return [
	// 			'success' => false,
	// 			'errors' => ['Default Admin user not found'],
	// 		];
	// 	}

	// 	$validation = Self::validate($record_data->toArray(), $admin);
	// 	if (!$validation['success']) {
	// 		return [
	// 			'success' => false,
	// 			'errors' => $validation['errors'],
	// 		];
	// 	}

	// 	$record = self::firstOrNew([
	// 		'company_id' => $company->id,
	// 		'code' => $record_data->code,
	// 	]);
	// 	$record->name = $record_data->name;
	// 	$record->created_by_id = $admin->id;
	// 	$record->save();
	// 	return [
	// 		'success' => true,
	// 	];
	// }

}

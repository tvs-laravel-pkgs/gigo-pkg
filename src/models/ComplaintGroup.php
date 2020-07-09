<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Validator;

class ComplaintGroup extends BaseModel {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'complaint_groups';
	public $timestamps = true;
	protected $fillable = [
		"id",
		"company_id",
		"code",
		"name",
	];

	// Getter & Setters --------------------------------------------------------------

	// Query Scopes --------------------------------------------------------------

	public function scopeFilterSearch($query, $term) {
		if (strlen($term)) {
			$query->where(function ($query) use ($term) {
				$query->orWhere('code', 'LIKE', '%' . $term . '%');
				$query->orWhere('name', 'LIKE', '%' . $term . '%');
			});
		}
	}

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

}

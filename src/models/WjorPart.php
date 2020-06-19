<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;
use Auth;
use DB;
use Illuminate\Database\Eloquent\SoftDeletes;
use Validator;

class WjorPart extends BaseModel {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'wjor_parts';
	public $timestamps = false;
	protected $fillable = [
		"id",
		"wjor_id",
		"part_id",
	];
	// Getters --------------------------------------------------------------

	// Setters --------------------------------------------------------------

	// Relationships --------------------------------------------------------------

	public function wjor() {
		return $this->belongsTo('App\WarrantyJobOrderRequest');
	}

	public function part() {
		return $this->belongsTo('App\Part');
	}

	// Query Scopes --------------------------------------------------------------

	// Static Operations --------------------------------------------------------------

	public static function relationships($action = '') {
		$relationships = [
			'wjor',
			'part',
		];

		return $relationships;
	}

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
			'errors' => [],
		];
	}

	public static function createFromObject($record_data) {
		$errors = [];
		$company = Company::where('code', $record_data->company_code)->first();
		if (!$company) {
			return [
				'success' => false,
				'errors' => ['Invalid Company : ' . $record_data->company],
			];
		}

		$admin = $company->admin();
		if (!$admin) {
			return [
				'success' => false,
				'errors' => ['Default Admin user not found'],
			];
		}

		$validation = Self::validate($original_record, $admin);
		if (count($validation['success']) > 0 || count($errors) > 0) {
			return [
				'success' => false,
				'errors' => array_merge($validation['errors'], $errors),
			];
		}

		$record = self::firstOrNew([
			'company_id' => $company->id,
			'code' => $record_data->code,
		]);
		$record->name = $record_data->name;
		$record->created_by_id = $admin->id;
		$record->save();
		return [
			'success' => true,
		];
	}

	public static function saveFromNgArray($input, $owner = null) {
		$owner = !is_null($owner) ? $owner : Auth::user();

		if (!isset($input['id']) || !$input['id']) {
			$record = new Self();
			$record->company_id = $owner->company_id;
			$record->number = rand();

		} else {
			$record = Self::find($input['id']);
			if (!$record) {
				return [
					'success' => false,
					'error' => 'Record not found',
				];
			}
		}
		$record->fill($input);
		$record->job_order_id = $input['job_order']['id'];
		$record->complaint_id = $input['complaint']['id'];
		$record->fault_id = $input['fault']['id'];
		$record->supplier_id = $input['supplier']['id'];
		$record->primary_segment_id = $input['primary_segment']['id'];
		$record->secondary_segment_id = $input['secondary_segment']['id'];
		$record->operating_condition_id = $input['operating_condition']['id'];
		$record->normal_road_condition_id = $input['normal_road_condition']['id'];
		$record->failure_road_condition_id = $input['failure_road_condition']['id'];
		// $record->load_carried_type_id = $input['load_carried_type']['id'];
		$record->load_range_id = $input['load_range']['id'];
		$record->terrain_at_failure_id = $input['terrain_at_failure']['id'];
		// $record->reading_type_id = $input['reading_type']['id'];
		$record->status_id = 9100; //New
		$record->save();
		$record->number = 'WJOR-' . $record->id;
		// $record->failure_date = ;
		$record->save();
		return [
			'success' => true,
			'message' => 'Record created successfully',
			'warranty_job_order_request' => $record,
		];

	}

	public function saveFromFormArray($input, $owner = null) {
		try {
			DB::beginTransaction();
			$owner = !is_null($owner) ? $owner : Auth::user();

			if (!isset($input['id']) || !$input['id']) {
				$record = new Self();
				$record->company_id = $owner->company_id;
				$record->number = rand();

			} else {
				$record = Self::find($input['id']);
				if (!$record) {
					return [
						'success' => false,
						'error' => 'Record not found',
					];
				}
			}
			$record->fill($input);
			$record->status_id = 9100; //New
			$record->save();
			$record->number = 'WJOR-' . $record->id;
			$record->save();

			//SAVE ATTACHMENTS
			$attachement_path = storage_path('app/public/wjor/');
			Storage::makeDirectory($attachement_path, 0777);
			if (count($input['photos']) > 0) {
				foreach ($input['photos'] as $key => $photo) {
					$value = rand(1, 100);
					$image = $photo;

					$file_name_with_extension = $image->getClientOriginalName();
					$file_name = pathinfo($file_name_with_extension, PATHINFO_FILENAME);
					$extension = $image->getClientOriginalExtension();
					// dd($file_name, $extension);
					//ISSUE : file name should be stored
					$name = $record->id . '_' . $file_name . '_' . rand(10, 1000) . '.' . $extension;

					$photo->move($attachement_path, $name);
					$attachement = new Attachment;
					$attachement->attachment_of_id = 9120;
					$attachement->attachment_type_id = 244;
					$attachement->entity_id = $record->id;
					$attachement->name = $name;
					$attachement->save();
				}
			}
			DB::commit();

			return [
				'success' => true,
				'message' => 'Record created successfully',
				'warranty_job_order_request' => $record,
			];
		} catch (Exceprion $e) {
			DB::rollBack();
			return response()->json([
				'success' => false,
				'error' => $e->getMessage(),
			]);
		}

	}

}

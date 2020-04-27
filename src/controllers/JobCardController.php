<?php

namespace Abs\GigoPkg;
use Abs\ApprovalPkg\ApprovalType;
use Abs\ApprovalPkg\EntityStatus;
use Abs\GigoPkg\JobCard;
use Abs\GigoPkg\Journal;
use App\ActivityLog;
use App\Config;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class JobCardController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.admin_theme');
	}

	public function getJvFilterData() {
		$this->data['extras'] = [
			'status' => [
				['id' => '', 'name' => 'Select Status'],
				['id' => '1', 'name' => 'Active'],
				['id' => '0', 'name' => 'Inactive'],
			],
			'journal_list' => Journal::select('id', 'name')->get(),
			'jv_account_type_list' => Config::select('id', 'name')->where('config_type_id', 27)->get(),
		];

		return response()->json($this->data);
	}

	public function getJobCardList(Request $request) {
		$job_cards = JobCard::withTrashed()
			->select([
				'job_cards.id',
				'job_cards.name',
				'job_cards.short_name',
				DB::raw('COALESCE(journals.name,"--") as journal'),
				DB::raw('COALESCE(from_ac.name,"--") as from_account'),
				DB::raw('COALESCE(to_ac.name,"--") as to_account'),
				DB::raw('IF(job_cards.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->leftJoin('job_card_field as journal', function ($join) {
				$join->on('journal.job_card_id', 'job_cards.id')
					->where('journal.field_id', 1420);
			})
			->leftJoin('journals', 'journals.id', 'journal.value')
			->leftJoin('job_card_field as from_account', function ($join) {
				$join->on('from_account.job_card_id', 'job_cards.id')
					->where('from_account.field_id', 1421);
			})
			->leftJoin('configs as from_ac', 'from_ac.id', 'from_account.value')
			->leftJoin('job_card_field as to_account', function ($join) {
				$join->on('to_account.job_card_id', 'job_cards.id')
					->where('to_account.field_id', 1422);
			})
			->leftJoin('configs as to_ac', 'to_ac.id', 'to_account.value')
			->where(function ($query) use ($request) {
				if (!empty($request->name)) {
					$query->where('job_cards.name', 'LIKE', '%' . $request->name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->short_name)) {
					$query->where('job_cards.short_name', 'LIKE', '%' . $request->short_name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->journal_name)) {
					$query->where('journals.id', $request->journal_name);
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->from_account)) {
					$query->where('from_ac.id', $request->from_account);
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->to_account)) {
					$query->where('to_ac.id', $request->to_account);
				}
			})
			->where(function ($query) use ($request) {
				if ($request->status == '1') {
					$query->whereNull('job_cards.deleted_at');
				} else if ($request->status == '0') {
					$query->whereNotNull('job_cards.deleted_at');
				}
			})
			->where('job_cards.company_id', Auth::user()->company_id)
		// ->orderby('job_cards.id', 'desc')
		;

		return Datatables::of($job_cards)
			->addColumn('name', function ($job_card) {
				$status = $job_card->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indicator ' . $status . '"></span>' . $job_card->name;
			})
			->addColumn('action', function ($job_card) {
				$img_edit = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');
				$img_edit_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow-active.svg');
				$img_view = asset('public/themes/' . $this->data['theme'] . '/img/content/table/eye.svg');
				$img_view_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/eye-active.svg');
				$img_delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				$img_delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');

				$output = '';
				if (Entrust::can('edit-job-card')) {
					$output .= '<a href="#!/gigo-pkg/job-card/edit/' . $job_card->id . '" id = "" title="Edit"><img src="' . $img_edit . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img_edit_active . '" onmouseout=this.src="' . $img_edit . '"></a>';
				}
				// if (Entrust::can('view-job-card')) {
				// 	$output .= '<a href="#!/gigo-pkg/job-card/view/' . $job_card->id . '" id = "" title="View"><img src="' . $img_view . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img_view_active . '" onmouseout=this.src="' . $img_view . '"></a>';
				// }
				if (Entrust::can('delete-job-card')) {
					$output .= '<a href="javascript:;" data-toggle="modal" data-target="#delete_job_card" onclick="angular.element(this).scope().deleteJobCard(' . $job_card->id . ')" title="Delete"><img src="' . $img_delete . '" alt="Delete" class="img-responsive delete" onmouseover=this.src="' . $img_delete_active . '" onmouseout=this.src="' . $img_delete . '"></a>
					';
				}
				return $output;
			})
			->make(true);
	}

	public function getJobCardFormData(Request $request) {
		$id = $request->id;
		if (!$id) {
			$job_card = new JobCard;
			$jv_field = [
				['is_editable' => 'Yes'],
				['is_editable' => 'Yes'],
				['is_editable' => 'Yes'],
			];
			$action = 'Add';
		} else {
			$job_card = JobCard::withTrashed()->find($id);
			$action = 'Edit';
			$jv_field = DB::table('job_card_field')
				->where('job_card_id', $id)
				->get();
		}
		$this->data['job_card'] = $job_card;
		$this->data['action'] = $action;
		$this->data['theme'];
		$this->data['extras'] = [
			'status_list' => EntityStatus::select('id', 'name')->company()->where('entity_id', 7221)->get(),
			'approval_type_list' => ApprovalType::where('entity_id', 7221)->select('id', 'name')->get(),
			'journal_list' => Journal::select('id', 'name')->get(),
			'jv_account_type_list' => Config::select('id', 'name')->where('config_type_id', 27)->get(),
		];

		$this->data['jv_field'] = $jv_field;
		return response()->json($this->data);
	}

	public function getJobCardView(Request $request) {
		$id = $request->id;
		$this->data['job_card'] = $job_card = JobCard::withTrashed()->with([
			'approvalType',
			'approvalTypeInitialStatus',
			'approvalTypeFinalStatus',
		])->find($id);
		$this->data['action'] = 'View';

		$this->data['jv_fields'] = $jv_fields = DB::table('job_card_field')->select(
			'job_card_field.*',
			'journals.name as journals',
			// 'from_account.name as value',
			'title.name as title',
			DB::raw('(CASE WHEN job_card_field.field_id= 1420
			 THEN journals.name WHEN job_card_field.field_id= 1421
			 THEN from_account.name WHEN job_card_field.field_id= 1422
			 THEN from_account.name
			 ELSE "--" END) as value')
		)
			->leftJoin('journals', 'journals.id', 'job_card_field.value')
			->leftJoin('configs as from_account', 'from_account.id', 'job_card_field.value')
			->leftJoin('configs as title', 'title.id', 'job_card_field.field_id')
			->where('job_card_field.job_card_id', $id)->get();

		return response()->json($this->data);
	}

	public function saveJobCard(Request $request) {
		// dd($request->all());
		try {
			$error_messages = [
				'name.required' => 'Name is Required',
				'name.unique' => 'Name is already taken',
				'name.min' => 'Name is Minimum 3 Charachers',
				'name.max' => 'Name is Maximum 64 Charachers',
				'short_name.required' => 'Name is Required',
				'short_name.unique' => 'Name is already taken',
				'short_name.min' => 'Name is Minimum 3 Charachers',
				'short_name.max' => 'Name is Maximum 24 Charachers',
				'approval_type_id.required' => 'Approval Flow Type is Required',
				'initial_status_id.required' => 'Initial Status is Required',
				'final_approved_status_id.required' => 'Final Approved Status is Required',
			];
			$validator = Validator::make($request->all(), [
				'name' => [
					'required:true',
					'min:3',
					'max:64',
					'unique:job_cards,name,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'short_name' => [
					'required:true',
					'min:2',
					'max:24',
					'unique:job_cards,short_name,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'approval_type_id' => 'required',
				'initial_status_id' => 'required',
				'final_approved_status_id' => 'required',
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$job_card = new JobCard;
				$job_card->created_by_id = Auth::user()->id;
				$job_card->created_at = Carbon::now();
				$job_card->updated_at = NULL;
			} else {
				$job_card = JobCard::withTrashed()->find($request->id);
				$job_card->updated_by_id = Auth::user()->id;
				$job_card->updated_at = Carbon::now();
			}
			$job_card->fill($request->all());
			$job_card->company_id = Auth::user()->company_id;
			if ($request->status == 'Inactive') {
				$job_card->deleted_at = Carbon::now();
				$job_card->deleted_by_id = Auth::user()->id;
			} else {
				$job_card->deleted_by_id = NULL;
				$job_card->deleted_at = NULL;
			}
			$job_card->save();
			// dd($request->jv_fields);
			if (!empty($request->jv_fields)) {
				foreach ($request->jv_fields as $jv_field) {
					// dd($jv_field);
					// if ($jv_field['is_open'] == 'Yes') {
					// 	$is_open = 1;
					// 	$is_editable = 1;
					// 	$jv_field['value'] = NULL;
					// } else {
					// $is_open = 0;
					if ($jv_field['is_editable'] == 'Yes' || empty($jv_field['is_editable'])) {
						$is_editable = 1;
						$is_open = 1;
						$jv_field['value'] = NULL;
					} else {
						$is_editable = 0;
						$is_open = 0;
					}
					// }

					if (!$request->id) {
						$jv_field_types = DB::table('job_card_field')->insert([
							'job_card_id' => $job_card->id,
							'field_id' => $jv_field['field_id'],
							'is_open' => $is_open,
							'is_editable' => $is_editable,
							'value' => $jv_field['value'],
						]);
					} else {
						$jv_field_types = DB::table('job_card_field')
							->where([
								'job_card_id' => $request->id,
								'field_id' => $jv_field['field_id'],
							])
							->update([
								'is_open' => $is_open,
								'is_editable' => $is_editable,
								'value' => $jv_field['value'],
							]);
					}
				}
			}

			$activity = new ActivityLog;
			$activity->date_time = Carbon::now();
			$activity->user_id = Auth::user()->id;
			$activity->module = 'Job Cards';
			$activity->entity_id = $job_card->id;
			$activity->entity_type_id = 1420;
			$activity->activity_id = $request->id == NULL ? 280 : 281;
			$activity->activity = $request->id == NULL ? 280 : 281;
			$activity->details = json_encode($activity);
			$activity->save();

			DB::commit();
			if (!($request->id)) {
				return response()->json([
					'success' => true,
					'message' => 'Job Card Added Successfully',
				]);
			} else {
				return response()->json([
					'success' => true,
					'message' => 'Job Card Updated Successfully',
				]);
			}
		} catch (Exceprion $e) {
			DB::rollBack();
			return response()->json([
				'success' => false,
				'error' => $e->getMessage(),
			]);
		}
	}

	public function deleteJobCard(Request $request) {
		DB::beginTransaction();
		try {
			$job_card = JobCard::withTrashed()->where('id', $request->id)->forceDelete();
			if ($job_card) {

				$activity = new ActivityLog;
				$activity->date_time = Carbon::now();
				$activity->user_id = Auth::user()->id;
				$activity->module = 'Job Cards';
				$activity->entity_id = $request->id;
				$activity->entity_type_id = 1420;
				$activity->activity_id = 282;
				$activity->activity = 282;
				$activity->details = json_encode($activity);
				$activity->save();

				$jv_field_types = DB::table('job_card_field')->where('job_card_id', $request->id)->delete();
				DB::commit();
				return response()->json(['success' => true, 'message' => 'Job Card Deleted Successfully']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

	public function getJobCard(Request $request) {
		$error_messages = [
			'id.required' => 'ID is required',
		];

		$validator = Validator::make($request->all(), [
			'id' => [
				'required:true',
			],
		], $error_messages);

		if ($validator->fails()) {
			return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
		}

		//NEW CODE
		$this->data['job_card'] = $job_card = JobCard::with([
			'fields',
		])->find($request->id);

		foreach ($job_card->fields as $field) {
			// dump($field->pivot->is_editable);
			if (!$field->pivot->is_editable) {
				// dump("false");
				if ($field->pivot->field_id == 1420) {
					//JOURNAL
					$job_card->journal_editable = false;
					$job_card->journal = Journal::select([
						'journals.id',
						'journals.name',
					])->find($field->pivot->value);
				} elseif ($field->pivot->field_id == 1421) {
					//FROM ACCOUNT TYPE
					$job_card->from_account_type_editable = false;
					$job_card->from_account_type = Config::find($field->pivot->value);
				} elseif ($field->pivot->field_id == 1422) {
					//TO ACCOUNT TYPE
					$job_card->to_account_type_editable = false;
					$job_card->to_account_type = Config::find($field->pivot->value);
				}
			} else {
				// dump("true");
				if ($field->pivot->field_id == 1420) {
					//JOURNAL
					$job_card->journal_editable = true;
				} elseif ($field->pivot->field_id == 1421) {
					//FROM ACCOUNT TYPE
					$job_card->from_account_type_editable = true;
				} elseif ($field->pivot->field_id == 1422) {
					//TO ACCOUNT TYPE
					$job_card->to_account_type_editable = true;
				}
			}
		}
		// dd($job_card);
		return response()->json($this->data);
	}
}

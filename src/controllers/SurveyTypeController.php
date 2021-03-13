<?php

namespace Abs\GigoPkg;
use Abs\AttributePkg\Models\Field;
use Abs\SerialNumberPkg\SerialNumberGroup;
use App\Config;
use App\FinancialYear;
use App\Http\Controllers\Controller;
use App\SurveyType;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class SurveyTypeController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	public function getSurveyTypeFilter() {
		$this->data['extras'] = [
			'status' => [
				['id' => '', 'name' => 'Select Status'],
				['id' => '1', 'name' => 'Active'],
				['id' => '0', 'name' => 'Inactive'],
			],

			'attendee_type' => collect(Config::select('id', 'name')->where('config_type_id', 410)->get())->prepend(['id' => '', 'name' => 'Select Attendee Type']),
			'event_list' => collect(Config::select('id', 'name')->where('config_type_id', 411)->get())->prepend(['id' => '', 'name' => 'Select Event']),
		];

		return response()->json($this->data);
	}

	public function getSurveyTypeList(Request $request) {
		// dd($request->all());
		$survey_types = SurveyType::withTrashed()

			->select([
				'survey_types.id',
				'survey_types.number',
				'survey_types.name',
				'attendee_type.name as attendee_name',
				'trigger_event.name as trigger_event_name',

				DB::raw('IF(survey_types.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->join('configs as attendee_type', 'attendee_type.id', 'survey_types.attendee_type_id')
			->join('configs as trigger_event', 'trigger_event.id', 'survey_types.survey_trigger_event_id')
		// ->leftJoin('configs as trigger_event', 'trigger_event.id', 'survey_types.survey_trigger_event_id')
			->where('survey_types.company_id', Auth::user()->company_id)

			->where(function ($query) use ($request) {
				if (!empty($request->attendee_type_id) && $request->attendee_type_id != '<%$ctrl.attendee_type_id%>') {
					$query->where('survey_types.attendee_type_id', $request->attendee_type_id);
				}
			})

			->where(function ($query) use ($request) {
				if (!empty($request->trigger_event_id) && $request->trigger_event_id != '<%$ctrl.trigger_event_id%>') {
					$query->where('survey_types.survey_trigger_event_id', $request->trigger_event_id);
				}
			})

			->where(function ($query) use ($request) {
				if ($request->status == '1') {
					$query->whereNull('survey_types.deleted_at');
				} else if ($request->status == '0') {
					$query->whereNotNull('survey_types.deleted_at');
				}
			})
		;

		return Datatables::of($survey_types)

			->addColumn('status', function ($survey_types) {
				$status = $survey_types->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indicator ' . $status . '"></span>' . $survey_types->status;
			})

			->addColumn('action', function ($survey_types) {
				$img1 = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');
				$img1_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow-active.svg');
				$img_delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				$img_delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');
				$action = '';

				if (Entrust::can('edit-survey-type')) {
					$action .= '<a href="#!/survey-type/edit/' . $survey_types->id . '" id = "" title="Edit"><img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1 . '" onmouseout=this.src="' . $img1 . '"></a>';

				}

				if (Entrust::can('delete-survey-type')) {
					$action .= '<a href="javascript:;" data-toggle="modal" data-target="#delete_trade_plate_number" onclick="angular.element(this).scope().deleteTradePlateNumber(' . $survey_types->id . ')" title="Delete"><img src="' . $img_delete . '" alt="Delete" class="img-responsive delete" onmouseover=this.src="' . $img_delete . '" onmouseout=this.src="' . $img_delete . '"></a>';

				}
				return $action;
			})
			->make(true);
	}

	public function getSurveyTypeFormData(Request $request) {
		// dd($request->all());
		$id = $request->id;
		if (!$id) {
			$survey_type = new SurveyType;
			$survey_type->survey_field = [];
			$action = 'Add';
		} else {
			$survey_type = SurveyType::withTrashed()->with([
				'surveyField',
			])->find($id);

			$action = 'Edit';
		}

		$this->data['success'] = true;
		$this->data['survey_type'] = $survey_type;
		$this->data['action'] = $action;

		$this->data['extras'] = [
			'field_list' => collect(Field::select('id', 'name')->where('category_id', 1041)->get())->prepend(['id' => '', 'name' => 'Select Field']),
			'attendee_type' => collect(Config::select('id', 'name')->where('config_type_id', 410)->get())->prepend(['id' => '', 'name' => 'Select Attendee Type']),
			'event_list' => collect(Config::select('id', 'name')->where('config_type_id', 411)->get())->prepend(['id' => '', 'name' => 'Select Event']),
		];

		return response()->json($this->data);
	}

	public function saveSurveyType(Request $request) {
		// dd($request->all());
		try {
			$error_messages = [
				'name.required' => 'Name is Required',
				'name.min' => 'Name is Minimum 3 Charachers',
				'name.max' => 'Name is Maximum 64 Charachers',
				'attendee_type_id.required' => 'Attendee is Required',
				'purpose.required' => 'Purpose is Required',
				'survey_trigger_event_id.required' => 'Survey Trigger Event is Required',
				'attendee_type_id.unique' => 'Selected Survey Trigger Event & Attendee Type are already taken',
			];
			$validator = Validator::make($request->all(), [
				'name' => [
					'required:true',
					'min:3',
					'max:64',
				],
				'purpose' => [
					'required:true',
				],
				'survey_trigger_event_id' => [
					'required:true',
					'exists:configs,id',
				],
				'attendee_type_id' => [
					'required',
					'exists:configs,id',
					'unique:survey_types,attendee_type_id,' . $request->id . ',id,company_id,' . Auth::user()->company_id . ',survey_trigger_event_id,' . $request->survey_trigger_event_id,
				],
			], $error_messages);

			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			//Check Unique Questions
			if ($request->questions) {
				$questions = array_column($request->questions, 'field_id');
				$questions_unique = array_unique($questions);
				if (count($questions) != count($questions_unique)) {
					return response()->json(['success' => false, 'errors' => ['Some Questions are already been taken']]);
				}
			} else {
				return response()->json([
					'success' => false,
					'message' => 'Select Atleast one question',
				]);
			}

			DB::beginTransaction();

			if (!$request->id) {

				if (date('m') > 3) {
					$year = date('Y') + 1;
				} else {
					$year = date('Y');
				}
				//GET FINANCIAL YEAR ID
				$financial_year = FinancialYear::where('from', $year)
					->where('company_id', Auth::user()->company_id)
					->first();

				if (!$financial_year) {
					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => [
							'Fiancial Year Not Found',
						],
					]);
				}

				//GENERATE GATE IN VEHICLE NUMBER
				$generateNumber = SerialNumberGroup::generateNumber(113);
				if (!$generateNumber['success']) {
					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => [
							'No Survey Type number found for FY : ' . $financial_year->year,
						],
					]);
				}

				$survey_type = new SurveyType;

				$survey_type->number = $generateNumber['number'];
				$survey_type->company_id = Auth::user()->company_id;
				$survey_type->created_by_id = Auth::user()->id;
				$survey_type->created_at = Carbon::now();
			} else {
				$survey_type = SurveyType::withTrashed()->find($request->id);
				$survey_type->updated_by_id = Auth::user()->id;
				$survey_type->updated_at = Carbon::now();
			}

			$survey_type->name = $request->name;
			$survey_type->attendee_type_id = $request->attendee_type_id;
			$survey_type->purpose = $request->purpose;
			$survey_type->survey_trigger_event_id = $request->survey_trigger_event_id;

			if ($request->status == 'Inactive') {
				$survey_type->deleted_at = Carbon::now();
			} else {
				$survey_type->deleted_at = NULL;
			}
			$survey_type->save();

			$survey_type->surveyField()->sync([]);

			foreach ($request->questions as $key => $question) {
				if (isset($question['field_id'])) {
					$survey_type->surveyField()->attach($question['field_id']);
				}
			}

			DB::commit();

			if (!($request->id)) {
				return response()->json([
					'success' => true,
					'message' => 'Survey Type Added Successfully',
				]);
			} else {
				return response()->json([
					'success' => true,
					'message' => 'Survey Type Updated Successfully',
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

	public function deleteSurveyType(Request $request) {
		DB::beginTransaction();
		// dd($request->id);
		try {
			$survey_type = SurveyType::withTrashed()->where('id', $request->id)->forceDelete();
			if ($survey_type) {
				DB::commit();
				return response()->json(['success' => true, 'message' => 'Survey Type Deleted Successfully']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}
}
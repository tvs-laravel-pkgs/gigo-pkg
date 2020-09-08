<?php

namespace Abs\GigoPkg\Api;

use Abs\SerialNumberPkg\SerialNumberGroup;
use App\Bay;
use App\FinancialYear;
use App\GateLog;
use App\GatePass;
use App\Http\Controllers\Controller;
use App\Outlet;
use App\ShortUrl;
use App\Survey;
use App\SurveyAnswer;
use App\SurveyType;
use App\Vehicle;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Validator;

class VehicleGatePassController extends Controller {
	public $successStatus = 200;

	public function getVehicleGatePassList(Request $request) {
		// dd($request->all());
		try {
			$vehicle_gate_pass_list = GatePass::select([
				'job_orders.driver_name',
				'job_orders.driver_mobile_number',
				'vehicles.registration_number',
				'models.model_name',
				'job_orders.number as job_card_number',
				'gate_passes.number as gate_pass_no',
				'gate_passes.id',
				'gate_logs.id as gate_log_id',
				'configs.name as status',
				'gate_passes.status_id',
				DB::raw('DATE_FORMAT(gate_passes.created_at,"%d/%m/%Y, %h:%s %p") as date_and_time'),
			])
				->join('job_orders', 'job_orders.id', 'gate_passes.job_order_id')
				->leftJoin('job_cards', 'job_cards.id', 'gate_passes.job_card_id')
				->join('gate_logs', 'gate_logs.job_order_id', 'job_orders.id')
				->join('vehicles', 'vehicles.id', 'job_orders.vehicle_id')
				->join('models', 'models.id', 'vehicles.model_id')
				->join('configs', 'configs.id', 'gate_passes.status_id')
				->where(function ($query) use ($request) {
					if (!empty($request->search_key)) {
						$query->where('vehicles.registration_number', 'LIKE', '%' . $request->search_key . '%')
							->orWhere('job_orders.driver_name', 'LIKE', '%' . $request->search_key . '%')
							->orWhere('job_orders.driver_mobile_number', 'LIKE', '%' . $request->search_key . '%')
							->orWhere('models.model_name', 'LIKE', '%' . $request->search_key . '%')
						// ->orWhere('job_cards.job_card_number', 'LIKE', '%' . $request->search_key . '%')
							->orWhere('gate_passes.number', 'LIKE', '%' . $request->search_key . '%')
							->orWhere('configs.name', 'LIKE', '%' . $request->search_key . '%')
							->orWhere('job_orders.number', 'LIKE', '%' . $request->search_key . '%')
						;
					}
				})
				->where(function ($query) use ($request) {
					if (!empty($request->gate_pass_created_date)) {
						$query->whereDate('gate_passes.created_at', date('Y-m-d', strtotime($request->gate_pass_created_date)));
					}
				})
				->where(function ($query) use ($request) {
					if (!empty($request->registration_number)) {
						$query->where('vehicles.registration_number', 'LIKE', '%' . $request->registration_number . '%');
					}
				})
				->where(function ($query) use ($request) {
					if (!empty($request->driver_name)) {
						$query->where('job_orders.driver_name', 'LIKE', '%' . $request->driver_name . '%');
					}
				})
				->where(function ($query) use ($request) {
					if (!empty($request->driver_mobile_number)) {
						$query->where('job_orders.driver_mobile_number', 'LIKE', '%' . $request->driver_mobile_number . '%');
					}
				})
				->where(function ($query) use ($request) {
					if (!empty($request->model_id)) {
						$query->where('vehicles.model_id', $request->model_id);
					}
				})
				->where(function ($query) use ($request) {
					if (!empty($request->status_id)) {
						$query->where('gate_passes.status_id', $request->status_id);
					}
				})
				->where(function ($query) use ($request) {
					if (!empty($request->job_card_number)) {
						$query->where('job_orders.number', 'LIKE', '%' . $request->job_card_number . '%');
					}
				})
				->where(function ($query) use ($request) {
					if (!empty($request->number)) {
						$query->where('gate_passes.number', 'LIKE', '%' . $request->number . '%');
					}
				})
			//->where('job_cards.outlet_id', Auth::user()->employee->outlet_id)
				->where('gate_passes.type_id', 8280) // Vehicle Gate Pass
				->orderBy('gate_passes.status_id', 'ASC')
				->orderBy('gate_passes.created_at', 'DESC')
				->groupBy('gate_passes.id')
			;

			$total_records = $vehicle_gate_pass_list->get()->count();

			if ($request->offset) {
				$vehicle_gate_pass_list->offset($request->offset);
			}
			if ($request->limit) {
				$vehicle_gate_pass_list->limit($request->limit);
			}

			$vehicle_gate_passes = $vehicle_gate_pass_list->get();

			return response()->json([
				'success' => true,
				'vehicle_gate_passes' => $vehicle_gate_passes,
				'total_records' => $total_records,
			]);
		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Error',
				'errors' => [
					'Error : ' . $e->getMessage() . '. Line : ' . $e->getLine() . '. File : ' . $e->getFile(),
				],
			]);
		}
	}

	public function viewVehicleGatePass(Request $r) {
		// dd($r->all());
		try {
			$view_vehicle_gate_pass = GateLog::
				with([
				'vehicleAttachment',
				'kmAttachment',
				'driverAttachment',
				'chassisAttachment',
				'status',
				'gatePass',
				'gatePass.status',
				'jobOrder',
				'jobOrder.vehicle',
				'jobOrder.vehicle.model',
				'jobOrder.jobCard',
				'jobOrder.jobCard.jobCardReturnableItems',
				'jobOrder.jobCard.jobCardReturnableItems.attachment',
			])
				->find($r->gate_log_id)
			;

			if (!$view_vehicle_gate_pass) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Vehicle Gate Pass Not Found!',
					],
				]);
			}
			$view_vehicle_gate_pass->gate_in_attachment_path = url('storage/app/public/gigo/gate_in/attachments/');
			$view_vehicle_gate_pass->returnable_item_attachment_path = url('storage/app/public/gigo/job_card/returnable_items/');

			// CHANGE FORMAT OF GATE IN DATE AND TIME
			$view_vehicle_gate_pass->gate_in_date_time = date('d/m/Y h:i a', strtotime($view_vehicle_gate_pass->gate_in_date));
			$view_vehicle_gate_pass->covering_letter_pdf = url('storage/app/public/gigo/pdf/' . $view_vehicle_gate_pass->jobOrder->id . '_covering_letter.pdf');
			$view_vehicle_gate_pass->gate_pass_pdf = url('storage/app/public/gigo/pdf/' . $view_vehicle_gate_pass->jobOrder->id . '_gatepass.pdf');

			return response()->json([
				'success' => true,
				'view_vehicle_gate_pass' => $view_vehicle_gate_pass,
				'pdf_link' => url('storage/app/public/gigo/pdf'),
			]);
		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Error',
				'errors' => [
					'Error : ' . $e->getMessage() . '. Line : ' . $e->getLine() . '. File : ' . $e->getFile(),
				],
			]);
		}
	}

	public function saveVehicleGateOutEntry(Request $request) {
		// dd($request->all());
		try {
			$validator = Validator::make($request->all(), [
				'gate_log_id' => [
					'required',
					'integer',
					'exists:gate_logs,id',
				],
				'remarks' => [
					'nullable',
					'string',
					'max:191',
				],
			]);

			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				]);
			}

			DB::beginTransaction();

			$gate_log = GateLog::with([
				'jobOrder',
				'jobOrder.vehicle',
			])->find($request->gate_log_id);

			$gate_log_update = GateLog::where('id', $request->gate_log_id)
				->update([
					'gate_out_date' => Carbon::now(),
					'gate_out_remarks' => $request->remarks ? $request->remarks : NULL,
					'status_id' => 8124, //GATE OUT COMPLETED
					'updated_by_id' => Auth::user()->id,
					'updated_at' => Carbon::now(),
				]);

			if ($gate_log_update) {
				$gate_pass = GatePass::find($gate_log->gate_pass_id);
				if (!$gate_pass) {
					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => [
							'Gate Pass Not Found!',
						],
					]);
				}

				$gate_pass_update = GatePass::where('id', $gate_pass->id)
					->update([
						'status_id' => 8341, //GATE OUT COMPLETED
						'gate_out_date' => Carbon::now(),
						'updated_by_id' => Auth::user()->id,
						'updated_at' => Carbon::now(),
					]);
			}

			Bay::where('job_order_id', $gate_log->job_order_id)
				->update([
					'status_id' => 8240, //Free
					'job_order_id' => NULL, //Free
					'updated_by_id' => Auth::user()->id,
					'updated_at' => Carbon::now(),
				]);

			//Check Survey
			$survey_types = SurveyType::with([
				'surveyField',
			])
				->where('company_id', Auth::user()->company_id)
				->where('survey_trigger_event_id', 11221)
				->get();

			if ($survey_types) {
				foreach ($survey_types as $key => $survey_type) {
					// dd($survey_type);
					if ($survey_type->surveyField) {
						//Driver
						if ($survey_type->attendee_type_id == 11200) {
							if ($gate_log->jobOrder->driver_mobile_number) {

								//Save Surveys
								$survey = Survey::firstOrNew([
									'survey_of_id' => 11260,
									'survey_for_id' => $gate_log->job_order_id,
									'survey_type_id' => $survey_type->id,
									'status_id' => 11240,
									'company_id' => Auth::user()->company_id,
								]);

								if ($survey->exists) {
									$survey->updated_by_id = Auth::user()->id;
									$survey->updated_at = Carbon::now();
								} else {

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
									//GET BRANCH/OUTLET
									$branch = Outlet::where('id', $gate_log->jobOrder->outlet_id)->first();

									//GENERATE GATE IN VEHICLE NUMBER
									$generateNumber = SerialNumberGroup::generateNumber(112);
									if (!$generateNumber['success']) {
										return response()->json([
											'success' => false,
											'error' => 'Validation Error',
											'errors' => [
												'No Floating Gatepass number found for FY : ' . $financial_year->year . ', State : ' . $outlet->code . ', Outlet : ' . $outlet->code,
											],
										]);
									}

									$survey->number = $generateNumber['number'];
									$survey->created_by_id = Auth::user()->id;
									$survey->created_at = Carbon::now();
								}

								$survey->save();

								//Save Surevey Questions
								foreach ($survey_type->surveyField as $keys => $survey_field) {
									$survey_question = SurveyAnswer::firstOrNew([
										'survey_id' => $survey->id,
										'survey_type_field_id' => $survey_field->id,
									]);
									$survey_question->answer = NULL;
									$survey_question->save();
								}

								$url = url('/') . '/feedback/' . $survey->id;

								$short_url = ShortUrl::createShortLink($url, $maxlength = "7");

								$message = 'Greetings from TVS & Sons! Thank you for having your vehicle serviced from TVS & Sons.Kindly click on this link to give Service Feedback: ' . $short_url;

								$msg = sendSMSNotification($gate_log->jobOrder->driver_mobile_number, $message);
							}
						}
						//Cusotmer
						else {
							if ($gate_log->jobOrder->customer) {
								//Save Surveys
								$survey = Survey::firstOrNew([
									'survey_of_id' => 11260,
									'survey_for_id' => $gate_log->job_order_id,
									'survey_type_id' => $survey_type->id,
									'status_id' => 11240,
									'company_id' => Auth::user()->company_id,
								]);

								if ($survey->exists) {
									$survey->updated_by_id = Auth::user()->id;
									$survey->updated_at = Carbon::now();
								} else {

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
									//GET BRANCH/OUTLET
									$branch = Outlet::where('id', $gate_log->jobOrder->outlet_id)->first();

									//GENERATE GATE IN VEHICLE NUMBER
									$generateNumber = SerialNumberGroup::generateNumber(112);
									if (!$generateNumber['success']) {
										return response()->json([
											'success' => false,
											'error' => 'Validation Error',
											'errors' => [
												'No Floating Gatepass number found for FY : ' . $financial_year->year . ', State : ' . $outlet->code . ', Outlet : ' . $outlet->code,
											],
										]);
									}

									$survey->created_by_id = Auth::user()->id;
									$survey->created_at = Carbon::now();
								}

								$survey->number = $generateNumber['number'];
								$survey->save();

								//Save Surevey Questions
								foreach ($survey_type->surveyField as $keys => $survey_field) {
									$survey_question = SurveyAnswer::firstOrNew([
										'survey_id' => $survey->id,
										'survey_type_field_id' => $survey_field->id,
									]);
									$survey_question->answer = NULL;
									$survey_question->save();
								}

								$url = url('/') . '/feedback/' . $survey->id;

								$short_url = ShortUrl::createShortLink($url, $maxlength = "7");

								$message = 'Greetings from TVS & Sons! Thank you for having your vehicle serviced from TVS & Sons.Kindly click on this link to give Service Feedback: ' . $short_url;

								$msg = sendSMSNotification($gate_log->jobOrder->driver_mobile_number, $message);
							}
						}
					}
				}
			}

			DB::commit();

			$gate_out_data['gate_pass_no'] = !empty($gate_pass->number) ? $gate_pass->number : NULL;
			$gate_out_data['registration_number'] = !empty($gate_log->jobOrder->vehicle) ? $gate_log->jobOrder->vehicle->registration_number : NULL;

			if ($gate_out_data['registration_number']) {
				$number = $gate_out_data['registration_number'];
			} else {
				if ($gate_log->jobOrder->vehicle->chassis_number) {
					$number = $gate_log->jobOrder->vehicle->chassis_number;
					$gate_out_data['registration_number'] = $gate_log->jobOrder->vehicle->chassis_number;
				} else {
					$number = $gate_log->jobOrder->vehicle->engine_number;
					$gate_out_data['registration_number'] = $gate_log->jobOrder->vehicle->engine_number;
				}
			}

			$message = 'Greetings from TVS & Sons! Your vehicle ' . $number . ' has successfully Gate out from TVS Service Center - ' . Auth::user()->employee->outlet->ax_name . ' at ' . date('d-m-Y h:i A');

			//Send SMS to Driver
			if ($gate_log->jobOrder->driver_mobile_number) {
				$msg = sendSMSNotification($gate_log->jobOrder->driver_mobile_number, $message);
			}

			//Send SMS to Customer
			if ($gate_log->jobOrder->customer) {
				if ($gate_log->jobOrder->customer->mobile_no) {
					$msg = sendSMSNotification($gate_log->jobOrder->customer->mobile_no, $message);
				}
			}

			return response()->json([
				'success' => true,
				'gate_out_data' => $gate_out_data,
				'message' => 'Vehicle Gate Out successfully!!',
			]);

		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Error',
				'errors' => [
					'Error : ' . $e->getMessage() . '. Line : ' . $e->getLine() . '. File : ' . $e->getFile(),
				],
			]);
		}
	}

}

<?php

namespace Abs\GigoPkg\Api;

use Abs\SerialNumberPkg\SerialNumberGroup;
use App\Attachment;
use App\Bay;
use App\Config;
use App\FinancialYear;
use App\GateLog;
use App\GatePass;
use App\Http\Controllers\Controller;
use App\JobCard;
use App\JobOrder;
use App\Outlet;
use App\ShortUrl;
use App\Survey;
use App\SurveyAnswer;
use App\SurveyType;
use App\Vehicle;
use App\VehicleInventoryItem;
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
				'vehicles.engine_number',
				'vehicles.chassis_number',
				// 'models.model_name',
				DB::raw('IF(models.model_name IS NULL,"-",models.model_name) as model_name'),
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
				->leftJoin('models', 'models.id', 'vehicles.model_id')
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
				->where('gate_passes.company_id', Auth::user()->company_id)
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

			$params = [
				'config_type_id' => 48,
				'add_default' => true,
				'default_text' => "Select Status",
			];

			$extras = [
				'status_list' => Config::getDropDownList($params),
			];

			return response()->json([
				'success' => true,
				'vehicle_gate_passes' => $vehicle_gate_passes,
				'total_records' => $total_records,
				'extras' => $extras,
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

			$inventory_params['field_type_id'] = [11, 12];

			$extras = [
				'inventory_type_list' => VehicleInventoryItem::getInventoryList($view_vehicle_gate_pass->jobOrder->id, $inventory_params, $type = [11300]),
			];

			return response()->json([
				'success' => true,
				'view_vehicle_gate_pass' => $view_vehicle_gate_pass,
				'pdf_link' => url('storage/app/public/gigo/pdf'),
				'extras' => $extras,
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

			//Check Driver & Security Signature
			if ($request->web == 'website') {
				// $validator = Validator::make($request->all(), [
				// 	'driver_signature' => [
				// 		'required',
				// 	],
				// 	'security_signature' => [
				// 		'required',
				// 	],
				// ]);
			} else {
				// $validator = Validator::make($request->all(), [
				// 	'security_signature' => [
				// 		'required',
				// 		'mimes:jpeg,jpg',
				// 	],
				// 	'driver_signature' => [
				// 		'required',
				// 		'mimes:jpeg,jpg',
				// 	],
				// ]);
			}
			// if ($validator->fails()) {
			// 	return response()->json([
			// 		'success' => false,
			// 		'error' => 'Validation Error',
			// 		'errors' => $validator->errors()->all(),
			// 	]);
			// }

			DB::beginTransaction();

			$gate_log = GateLog::with([
				'jobOrder',
				'jobOrder.vehicle',
			])->find($request->gate_log_id);

			$job_order = JobOrder::find($gate_log->jobOrder->id);

			if ($job_order) {
				$job_order->vehicle_delivery_status_id = 3;
				if($job_order->status_id == 8478){
					$job_order->status_id = 8467; // Waiting for Payment
				}
				$job_order->save();

				$inventories = DB::table('job_order_vehicle_inventory_item')->where('gate_log_id', $gate_log->id)->where('entry_type_id', 11301)->delete();

				if ($request->vehicle_inventory_items) {
					foreach ($request->vehicle_inventory_items as $key => $vehicle_inventory_item) {
						if (isset($vehicle_inventory_item['inventory_item_id']) && $vehicle_inventory_item['is_available'] == 1) {
							$job_order->vehicleInventoryItem()
								->attach(
									$vehicle_inventory_item['inventory_item_id'],
									[
										'is_available' => 1,
										'remarks' => $vehicle_inventory_item['remarks'],
										'gate_log_id' => $gate_log->id,
										'entry_type_id' => 11301,
									]
								);
						}
					}
				}
			}

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

									//GENERATE SURVEY NUMBER
									$generateNumber = SerialNumberGroup::generateNumber(112);
									if (!$generateNumber['success']) {
										return response()->json([
											'success' => false,
											'error' => 'Validation Error',
											'errors' => [
												'No Survey Number found for FY : ' . $financial_year->year,
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

								if ($gate_log->jobOrder->driver_mobile_number) {
									$msg = sendSMSNotification($gate_log->jobOrder->driver_mobile_number, $message);
								}
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

									//GENERATE SURVEY NUMBER
									$generateNumber = SerialNumberGroup::generateNumber(112);
									if (!$generateNumber['success']) {
										return response()->json([
											'success' => false,
											'error' => 'Validation Error',
											'errors' => [
												'No Survey Number found for FY : ' . $financial_year->year,
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

								$contact_number = $gate_log->jobOrder->contact_number ? $gate_log->jobOrder->contact_number : $gate_log->jobOrder->customer->mobile_no;
								$msg = sendSMSNotification($contact_number, $message);
							}
						}
					}
				}
			}

			//Save Driver & Security Signature
			if ($request->web == 'website') {
				//DRIVER E SIGN
				if (!empty($request->driver_signature)) {
					$remove_previous_attachment = Attachment::where([
						'entity_id' => $job_order->id,
						'attachment_of_id' => 227,
						'attachment_type_id' => 10098,
					])->forceDelete();

					$driver_sign = str_replace('data:image/png;base64,', '', $request->driver_signature);
					$driver_sign = str_replace(' ', '+', $driver_sign);

					$user_images_des = storage_path('app/public/gigo/job_order/attachments/');
					File::makeDirectory($user_images_des, $mode = 0777, true, true);

					$filename = $job_order->id . "webcam_gate_in_driver_sign_" . strtotime("now") . ".png";

					File::put($attachment_path . $filename, base64_decode($driver_sign));

					//SAVE ATTACHMENT
					$attachment = new Attachment;
					$attachment->attachment_of_id = 227; //JOB ORDER
					$attachment->attachment_type_id = 10098; //GateIn Driver Signature
					$attachment->entity_id = $job_order->id;
					$attachment->name = $filename;
					$attachment->created_by = Auth()->user()->id;
					$attachment->created_at = Carbon::now();
					$attachment->save();
				}

				//SECURITY E SIGN
				if (!empty($request->security_signature)) {
					$remove_previous_attachment = Attachment::where([
						'entity_id' => $job_order->id,
						'attachment_of_id' => 227,
						'attachment_type_id' => 10098,
					])->forceDelete();

					$security_sign = str_replace('data:image/png;base64,', '', $request->security_signature);
					$security_sign = str_replace(' ', '+', $security_sign);

					$user_images_des = storage_path('app/public/gigo/job_order/attachments/');
					File::makeDirectory($user_images_des, $mode = 0777, true, true);

					$filename = $job_order->id . "webcam_gate_in_security_sign_" . strtotime("now") . ".png";

					File::put($attachment_path . $filename, base64_decode($security_sign));

					//SAVE ATTACHMENT
					$attachment = new Attachment;
					$attachment->attachment_of_id = 227; //JOB ORDER
					$attachment->attachment_type_id = 10099; //GateIn Security Signature
					$attachment->entity_id = $job_order->id;
					$attachment->name = $filename;
					$attachment->created_by = Auth()->user()->id;
					$attachment->created_at = Carbon::now();
					$attachment->save();
				}

			} else {
				if (!empty($request->driver_signature)) {
					$remove_previous_attachment = Attachment::where([
						'entity_id' => $job_order->id,
						'attachment_of_id' => 227,
						'attachment_type_id' => 10100,
					])->forceDelete();

					$image = $request->driver_signature;
					$time_stamp = date('Y_m_d_h_i_s');
					$extension = $image->getClientOriginalExtension();
					$name = $job_order->id . '_' . $time_stamp . '_gateout_driver_signature.' . $extension;
					$image->move(storage_path('app/public/gigo/job_order/attachments/'), $name);

					//SAVE ATTACHMENT
					$attachment = new Attachment;
					$attachment->attachment_of_id = 227; //JOB ORDER
					$attachment->attachment_type_id = 10100; //GateOut Driver Signature
					$attachment->entity_id = $job_order->id;
					$attachment->name = $name;
					$attachment->created_by = Auth()->user()->id;
					$attachment->created_at = Carbon::now();
					$attachment->save();
				}
				if (!empty($request->security_signature)) {
					$remove_previous_attachment = Attachment::where([
						'entity_id' => $job_order->id,
						'attachment_of_id' => 227,
						'attachment_type_id' => 10101,
					])->forceDelete();

					$image = $request->security_signature;
					$time_stamp = date('Y_m_d_h_i_s');
					$extension = $image->getClientOriginalExtension();
					$name = $job_order->id . '_' . $time_stamp . '_gateout_security_signature.' . $extension;
					$image->move(storage_path('app/public/gigo/job_order/attachments/'), $name);

					//SAVE ATTACHMENT
					$attachment = new Attachment;
					$attachment->attachment_of_id = 227; //JOB ORDER
					$attachment->attachment_type_id = 10101; //GateOut Security Signature
					$attachment->entity_id = $job_order->id;
					$attachment->name = $name;
					$attachment->created_by = Auth()->user()->id;
					$attachment->created_at = Carbon::now();
					$attachment->save();
				}
			}

			DB::commit();

			//Generate Inventory PDF
			$generate_inventory_pdf = JobOrder::generateInventoryPDF($job_order->id, $type = 'GateOut');

			//Generate GatePass PDF
			if ($gate_pass->job_card_id) {
				$generate_estimate_pdf = JobCard::generateGatePassPDF($gate_pass->job_card_id, $type = 'GateOut');
			} else {
				$generate_estimate_gatepass_pdf = JobOrder::generateEstimateGatePassPDF($job_order->id, $type = 'GateOut');
			}

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

<?php

namespace Abs\GigoPkg\Api;

use Abs\BasicPkg\Traits\CrudTrait;
use Abs\GigoPkg\Bay;
use Abs\GigoPkg\GatePass;
use Abs\GigoPkg\GatePassDetail;
use Abs\GigoPkg\GatePassItem;
use Abs\GigoPkg\JobCard;
use Abs\GigoPkg\JobCardReturnableItem;
use Abs\GigoPkg\JobOrder;
use Abs\GigoPkg\JobOrderIssuedPart;
use Abs\GigoPkg\JobOrderRepairOrder;
use Abs\GigoPkg\MechanicTimeLog;
use Abs\GigoPkg\RepairOrder;
use Abs\GigoPkg\RepairOrderMechanic;
use Abs\GigoPkg\ShortUrl;
use Abs\PartPkg\Part;
use Abs\SerialNumberPkg\SerialNumberGroup;
use Abs\TaxPkg\Tax;
use App\Attachment;
use App\Config;
use App\Customer;
use App\Employee;
use App\FinancialYear;
use App\Http\Controllers\Controller;
use App\Invoice;
use App\JobOrderPart;
use App\Outlet;
use App\SplitOrderType;
use App\VehicleInspectionItem;
use App\VehicleInspectionItemGroup;
use App\VehicleInventoryItem;
use App\Vendor;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Storage;
use Validator;

class JobCardController extends Controller {
	use CrudTrait;
	public $model = JobCard::class;
	public $successStatus = 200;

	public function getJobCardList(Request $request) {
		try {
			$validator = Validator::make($request->all(), [
				'floor_supervisor_id' => [
					'required',
					'exists:users,id',
					'integer',
				],
			]);
			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				]);
			}

			$job_card_list = JobCard::select([
				'job_cards.id as job_card_id',
				'job_cards.job_card_number',
				'job_cards.bay_id',
				'job_orders.id as job_order_id',
				'job_cards.created_at',
				'vehicles.registration_number',
				'models.model_name as vehicle_model',
				'customers.name as customer_name',
				'status.name as status',
				'service_types.name as service_type',
				'quote_types.name as quote_type',
				'service_order_types.name as job_order_type',
				'gate_passes.id as gate_pass_id',

			])
				->join('job_orders', 'job_orders.id', 'job_cards.job_order_id')
				->leftJoin('gate_passes', 'gate_passes.job_card_id', 'job_cards.id')
				->leftJoin('vehicles', 'job_orders.vehicle_id', 'vehicles.id')
				->leftJoin('models', 'models.id', 'vehicles.model_id')
				->leftJoin('vehicle_owners', function ($join) {
					$join->on('vehicle_owners.vehicle_id', 'job_orders.vehicle_id')
						->whereRaw('vehicle_owners.from_date = (select MAX(vehicle_owners1.from_date) from vehicle_owners as vehicle_owners1 where vehicle_owners1.vehicle_id = job_orders.vehicle_id)');
				})
				->leftJoin('customers', 'vehicle_owners.customer_id', 'customers.id')
				->leftJoin('configs as status', 'status.id', 'job_cards.status_id')
				->leftJoin('service_types', 'service_types.id', 'job_orders.service_type_id')
				->leftJoin('quote_types', 'quote_types.id', 'job_orders.quote_type_id')
				->leftJoin('service_order_types', 'service_order_types.id', 'job_orders.type_id')
				->where(function ($query) use ($request) {
					if (!empty($request->search_key)) {
						$query->where('vehicles.registration_number', 'LIKE', '%' . $request->search_key . '%')
							->orWhere('customers.name', 'LIKE', '%' . $request->search_key . '%')
							->orWhere('models.model_number', 'LIKE', '%' . $request->search_key . '%')
							->orWhere('job_cards.job_card_number', 'LIKE', '%' . $request->search_key . '%')
							->orWhere('status.name', 'LIKE', '%' . $request->search_key . '%')
						;
					}
				})
				->where(function ($query) use ($request) {
					if (!empty($request->date)) {
						$query->whereDate('job_cards.created_at', date('Y-m-d', strtotime($request->date)));
					}
				})
				->where(function ($query) use ($request) {
					if (!empty($request->reg_no)) {
						$query->where('vehicles.registration_number', 'LIKE', '%' . $request->reg_no . '%');
					}
				})
				->where(function ($query) use ($request) {
					if (!empty($request->job_card_no)) {
						$query->where('job_cards.job_card_number', 'LIKE', '%' . $request->job_card_no . '%');
					}
				})
				->where(function ($query) use ($request) {
					if (!empty($request->customer_id)) {
						$query->where('vehicle_owners.customer_id', $request->customer_id);
					}
				})
				->where(function ($query) use ($request) {
					if (!empty($request->model_id)) {
						$query->where('vehicles.model_id', $request->model_id);
					}
				})
				->where(function ($query) use ($request) {
					if (!empty($request->status_id)) {
						$query->where('job_cards.status_id', $request->status_id);
					}
				})
				->where(function ($query) use ($request) {
					if (!empty($request->quote_type_id)) {
						$query->where('job_orders.quote_type_id', $request->quote_type_id);
					}
				})
				->where(function ($query) use ($request) {
					if (!empty($request->service_type_id)) {
						$query->where('job_orders.service_type_id', $request->service_type_id);
					}
				})
				->where(function ($query) use ($request) {
					if (!empty($request->job_order_type_id)) {
						$query->where('job_orders.type_id', $request->job_order_type_id);
					}
				});

			if (!Entrust::can('view-overall-outlets-job-card')) {
				if (Entrust::can('view-mapped-outlet-job-card')) {

					$job_card_list->whereIn('job_cards.outlet_id', Auth::user()->employee->outlets->pluck('id')->toArray());
				} else if (Entrust::can('view-own-outlet-job-card')) {
					$job_card_list->where('job_cards.outlet_id', Auth::user()->employee->outlet_id)->whereRaw("IF (job_cards.`status_id` = '8220', job_cards.`floor_supervisor_id` IS  NULL, job_cards.`floor_supervisor_id` = '" . $request->floor_supervisor_id . "')");
				} else {
					$job_card_list->where('job_cards.floor_supervisor_id', Auth::user()->id);
				}

			} else {
				$job_card_list->whereRaw("IF (job_cards.`status_id` = '8220', job_cards.`floor_supervisor_id` IS  NULL, job_cards.`floor_supervisor_id` = '" . $request->floor_supervisor_id . "')");
			}

			$job_card_list->groupBy('job_cards.id')
				->orderBy('job_cards.created_at', 'DESC');

			$total_records = $job_card_list->get()->count();

			if ($request->offset) {
				$job_card_list->offset($request->offset);
			}
			if ($request->limit) {
				$job_card_list->limit($request->limit);
			}

			$job_cards = $job_card_list->get();

			return response()->json([
				'success' => true,
				'job_card_list' => $job_cards,
				'total_records' => $total_records,
			]);
		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => [
					'Error : ' . $e->getMessage() . '. Line : ' . $e->getLine() . '. File : ' . $e->getFile(),
				],
			]);
		}
	}

	public function getUpdateJcFormData(Request $r) {
		try {
			$job_order = JobOrder::with([
				'status',
				'gateLog',
				'gateLog.status',
				'vehicle',
				'vehicle.model',
				'jobCard',
				'jobCard.attachment',
			])
				->find($r->id);
			if (!$job_order) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => ['Job Order Not Found!'],
				]);
			}

			$job_order->attachment_path = 'storage/app/public/gigo/job_card/attachments';

			return response()->json([
				'success' => true,
				'job_order' => $job_order,
			]);
		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	public function saveJobCard(Request $request) {
		try {
			$validator = Validator::make($request->all(), [
				'job_order_id' => [
					'required',
					'exists:job_orders,id',
					'integer',
				],
				'job_card_number' => [
					'required',
					'min:10',
					'integer',
				],
				'job_card_photo' => [
					'required_if:saved_attachment,0',
					'mimes:jpeg,jpg,png',
				],
				'job_card_date' => [
					'required',
					'date_format:"d-m-Y',
				],
			]);

			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				]);
			}

			$job_order = JobOrder::with([
				'jobCard',
				'gateLog',
				'jobOrderRepairOrders',
				'jobOrderParts',
			])
				->find($request->job_order_id);

			DB::beginTransaction();

			//JOB Card SAVE
			$job_card = JobCard::firstOrNew([
				'job_order_id' => $request->job_order_id,
			]);
			$job_card->job_card_number = $request->job_card_number;
			$job_card->date = date('Y-m-d', strtotime($request->job_card_date));
			$job_card->outlet_id = $job_order->outlet_id;
			$job_card->status_id = 8220; //Floor Supervisor not Assigned
			$job_card->company_id = Auth::user()->company_id;
			$job_card->created_by = Auth::user()->id;
			$job_card->save();

			//Update Job Order status
			JobOrder::where('id', $request->job_order_id)->update(['status_id' => 8470, 'updated_by_id' => Auth::user()->id, 'updated_at' => Carbon::now()]);

			//CREATE DIRECTORY TO STORAGE PATH
			$attachment_path = storage_path('app/public/gigo/job_card/attachments/');
			Storage::makeDirectory($attachment_path, 0777);

			//SAVE Job Card ATTACHMENT
			if (!empty($request->job_card_photo)) {
				$attachment = $request->job_card_photo;
				$entity_id = $job_card->id;
				$attachment_of_id = 228; //Job Card
				$attachment_type_id = 255; //Jobcard Photo
				saveAttachment($attachment_path, $attachment, $entity_id, $attachment_of_id, $attachment_type_id);
			}

			//UPDATE JOB ORDER REPAIR ORDER STATUS
			JobOrderRepairOrder::where('job_order_id', $request->job_order_id)->update(['status_id' => 8181, 'updated_by_id' => Auth::user()->id, 'updated_at' => Carbon::now()]);

			//UPDATE JOB ORDER PARTS STATUS
			JobOrderPart::where('job_order_id', $request->job_order_id)->update(['status_id' => 8201, 'updated_by_id' => Auth::user()->id, 'updated_at' => Carbon::now()]);

			DB::commit();

			return response()->json([
				'success' => true,
				'message' => 'Job Card Updated successfully!!',
			]);

		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => [
					'Error : ' . $e->getMessage() . '. Line : ' . $e->getLine() . '. File : ' . $e->getFile(),
				],
			]);
		}
	}

	//BAY ASSIGNMENT
	public function getBayFormData(Request $r) {
		try {
			$job_card = JobCard::with([
				'bay',
				'jobOrder',
				'jobOrder.vehicle',
				'jobOrder.vehicle.model',
				'status',
			])
				->find($r->id);
			if (!$job_card) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => ['Job Card Not Found!'],
				]);
			}

			$bay_list = Bay::with([
				'status',
				'jobOrder',
			])
				->where('outlet_id', $job_card->outlet_id)
				->get();
			foreach ($bay_list as $key => $bay) {
				if ($bay->status_id == 8241 && $bay->id == $job_card->bay_id) {
					$bay->selected = true;
				} else {
					$bay->selected = false;
				}
			}

			$extras = [
				'bay_list' => $bay_list,
			];

			return response()->json([
				'success' => true,
				'job_card' => $job_card,
				'extras' => $extras,
			]);
		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	public function saveBay(Request $request) {
		// dd($request->all());
		try {

			$validator = Validator::make($request->all(), [
				'job_card_id' => [
					'required',
					'integer',
					'exists:job_cards,id',
				],
				'bay_id' => [
					'required',
					'integer',
					'exists:bays,id',
				],
				'floor_supervisor_id' => [
					'required',
					'integer',
					'exists:users,id',
				],
			]);

			if ($validator->fails()) {
				$errors = $validator->errors()->all();
				$success = false;
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				]);
			}
			DB::beginTransaction();

			$job_card = JobCard::find($request->job_card_id);
			if (!$job_card->jobOrder) {
				return response()->json([
					'success' => false,
					'error' => 'Job Order Not Found!',
				]);
			}
			$job_card->floor_supervisor_id = $request->floor_supervisor_id;
			if ($job_card->bay_id) {

				//Exists bay checking and Bay status update
				if ($job_card->bay_id != $request->bay_id) {
					$bay = Bay::find($job_card->bay_id);
					$bay->status_id = 8240; //Free
					$bay->job_order_id = NULL;
					$bay->updated_by_id = Auth::user()->id;
					$bay->updated_at = Carbon::now();
					$bay->save();
				}
			}
			$job_card->bay_id = $request->bay_id;
			if ($job_card->status_id == 8220) {
				$job_card->status_id = 8221; //Work In Progress
			}
			$job_card->updated_by = Auth::user()->id;
			$job_card->updated_at = Carbon::now();
			$job_card->save();

			$bay = Bay::find($request->bay_id);
			$bay->job_order_id = $job_card->job_order_id;
			$bay->status_id = 8241; //Assigned
			$bay->updated_by_id = Auth::user()->id;
			$bay->updated_at = Carbon::now();
			$bay->save();

			$job_order = JobOrder::where('id', $job_card->job_order_id)->first();
			$job_order->floor_supervisor_id = $request->floor_supervisor_id;
			$job_order->save();

			DB::commit();

			return response()->json([
				'success' => true,
				'message' => 'Bay assignment saved successfully!!',
			]);

		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	//BAY VIEW
	public function getBayViewData(Request $r) {
		//dd($r->all());
		try {
			$job_card = JobCard::with([
				'jobOrder',
				'jobOrder.vehicle',
				'jobOrder.vehicle.model',
				'bay',
				'status',
			])->find($r->id);

			if (!$job_card) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => ['Job Card Not Found!'],
				]);
			}
			return response()->json([
				'success' => true,
				'job_card' => $job_card,
			]);
		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	//SCHEDULE
	public function LabourAssignmentFormData(Request $r) {
		// dd($r->all());
		try {
			//JOB CARD
			$job_card = JobCard::with([
				'status',
				'jobOrder',
				'jobOrder.vehicle',
				'jobOrder.vehicle.model',
				'jobOrder.jobOrderRepairOrders',
				'jobOrder.jobOrderRepairOrders.status',
				'jobOrder.jobOrderRepairOrders.repairOrder',
				'jobOrder.jobOrderRepairOrders.repairOrderMechanics',
				'jobOrder.jobOrderRepairOrders.repairOrderMechanics.mechanic',
				'jobOrder.jobOrderRepairOrders.repairOrderMechanics.status',
			])->find($r->id);

			if (!$job_card) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Job Card Not Found!',
					],
				]);
			}

			//FOR TOTAL WORKING TIME PERTICULAR EMPLOYEE
			$total_duration = 0;
			if (!empty($job_card->jobOrder->jobOrderRepairOrders)) {
				foreach ($job_card->jobOrder->jobOrderRepairOrders as $key => $job_order_repair_order) {
					$overall_total_duration = [];
					if ($job_order_repair_order->repairOrderMechanics) {
						foreach ($job_order_repair_order->repairOrderMechanics as $key1 => $repair_order_mechanic) {
							$duration = [];
							if ($repair_order_mechanic->mechanicTimeLogs) {
								// $duration_difference = []; //FOR START TIME AND END TIME DIFFERENCE
								foreach ($repair_order_mechanic->mechanicTimeLogs as $key2 => $mechanic_time_log) {
									// dd($mechanic_time_log);
									if ($mechanic_time_log->end_date_time) {
										$time1 = strtotime($mechanic_time_log->start_date_time);
										$time2 = strtotime($mechanic_time_log->end_date_time);
										if ($time2 < $time1) {
											$time2 += 86400;
										}
										//TOTAL DURATION FOR PARTICLUAR EMPLOEE
										$duration[] = date("H:i:s", strtotime("00:00") + ($time2 - $time1));

										//OVERALL TOTAL WORKING DURATION
										$overall_total_duration[] = date("H:i:s", strtotime("00:00") + ($time2 - $time1));
									} else {
										$duration[] = '-';
										$overall_total_duration[] = '-';
									}
								}
							}
							//TOTAL WORKING HOURS PER EMPLOYEE
							$total_duration = sum_mechanic_duration($duration);
							$total_duration = date("H:i:s", strtotime($total_duration));
							// dd($total_duration);
							$format_change = explode(':', $total_duration);
							$hour = $format_change[0] . 'h';
							$minutes = $format_change[1] . 'm';
							// $seconds = $format_change[2] . 's';
							$repair_order_mechanic['total_duration'] = $hour . ' ' . $minutes; // . ' ' . $seconds;
							unset($duration);
						}

					} else {
						$repair_order_mechanic['total_duration'] = '';
					}
					//OVERALL WORKING HOURS
					$overall_total_duration = sum_mechanic_duration($overall_total_duration);
					// $overall_total_duration = date("H:i:s", strtotime($overall_total_duration));
					// dd($total_duration);
					$format_change = explode(':', $overall_total_duration);
					$hour = $format_change[0] . 'h';
					$minutes = $format_change[1] . 'm';
					// $seconds = $format_change[2] . 's';
					$job_order_repair_order['overall_total_duration'] = $hour . ' ' . $minutes; // . ' ' . $seconds;
					unset($overall_total_duration);
				}
			}

			return response()->json([
				'success' => true,
				'job_card_view' => $job_card,
			]);

		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	public function getMechanicTimeLog(Request $request) {
		// dd($request->all());
		try {
			//REPAIR ORDER
			$this->data['repair_order'] = $repair_order = RepairOrder::with([

			])->find($request->repair_order_id);

			if (!$repair_order) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Repair Order Not Found!',
					],
				]);
			}

			$this->data['repair_order_mechanic_time_logs'] = $repair_order_mechanic_time_logs = MechanicTimeLog::with([
				'status',
				'reason',
				'repairOrderMechanic',
				'repairOrderMechanic.mechanic',
			])
				->where('repair_order_mechanic_id', $request->repair_order_mechanic_id)
				->get();

			if (!$repair_order_mechanic_time_logs) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Repair Order Mechanic Not Found!',
					],
				]);
			}

			$total_duration = 0;
			if ($repair_order_mechanic_time_logs) {
				$duration = [];
				foreach ($repair_order_mechanic_time_logs as $key => $repair_order_mechanic_time_log) {
					// dd($repair_order_mechanic_time_log);
					//PERTICULAR MECHANIC DATE
					$repair_order_mechanic_time_log->date = date('d/m/Y', strtotime($repair_order_mechanic_time_log->start_date_time));

					//PERTICULAR MECHANIC STATR TIME
					$repair_order_mechanic_time_log->start_time = date('h:i a', strtotime($repair_order_mechanic_time_log->start_date_time));

					//PERTICULAR MECHANIC END TIME
					$repair_order_mechanic_time_log->end_time = !empty($repair_order_mechanic_time_log->end_date_time) ? date('h:i a', strtotime($repair_order_mechanic_time_log->end_date_time)) : '-';

					if ($repair_order_mechanic_time_log->end_date_time) {
						$time1 = strtotime($repair_order_mechanic_time_log->start_date_time);
						$time2 = strtotime($repair_order_mechanic_time_log->end_date_time);
						if ($time2 < $time1) {
							$time2 += 86400;
						}

						//TIME DURATION DIFFERENCE PERTICULAR MECHANIC DURATION
						$duration_difference[] = date("H:i:s", strtotime("00:00") + ($time2 - $time1));

						//TOTAL DURATION FOR PARTICLUAR EMPLOEE
						$duration[] = date("H:i:s", strtotime("00:00") + ($time2 - $time1));

						//OVERALL TOTAL WORKING DURATION
						$overall_total_duration[] = date("H:i:s", strtotime("00:00") + ($time2 - $time1));

						$repair_order_mechanic_time_log->duration_difference = sum_mechanic_duration($duration_difference);
						unset($duration_difference);
					} else {
						$duration[] = '-';
						$overall_total_duration[] = '-';
					}
				}

				// TOTAL WORKING HOURS PER EMPLOYEE
				$total_duration = sum_mechanic_duration($duration);
				$total_duration = date("H:i:s", strtotime($total_duration));
				// dd($total_duration);
				$format_change = explode(':', $total_duration);
				$hour = $format_change[0] . 'h';
				$minutes = $format_change[1] . 'm';
				// $seconds = $format_change[2] . 's';
				$this->data['total_duration'] = $hour . ' ' . $minutes; //. ' ' . $seconds;
				unset($duration);
			}
			return response()->json([
				'success' => true,
				// 'repair_order' => $repair_order,
				// 'repair_order_mechanic_time_logs' => $repair_order_mechanic_time_logs,
				'data' => $this->data,
			]);

		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	public function getMechanic(Request $request) {
		// dd($request->all());
		try {
			//JOB CARD
			$job_card = JobCard::find($request->id);

			if (!$job_card) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Job Card Not Found!',
					],
				]);
			}

			//REPAIR ORDER
			$repair_order = RepairOrder::find($request->repair_order_id);

			if (!$repair_order) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Repair Order Not Found!',
					],
				]);
			}

			//REPAIR ORDER MECHNICS
			$repair_order_mechanics = RepairOrderMechanic::join('job_order_repair_orders', 'job_order_repair_orders.id', 'repair_order_mechanics.job_order_repair_order_id')->where('job_order_repair_orders.repair_order_id', $request->repair_order_id)->where('job_order_repair_orders.job_order_id', $job_card->job_order_id)->pluck('repair_order_mechanics.mechanic_id')->toArray();

			$employee_details = Employee::select([
				'users.id',
				DB::RAW('CONCAT(users.ecode, " / ",users.name) as user_name'),
				'users.ecode as user_code',
				'outlets.code as outlet_code',
				'deputed_outlet.code as deputed_outlet_code',
				'attendance_logs.user_id',
			])
				->join('users', 'users.entity_id', 'employees.id')
				->leftJoin('attendance_logs', function ($join) {
					$join->on('attendance_logs.user_id', 'users.id')
						->whereNull('attendance_logs.out_time')
						->whereDate('attendance_logs.date', '=', date('Y-m-d', strtotime("now")));
				})
				->join('outlets', 'outlets.id', 'employees.outlet_id')
				->leftjoin('outlets as deputed_outlet', 'deputed_outlet.id', 'employees.deputed_outlet_id')
				->where('employees.is_mechanic', 1)
				->where('users.user_type_id', 1) //EMPLOYEE
			// ->where('employees.skill_level_id', $repair_order->skill_level_id)
				->where('employees.outlet_id', $job_card->outlet_id)
				->orWhere('employees.deputed_outlet_id', $job_card->outlet_id)
				->orderBy('users.name', 'asc')
				->get()
			;

			return response()->json([
				'success' => true,
				'repair_order' => $repair_order,
				'repair_order_mechanics' => $repair_order_mechanics,
				'employee_details' => $employee_details,
			]);

		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	public function saveMechanic(Request $request) {
		// dd($request->all());
		try {
			$validator = Validator::make($request->all(), [
				'job_card_id' => [
					'required',
					'integer',
					'exists:job_cards,id',
				],
				'repair_order_id' => [
					'required',
					'integer',
					'exists:repair_orders,id',
				],
				'selected_mechanic_ids' => [
					'required',
					'string',
				],
			]);

			if ($validator->fails()) {
				$errors = $validator->errors()->all();
				$success = false;
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				]);
			}

			$mechanic_ids = explode(',', $request->selected_mechanic_ids);
			// dd($mechanic_ids);
			DB::beginTransaction();
			$job_card = JobCard::with([
				'jobOrder',
				'jobOrder.jobOrderRepairOrders',
				'jobOrder.jobOrderRepairOrders.repairOrder',
			])
				->find($request->job_card_id);

			if (count($mechanic_ids) > 0) {
				foreach ($job_card->jobOrder->jobOrderRepairOrders as $JobOrderRepairOrder) {
					if ($JobOrderRepairOrder->repair_order_id == $request->repair_order_id) {
						$repair_order_mechanic_remove = RepairOrderMechanic::where('job_order_repair_order_id', $JobOrderRepairOrder->id)->whereNotIn('mechanic_id', $mechanic_ids)->forceDelete();

						foreach ($mechanic_ids as $mechanic_id) {
							$repair_order_mechanic = RepairOrderMechanic::firstOrNew([
								'job_order_repair_order_id' => $JobOrderRepairOrder->id,
								'mechanic_id' => $mechanic_id,
							]);
							// dd($repair_order_mechanic);
							if ($repair_order_mechanic->exists) {
								$repair_order_mechanic->updated_by_id = Auth::user()->id;
								$repair_order_mechanic->updated_at = Carbon::now();
							} else {
								$repair_order_mechanic->created_by_id = Auth::user()->id;
								$repair_order_mechanic->created_at = Carbon::now();
							}
							$repair_order_mechanic->fill($request->all());
							if (!$repair_order_mechanic->exists) {
								$repair_order_mechanic->status_id = 8260; //PENDING
							}
							$repair_order_mechanic->save();

							$job_order_repair_order = JobOrderRepairOrder::where('id', $JobOrderRepairOrder->id)
								->update([
									'status_id' => 8182, //WORK PENDING
									'updated_by_id' => Auth::user()->id,
									'updated_at' => Carbon::now(),
								])
							;
						}
					} else {
						continue;
					}
				}
			}

			DB::commit();

			return response()->json([
				'success' => true,
				'message' => 'Mechanic assigned successfully!!',
			]);

		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	public function LabourAssignmentFormSave(Request $request) {
		try {
			$items_validator = Validator::make($request->labour_details[0], [
				'job_order_repair_order_id' => [
					'required',
					'integer',
				],
			]);

			if ($items_validator->fails()) {
				return response()->json(['success' => false, 'errors' => $items_validator->errors()->all()]);
			}

			DB::beginTransaction();

			foreach ($request->labour_details as $key => $repair_orders) {
				$job_order_repair_order = JobOrderRepairOrder::find($repair_orders['job_order_repair_order_id']);
				if (!$job_order_repair_order) {
					return response()->json([
						'success' => false,
						'error' => 'Job order Repair Order Not found!',
					]);
				}
				foreach ($repair_orders as $key => $mechanic) {
					if (is_array($mechanic)) {
						$repair_order_mechanic = RepairOrderMechanic::firstOrNew([
							'job_order_repair_order_id' => $repair_orders['job_order_repair_order_id'],
							'mechanic_id' => $mechanic['mechanic_id'],
						]);
						$repair_order_mechanic->job_order_repair_order_id = $repair_orders['job_order_repair_order_id'];
						$repair_order_mechanic->mechanic_id = $mechanic['mechanic_id'];
						$repair_order_mechanic->status_id = 8060;
						$repair_order_mechanic->created_by_id = Auth::user()->id;
						if ($repair_order_mechanic->exists) {
							$repair_order_mechanic->updated_by_id = Auth::user()->id;
						}
						$repair_order_mechanic->save();
					}
				}

			}
			DB::commit();
			return response()->json([
				'success' => true,
				'message' => 'Repair Order Mechanic added successfully!!',
			]);

		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	public function getLabourReviewData(Request $request) {
		// dd($request->all());
		try {
			$labour_review_data = JobCard::with([
				'status',
				'jobOrder',
				'jobOrder.vehicle',
				'jobOrder.vehicle.model',
				// 'jobOrder.jobOrderRepairOrders',
				'jobOrder.jobOrderRepairOrders' => function ($q) use ($request) {
					$q->where('id', $request->job_order_repair_order_id);
				},
				'jobOrder.jobOrderRepairOrders.status',
				'jobOrder.jobOrderRepairOrders.repairOrderMechanics',
				'jobOrder.jobOrderRepairOrders.repairOrderMechanics.mechanic',
				'jobOrder.jobOrderRepairOrders.repairOrderMechanics.status',
				'jobOrder.jobOrderRepairOrders.repairOrderMechanics.mechanicTimeLogs',
				'jobOrder.jobOrderRepairOrders.repairOrderMechanics.mechanicTimeLogs.status',
			])
				->find($request->id);

			if (!$labour_review_data) {
				return response()->json([
					'success' => false,
					'error' => 'Job Card Not Found!',
				]);
			}

			//REPAIR ORDER
			$job_order_repair_order = JobOrderRepairOrder::with([
				'repairOrder',
				'repairOrderMechanics',
				'repairOrderMechanics.mechanic',
				'repairOrderMechanics.status',
				'repairOrderMechanics.mechanicTimeLogs',
				'repairOrderMechanics.mechanicTimeLogs.status',
				'repairOrderMechanics.mechanicTimeLogs.reason',
			])
				->find($request->job_order_repair_order_id);

			if (!$job_order_repair_order) {
				return response()->json([
					'success' => false,
					'error' => 'Job Order Repair Order Not found!',
				]);
			}

			$job_card_repair_order_details = $labour_review_data->jobOrder->jobOrderRepairOrders;
			//dd($job_card_repair_order_details);

			$total_duration = 0;
			$overall_total_duration = [];
			if (!empty($job_order_repair_order->repairOrderMechanics)) {
				foreach ($job_order_repair_order->repairOrderMechanics as $repair_order_mechanic) {
					$duration = [];
					if ($repair_order_mechanic->mechanicTimeLogs) {
						$duration_difference = []; //FOR START TIME AND END TIME DIFFERENCE
						foreach ($repair_order_mechanic->mechanicTimeLogs as $key2 => $mechanic_time_log) {
							// PERTICULAR MECHANIC DATE
							$mechanic_time_log->date = date('d/m/Y', strtotime($mechanic_time_log->start_date_time));

							//PERTICULAR MECHANIC STATR TIME
							$mechanic_time_log->start_time = date('h:i a', strtotime($mechanic_time_log->start_date_time));

							//PERTICULAR MECHANIC END TIME
							$mechanic_time_log->end_time = $mechanic_time_log->end_date_time ? date('h:i a', strtotime($mechanic_time_log->end_date_time)) : '-';

							if ($mechanic_time_log->end_date_time) {
								// dump('if');
								$time1 = strtotime($mechanic_time_log->start_date_time);
								$time2 = strtotime($mechanic_time_log->end_date_time);
								if ($time2 < $time1) {
									$time2 += 86400;
								}

								//TIME DURATION DIFFERENCE PERTICULAR MECHANIC DURATION
								$duration_difference[] = date("H:i:s", strtotime("00:00") + ($time2 - $time1));

								//TOTAL DURATION FOR PARTICLUAR EMPLOEE
								$duration[] = date("H:i:s", strtotime("00:00") + ($time2 - $time1));

								//OVERALL TOTAL WORKING DURATION
								$overall_total_duration[] = date("H:i:s", strtotime("00:00") + ($time2 - $time1));

								$mechanic_time_log->duration_difference = sum_mechanic_duration($duration_difference);
								unset($duration_difference);
							} else {
								//TOTAL DURATION FOR PARTICLUAR EMPLOEE
								$duration[] = '-';
							}
						}
						//TOTAL WORKING HOURS PER EMPLOYEE
						$total_duration = sum_mechanic_duration($duration);
						$total_duration = date("H:i:s", strtotime($total_duration));
						// dd($total_duration);
						$format_change = explode(':', $total_duration);
						$hour = $format_change[0] . 'h';
						$minutes = $format_change[1] . 'm';
						$seconds = $format_change[2] . 's';
						$repair_order_mechanic['total_duration'] = $hour . ' ' . $minutes; // . ' ' . $seconds;
						unset($duration);
					} else {
						$repair_order_mechanic['total_duration'] = '';
					}
				}
			}
			//OVERALL WORKING HOURS
			$overall_total_duration = sum_mechanic_duration($overall_total_duration);
			// $overall_total_duration = date("H:i:s", strtotime($overall_total_duration));
			// dd($total_duration);
			$format_change = explode(':', $overall_total_duration);
			$hour = $format_change[0] . 'h';
			$minutes = $format_change[1] . 'm';
			$seconds = $format_change[2] . 's';

			$labour_review_data->jobOrder['overall_total_duration'] = $hour . ' ' . $minutes; // . ' ' . $seconds;
			unset($overall_total_duration);

			$labour_review_data['creation_date'] = date('d/m/Y', strtotime($labour_review_data->created_at));
			$labour_review_data['creation_time'] = date('h:s a', strtotime($labour_review_data->created_at));

			// dd($labour_review_data);

			$status_ids = Config::where('config_type_id', 40)
				->where('id', '!=', 8185) // REVIEW PENDING
				->pluck('id')
				->toArray();
			if ($job_card_repair_order_details) {
				$save_enabled = true;
				foreach ($job_card_repair_order_details as $key => $job_card_repair_order) {
					if (in_array($job_card_repair_order->status_id, $status_ids)) {
						$save_enabled = false;
					}
				}
			}

			return response()->json([
				'success' => true,
				'labour_review_data' => $labour_review_data,
				'job_order_repair_order' => $job_order_repair_order,
				'save_enabled' => $save_enabled,
			]);

		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	public function LabourReviewSave(Request $request) {
		// dd($request->all());
		try {
			$validator = Validator::make($request->all(), [
				'job_card_id' => [
					'required',
					'integer',
					'exists:job_cards,id',
				],
				'job_order_repair_order_id' => [
					'required',
					'integer',
					'exists:job_order_repair_orders,id',
				],
				'status_id' => [
					'required',
					'integer',
					'exists:configs,id',
				],
				'observation' => [
					'required_if:status_id,8187',
					'string',
				],
				'action_taken' => [
					'required_if:status_id,8187',
					// 'string',
				],
				'remarks' => [
					'required_if:status_id,8186',
					// 'string',
				],
			]);

			if ($validator->fails()) {
				$errors = $validator->errors()->all();
				$success = false;
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				]);
			}

			DB::beginTransaction();

			//UPDATE JOB CARD STATUS
			// $job_card = JobCard::where('id', $job_card->id)
			// 	->update([
			// 		'status_id' => 8223, //Ready for Billing
			// 		'updated_by' => Auth::user()->id,
			// 		'updated_at' => Carbon::now(),
			// 	]);

			$job_order_repair_order = JobOrderRepairOrder::find($request->job_order_repair_order_id);
			$job_order_repair_order->fill($request->all());
			$job_order_repair_order->updated_at = Carbon::now();
			$job_order_repair_order->updated_by_id = Auth::user()->id;
			$job_order_repair_order->save();

			//Change Mechnanic status completed into rework
			if ($request->status_id == 8186) {
				$mechnic = RepairOrderMechanic::where('job_order_repair_order_id', $request->job_order_repair_order_id)
					->update([
						'status_id' => 8264, //Rework
						'updated_by_id' => Auth::user()->id,
						'updated_at' => Carbon::now(),
					]);
			}

			if ($request->status_id == 8187) {
				$total_count = JobOrderRepairOrder::where('job_order_id', $job_order_repair_order->job_order_id)->where('status_id', '!=', 8187)->count();
				if ($total_count == 0) {
					$job_card = JobCard::where('id', $request->job_card_id)
						->update([
							'status_id' => 8223, //Review Completed
							'updated_by' => Auth::user()->id,
							'updated_at' => Carbon::now(),
						]);
				}
			} else {
				$job_card = JobCard::where('id', $request->job_card_id)
					->update([
						'status_id' => 8221, //Work In Progress
						'updated_by' => Auth::user()->id,
						'updated_at' => Carbon::now(),
					]);
			}

			DB::commit();

			return response()->json([
				'success' => true,
				'message' => 'Review Updated Successfully!!',
			]);

		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	public function updateJobCardStatus(Request $request) {
		// dd($request->all());
		try {
			$validator = Validator::make($request->all(), [
				'id' => [
					'required',
					'integer',
					'exists:job_cards,id',
				],

			]);

			if ($validator->fails()) {
				$errors = $validator->errors()->all();
				$success = false;
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				]);
			}

			DB::beginTransaction();

			//Check All material items returned or not
			$material = GatePass::where('job_card_id', $request->id)->where('status_id', 8301)->count();
			if ($material > 0) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => ['Some OSL works are not completed!'],
				]);
			}

			$job_card = JobCard::find($request->id);
			$job_card->status_id = 8227; //Waiting for Parts Confirmation
			$job_card->updated_by = Auth::user()->id;
			$job_card->updated_at = Carbon::now();
			$job_card->save();

			DB::commit();

			return response()->json([
				'success' => true,
				'message' => 'Jobcard Updated Successfully!!',
			]);

		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	public function sendCustomerApproval(Request $request) {
		// dd($request->all());
		try {
			$validator = Validator::make($request->all(), [
				'job_card_id' => [
					'required',
					'exists:job_cards,id',
					'integer',
				],
			]);

			if ($validator->fails()) {
				$errors = $validator->errors()->all();
				$success = false;
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				]);
			}

			$job_card = JobCard::with([
				'jobOrder',
				'jobOrder.jobOrderRepairOrders',
				'jobOrder.customer',
				'jobOrder.vehicle',
			])
				->find($request->job_card_id);

			if (!$job_card) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => ['Job Card Not Found!'],
				]);
			}

			// dd($params);
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
			$branch = Outlet::where('id', Auth::user()->employee->outlet_id)->first();

			DB::beginTransaction();

			$params['job_card_id'] = $request->job_card_id;
			$params['customer_id'] = $job_card->jobOrder->customer->id;
			$params['outlet_id'] = $job_card->jobOrder->outlet->id;
			//LABOUR INVOICE ADD
			if ($request->labour_total_amount > 0) {
				$params['invoice_of_id'] = 7425; // LABOUR JOB CARD
				$params['invoice_amount'] = $request->labour_total_amount;

				//GENERATE GATE IN VEHICLE NUMBER
				$generateNumber = SerialNumberGroup::generateNumber(101, $financial_year->id, $branch->state_id, $branch->id);
				if (!$generateNumber['success']) {
					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => [
							'No Invoice Serial number found for FY : ' . $financial_year->from . ', State : ' . $branch->state->code . ', Outlet : ' . $branch->code,
						],
					]);
				}

				$error_messages_1 = [
					'number.required' => 'Serial number is required',
					'number.unique' => 'Serial number is already taken',
				];
				$validator_1 = Validator::make($generateNumber, [
					'number' => [
						'required',
						'unique:invoices,invoice_number,' . $params['job_card_id'] . ',entity_id,company_id,' . Auth::user()->company_id,
					],
				], $error_messages_1);

				$params['invoice_number'] = $generateNumber['number'];

				$this->saveInvoice($params);
			}

			//PART INVOICE ADD
			if ($request->part_total_amount > 0) {
				$params['invoice_of_id'] = 7426; // PART JOB CARD
				$params['invoice_amount'] = $request->part_total_amount;

				//GENERATE GATE IN VEHICLE NUMBER
				$generateNumber = SerialNumberGroup::generateNumber(101, $financial_year->id, $branch->state_id, $branch->id);
				if (!$generateNumber['success']) {
					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => [
							'No Invoice Serial number found for FY : ' . $financial_year->from . ', State : ' . $branch->state->code . ', Outlet : ' . $branch->code,
						],
					]);
				}

				$error_messages_1 = [
					'number.required' => 'Serial number is required',
					'number.unique' => 'Serial number is already taken',
				];
				$validator_1 = Validator::make($generateNumber, [
					'number' => [
						'required',
						'unique:invoices,invoice_number,' . $params['job_card_id'] . ',entity_id,company_id,' . Auth::user()->company_id,
					],
				], $error_messages_1);

				$params['invoice_number'] = $generateNumber['number'];

				$this->saveInvoice($params);
			}

			$customer_mobile = $job_card->jobOrder->customer->mobile_no;
			$vehicle_no = $job_card->jobOrder->vehicle->registration_number;

			if (!$customer_mobile) {
				return response()->json([
					'success' => false,
					'error' => 'Customer Mobile Number Not Found',
				]);
			}
			$job_order = JobOrder::find($job_card->job_order_id);

			if (!$job_order) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => ['Job Order Not Found!'],
				]);
			}

			$job_card->status_id = 8225; //Waiting for Customer Payment
			$job_card->save();

			$job_order->otp_no = mt_rand(111111, 999999);
			$job_order->updated_by_id = Auth::user()->id;
			$job_order->updated_at = Carbon::now();
			$job_order->save();

			$url = url('/') . '/jobcard/bill-details/view/' . $job_order->id . '/' . $job_order->otp_no;

			$short_url = ShortUrl::createShortLink($url, $maxlength = "7");

			$message = 'Dear Customer,Kindly click below link to pay for TVS job order ' . $short_url . ' Vehicle Reg Number : ' . $vehicle_no;

			$msg = sendSMSNotification($customer_mobile, $message);

			DB::commit();

			return response()->json([
				'success' => true,
				'message' => 'URL send to Customer Successfully!',
			]);

		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	public function saveInvoice($params) {

		DB::beginTransaction();

		$invoice = Invoice::firstOrNew([
			'invoice_of_id' => $params['invoice_of_id'],
			'entity_id' => $params['job_card_id'],
		]);
		// dump($params);
		if ($invoice->exists) {
			//FIRST
			$invoice->invoice_amount = $params['invoice_amount'];
			$invoice->updated_by_id = Auth::user()->id;
			$invoice->updated_at = Carbon::now();
		} else {
			//NEW
			$invoice->invoice_of_id = $params['invoice_of_id']; // JOB CARD
			$invoice->entity_id = $params['job_card_id'];
			$invoice->customer_id = $params['customer_id'];
			$invoice->company_id = Auth::user()->company_id;
			$invoice->invoice_number = $params['invoice_number'];
			$invoice->invoice_date = Carbon::now();
			$invoice->outlet_id = $params['outlet_id'];
			$invoice->sbu_id = 54; //SERVICE ALSERV
			$invoice->invoice_amount = $params['invoice_amount'];
			$invoice->payment_status_id = 10031; //PENDING
			$invoice->created_by_id = Auth::user()->id;
			$invoice->created_at = Carbon::now();
		}
		$invoice->save();

		DB::commit();

		return true;
	}

	public function VendorList(Request $request) {
		try {
			$validator = Validator::make($request->all(), [
				'vendor_code' => [
					'required',
				],
			]);
			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'errors' => $validator->errors()->all(),
				]);
			}
			DB::beginTransaction();

			$VendorList = Vendor::where('code', 'LIKE', '%' . $request->vendor_code . '%')
				->where(function ($query) {
					$query->where('type_id', 121)
						->orWhere('type_id', 122);
				})->get();

			DB::commit();
			return response()->json([
				'success' => true,
				'Vendor_list' => $VendorList,
			]);

		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	public function VendorDetails($vendor_id) {
		try {

			$vendor_details = Vendor::with([
				'primaryAddress',
			])
				->find($vendor_id);

			if (!$vendor_details) {
				return response()->json([
					'success' => false,
					'error' => 'Vendor Details Not found!',
				]);
			}
			return response()->json([
				'success' => true,
				'vendor_details' => $vendor_details,
			]);

		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	public function getRoadTestObservation(Request $request) {
		$job_card = JobCard::with(['status', 'jobOrder',
			'jobOrder.type',
			'jobOrder.vehicle',
			'jobOrder.vehicle.model',
			'jobOrder.vehicle.status',
			'jobOrder.status',
			'jobOrder.roadTestDoneBy',
			'jobOrder.roadTestPreferedBy',
			'jobOrder.gateLog'])
			->select([
				'job_cards.*',
				DB::raw('DATE_FORMAT(job_cards.created_at,"%d/%m/%Y") as date'),
				DB::raw('DATE_FORMAT(job_cards.created_at,"%h:%i %p") as time'),
			])
			->find($request->id);
		if (!$job_card) {
			return response()->json([
				'success' => false,
				'error' => 'Validation Error',
				'errors' => ['Job Card Not Found!'],
			]);
		}

		return response()->json([
			'success' => true,
			'job_card' => $job_card,
		]);

	}

	public function getExpertDiagnosis(Request $request) {
		$job_card = JobCard::with(['status', 'jobOrder',
			'jobOrder.type',
			'jobOrder.vehicle',
			'jobOrder.vehicle.model',
			'jobOrder.vehicle.status',
			'jobOrder.expertDiagnosisReportBy',
			'jobOrder.gateLog'])
			->select([
				'job_cards.*',
				DB::raw('DATE_FORMAT(job_cards.created_at,"%d/%m/%Y") as date'),
				DB::raw('DATE_FORMAT(job_cards.created_at,"%h:%i %p") as time'),
			])
			->find($request->id);

		if (!$job_card) {
			return response()->json([
				'success' => false,
				'error' => 'Validation Error',
				'errors' => ['Job Card Not Found!'],
			]);
		}
		return response()->json([
			'success' => true,
			'job_card' => $job_card,
		]);
	}

	public function getDmsCheckList(Request $request) {
		$job_card = JobCard::with([
			'status',
			'jobOrder',
			'jobOrder.type',
			'jobOrder.vehicle',
			'jobOrder.vehicle.model',
			'jobOrder.vehicle.status',
			'jobOrder.warrentyPolicyAttachment',
			'jobOrder.EWPAttachment',
			'jobOrder.AMCAttachment',
		])
			->select([
				'job_cards.*',
				DB::raw('DATE_FORMAT(job_cards.created_at,"%d/%m/%Y") as date'),
				DB::raw('DATE_FORMAT(job_cards.created_at,"%h:%i %p") as time'),
			])
			->find($request->id);
		if (!$job_card) {
			return response()->json([
				'success' => false,
				'error' => 'Validation Error',
				'errors' => ['Job Card Not Found!'],
			]);
		}

		//GET CAMPAIGNS
		$nameSpace = '\\App\\';
		$entity = 'JobOrderCampaign';
		$namespaceModel = $nameSpace . $entity;
		$job_card->jobOrder->campaigns = $this->compaigns($namespaceModel, $job_card->jobOrder, 1);

		return response()->json([
			'success' => true,
			'job_card' => $job_card,
		]);

	}

	public function compaigns($namespaceModel, $job_order, $type) {
		$model_campaigns = collect($namespaceModel::with([
			'claimType',
			'faultType',
			'complaintType',
			'campaignLabours',
			'campaignParts',
		])
				->where('campaign_type', 0)
				->where('vehicle_model_id', $job_order->vehicle->model_id)
				->where(function ($query) use ($type, $job_order) {
					if ($type == 1) {
						$query->where('job_order_id', $job_order->id);
					}
				})
				->get());

		$chassis_campaign_ids = $namespaceModel::whereHas('chassisNumbers', function ($query) use ($job_order) {
			$query->where('chassis_number', $job_order->vehicle->chassis_number);
		})
			->get()
			->pluck('id')
			->toArray();

		$chassis_no_campaigns = collect($namespaceModel::with([
			'chassisNumbers',
			'claimType',
			'faultType',
			'complaintType',
			'campaignLabours',
			'campaignParts',
		])
				->where('campaign_type', 2)
				->where(function ($query) use ($type, $job_order, $chassis_campaign_ids) {
					if ($type == 1) {
						$query->where('job_order_id', $job_order->id);
					} else {
						$query->whereIn('id', $chassis_campaign_ids);
					}
				})
				->get());
		$campaigns = $model_campaigns->merge($chassis_no_campaigns);
		return $campaigns;
	}

	public function getGateInDetail(Request $request) {
		$job_card = JobCard::with(['jobOrder',
			'jobOrder.type',
			'jobOrder.vehicle',
			'jobOrder.vehicle.model',
			'status',
			'jobOrder.vehicle.status',
			'jobOrder.outlet',
			'jobOrder.gateLog',
			'jobOrder.gateLog.createdBy',
			'jobOrder.gateLog.driverAttachment',
			'jobOrder.gateLog.kmAttachment',
			'jobOrder.gateLog.vehicleAttachment',
			'jobOrder.gateLog.chassisAttachment'])
			->select([
				'job_cards.*',
				DB::raw('DATE_FORMAT(job_cards.created_at,"%d/%m/%Y") as date'),
				DB::raw('DATE_FORMAT(job_cards.created_at,"%h:%i %p") as time'),
			])
			->find($request->id);

		if (!$job_card) {
			return response()->json([
				'success' => false,
				'error' => 'Validation Error',
				'errors' => ['Job Card Not Found!'],
			]);
		}

		$job_order = JobOrder::select([
			'job_orders.*',
			DB::raw('DATE_FORMAT(job_orders.created_at,"%d/%m/%Y") as date'),
			DB::raw('DATE_FORMAT(job_orders.created_at,"%h:%i %p") as time'),
		])
			->find($job_card->job_order_id);

		return response()->json([
			'success' => true,
			'job_card' => $job_card,
			'job_order' => $job_order,
		]);

	}

	public function getVehicleDetail(Request $request) {
		$job_card = JobCard::with(['jobOrder',
			'jobOrder.type',
			'jobOrder.vehicle',
			'jobOrder.vehicle.model',
			'status',
			'jobOrder.gateLog'])
			->select([
				'job_cards.*',
				DB::raw('DATE_FORMAT(job_cards.created_at,"%d/%m/%Y") as date'),
				DB::raw('DATE_FORMAT(job_cards.created_at,"%h:%i %p") as time'),
			])
			->find($request->id);

		if (!$job_card) {
			return response()->json([
				'success' => false,
				'error' => 'Validation Error',
				'errors' => ['Job Card Not Found!'],
			]);
		}

		return response()->json([
			'success' => true,
			'job_card' => $job_card,
		]);

	}

	public function getCustomerDetail(Request $request) {
		$job_card = JobCard::with(['jobOrder',
			'jobOrder.type',
			'jobOrder.vehicle',
			'jobOrder.vehicle.model',
			'status',
			'jobOrder.gateLog',
			'jobOrder.vehicle.currentOwner.customer',
			'jobOrder.vehicle.currentOwner.customer.address',
			'jobOrder.vehicle.currentOwner.customer.address.country',
			'jobOrder.vehicle.currentOwner.customer.address.state',
			'jobOrder.vehicle.currentOwner.customer.address.city',
			'jobOrder.vehicle.currentOwner.ownershipType'])
			->select([
				'job_cards.*',
				DB::raw('DATE_FORMAT(job_cards.created_at,"%d/%m/%Y") as date'),
				DB::raw('DATE_FORMAT(job_cards.created_at,"%h:%i %p") as time'),
			])
			->find($request->id);

		if (!$job_card) {
			return response()->json([
				'success' => false,
				'error' => 'Validation Error',
				'errors' => ['Job Card Not Found!'],
			]);
		}
		return response()->json([
			'success' => true,
			'job_card' => $job_card,
		]);

	}

	public function getOrderDetail(Request $request) {
		$job_card = JobCard::with(['jobOrder',
			'jobOrder.type',
			'jobOrder.vehicle',
			'jobOrder.vehicle.model',
			'status',
			'jobOrder.vehicle.status',
			'jobOrder.vehicle.currentOwner.ownershipType',
			'jobOrder.vehicle.lastJobOrder',
			'jobOrder.vehicle.lastJobOrder.jobCard',
			'jobOrder.type',
			'jobOrder.quoteType',
			'jobOrder.serviceType',
			'jobOrder.kmReadingType',
			'jobOrder.status',
			'jobOrder.gateLog',
			'jobOrder.gateLog.createdBy',
			'jobOrder.expertDiagnosisReportBy',
			'jobOrder.estimationType',
			'jobOrder.driverLicenseAttachment',
			'jobOrder.insuranceAttachment',
			'jobOrder.rcBookAttachment'])
			->select([
				'job_cards.*',
				DB::raw('DATE_FORMAT(job_cards.created_at,"%d/%m/%Y") as date'),
				DB::raw('DATE_FORMAT(job_cards.created_at,"%h:%i %p") as time'),
			])
			->find($request->id);

		if (!$job_card) {
			return response()->json([
				'success' => false,
				'error' => 'Validation Error',
				'errors' => ['Job Card Not Found!'],
			]);
		}

		return response()->json([
			'success' => true,
			'job_card' => $job_card,
		]);

	}

	public function getInventory(Request $request) {
		$job_card = JobCard::with(['jobOrder',
			'jobOrder.type',
			'jobOrder.vehicle',
			'jobOrder.vehicle.model',
			'status',
			'jobOrder.vehicle.status',
			'jobOrder.gateLog',
			'jobOrder.vehicleInventoryItem',
			'jobOrder.vehicleInspectionItems'])
			->select([
				'job_cards.*',
				DB::raw('DATE_FORMAT(job_cards.created_at,"%d/%m/%Y") as date'),
				DB::raw('DATE_FORMAT(job_cards.created_at,"%h:%i %p") as time'),
			])
			->find($request->id);

		if (!$job_card) {
			return response()->json([
				'success' => false,
				'error' => 'Validation Error',
				'errors' => ['Job Card Not Found!'],
			]);
		}

		$inventory_params['field_type_id'] = [11, 12];

		return response()->json([
			'success' => true,
			'job_card' => $job_card,
			'inventory_list' => VehicleInventoryItem::getInventoryList($job_card->job_order_id, $inventory_params),
		]);

	}

	public function getCaptureVoc(Request $request) {
		$job_card = JobCard::with(['jobOrder',
			'jobOrder.type',
			'jobOrder.vehicle',
			'jobOrder.vehicle.model',
			'status',
			'jobOrder.vehicle.status',
			'jobOrder.customerVoices',
			'jobOrder.gateLog'])
			->select([
				'job_cards.*',
				DB::raw('DATE_FORMAT(job_cards.created_at,"%d/%m/%Y") as date'),
				DB::raw('DATE_FORMAT(job_cards.created_at,"%h:%i %p") as time'),
			])
			->find($request->id);

		if (!$job_card) {
			return response()->json([
				'success' => false,
				'error' => 'Validation Error',
				'errors' => ['Job Card Not Found!'],
			]);
		}

		/*$job_order = JobOrder::company()->with([
				'vehicle',
				'vehicle.model',
				'vehicle.status',
				'customerVoices',
				'gateLog',
			])
				->select([
					'job_orders.*',
					DB::raw('DATE_FORMAT(job_orders.created_at,"%d/%m/%Y") as date'),
					DB::raw('DATE_FORMAT(job_orders.created_at,"%h:%i %p") as time'),
				])
		*/

		return response()->json([
			'success' => true,
			//'job_order' => $job_order,
			'job_card' => $job_card,
		]);

	}

	public function deleteOutwardItem(Request $request) {
		DB::beginTransaction();
		// dd($request->id);
		try {
			$vehicle = GatePassItem::withTrashed()->where('id', $request->id)->forceDelete();
			if ($vehicle) {
				DB::commit();
				return response()->json(['success' => true, 'message' => 'Outward Item Deleted Successfully']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

	public function getEstimateStatus(Request $request) {
		$job_card = JobCard::with(['jobOrder',
			'jobOrder.type',
			'jobOrder.vehicle',
			'jobOrder.vehicle.model',
			'status',
			'jobOrder.vehicle.status',
			'jobOrder.customerApprovalAttachment',
			'jobOrder.customerESign'])
			->select([
				'job_cards.*',
				DB::raw('DATE_FORMAT(job_cards.created_at,"%d/%m/%Y") as date'),
				DB::raw('DATE_FORMAT(job_cards.created_at,"%h:%i %p") as time'),
			])
			->find($request->id);
		if (!$job_card) {
			return response()->json([
				'success' => false,
				'error' => 'Validation Error',
				'errors' => ['Job Card Not Found!'],
			]);
		}

		return response()->json([
			'success' => true,
			'attachement_path' => url('storage/app/public/gigo/gate_in/attachments/'),
			'job_card' => $job_card,
		]);

	}

	public function getEstimate(Request $request) {
		$job_card = JobCard::with(['jobOrder',
			'jobOrder.type',
			'jobOrder.vehicle',
			'jobOrder.vehicle.model',
			'status'])->find($request->id);
		if (!$job_card) {
			return response()->json([
				'success' => false,
				'error' => 'Validation Error',
				'errors' => ['Job Card Not Found!'],
			]);
		}

		$job_order = JobOrder::with([
			'vehicle',
			'vehicle.model',
			'vehicle.status',
		])
			->select([
				'job_orders.*',
				DB::raw('DATE_FORMAT(job_orders.created_at,"%d/%m/%Y") as date'),
				DB::raw('DATE_FORMAT(job_orders.created_at,"%h:%i %p") as time'),
			])
			->where('company_id', Auth::user()->company_id)
			->find($job_card->job_order_id);

		$oem_recomentaion_labour_amount = 0;
		$additional_rot_and_parts_labour_amount = 0;

		foreach ($job_order->jobOrderRepairOrders as $oemrecomentation_labour) {

			if ($oemrecomentation_labour['is_recommended_by_oem'] == 1) {
				//SCHEDULED MAINTANENCE
				$oem_recomentaion_labour_amount += $oemrecomentation_labour['amount'];
			}
			if ($oemrecomentation_labour['is_recommended_by_oem'] == 0) {
				//ADDITIONAL ROT AND PARTS
				$additional_rot_and_parts_labour_amount += $oemrecomentation_labour['amount'];
			}
		}

		$oem_recomentaion_part_amount = 0;
		$additional_rot_and_parts_part_amount = 0;
		foreach ($job_order->jobOrderParts as $oemrecomentation_labour) {
			if ($oemrecomentation_labour['is_oem_recommended'] == 1) {
				//SCHEDULED MAINTANENCE
				$oem_recomentaion_part_amount += $oemrecomentation_labour['amount'];
			}
			if ($oemrecomentation_labour['is_oem_recommended'] == 0) {
				//ADDITIONAL ROT AND PARTS
				$additional_rot_and_parts_part_amount += $oemrecomentation_labour['amount'];
			}
		}

		//OEM RECOMENTATION LABOUR AND PARTS AND SUB TOTAL
		$job_order->oem_recomentation_labour_amount = $oem_recomentaion_labour_amount;
		$job_order->oem_recomentation_part_amount = $oem_recomentaion_part_amount;
		$job_order->oem_recomentation_sub_total = $oem_recomentaion_labour_amount + $oem_recomentaion_part_amount;

		//ADDITIONAL ROT & PARTS LABOUR AND PARTS AND SUB TOTAL
		$job_order->additional_rot_parts_labour_amount = $additional_rot_and_parts_labour_amount;
		$job_order->additional_rot_parts_part_amount = $additional_rot_and_parts_part_amount;
		$job_order->additional_rot_parts_sub_total = $additional_rot_and_parts_labour_amount + $additional_rot_and_parts_part_amount;

		//TOTAL ESTIMATE
		$job_order->total_estimate_labour_amount = $oem_recomentaion_labour_amount + $additional_rot_and_parts_labour_amount;
		$job_order->total_estimate_parts_amount = $oem_recomentaion_part_amount + $additional_rot_and_parts_part_amount;
		$job_order->total_estimate_amount = (($oem_recomentaion_labour_amount + $additional_rot_and_parts_labour_amount) + ($oem_recomentaion_part_amount + $additional_rot_and_parts_part_amount));

		return response()->json([
			'success' => true,
			'job_order' => $job_order,
			'job_card' => $job_card,
		]);

	}

	public function getPartsIndent(Request $request) {
		$job_card = JobCard::with([
			'jobOrder',
			'jobOrder.type',
			'jobOrder.vehicle',
			'jobOrder.vehicle.model',
			'jobOrder.vehicle.status',
			'status',
		])->find($request->id);

		if (!$job_card) {
			return response()->json([
				'success' => false,
				'error' => 'Validation Error',
				'errors' => [
					'Job Card Not Found!',
				],
			]);
		}

		$part_list = collect(JobOrderPart::select(
			'job_order_parts.id',
			'parts.code as name'
		)
				->join('parts', 'parts.id', 'job_order_parts.part_id')
				->where('job_order_id', $job_card->job_order_id)
				->groupBy('job_order_parts.id')
				->get())->prepend(['id' => '', 'name' => 'Select Part No']);

		$mechanic_list = collect(JobOrderRepairOrder::select(
			'users.id',
			'users.name'
		)
				->join('repair_order_mechanics', 'repair_order_mechanics.job_order_repair_order_id', 'job_order_repair_orders.id')
				->join('users', 'users.id', 'repair_order_mechanics.mechanic_id')
				->where('job_order_repair_orders.job_order_id', $job_card->job_order_id)
				->groupBy('users.id')
				->get())->prepend(['id' => '', 'name' => 'Select Issued To']);

		$issued_mode_list = Config::getDropDownList(['config_type_id' => 109, 'add_default' => true, 'default_text' => 'Select Issue Mode']);

		$extras = [
			'part_list' => $part_list,
			'mechanic_list' => $mechanic_list,
			'issued_mode_list' => $issued_mode_list,
		];

		$issued_parts = JobOrderIssuedPart::select(
			'job_order_issued_parts.id as issued_id',
			'parts.code',
			'job_order_parts.id',
			'job_order_parts.qty',
			'job_order_issued_parts.issued_qty',
			DB::raw('DATE_FORMAT(job_order_issued_parts.created_at,"%d-%m-%Y") as date'),
			'users.name as issued_to',
			'configs.name as config_name',
			'job_order_issued_parts.issued_mode_id',
			'job_order_issued_parts.issued_to_id'
		)
			->join('job_order_parts', 'job_order_parts.id', 'job_order_issued_parts.job_order_part_id')
			->join('parts', 'parts.id', 'job_order_parts.part_id')
			->join('users', 'users.id', 'job_order_issued_parts.issued_to_id')
			->join('configs', 'configs.id', 'job_order_issued_parts.issued_mode_id')
			->where('job_order_parts.job_order_id', $job_card->job_order_id)
			->groupBy('job_order_issued_parts.id')
			->get();

		return response()->json([
			'success' => true,
			'issued_parts' => $issued_parts,
			'extras' => $extras,
			'job_card' => $job_card,
		]);

	}

	public function getScheduleMaintenance(Request $request) {
		$job_card = JobCard::with(['jobOrder',
			'jobOrder.type',
			'jobOrder.vehicle',
			'jobOrder.vehicle.model',
			'status'])->find($request->id);
		if (!$job_card) {
			return response()->json([
				'success' => false,
				'error' => 'Validation Error',
				'errors' => ['Job Card Not Found!'],
			]);
		}

		$job_order = JobOrder::with([
			'vehicle',
			'vehicle.model',
			'vehicle.status'])
			->select([
				'job_orders.*',
				DB::raw('DATE_FORMAT(job_orders.created_at,"%d/%m/%Y") as date'),
				DB::raw('DATE_FORMAT(job_orders.created_at,"%h:%i %p") as time'),
			])->find($job_card->job_order_id);

		$schedule_maintenance_part_amount = 0;
		$schedule_maintenance_labour_amount = 0;
		$schedule_maintenance['labour_details'] = $job_order->jobOrderRepairOrders()->where('is_recommended_by_oem', 1)->get();
		if (!empty($schedule_maintenance['labour_details'])) {
			foreach ($schedule_maintenance['labour_details'] as $key => $value) {
				$schedule_maintenance_labour_amount += $value->amount;
				$value->repair_order = $value->repairOrder;
				$value->repair_order_type = $value->repairOrder->repairOrderType;
			}
		}
		$schedule_maintenance['labour_amount'] = $schedule_maintenance_labour_amount;

		$schedule_maintenance['part_details'] = $job_order->jobOrderParts()->where('is_oem_recommended', 1)->get();
		if (!empty($schedule_maintenance['part_details'])) {
			foreach ($schedule_maintenance['part_details'] as $key => $value) {
				$schedule_maintenance_part_amount += $value->amount;
				$value->part = $value->part;
			}
		}
		$schedule_maintenance['part_amount'] = $schedule_maintenance_part_amount;

		$schedule_maintenance['total_amount'] = $schedule_maintenance['labour_amount'] + $schedule_maintenance['part_amount'];
		// dd($schedule_maintenance['labour_details']);

		return response()->json([
			'success' => true,
			'job_order' => $job_order,
			'schedule_maintenance' => $schedule_maintenance,
			'job_card' => $job_card,
		]);

	}

	public function getPayableLabourPart(Request $request) {

		$job_card = JobCard::find($request->id);

		if (!$job_card) {
			return response()->json([
				'success' => false,
				'error' => 'Validation Error',
				'errors' => [
					'Job Card Not Found',
				],
			]);
		}

		$params['job_order_id'] = $job_card->job_order_id;
		$params['type_id'] = 0;

		$result = $this->getLabourPartsData($params);

		// $job_card = JobCard::with(['jobOrder',
		// 	'jobOrder.type',
		// 	'jobOrder.vehicle',
		// 	'jobOrder.vehicle.model',
		// 	'status'])->find($request->id);
		// if (!$job_card) {
		// 	return response()->json([
		// 		'success' => false,
		// 		'error' => 'Validation Error',
		// 		'errors' => ['Job Card Not Found!'],
		// 	]);
		// }

		// $job_order = JobOrder::with([
		// 	'vehicle',
		// 	'vehicle.model',
		// 	'vehicle.status',
		// 	'status',
		// 	'gateLog',
		// 	'jobOrderRepairOrders' => function ($query) {
		// 		$query->where('is_recommended_by_oem', 0);
		// 	},
		// 	'jobOrderRepairOrders.splitOrderType',
		// 	'jobOrderRepairOrders.repairOrder',
		// 	'jobOrderRepairOrders.repairOrder.repairOrderType',
		// 	'jobOrderParts' => function ($query) {
		// 		$query->where('is_oem_recommended', 0);
		// 	},
		// 	'jobOrderParts.splitOrderType',
		// 	'jobOrderParts.part',
		// ])
		// 	->select([
		// 		'job_orders.*',
		// 		DB::raw('DATE_FORMAT(job_orders.created_at,"%d/%m/%Y") as date'),
		// 		DB::raw('DATE_FORMAT(job_orders.created_at,"%h:%i %p") as time'),
		// 	])
		// 	->where('company_id', Auth::user()->company_id)
		// 	->find($job_card->job_order_id);

		// if (!$job_order) {
		// 	return response()->json([
		// 		'success' => false,
		// 		'error' => 'Validation error',
		// 		'errors' => ['Job Order Not found!'],
		// 	]);
		// }

		// $parts_total_amount = 0;
		// $labour_total_amount = 0;
		// $total_amount = 0;
		// if ($job_order->jobOrderRepairOrders) {
		// 	foreach ($job_order->jobOrderRepairOrders as $key => $labour) {
		// 		$labour_total_amount += $labour->amount;

		// 	}
		// }
		// if ($job_order->jobOrderParts) {
		// 	foreach ($job_order->jobOrderParts as $key => $part) {
		// 		$parts_total_amount += $part->amount;

		// 	}
		// }
		// $total_amount = $parts_total_amount + $labour_total_amount;

		//Check Newly added Part or Labour
		$labour_count = JobOrderRepairOrder::where('job_order_id', $job_card->job_order_id)->where('status_id', 8180)->count();
		$part_count = JobOrderPart::where('job_order_id', $job_card->job_order_id)->where('status_id', 8200)->count();

		$send_approval_status = 0;
		if ($labour_count > 0 || $part_count > 0) {
			$send_approval_status = 1;
		}

		return response()->json([
			'success' => true,
			'job_order' => $result['job_order'],
			'part_details' => $result['part_details'],
			'labour_details' => $result['labour_details'],
			'total_amount' => $result['total_amount'],
			'labour_total_amount' => $result['labour_amount'],
			'parts_total_amount' => $result['part_amount'],
			'job_card' => $job_card,
			'send_approval_status' => $send_approval_status,
		]);

		// return response()->json([
		// 	'success' => true,
		// 	'job_order' => $job_order,
		// 	'total_amount' => number_format($total_amount, 2),
		// 	'parts_total_amount' => number_format($parts_total_amount, 2),
		// 	'labour_total_amount' => number_format($labour_total_amount, 2),
		// 	'job_card' => $job_card,
		// 	'send_approval_status' => $send_approval_status,
		// ]);

	}

	public function getLabourPartsData($params) {

		$result = array();

		$job_order = JobOrder::with([
			'vehicle',
			'vehicle.model',
			'vehicle.status',
			'status',
			'serviceType',
			'jobOrderRepairOrders' => function ($query) use ($params) {
				$query->where('is_recommended_by_oem', $params['type_id']);
			},
			'jobOrderRepairOrders.repairOrder',
			'jobOrderRepairOrders.repairOrder.repairOrderType',
			'jobOrderRepairOrders.splitOrderType',
			'jobOrderParts' => function ($query) use ($params) {
				$query->where('is_oem_recommended', $params['type_id']);
			},
			'jobOrderParts.part',
			'jobOrderParts.part.taxCode',
			'jobOrderParts.splitOrderType',
		])
			->select([
				'job_orders.*',
				DB::raw('DATE_FORMAT(job_orders.created_at,"%d/%m/%Y") as date'),
				DB::raw('DATE_FORMAT(job_orders.created_at,"%h:%i %p") as time'),
			])
			->where('company_id', Auth::user()->company_id)
			->where('id', $params['job_order_id'])->first();

		$customer_paid_type = SplitOrderType::where('paid_by_id', '10013')->pluck('id')->toArray();

		$labour_amount = 0;
		$part_amount = 0;

		$labour_details = array();
		if ($job_order->jobOrderRepairOrders) {
			foreach ($job_order->jobOrderRepairOrders as $key => $value) {
				$labour_details[$key]['id'] = $value->id;
				$labour_details[$key]['labour_id'] = $value->repair_order_id;
				$labour_details[$key]['code'] = $value->repairOrder->code;
				$labour_details[$key]['name'] = $value->repairOrder->name;
				$labour_details[$key]['type'] = $value->repairOrder->repairOrderType ? $value->repairOrder->repairOrderType->short_name : '-';
				$labour_details[$key]['qty'] = $value->qty;
				$labour_details[$key]['amount'] = $value->amount;
				$labour_details[$key]['is_free_service'] = $value->is_free_service;
				$labour_details[$key]['split_order_type'] = $value->splitOrderType ? $value->splitOrderType->code . "|" . $value->splitOrderType->name : '-';
				$labour_details[$key]['removal_reason_id'] = $value->removal_reason_id;
				$labour_details[$key]['split_order_type_id'] = $value->split_order_type_id;
				if (in_array($value->split_order_type_id, $customer_paid_type) || !$value->split_order_type_id) {
					if ($value->is_free_service != 1 && $value->removal_reason_id == null) {
						$labour_amount += $value->amount;
					} else {
						$labour_details[$key]['amount'] = 0;
					}
				} else {
					$labour_details[$key]['amount'] = 0;
				}
			}
		}

		$part_details = array();
		if ($job_order->jobOrderParts) {
			foreach ($job_order->jobOrderParts as $key => $value) {
				$part_details[$key]['id'] = $value->id;
				$part_details[$key]['part_id'] = $value->part_id;
				$part_details[$key]['code'] = $value->part->code;
				$part_details[$key]['name'] = $value->part->name;
				$part_details[$key]['type'] = $value->part->taxCode ? $value->part->taxCode->code : '-';
				$part_details[$key]['rate'] = $value->rate;
				$part_details[$key]['qty'] = $value->qty;
				$part_details[$key]['amount'] = $value->amount;
				$part_details[$key]['is_free_service'] = $value->is_free_service;
				$part_details[$key]['split_order_type'] = $value->splitOrderType ? $value->splitOrderType->code . "|" . $value->splitOrderType->name : '-';
				$part_details[$key]['removal_reason_id'] = $value->removal_reason_id;
				$part_details[$key]['split_order_type_id'] = $value->split_order_type_id;
				if (in_array($value->split_order_type_id, $customer_paid_type) || !$value->split_order_type_id) {
					if ($value->is_free_service != 1 && $value->removal_reason_id == null) {
						$part_amount += $value->amount;
					} else {
						$part_details[$key]['amount'] = 0;
					}
				} else {
					$part_details[$key]['amount'] = 0;
				}
			}
		}

		$total_amount = $part_amount + $labour_amount;
		// dd($labour_details);
		$result['job_order'] = $job_order;
		$result['labour_details'] = $labour_details;
		$result['part_details'] = $part_details;
		$result['labour_amount'] = $labour_amount;
		$result['part_amount'] = $part_amount;
		$result['total_amount'] = $total_amount;

		return $result;
	}

	public function deletePayable(Request $request) {
		// dd($request->all());
		try {
			DB::beginTransaction();
			if ($request->payable_type == 'labour') {
				$validator = Validator::make($request->all(), [
					'labour_parts_id' => [
						'required',
						'integer',
						'exists:job_order_repair_orders,id',
					],
				]);

				if ($validator->fails()) {
					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => $validator->errors()->all(),
					]);
				}

				$job_order_repair_order = JobOrderRepairOrder::find($request->labour_parts_id);
				if ($request->removal_reason_id == 10022) {
					$job_order_repair_order->removal_reason_id = $request->removal_reason_id;
					$job_order_repair_order->removal_reason = $request->removal_reason;
				} else {
					$job_order_repair_order->removal_reason_id = $request->removal_reason_id;
					$job_order_repair_order->removal_reason = NULL;
				}
				$job_order_repair_order->updated_by_id = Auth::user()->id;
				$job_order_repair_order->updated_at = Carbon::now();
				$job_order_repair_order->save();

			} else {
				$validator = Validator::make($request->all(), [
					'labour_parts_id' => [
						'required',
						'integer',
						'exists:job_order_parts,id',
					],
				]);

				if ($validator->fails()) {
					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => $validator->errors()->all(),
					]);
				}

				$job_order_parts = JobOrderPart::find($request->labour_parts_id);
				if ($request->removal_reason_id == 10022) {
					$job_order_parts->removal_reason_id = $request->removal_reason_id;
					$job_order_parts->removal_reason = $request->removal_reason;
				} else {
					$job_order_parts->removal_reason_id = $request->removal_reason_id;
					$job_order_parts->removal_reason = NULL;
				}
				$job_order_parts->updated_by_id = Auth::user()->id;
				$job_order_parts->updated_at = Carbon::now();
				$job_order_parts->save();
			}

			DB::commit();
			if ($request->payable_type == 'labour') {
				return response()->json([
					'success' => true,
					'message' => 'Labour Details Successfully',
				]);
			} else {
				return response()->json([
					'success' => true,
					'message' => 'Parts Details Successfully',
				]);
			}

		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Error!',
				'errors' => [
					'Error : ' . $e->getMessage() . '. Line : ' . $e->getLine() . '. File : ' . $e->getFile(),
				],
			]);
		}
	}

	public function sendConfirmation(Request $request) {
		// dd($request->all());
		try {
			$validator = Validator::make($request->all(), [
				'job_order_id' => [
					'required',
					'exists:job_orders,id',
					'integer',
				],
			]);

			if ($validator->fails()) {
				$errors = $validator->errors()->all();
				$success = false;
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				]);
			}

			$job_order = JobOrder::with([
				'customer',
				'vehicle',
			])
				->find($request->job_order_id);

			// dd($job_order);
			if (!$job_order) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => ['Job Order Not Found!'],
				]);
			}

			DB::beginTransaction();

			$customer_mobile = $job_order->customer->mobile_no;
			$vehicle_no = $job_order->vehicle->registration_number;

			if (!$customer_mobile) {
				return response()->json([
					'success' => false,
					'error' => 'Customer Mobile Number Not Found',
				]);
			}

			$job_order->otp_no = mt_rand(111111, 999999);
			$job_order->status_id = 8469; //Waiting for Customer Approval
			$job_order->updated_by_id = Auth::user()->id;
			$job_order->updated_at = Carbon::now();
			$job_order->save();

			$url = url('/') . '/vehicle-inward/estimate/customer/view/' . $request->job_order_id . '/' . $job_order->otp_no;

			$short_url = ShortUrl::createShortLink($url, $maxlength = "7");

			$message = 'Dear Customer,Kindly click below link to approve for revised TVS job order ' . $short_url . ' Vehicle Reg Number : ' . $vehicle_no;

			$msg = sendSMSNotification($customer_mobile, $message);

			DB::commit();

			return response()->json([
				'success' => true,
				'message' => $message,
			]);

		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	//VEHICLE INSPECTION GET FORM DATA
	public function getVehicleInspection(Request $request) {
		try {

			$job_card = JobCard::with(['jobOrder',
				'jobOrder.type',
				'jobOrder.vehicle',
				'jobOrder.vehicle.model',
				'status'])->find($request->id);
			if (!$job_card) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => ['Job Card Not Found!'],
				]);
			}

			$job_order = JobOrder::with([
				'vehicle',
				'vehicle.model',
				'vehicle.status',
				'status',
				'vehicleInspectionItems',
			])
				->select([
					'job_orders.*',
					DB::raw('DATE_FORMAT(job_orders.created_at,"%d/%m/%Y") as date'),
					DB::raw('DATE_FORMAT(job_orders.created_at,"%h:%i %p") as time'),
				])
				->where('company_id', Auth::user()->company_id)
				->find($job_card->job_order_id);

			//VEHICLE INSPECTION ITEM
			$vehicle_inspection_item_group = VehicleInspectionItemGroup::where('company_id', Auth::user()->company_id)->select('id', 'name')->get();

			$vehicle_inspection_item_groups = array();
			foreach ($vehicle_inspection_item_group as $key => $value) {
				$item_group = array();
				$item_group['id'] = $value->id;
				$item_group['name'] = $value->name;

				$inspection_items = VehicleInspectionItem::where('group_id', $value->id)->get()->keyBy('id');

				$vehicle_inspections = $job_order->vehicleInspectionItems()->orderBy('vehicle_inspection_item_id')->get()->toArray();

				if (count($vehicle_inspections) > 0) {
					foreach ($vehicle_inspections as $value) {
						if (isset($inspection_items[$value['id']])) {
							$inspection_items[$value['id']]->status_id = $value['pivot']['status_id'];
						}
					}
				}
				$item_group['vehicle_inspection_items'] = $inspection_items;

				$vehicle_inspection_item_groups[] = $item_group;
			}

			$params['config_type_id'] = 32;
			$params['add_default'] = false;
			$extras = [
				'inspection_results' => Config::getDropDownList($params), //VEHICLE INSPECTION RESULTS
			];

			$inventory_params['field_type_id'] = [11, 12];
			//Job card details need to get future

			return response()->json([
				'success' => true,
				'extras' => $extras,
				'vehicle_inspection_item_groups' => $vehicle_inspection_item_groups,
				'job_order' => $job_order,
				'job_card' => $job_card,
			]);
		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Error',
				'errors' => [
					'Error : ' . $e->getMessage() . '. Line : ' . $e->getLine() . '. File : ' . $e->getFile(),
				],
			]);
		}
	}

	public function getReturnableItems(Request $request) {
		$job_card = $job_card = JobCard::with([
			'status',
			'jobOrder',
			'jobOrder.vehicle',
			'jobOrder.vehicle.model',
		])->find($request->id);

		if (!$job_card) {
			return response()->json([
				'success' => true,
				'job_card' => $job_card,
				'returnable_items' => $returnable_items,
				'attachement_path' => url('storage/app/public/gigo/job_card/returnable_items/'),
			]);
		}

		$returnable_items = JobCardReturnableItem::with([
			'attachment',
		])
			->where('job_card_id', $job_card->id)
			->get();

		return response()->json([
			'success' => true,
			'job_card' => $job_card,
			'returnable_items' => $returnable_items,
			'attachement_path' => url('storage/app/public/gigo/job_card/returnable_items/'),
		]);

	}

	public function getReturnableItemFormdata(Request $request) {
		// dd($request->all());
		$job_card = JobCard::with([
			'jobOrder',
			'jobOrder.vehicle',
			'jobOrder.vehicle.model',
			'jobOrder.jobOrderParts',
			'jobOrder.jobOrderParts.part',
			'status',
		])
			->find($request->id);

		if (!$job_card) {
			return response()->json([
				'success' => false,
				'error' => 'Validation Error',
				'errors' => ['Job Card Not Found!'],
			]);
		}
		if ($request->returnable_item_id) {
			$returnable_item = JobCardReturnableItem::with([
				'attachment',
			])
				->find($request->returnable_item_id);
			//->first();
			$action = 'Edit';
		} else {
			$returnable_item = new JobCardReturnableItem;
			$action = 'Add';
		}
		return response()->json([
			'success' => true,
			'job_card' => $job_card,
			'returnable_item' => $returnable_item,
			'attachement_path' => url('storage/app/public/gigo/returnable_items/'),
		]);
	}

	public function ReturnableItemSave(Request $request) {
		// dd($request->all());
		try {
			$validator = Validator::make($request->all(), [
				'job_card_id' => [
					'required',
					'integer',
					'exists:job_cards,id',
				],
				'job_card_returnable_items.*.id' => [
					'required',
					'integer',
					'exists:parts,id',
				],
				'job_card_returnable_items.*.item_name' => [
					'required',
					'string',
					'max:191',
				],
				'job_card_returnable_items.*.item_description' => [
					'required',
					'string',
					'max:191',
				],
				'job_card_returnable_items.*.item_make' => [
					'nullable',
					'string',
					'max:191',
				],
				'job_card_returnable_items.*.item_model' => [
					'nullable',
					'string',
					'max:191',
				],
				'job_card_returnable_items.*.item_serial_no' => [
					'nullable',
					'string',
				],
				'job_card_returnable_items.*.qty' => [
					'required',
					'numeric',
					'regex:/^\d+(\.\d{1,2})?$/',
				],
				'job_card_returnable_items.*.remarks' => [
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

			//START FOR CHECK QUANTITY VALIDATION
			$job_card = JobCard::find($request->job_card_id);

			$job_order_part = JobOrderPart::where([
				'job_order_id' => $job_card->job_order_id,
				'part_id' => $request->job_card_returnable_items[0]['id'],
			])->first();

			if ($request->job_card_returnable_items[0]['qty'] > $job_order_part->qty) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'message' => 'Quantity Not More then ' . $job_order_part->qty . '.For this item!',
				]);
			}
			//END FOR CHECK QUANTITY VALIDATION

			$job_card_returnable_items_count = count($request->job_card_returnable_items);
			$job_card_returnable_unique_items_count = count(array_unique(array_column($request->job_card_returnable_items, 'item_serial_no')));
			if ($job_card_returnable_items_count != $job_card_returnable_unique_items_count) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'message' => 'Returnable items serial numbers are not unique',
				]);
			}
			DB::beginTransaction();

			if (!empty($request->attachment_removal_ids)) {
				$attachment_remove = json_decode($request->attachment_removal_ids, true);
				Attachment::whereIn('id', $attachment_remove)->delete();
			}

			if (isset($request->job_card_returnable_items) && count($request->job_card_returnable_items) > 0) {
				//Inserting Job card returnable items
				foreach ($request->job_card_returnable_items as $key => $job_card_returnable_item) {
					$returnable_item = JobCardReturnableItem::firstOrNew([
						'item_name' => $job_card_returnable_item['item_name'],
						'item_serial_no' => $job_card_returnable_item['item_serial_no'],
						'part_id' => $job_card_returnable_item['id'],
						'job_card_id' => $request->job_card_id,
					]);
					$returnable_item->fill($job_card_returnable_item);
					$returnable_item->job_card_id = $request->job_card_id;
					if ($returnable_item->exists) {
						$returnable_item->updated_at = Carbon::now();
						$returnable_item->updated_by_id = Auth::user()->id;
					} else {
						$returnable_item->created_at = Carbon::now();
						$returnable_item->created_by_id = Auth::user()->id;
					}
					$returnable_item->save();

					//Attachment Save
					$attachment_path = storage_path('app/public/gigo/returnable_items/');
					Storage::makeDirectory($attachment_path, 0777);

					//SAVE RETURNABLE ITEMS PHOTO ATTACHMENT
					if (!empty($job_card_returnable_item['attachments']) && count($job_card_returnable_item['attachments']) > 0) {
						foreach ($job_card_returnable_item['attachments'] as $key => $returnable_item_attachment) {
							$file_name_with_extension = $returnable_item_attachment->getClientOriginalName();
							$file_name = pathinfo($file_name_with_extension, PATHINFO_FILENAME);
							$extension = $returnable_item_attachment->getClientOriginalExtension();
							$name = $returnable_item->id . '_' . $file_name . '.' . $extension;
							$name = str_replace(' ', '-', $name); // Replaces all spaces with hyphens.
							$returnable_item_attachment->move($attachment_path, $name);
							$attachement = new Attachment;
							$attachement->attachment_of_id = 232; //Job Card Returnable Item
							$attachement->attachment_type_id = 239; //Job Card Returnable Item
							$attachement->name = $name;
							$attachement->entity_id = $returnable_item->id;
							$attachement->created_by = Auth::user()->id;
							$attachement->save();
						}
					}
				}
			}

			DB::commit();
			return response()->json([
				'success' => true,
				'message' => 'Returnable items added successfully!!',
			]);
		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	public function getReturnablePartsFormdata(Request $request) {
		$job_card = JobCard::with([
			'jobOrder',
			'jobOrder.vehicle',
			'jobOrder.vehicle.model',
			'status',
		])
			->find($request->id);

		$job_order_parts = JobOrderPart::with('part')->where('job_order_parts.job_order_id', $job_card->job_order_id)->orderBy('job_order_parts.part_id')->get()->keyBy('part_id');

		$returned_parts = JobCardReturnableItem::where('job_card_id', $request->id)->orderBy('job_card_returnable_items.part_id')->get()->toArray();

		if (count($returned_parts) > 0) {
			foreach ($returned_parts as $value) {
				if (isset($job_order_parts[$value['part_id']])) {
					$job_order_parts[$value['part_id']]->checked = true;
					$job_order_parts[$value['part_id']]->returned_qty = $value['qty'];
				}
			}
		}

		if (!$job_card) {
			return response()->json([
				'success' => false,
				'error' => 'Validation Error',
				'errors' => ['Job Card Not Found!'],
			]);
		}

		return response()->json([
			'success' => true,
			'job_card' => $job_card,
			'job_order_parts' => $job_order_parts,
		]);
	}

	public function ReturnablePartSave(Request $request) {
		// dd($request->all());
		try {
			DB::beginTransaction();

			if ($request->returned_parts) {
				$delete_parts = JobCardReturnableItem::where('job_card_id', $request->job_card_id)->forceDelete();

				foreach ($request->returned_parts as $key => $parts) {
					if (isset($parts['qty'])) {
						$returnable_part = new JobCardReturnableItem;
						$returnable_part->job_card_id = $request->job_card_id;
						$returnable_part->part_id = $parts['part_id'];
						$returnable_part->item_name = $parts['part_name'];
						$returnable_part->qty = $parts['qty'];
						$returnable_part->created_by_id = Auth::user()->id;
						$returnable_part->created_at = Carbon::now();
						$returnable_part->save();
					}
				}
			} else {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => ['Retuned items cannot be empty!'],
				]);
			}

			DB::commit();
			if (!($request->id)) {
				return response()->json([
					'success' => true,
					'message' => 'Returnable Parts Saved Successfully',
				]);
			} else {
				return response()->json([
					'success' => true,
					'message' => 'Returnable Parts Saved Successfully',
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

	public function viewJobCard($job_card_id) {
		try {
			$job_card = JobCard::find($job_card_id);
			if (!$job_card) {
				return response()->json([
					'success' => false,
					'error' => 'Job Card Not Found!',
				]);
			}
			//issue: relations naming & repeated query
			$job_card_detail = JobCard::with([
				//JOB CARD RELATION
				'bay',
				'outlet',
				'company',
				'business',
				'sacCode',
				'model',
				'segment',
				'status',
				//JOB ORDER RELATION
				'jobOrder',
				'jobOrder.type',
				'jobOrder.quoteType',
				'jobOrder.serviceType',
				'jobOrder.roadTestDoneBy',
				'jobOrder.roadTestPreferedBy',
				'jobOrder.expertDiagnosisReportBy',
				'jobOrder.floorAdviser',
				'jobOrder.status',
				'jobOrder.jobOrderPart',
				'jobOrder.jobOrderPart.status',
				'jobOrder.jobOrderRepairOrder',
				'jobOrder.jobOrderRepairOrder.status',
				'jobOrder.customerVoice',
				'jobOrder.getEomRecomentation',
				'jobOrder.getAdditionalRotAndParts',
				'jobOrder.jobOrderRepairOrder.repairOrderMechanic',
				'jobOrder.jobOrderRepairOrder.repairOrderMechanic.mechanic',
				'jobOrder.jobOrderRepairOrder.repairOrderMechanic.status',
				'jobOrder.gateLog',
				'jobOrder.gateLog.vehicleDetail',
				'jobOrder.gateLog.vehicleDetail.vehicleCurrentOwner',
				'jobOrder.gateLog.vehicleDetail.vehicleCurrentOwner.CustomerDetail',
				'jobOrder.gateLog.vehicleDetail.vehicleCurrentOwner.ownerShipDetail',
				'jobOrder.gateLog.vehicleDetail.vehicleModel',
				'jobOrder.gateLog.driverAttachment',
				'jobOrder.gateLog.kmAttachment',
				'jobOrder.gateLog.vehicleAttachment',
				'jobOrder.vehicleInventoryItem',
				'jobOrder.jobOrderVehicleInspectionItem',
			])
				->find($job_card_id);

			//GET OEM RECOMENTATION AND ADDITIONAL ROT & PARTS
			$oem_recomentaion_labour_amount = 0;
			$additional_rot_and_parts_labour_amount = 0;
			if (!empty($job_card_detail->jobOrder->getEomRecomentation)) {
				// dd($job_card_detail->jobOrder->getEOMRecomentation);
				foreach ($job_card_detail->jobOrder->getEomRecomentation as $oemrecomentation_labour) {
					if ($oemrecomentation_labour['is_recommended_by_oem'] == 1) {
						//SCHEDULED MAINTANENCE
						$oem_recomentaion_labour_amount += $oemrecomentation_labour['amount'];
					}
					if ($oemrecomentation_labour['is_recommended_by_oem'] == 0) {
						//ADDITIONAL ROT AND PARTS
						$additional_rot_and_parts_labour_amount += $oemrecomentation_labour['amount'];
					}
				}
			}

			$oem_recomentaion_part_amount = 0;
			$additional_rot_and_parts_part_amount = 0;
			if (!empty($job_card_detail->jobOrder->getAdditionalRotAndParts)) {
				foreach ($job_card_detail->jobOrder->getAdditionalRotAndParts as $oemrecomentation_labour) {
					if ($oemrecomentation_labour['is_oem_recommended'] == 1) {
						//SCHEDULED MAINTANENCE
						$oem_recomentaion_part_amount += $oemrecomentation_labour['amount'];
					}
					if ($oemrecomentation_labour['is_oem_recommended'] == 0) {
						//ADDITIONAL ROT AND PARTS
						$additional_rot_and_parts_part_amount += $oemrecomentation_labour['amount'];
					}
				}
			}
			//OEM RECOMENTATION LABOUR AND PARTS AND SUB TOTAL
			$job_card_detail->oem_recomentation_labour_amount = $oem_recomentaion_labour_amount;
			$job_card_detail->oem_recomentation_part_amount = $oem_recomentaion_part_amount;
			$job_card_detail->oem_recomentation_sub_total = $oem_recomentaion_labour_amount + $oem_recomentaion_part_amount;

			//ADDITIONAL ROT & PARTS LABOUR AND PARTS AND SUB TOTAL
			$job_card_detail->additional_rot_parts_labour_amount = $additional_rot_and_parts_labour_amount;
			$job_card_detail->additional_rot_parts_part_amount = $additional_rot_and_parts_part_amount;
			$job_card_detail->additional_rot_parts_sub_total = $additional_rot_and_parts_labour_amount + $additional_rot_and_parts_part_amount;

			//TOTAL ESTIMATE
			$job_card_detail->total_estimate_labour_amount = $oem_recomentaion_labour_amount + $additional_rot_and_parts_labour_amount;
			$job_card_detail->total_estimate_parts_amount = $oem_recomentaion_part_amount + $additional_rot_and_parts_part_amount;
			$job_card_detail->total_estimate_amount = (($oem_recomentaion_labour_amount + $additional_rot_and_parts_labour_amount) + ($oem_recomentaion_part_amount + $additional_rot_and_parts_part_amount));

			$job_card_detail->gate_log_attachment_url = storage_path('app/public/gigo/gate_in/attachments/');

			return response()->json([
				'success' => true,
				'job_card_detail' => $job_card_detail,
			]);
		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	//TIME LOG
	public function getJobCardTimeLog(Request $request) {
		// dd($request->all());
		try {
			$job_card_time_log = JobCard::with([
				'status',
				'jobOrder',
				// 'jobOrder.gateLog',
				// 'jobOrder.gateLog.vehicleDetail',
				// 'jobOrder.gateLog.vehicleDetail.vehicleModel',
				'jobOrder.jobOrderRepairOrders',
				'jobOrder.jobOrderRepairOrders.status',
				'jobOrder.jobOrderRepairOrders.repairOrderMechanics',
				'jobOrder.jobOrderRepairOrders.repairOrderMechanics.mechanic',
				'jobOrder.jobOrderRepairOrders.repairOrderMechanics.status',
				'jobOrder.jobOrderRepairOrders.repairOrderMechanics.mechanicTimeLogs',
				'jobOrder.jobOrderRepairOrders.repairOrderMechanics.mechanicTimeLogs.status',
			])
				->find($request->id);

			if (!$job_card_time_log) {
				return response()->json([
					'success' => false,
					'error' => 'Job Card Not found!',
				]);
			}

			$total_duration = 0;
			$overall_total_duration = [];
			//REPAIR ORDER BASED TIME LOG ONLY FOR WEB
			if (!empty($request->job_order_repair_order_id)) {
				$job_order_repair_order = JobOrderRepairOrder::with([
					'repairOrder',
					'repairOrderMechanics',
					'repairOrderMechanics.mechanic',
					'repairOrderMechanics.status',
					'repairOrderMechanics.mechanicTimeLogs',
					'repairOrderMechanics.mechanicTimeLogs.status',
					'repairOrderMechanics.mechanicTimeLogs.reason',
				])
					->find($request->job_order_repair_order_id);

				if (!$job_order_repair_order) {
					return response()->json([
						'success' => false,
						'error' => 'Job Order Repair Order Not found!',
					]);
				}

				if (!empty($job_order_repair_order->repairOrderMechanics)) {
					foreach ($job_order_repair_order->repairOrderMechanics as $repair_order_mechanic) {
						$duration = [];
						if ($repair_order_mechanic->mechanicTimeLogs) {
							$duration_difference = []; //FOR START TIME AND END TIME DIFFERENCE
							foreach ($repair_order_mechanic->mechanicTimeLogs as $key2 => $mechanic_time_log) {
								// PERTICULAR MECHANIC DATE
								$mechanic_time_log->date = date('d/m/Y', strtotime($mechanic_time_log->start_date_time));

								//PERTICULAR MECHANIC STATR TIME
								$mechanic_time_log->start_time = date('h:i a', strtotime($mechanic_time_log->start_date_time));

								//PERTICULAR MECHANIC END TIME
								$mechanic_time_log->end_time = $mechanic_time_log->end_date_time ? date('h:i a', strtotime($mechanic_time_log->end_date_time)) : '-';

								if ($mechanic_time_log->end_date_time) {
									// dump('if');
									$time1 = strtotime($mechanic_time_log->start_date_time);
									$time2 = strtotime($mechanic_time_log->end_date_time);
									if ($time2 < $time1) {
										$time2 += 86400;
									}

									//TIME DURATION DIFFERENCE PERTICULAR MECHANIC DURATION
									$duration_difference[] = date("H:i:s", strtotime("00:00") + ($time2 - $time1));

									//TOTAL DURATION FOR PARTICLUAR EMPLOEE
									$duration[] = date("H:i:s", strtotime("00:00") + ($time2 - $time1));

									//OVERALL TOTAL WORKING DURATION
									$overall_total_duration[] = date("H:i:s", strtotime("00:00") + ($time2 - $time1));

									$mechanic_time_log->duration_difference = sum_mechanic_duration($duration_difference);
									unset($duration_difference);
								} else {
									//TOTAL DURATION FOR PARTICLUAR EMPLOEE
									$duration[] = '-';
								}
							}
							//TOTAL WORKING HOURS PER EMPLOYEE
							$total_duration = sum_mechanic_duration($duration);
							$total_duration = date("H:i:s", strtotime($total_duration));
							// dd($total_duration);
							$format_change = explode(':', $total_duration);
							$hour = $format_change[0] . 'h';
							$minutes = $format_change[1] . 'm';
							$seconds = $format_change[2] . 's';
							$repair_order_mechanic['total_duration'] = $hour . ' ' . $minutes; // . ' ' . $seconds;
							unset($duration);
						} else {
							$repair_order_mechanic['total_duration'] = '';
						}
					}
				}
			} else {
				//OVERALL TIME LOG ONLY FOR ANDROID APP
				if (!empty($job_card_time_log->jobOrder->jobOrderRepairOrders)) {
					foreach ($job_card_time_log->jobOrder->jobOrderRepairOrders as $key => $job_card_repair_order) {
						$duration = [];
						$job_card_repair_order->assigned_to_employee_count = count($job_card_repair_order->repairOrderMechanics);
						if ($job_card_repair_order->repairOrderMechanics) {
							foreach ($job_card_repair_order->repairOrderMechanics as $key1 => $repair_order_mechanic) {
								if ($repair_order_mechanic->mechanicTimeLogs) {
									$duration_difference = []; //FOR START TIME AND END TIME DIFFERENCE
									foreach ($repair_order_mechanic->mechanicTimeLogs as $key2 => $mechanic_time_log) {
										// PERTICULAR MECHANIC DATE
										$mechanic_time_log->date = date('d/m/Y', strtotime($mechanic_time_log->start_date_time));

										//PERTICULAR MECHANIC STATR TIME
										$mechanic_time_log->start_time = date('h:i a', strtotime($mechanic_time_log->start_date_time));

										//PERTICULAR MECHANIC END TIME
										$mechanic_time_log->end_time = $mechanic_time_log->end_date_time ? date('h:i a', strtotime($mechanic_time_log->end_date_time)) : '-';

										if ($mechanic_time_log->end_date_time) {
											// dump('if');
											$time1 = strtotime($mechanic_time_log->start_date_time);
											$time2 = strtotime($mechanic_time_log->end_date_time);
											if ($time2 < $time1) {
												$time2 += 86400;
											}

											//TIME DURATION DIFFERENCE PERTICULAR MECHANIC DURATION
											$duration_difference[] = date("H:i:s", strtotime("00:00") + ($time2 - $time1));

											//TOTAL DURATION FOR PARTICLUAR EMPLOEE
											$duration[] = date("H:i:s", strtotime("00:00") + ($time2 - $time1));

											//OVERALL TOTAL WORKING DURATION
											$overall_total_duration[] = date("H:i:s", strtotime("00:00") + ($time2 - $time1));

											$mechanic_time_log->duration_difference = sum_mechanic_duration($duration_difference);
											unset($duration_difference);
										} else {
											//TOTAL DURATION FOR PARTICLUAR EMPLOEE
											$duration[] = '-';
										}
									}
								}
							}
							//TOTAL WORKING HOURS PER EMPLOYEE
							$total_duration = sum_mechanic_duration($duration);
							$total_duration = date("H:i:s", strtotime($total_duration));
							// dd($total_duration);
							$format_change = explode(':', $total_duration);
							$hour = $format_change[0] . 'h';
							$minutes = $format_change[1] . 'm';
							$seconds = $format_change[2] . 's';
							$job_card_repair_order['total_duration'] = $hour . ' ' . $minutes . ' ' . $seconds;
							unset($duration);
						} else {
							$job_card_repair_order['total_duration'] = '';
						}

					}
				}
			}

			//OVERALL WORKING HOURS
			$overall_total_duration = sum_mechanic_duration($overall_total_duration);
			// $overall_total_duration = date("H:i:s", strtotime($overall_total_duration));
			$format_change = explode(':', $overall_total_duration);
			$hour = $format_change[0] . 'h';
			$minutes = $format_change[1] . 'm';
			$seconds = $format_change[2] . 's';
			if (!empty($request->job_order_repair_order_id)) {
				$job_order_repair_order['overall_total_duration'] = $hour . ' ' . $minutes; // . ' ' . $seconds;
			} else {
				$job_card_time_log->jobOrder['overall_total_duration'] = $hour . ' ' . $minutes; // . ' ' . $seconds;
			}

			unset($overall_total_duration);

			$job_card_time_log->no_of_ROT = count($job_card_time_log->jobOrder->jobOrderRepairOrders);

			if (!empty($request->job_order_repair_order_id)) {
				return response()->json([
					'success' => true,
					'job_order_repair_order_time_log' => $job_order_repair_order,
				]);
			} else {
				return response()->json([
					'success' => true,
					'job_card_time_log' => $job_card_time_log,
				]);
			}

		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	//JOB CARD GATE PASS VIEW
	public function viewMeterialGatePass(Request $request) {
		// dd($request->all());
		try {
			$view_metrial_gate_pass = JobCard::with([
				'status',
				'jobOrder',
				'jobOrder.type',
				'jobOrder.vehicle',
				'jobOrder.vehicle.model',
				'gatePasses' => function ($query) {
					$query->where('gate_passes.type_id', 8281); //MATRIAL GATE PASS
				},
				'gatePasses.type',
				'gatePasses.status',
				'gatePasses.gatePassDetail',
				'gatePasses.gatePassDetail.vendorType',
				'gatePasses.gatePassDetail.vendor',
				'gatePasses.gatePassDetail.vendor.primaryAddress',
				'gatePasses.gatePassItems',
				'gatePasses.gatePassItems.attachment',
			])
				->find($request->id);

			if (!$view_metrial_gate_pass) {
				return response()->json([
					'success' => false,
					'error' => 'Job Card Not found!',
				]);
			}

			$job_order = JobOrder::with([
				'vehicle',
				'vehicle.model',
				'vehicle.status',
				'status',
			])
				->find($view_metrial_gate_pass->job_order_id);

			//GET ITEM COUNT
			if (!empty($view_metrial_gate_pass->gatePasses)) {
				foreach ($view_metrial_gate_pass->gatePasses as $gate_pass) {
					if (!empty($gate_pass->gatePassItems)) {
						$view_metrial_gate_pass->no_of_items = count($gate_pass->gatePassItems);
					} else {
						$view_metrial_gate_pass->no_of_items = 0;
					}
				}
			}

			return response()->json([
				'success' => true,
				'view_metrial_gate_pass' => $view_metrial_gate_pass,
				'job_order' => $job_order,
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

	//JOB CARD Vendor Details
	public function getMeterialGatePassData(Request $request) {
		// dd($request->all());
		try {
			$job_card = JobCard::with([
				'status',
				'jobOrder',
				'jobOrder.type',
				'jobOrder.vehicle',
				'jobOrder.vehicle.model',
			])
				->find($request->id);

			if (!$job_card) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Job Card Not found!',
					],
				]);

			}

			if (isset($request->gate_pass_id)) {
				$gate_pass = GatePass::with([
					'gatePassDetail',
					'gatePassDetail.vendorType',
					'gatePassDetail.vendor',
					'gatePassDetail.vendor.primaryAddress',
					'gatePassItems.attachment',
				])
					->find($request->gate_pass_id);

				if (!$gate_pass) {
					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => [
							'Material Gate Pass Not found!',
						],
					]);
				}
			} else {
				$gate_pass = new GatePass();
				$gate_pass->gate_pass_detail = new GatePassDetail();
				$gate_pass->gate_pass_detail->vendor = new Vendor();
				$gate_pass->gate_pass_items = new GatePassItem();
			}

			return response()->json([
				'success' => true,
				'gate_pass' => $gate_pass,
				'job_card' => $job_card,
				'attachement_path' => url('app/public/gigo/material_gate_pass/attachments/'),
			]);

		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Error!',
				'errors' => [
					'Error : ' . $e->getMessage() . '. Line : ' . $e->getLine() . '. File : ' . $e->getFile(),
				],
			]);
		}
	}

	//Material GatePass Item Save
	public function saveMaterialGatePass(Request $request) {
		// dd($request->all());
		try {
			$validator = Validator::make($request->all(), [
				'job_card_id' => [
					'required',
					'integer',
					'exists:job_cards,id',
				],
				'vendor_type_id' => [
					'required',
					'integer',
					'exists:configs,id',
				],
				'vendor_id' => [
					'required',
					'integer',
					'exists:vendors,id',
				],
				'work_order_no' => [
					'required',
					'string',
				],
				'work_order_description' => [
					'required',
				],
				'item_details.*.item_description' => [
					'required',
					'min:3',
					'max:191',
				],
				'item_details.*.name' => [
					'required',
					'min:3',
					'max:191',
				],
				'item_details.*.item_make' => [
					'nullable',
					'min:3',
					'max:191',
				],
				'item_details.*.item_model' => [
					'nullable',
					'min:3',
					'max:191',
				],
				'item_details.*.item_serial_no' => [
					'required',
					'min:3',
					'max:191',
				],
				'item_details.*.qty' => [
					'required',
				],
				'item_details.*.remarks' => [
					'required',
					'min:3',
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

			$gate_pass = GatePass::firstOrNew([
				'id' => $request->gate_pass_id,
			]);

			$gate_pass->type_id = 8281; //Material Gate Pass
			$gate_pass->status_id = 8300; //Gate Out Pending
			$gate_pass->company_id = Auth::user()->company_id;
			$gate_pass->fill($request->all());
			$gate_pass->save();

			if (!$request->gate_pass_id) {
				//GENERATE MATERIAl GATE PASS NUMBER
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
				$branch = Outlet::where('id', Auth::user()->employee->outlet_id)->first();

				$generateNumber = SerialNumberGroup::generateNumber(24, $financial_year->id, $branch->state_id, $branch->id);
				if (!$generateNumber['success']) {
					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => [
							'No Material Gate Pass Serial number found for FY : ' . $financial_year->from . ', State : ' . $branch->state->code . ', Outlet : ' . $branch->code,
						],
					]);
				}

				$error_messages_1 = [
					'number.required' => 'Serial number is required',
					'number.unique' => 'Serial number is already taken',
				];

				$validator_1 = Validator::make($generateNumber, [
					'number' => [
						'required',
						'unique:gate_passes,number,' . $gate_pass->id . ',id,company_id,' . Auth::user()->company_id,
					],
				], $error_messages_1);

				if ($validator_1->fails()) {
					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => $validator_1->errors()->all(),
					]);
				}
				$gate_pass->number = $generateNumber['number'];
				$gate_pass->save();
			}

			//SAVE GATE PASS DETAIL
			$gate_pass_detail = GatePassDetail::firstOrNew([
				'gate_pass_id' => $gate_pass->id,
			]);
			$gate_pass_detail->vendor_type_id = $request->vendor_type_id;
			$gate_pass_detail->vendor_id = $request->vendor_id;
			$gate_pass_detail->work_order_no = $request->work_order_no;
			$gate_pass_detail->vendor_contact_no = $request->vendor_contact_no;
			$gate_pass_detail->work_order_description = $request->work_order_description;
			$gate_pass_detail->created_by_id = Auth::user()->id;
			$gate_pass_detail->save();

			if (!empty($request->gate_pass_item_removal_id)) {
				$gate_pass_item_removal_id = json_decode($request->gate_pass_item_removal_id, true);
				GatePassItem::whereIn('id', $gate_pass_item_removal_id)->delete();

				$attachment_remove = json_decode($request->gate_pass_item_removal_id, true);
				Attachment::where('entity_id', $attachment_remove)->where('attachment_of_id', 231)->delete();
			}

			if (!empty($request->attachment_removal_ids)) {
				$attachment_remove = json_decode($request->attachment_removal_ids, true);
				Attachment::whereIn('id', $attachment_remove)->delete();
			}

			//CREATE DIRECTORY TO STORAGE PATH
			$attachment_path = storage_path('app/public/gigo/material_gate_pass/attachments/');
			Storage::makeDirectory($attachment_path, 0777);

			if (isset($request->item_details)) {
				foreach ($request->item_details as $key => $item_detail) {
					$item_detail['gate_pass_id'] = $gate_pass->id;
					$validator1 = Validator::make($item_detail, [
						'item_serial_no' => [
							'unique:gate_pass_items,item_serial_no,' . $item_detail['id'] . ',id,gate_pass_id,' . $item_detail['gate_pass_id'] . ',name,' . $item_detail['name'],
						],
					]);

					if ($validator1->fails()) {
						return response()->json([
							'success' => false,
							'error' => 'Validation Error',
							'errors' => $validator1->errors()->all(),
						]);
					}
					$gate_pass_item = GatePassItem::firstOrNew([
						'id' => $item_detail['id'],
					]);
					$gate_pass_item->fill($item_detail);
					$gate_pass_item->save();

					//SAVE MATERIAL OUTWARD ATTACHMENT
					if (!empty($item_detail['material_outward_attachment'])) {
						foreach ($item_detail['material_outward_attachment'] as $key => $material_outward_attachment) {
							$image = $material_outward_attachment;
							$file_name = $image->getClientOriginalName();

							$name = $gate_pass_item->id . '_' . rand(10, 1000) . $file_name;
							$name = str_replace(' ', '-', $name); // Replaces all spaces with hyphens.
							$material_outward_attachment->move(storage_path('app/public/gigo/material_gate_pass/attachments/'), $name);
							$attachement = new Attachment;
							$attachement->entity_id = $gate_pass_item->id;
							$attachement->attachment_of_id = 231; //Material Gate Pass
							$attachement->attachment_type_id = 238; //Material Gate Pass
							$attachement->name = $name;
							$attachement->save();
						}
					}
				}
			}

			DB::commit();

			return response()->json([
				'success' => true,
				'message' => 'Material Gate Pass Item Saved Successfully!!',
			]);

		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Error!',
				'errors' => [
					'Error : ' . $e->getMessage() . '. Line : ' . $e->getLine() . '. File : ' . $e->getFile(),
				],
			]);
		}
	}

	public function viewSplitOrderDetails(Request $request) {
		// dd($request->all());
		try {
			$job_card = JobCard::with([
				'jobOrder',
				'jobOrder.serviceType',
				'jobOrder.type',
				'jobOrder.vehicle',
				'jobOrder.vehicle.model',
				'jobOrder.jobOrderRepairOrders' => function ($q) {
					$q->whereNull('removal_reason_id');
				},
				'jobOrder.jobOrderRepairOrders.repairOrder',
				'jobOrder.jobOrderRepairOrders.repairOrder.repairOrderType',
				'jobOrder.jobOrderRepairOrders.repairOrder.taxCode',
				'jobOrder.jobOrderRepairOrders.repairOrder.taxCode.taxes',
				'jobOrder.jobOrderParts' => function ($q) {
					$q->whereNull('removal_reason_id');
				},
				'jobOrder.jobOrderParts.part',
				'jobOrder.jobOrderParts.part.taxCode',
				'jobOrder.jobOrderParts.part.taxCode.taxes',
				'status',
			])
				->find($request->id);

			if (!$job_card) {
				return response()->json([
					'success' => false,
					'error' => 'Job Card Not found!',
				]);
			}

			$job_card['creation_date'] = date('d/m/Y', strtotime($job_card->created_at));
			$taxes = Tax::get();

			$parts_amount = 0;
			$labour_amount = 0;
			$total_amount = 0;

			//dd($job_card->jobOrder->vehicle->currentOwner);

			//Check which tax applicable for customer
			if ($job_card->jobOrder->outlet->state_id == $job_card->jobOrder->vehicle->currentOwner->customer->primaryAddress->state_id) {
				$tax_type = 1160; //Within State
			} else {
				$tax_type = 1161; //Inter State
			}

			//Count Tax Type
			$taxes = Tax::get();

			$unassigned_labour_count = 0;
			$unassigned_part_count = 0;

			$labour_details = array();
			if ($job_card->jobOrder->jobOrderRepairOrders) {
				foreach ($job_card->jobOrder->jobOrderRepairOrders as $key => $labour) {
					$total_amount = 0;
					$labour_details[$key]['id'] = $labour->id;
					$labour_details[$key]['repair_order_id'] = $labour->repairOrder->id;
					$labour_details[$key]['name'] = $labour->repairOrder->code . ' | ' . $labour->repairOrder->name;
					$labour_details[$key]['hsn_code'] = $labour->repairOrder->taxCode ? $labour->repairOrder->taxCode->code : '-';
					$labour_details[$key]['qty'] = $labour->qty;
					$labour_details[$key]['amount'] = $labour->amount;
					$labour_details[$key]['is_free_service'] = $labour->is_free_service;
					$labour_details[$key]['split_order_type_id'] = $labour->split_order_type_id;
					$tax_amount = 0;
					$tax_values = array();
					if ($labour->repairOrder->taxCode) {
						foreach ($labour->repairOrder->taxCode->taxes as $tax_key => $value) {
							$percentage_value = 0;
							if ($value->type_id == $tax_type) {
								$percentage_value = ($labour->amount * $value->pivot->percentage) / 100;
								$percentage_value = number_format((float) $percentage_value, 2, '.', '');
							}
							$tax_values[$tax_key]['tax_value'] = $percentage_value;
							$tax_amount += $percentage_value;
						}
					} else {
						for ($i = 0; $i < count($taxes); $i++) {
							$tax_values[$i]['tax_value'] = 0.00;
						}
					}

					$labour_details[$key]['tax_values'] = $tax_values;

					$total_amount = $tax_amount + $labour->amount;
					$total_amount = number_format((float) $total_amount, 2, '.', '');

					$labour_details[$key]['tax_amount'] = number_format((float) $tax_amount, 2, '.', '');
					$labour_details[$key]['total_amount'] = $total_amount;

					if ($labour->split_order_type_id == null) {
						$unassigned_labour_count++;
					}
				}
			}

			$part_details = array();
			if ($job_card->jobOrder->jobOrderParts) {
				foreach ($job_card->jobOrder->jobOrderParts as $key => $parts) {
					$total_amount = 0;
					$part_details[$key]['id'] = $parts->id;
					$part_details[$key]['part_id'] = $parts->part->id;
					$part_details[$key]['name'] = $parts->part->code . ' | ' . $parts->part->name;
					$part_details[$key]['hsn_code'] = $parts->part->taxCode ? $parts->part->taxCode->code : '-';
					$part_details[$key]['qty'] = $parts->qty;
					$part_details[$key]['rate'] = $parts->rate;
					$part_details[$key]['amount'] = $parts->amount;
					$part_details[$key]['is_free_service'] = $parts->is_free_service;
					$part_details[$key]['split_order_type_id'] = $parts->split_order_type_id;
					$tax_amount = 0;
					$tax_values = array();
					if ($parts->part->taxCode) {
						foreach ($parts->part->taxCode->taxes as $tax_key => $value) {
							$percentage_value = 0;
							if ($value->type_id == $tax_type) {
								$percentage_value = ($parts->amount * $value->pivot->percentage) / 100;
								$percentage_value = number_format((float) $percentage_value, 2, '.', '');
							}
							$tax_values[$tax_key]['tax_value'] = $percentage_value;
							$tax_amount += $percentage_value;
						}
					} else {
						for ($i = 0; $i < count($taxes); $i++) {
							$tax_values[$i]['tax_value'] = 0.00;
						}
					}

					$part_details[$key]['tax_values'] = $tax_values;

					$total_amount = $tax_amount + $parts->amount;
					$total_amount = number_format((float) $total_amount, 2, '.', '');

					$part_details[$key]['total_amount'] = $total_amount;
					$part_details[$key]['tax_amount'] = number_format((float) $tax_amount, 2, '.', '');

					if ($parts->split_order_type_id == null) {
						$unassigned_part_count++;
					}
				}
			}

			$total_amount = $parts_amount + $labour_amount;

			$unassigned_part_amount = 0;
			foreach ($part_details as $key => $part) {
				if (!$part['split_order_type_id']) {
					// $unassigned_part_count += 1;
					$unassigned_part_amount += $part['total_amount'];
				}
			}
			$unassigned_labour_amount = 0;
			foreach ($labour_details as $key => $labour) {
				if (!$labour['split_order_type_id']) {
					// $unassigned_labour_count += 1;
					$unassigned_labour_amount += $labour['total_amount'];
				}
			}
			$unassigned_total_count = $unassigned_labour_count + $unassigned_part_count;
			$unassigned_total_amount = $unassigned_labour_amount + $unassigned_part_amount;

			$extras = [
				'split_order_types' => SplitOrderType::get(),
				'taxes' => $taxes,
			];

			return response()->json([
				'success' => true,
				'job_card' => $job_card,
				'extras' => $extras,
				'part_details' => $part_details,
				'labour_details' => $labour_details,
				'parts_total_amount' => number_format($parts_amount, 2),
				'labour_total_amount' => number_format($labour_amount, 2),
				'total_amount' => number_format($total_amount, 2),
				'unassigned_total_amount' => number_format($unassigned_total_amount, 2),
				'unassigned_total_count' => $unassigned_total_count,
			]);

		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	public function splitOrderUpdate(Request $request) {
		// dd($request->all());
		try {
			$validator = Validator::make($request->all(), [
				'split_order_type_id' => [
					'required',
					// 'exists:split_order_types,id',
					// 'integer',
				],
			]);

			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				]);
			}

			if ($request->type == 'Part') {
				$job_order_part = JobOrderPart::find($request->part_id);
				if (!$job_order_part) {
					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => ['Job Order Part Not Found!'],
					]);
				}
				$job_order_part->split_order_type_id = $request->split_order_type_id == '-1' ? NULL : $request->split_order_type_id;
				$job_order_part->updated_at = Carbon::now();
				$job_order_part->updated_by_id = Auth::user()->id;
				$job_order_part->save();
			} else {
				$job_order_repair_order = JobOrderRepairOrder::find($request->labour_id);
				if (!$job_order_repair_order) {
					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => ['Job Order Repair Order Not Found!'],
					]);
				}
				$job_order_repair_order->split_order_type_id = $request->split_order_type_id == '-1' ? NULL : $request->split_order_type_id;
				$job_order_repair_order->updated_at = Carbon::now();
				$job_order_repair_order->updated_by_id = Auth::user()->id;
				$job_order_repair_order->save();
			}
			return response()->json([
				'success' => true,
				'type_id' => $request->split_order_type_id,
				'message' => 'Split Order Type Update Successfully',
			]);
		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	public function viewBillDetails(Request $request) {
		// dd($request->all());
		try {
			$job_card = JobCard::with([
				'jobOrder',
				'jobOrder.serviceType',
				'jobOrder.type',
				'jobOrder.vehicle',
				'jobOrder.vehicle.model',
				'jobOrder.jobOrderRepairOrders' => function ($q) {
					$q->whereNull('removal_reason_id');
				},
				'jobOrder.jobOrderRepairOrders.repairOrder',
				'jobOrder.jobOrderRepairOrders.repairOrder.repairOrderType',
				'jobOrder.jobOrderRepairOrders.repairOrder.taxCode',
				'jobOrder.jobOrderRepairOrders.repairOrder.taxCode.taxes',
				'jobOrder.jobOrderParts' => function ($q) {
					$q->whereNull('removal_reason_id');
				},
				'jobOrder.jobOrderParts.part',
				'jobOrder.jobOrderParts.part.taxCode',
				'jobOrder.jobOrderParts.part.taxCode.taxes',
				'status',
			])
				->find($request->id);

			if (!$job_card) {
				return response()->json([
					'success' => false,
					'error' => 'Job Card Not found!',
				]);
			}

			$customer_paid_type_id = SplitOrderType::where('paid_by_id', '10013')->pluck('id')->toArray();

			$job_card['creation_date'] = date('d/m/Y', strtotime($job_card->created_at));

			//Check which tax applicable for customer
			if ($job_card->jobOrder->outlet->state_id == $job_card->jobOrder->vehicle->currentOwner->customer->primaryAddress->state_id) {
				$tax_type = 1160; //Within State
			} else {
				$tax_type = 1161; //Inter State
			}

			//Count Tax Type
			$taxes = Tax::get();

			$labour_details = array();
			if ($job_card->jobOrder->jobOrderRepairOrders) {
				$labour_total_amount = 0;
				foreach ($job_card->jobOrder->jobOrderRepairOrders as $key => $labour) {
					$tax_values = array();
					if (in_array($labour->split_order_type_id, $customer_paid_type_id)) {
						$labour_sub_total = 0;
						$total_amount = 0;
						$labour_details[$key]['id'] = $labour->id;
						$labour_details[$key]['repair_order_id'] = $labour->repairOrder->id;
						$labour_details[$key]['name'] = $labour->repairOrder->code . ' | ' . $labour->repairOrder->name;
						$labour_details[$key]['hsn_code'] = $labour->repairOrder->taxCode ? $labour->repairOrder->taxCode->code : '-';
						$labour_details[$key]['qty'] = $labour->qty;
						$labour_details[$key]['amount'] = $labour->amount;
						$labour_details[$key]['is_free_service'] = $labour->is_free_service;
						$labour_details[$key]['split_order_type_id'] = $labour->split_order_type_id;
						$tax_amount = 0;
						$labour_details[$key]['tax_code'] = $labour->repairOrder->taxCode;

						$tax_values = array();
						if ($labour->is_free_service != 1) {
							if ($labour->repairOrder->taxCode) {
								foreach ($labour->repairOrder->taxCode->taxes as $tax_key => $value) {
									$percentage_value = 0;
									if ($value->type_id == $tax_type) {
										$percentage_value = ($labour->amount * $value->pivot->percentage) / 100;
										$percentage_value = number_format((float) $percentage_value, 2, '.', '');
									}
									$tax_values[$tax_key]['tax_value'] = $percentage_value;
									$tax_amount += $percentage_value;
								}
							} else {
								for ($i = 0; $i < count($taxes); $i++) {
									$tax_values[$i]['tax_value'] = 0.00;
								}
							}

							$total_amount = $tax_amount + $labour->amount;
							$total_amount = number_format((float) $total_amount, 2, '.', '');

							$labour_details[$key]['tax_amount'] = number_format((float) $tax_amount, 2, '.', '');
							$labour_details[$key]['total_amount'] = $total_amount;

						} else {
							$labour_details[$key]['amount'] = 0;

							for ($i = 0; $i < count($taxes); $i++) {
								$tax_values[$i]['tax_value'] = 0.00;
							}

							$total_amount = 0;
							$labour_details[$key]['tax_amount'] = 0;
							$labour_details[$key]['total_amount'] = 0;
						}

						$labour_details[$key]['tax_values'] = $tax_values;

						$labour_total_amount += $total_amount;

					} else {

						$labour_sub_total = 0;
						$total_amount = 0;
						$labour_details[$key]['id'] = $labour->id;
						$labour_details[$key]['repair_order_id'] = $labour->repairOrder->id;
						$labour_details[$key]['name'] = $labour->repairOrder->code . ' | ' . $labour->repairOrder->name;
						$labour_details[$key]['hsn_code'] = $labour->repairOrder->taxCode ? $labour->repairOrder->taxCode->code : '-';
						$labour_details[$key]['qty'] = $labour->qty;
						$labour_details[$key]['amount'] = $labour->amount;
						$labour_details[$key]['is_free_service'] = $labour->is_free_service;
						$labour_details[$key]['split_order_type_id'] = $labour->split_order_type_id;
						$tax_amount = 0;
						$labour_details[$key]['tax_code'] = $labour->repairOrder->taxCode;
						$tax_values = array();
						if ($labour->repairOrder->taxCode) {
							foreach ($labour->repairOrder->taxCode->taxes as $tax_key => $value) {
								$percentage_value = 0;
								if ($value->type_id == $tax_type) {
									$percentage_value = ($labour->amount * $value->pivot->percentage) / 100;
									$percentage_value = number_format((float) $percentage_value, 2, '.', '');
								}
								$tax_values[$tax_key]['tax_value'] = $percentage_value;
								$tax_amount += $percentage_value;
							}
						} else {
							for ($i = 0; $i < count($taxes); $i++) {
								$tax_values[$i]['tax_value'] = 0.00;
							}
						}
						$labour_details[$key]['tax_values'] = $tax_values;

						$total_amount = $tax_amount + $labour->amount;
						$total_amount = number_format((float) $total_amount, 2, '.', '');

						$labour_details[$key]['tax_amount'] = number_format((float) $tax_amount, 2, '.', '');
						$labour_details[$key]['total_amount'] = $total_amount;

						$labour_total_amount += $total_amount;
					}
				}
				$job_card['labour_total_amount'] = $labour_total_amount;
			}
			// dd($labour_details);

			$part_details = array();
			if ($job_card->jobOrder->jobOrderParts) {
				$parts_total_amount = 0;
				foreach ($job_card->jobOrder->jobOrderParts as $key => $parts) {
					if (in_array($parts->split_order_type_id, $customer_paid_type_id)) {
						$part_sub_total = 0;
						$total_amount = 0;
						$part_details[$key]['id'] = $parts->id;
						$part_details[$key]['part_id'] = $parts->part->id;
						$part_details[$key]['name'] = $parts->part->code . ' | ' . $parts->part->name;
						$part_details[$key]['hsn_code'] = $parts->part->taxCode ? $parts->part->taxCode->code : '-';
						$part_details[$key]['qty'] = $parts->qty;
						$part_details[$key]['rate'] = $parts->rate;
						$part_details[$key]['amount'] = $parts->amount;
						$part_details[$key]['is_free_service'] = $parts->is_free_service;
						$part_details[$key]['split_order_type_id'] = $parts->split_order_type_id;
						$tax_amount = 0;
						$part_details[$key]['tax_code'] = $parts->part->taxCode;

						$tax_values = array();

						if ($parts->is_free_service != 1) {
							if ($parts->part->taxCode) {
								foreach ($parts->part->taxCode->taxes as $tax_key => $value) {
									$percentage_value = 0;
									if ($value->type_id == $tax_type) {
										$percentage_value = ($parts->amount * $value->pivot->percentage) / 100;
										$percentage_value = number_format((float) $percentage_value, 2, '.', '');
									}
									$tax_values[$tax_key]['tax_value'] = $percentage_value;
									$tax_amount += $percentage_value;
								}
							} else {
								for ($i = 0; $i < count($taxes); $i++) {
									$tax_values[$i]['tax_value'] = 0.00;
								}
							}
							$total_amount = $tax_amount + $parts->amount;
							$total_amount = number_format((float) $total_amount, 2, '.', '');

							$part_details[$key]['total_amount'] = $total_amount;
							$part_details[$key]['tax_amount'] = number_format((float) $tax_amount, 2, '.', '');
						} else {
							$part_details[$key]['amount'] = 0;

							for ($i = 0; $i < count($taxes); $i++) {
								$tax_values[$i]['tax_value'] = 0.00;
							}
							$total_amount = 0;
							$part_details[$key]['total_amount'] = $total_amount;
							$part_details[$key]['tax_amount'] = number_format((float) $tax_amount, 2, '.', '');
						}

						$part_details[$key]['tax_values'] = $tax_values;

					} else {
						$part_sub_total = 0;
						$total_amount = 0;
						$part_details[$key]['id'] = $parts->id;
						$part_details[$key]['part_id'] = $parts->part->id;
						$part_details[$key]['name'] = $parts->part->code . ' | ' . $parts->part->name;
						$part_details[$key]['hsn_code'] = $parts->part->taxCode ? $parts->part->taxCode->code : '-';
						$part_details[$key]['qty'] = $parts->qty;
						$part_details[$key]['rate'] = $parts->rate;
						$part_details[$key]['amount'] = $parts->amount;
						$part_details[$key]['is_free_service'] = $parts->is_free_service;
						$part_details[$key]['split_order_type_id'] = $parts->split_order_type_id;
						$tax_amount = 0;
						$part_details[$key]['tax_code'] = $parts->part->taxCode;
						$tax_values = array();
						if ($parts->part->taxCode) {
							foreach ($parts->part->taxCode->taxes as $tax_key => $value) {
								$percentage_value = 0;
								if ($value->type_id == $tax_type) {
									$percentage_value = ($parts->amount * $value->pivot->percentage) / 100;
									$percentage_value = number_format((float) $percentage_value, 2, '.', '');
								}
								$tax_values[$tax_key]['tax_value'] = $percentage_value;
								$tax_amount += $percentage_value;
							}
						} else {
							for ($i = 0; $i < count($taxes); $i++) {
								$tax_values[$i]['tax_value'] = 0.00;
							}
						}

						$part_details[$key]['tax_values'] = $tax_values;

						$total_amount = $tax_amount + $parts->amount;
						$total_amount = number_format((float) $total_amount, 2, '.', '');

						$part_details[$key]['total_amount'] = $total_amount;
						$part_details[$key]['tax_amount'] = number_format((float) $tax_amount, 2, '.', '');

						$parts_total_amount += $total_amount;
					}
				}
				$job_card['parts_total_amount'] = $parts_total_amount;
			}

			$extras = [
				'split_order_types' => SplitOrderType::get(),
				'taxes' => $taxes,
			];

			return response()->json([
				'success' => true,
				'job_card' => $job_card,
				'extras' => $extras,
				'part_details' => $part_details,
				'labour_details' => $labour_details,
			]);

		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	public function getBillDetailFormData(Request $request) {
		// dd($request->all());
		try {
			$job_card = JobCard::with([
				'status',
				'jobOrder',
				'jobOrder.vehicle',
				'jobOrder.vehicle.model',
			])
				->find($request->id);

			if (!$job_card) {
				return response()->json([
					'success' => false,
					'error' => 'Job Card Not found!',
				]);
			}

			$job_card['creation_date'] = date('d/m/Y', strtotime($job_card->created_at));
			$job_card['creation_time'] = date('h:s a', strtotime($job_card->created_at));

			return response()->json([
				'success' => true,
				'job_card' => $job_card,
			]);

		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	public function updateBillDetails(Request $request) {
		// dd($request->all());
		try {
			$validator = Validator::make($request->all(), [
				'job_card_id' => [
					'required',
					'exists:job_cards,id',
					'integer',
				],
				'bill_number' => [
					'required',
					'string',
				],
				'bill_date' => [
					'required',
					'date_format:"d-m-Y',
				],
			]);
			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'errors' => $validator->errors()->all(),
				]);
			}

			dd("NEED TO CLARIFY SAVE FORM DATA!");

			DB::beginTransaction();

			DB::commit();
			return response()->json([
				'success' => true,
				'message' => "Bill Details Updated Successfully",
			]);

		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}
}
<?php

namespace Abs\GigoPkg\Api;

use Abs\GigoPkg\JobCard;
use Abs\GigoPkg\JobOrderRepairOrder;
use Abs\GigoPkg\MechanicTimeLog;
use Abs\GigoPkg\PauseWorkReason;
use Abs\GigoPkg\RepairOrderMechanic;
use App\Http\Controllers\Controller;
use App\JobOrder;
use App\User;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class MyJobCardController extends Controller {
	public $successStatus = 200;

	public function getMyJobCardList(Request $request) {
		// dd($request->all());
		try {
			$validator = Validator::make($request->all(), [
				'user_id' => [
					'required',
					'exists:users,id',
					'integer',
				],
			]);
			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'errors' => $validator->errors()->all(),
				]);
			}

			$user_details = User::with([
				'employee',
				'employee.outlet',
				'employee.outlet.state',
			])
				->find($request->user_id);

			$my_job_card_list = JobCard::select([
				'job_cards.id',
				'job_cards.job_card_number as jc_number',
				'vehicles.registration_number',
				DB::raw('COUNT(job_order_repair_orders.id) as no_of_ROTs'),
				DB::raw('CASE
                        WHEN count((CASE WHEN repair_order_mechanics.status_id = "8264" THEN repair_order_mechanics.status_id END )) > 0 THEN "Rescheduled"
                        WHEN count((CASE WHEN repair_order_mechanics.status_id = "8263" THEN repair_order_mechanics.status_id END )) =  COUNT(job_order_repair_orders.id) THEN "Completed"
                        WHEN count((CASE WHEN repair_order_mechanics.status_id = "8260" THEN repair_order_mechanics.status_id END )) =  COUNT(job_order_repair_orders.id) THEN "Not Yet Started"
                        WHEN count((CASE WHEN repair_order_mechanics.status_id = "8262" THEN repair_order_mechanics.status_id END )) > 0 && count((CASE WHEN repair_order_mechanics.status_id = "8264" THEN repair_order_mechanics.status_id END )) = 0 && count((CASE WHEN repair_order_mechanics.status_id = "8261" THEN repair_order_mechanics.status_id END )) = 0 THEN "Work Paused"
                        ELSE "Work InProgress" END AS status'),
				// 'configs.name as status',
				'job_cards.created_at',
				'models.model_number',
				'customers.name as customer_name',
			])
				->join('job_orders', 'job_orders.id', 'job_cards.job_order_id')
				->join('job_order_repair_orders', 'job_order_repair_orders.job_order_id', 'job_orders.id')
				->join('repair_order_mechanics', 'repair_order_mechanics.job_order_repair_order_id', 'job_order_repair_orders.id')
				->join('vehicles', 'vehicles.id', 'job_orders.vehicle_id')
				->join('vehicle_owners', function ($join) {
					$join->on('vehicle_owners.vehicle_id', 'job_orders.vehicle_id')
						->whereRaw('vehicle_owners.from_date = (select MAX(vehicle_owners1.from_date) from vehicle_owners as vehicle_owners1 where vehicle_owners1.vehicle_id = job_orders.vehicle_id)');
				})
				->join('customers', 'customers.id', 'vehicle_owners.customer_id')
				->join('models', 'models.id', 'vehicles.model_id')
				->join('configs', 'configs.id', 'repair_order_mechanics.status_id')
				->where('repair_order_mechanics.mechanic_id', $request->user_id)
				->groupBy('job_order_repair_orders.job_order_id')
				->orderBy('job_cards.created_at', 'DESC')
				->get();

			return response()->json([
				'success' => true,
				'user_details' => $user_details,
				'my_job_card_list' => $my_job_card_list,
			]);
		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	// JOB CARD VIEW DATA
	public function getMyJobCardData(Request $request) {
		// dd($request->all());
		try {
			$job_card = JobCard::with([
				'jobOrder',
				'jobOrder.vehicle',
				'jobOrder.vehicle.model',
				'status',
			])->find($request->job_card_id);

			if (!$job_card) {
				return response()->json([
					'success' => false,
					'error' => 'Invalid Job Order!',
				]);
			}

			$user_details = User::with([
				'employee',
				'employee.outlet',
				'employee.outlet.state',
			])
				->find($request->mechanic_id);

			$my_job_orders = RepairOrderMechanic::join('job_order_repair_orders', 'job_order_repair_orders.id', 'repair_order_mechanics.job_order_repair_order_id')
				->join('repair_orders', 'repair_orders.id', 'job_order_repair_orders.repair_order_id')
				->join('configs', 'configs.id', 'repair_order_mechanics.status_id')
				->select('repair_orders.code as repair_code', 'repair_orders.name as repair_name', 'repair_order_mechanics.status_id', 'configs.name as status_name', 'repair_order_mechanics.job_order_repair_order_id', 'repair_order_mechanics.id')
				->where('repair_order_mechanics.mechanic_id', $request->mechanic_id)
				->where('job_order_repair_orders.job_order_id', $job_card->job_order_id)
				->get();

			$other_work_status = '0';

			//Check Mechnic start any assigned work
			//if started means other works cannot be started until current work paused or completed
			$works_count = RepairOrderMechanic::where('mechanic_id', $request->mechanic_id)->where('status_id', 8261)->count();
			if ($works_count > 0) {
				$other_work_status = '1';
			}

			foreach ($my_job_orders as $key => $my_job_order) {

				//Check in this job card assigned works any one started or not.
				//if started means other work cannot be started until current work paused or completed
				// if ($my_job_order->status_id == '8261') {
				// 	$other_work_status = '1';
				// }

				$assigned_mechanics = User::join('repair_order_mechanics', 'repair_order_mechanics.mechanic_id', 'users.id')->where('repair_order_mechanics.job_order_repair_order_id', $my_job_order->job_order_repair_order_id)->select('users.name')->get()->toArray();
				$my_job_order->assigned_mechanics = $assigned_mechanics;

				if ($my_job_order->status_id == 8261 || $my_job_order->status_id == 8262) {
					$mechanic_log = MechanicTimeLog::where('repair_order_mechanic_id', $my_job_order->id)->select('start_date_time as start_time')->first();
					$my_job_order->start_time = $mechanic_log->start_time;
				}

				if ($my_job_order->status_id == 8262) {
					$mechanic_log = MechanicTimeLog::where('repair_order_mechanic_id', $my_job_order->id)->orderBy('id', 'DESC')->select('end_date_time as pause_time')->first();
					$my_job_order->pause_time = $mechanic_log->pause_time;
				}

				if ($my_job_order->status_id == 8263) {
					$mechanic_time_log = MechanicTimeLog::select('start_date_time', 'end_date_time')->where('repair_order_mechanic_id', $my_job_order->id)->get()->toArray();
					$total_hours = '00:00';
					if ($mechanic_time_log) {
						foreach ($mechanic_time_log as $key => $repair_order_mechanic_time_log) {
							$time1 = strtotime($repair_order_mechanic_time_log['start_date_time']);
							$time2 = strtotime($repair_order_mechanic_time_log['end_date_time']);
							if ($time2 < $time1) {
								$time2 += 86400;
							}
							$duration[] = date("H:i:s", strtotime("00:00") + ($time2 - $time1));
						}
						$total_duration = sum_mechanic_duration($duration);
						$format_change = explode(':', $total_duration);

						$hour = $format_change[0];
						$minutes = $format_change[1];
						// $seconds = $format_change[2];
						// $total_hours = $hour . ':' . $minutes . ':' . $seconds;
						$total_hours = $hour . ':' . $minutes;
						unset($duration);
					}
					$my_job_order->total_time = $total_hours;
				}
			}

			$pass_work_reasons = PauseWorkReason::where('company_id', Auth::user()->company_id)
				->get();

			return response()->json([
				'success' => true,
				'job_card' => $job_card,
				'user_details' => $user_details,
				'my_job_orders' => $my_job_orders,
				'pass_work_reasons' => $pass_work_reasons,
				'other_work_status' => $other_work_status,
			]);

		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	// JOB CARD VIEW Save
	public function saveMyStartWorkLog(Request $request) {
		// dd($request->all());
		try {
			$validator = Validator::make($request->all(), [
				'job_repair_order_id' => [
					'required',
				],
				'machanic_id' => [
					'required',
				],
				'status_id' => [
					'required',
				],
			]);
			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'errors' => $validator->errors()->all(),
				]);
			}
			$repair_order_mechanic = RepairOrderMechanic::where('job_order_repair_order_id', $request->job_repair_order_id)->where('mechanic_id', $request->machanic_id)->first();

			if (!$repair_order_mechanic) {
				return response()->json([
					'success' => false,
					'error' => 'Job Order Repair Order Mechanic Not Found!',
				]);
			}
			DB::beginTransaction();

			if ($request->status_id == 8261 || $request->status_id == 8264) {
				$mechanic_time_log = new MechanicTimeLog;
				$mechanic_time_log->start_date_time = Carbon::now();
				$mechanic_time_log->repair_order_mechanic_id = $repair_order_mechanic->id;
				$mechanic_time_log->status_id = $request->status_id;
				$mechanic_time_log->created_by_id = Auth::user()->id;
				$mechanic_time_log->save();
			} else {
				$mechanic_time_log = MechanicTimeLog::where('repair_order_mechanic_id', $repair_order_mechanic->id)->whereNull('end_date_time')->update(['end_date_time' => Carbon::now(), 'reason_id' => $request->reason_id, 'status_id' => $request->status_id]);
			}

			//Update Work Status
			$update_repair_order_mechanic = RepairOrderMechanic::where('id', $repair_order_mechanic->id)->where('mechanic_id', $request->machanic_id)->update(['status_id' => $request->status_id]);

			if ($request->status_id == 8262) {
				$repair_order_mechanic_count = RepairOrderMechanic::where('job_order_repair_order_id', $request->job_repair_order_id)->where('status_id', 8261)->count();
				//Repair Order status change inprogress into work paused
				if ($repair_order_mechanic_count > 0) {
					JobOrderRepairOrder::where('id', $request->job_repair_order_id)->update(['status_id' => 8183]);
				} else {
					JobOrderRepairOrder::where('id', $request->job_repair_order_id)->update(['status_id' => 8184]);
				}
			} else {
				JobOrderRepairOrder::where('id', $request->job_repair_order_id)->update(['status_id' => 8183]);
			}

			DB::commit();
			return response()->json([
				'success' => true,
				'mechanic_time_log' => "Work Log Saved Successfully",
				'work_status' => $request->status_id,
			]);
		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	public function saveMyFinishWorkLog(Request $request) {
		// dd($request->all());
		try {
			$validator = Validator::make($request->all(), [
				'job_repair_order_id' => [
					'required',
				],
				'machanic_id' => [
					'required',
				],
				'status_id' => [
					'required',
				],
			]);
			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'errors' => $validator->errors()->all(),
				]);
			}
			$repair_order_mechanic = RepairOrderMechanic::where('job_order_repair_order_id', $request->job_repair_order_id)->where('mechanic_id', $request->machanic_id)->first();

			if (!$repair_order_mechanic) {
				return response()->json([
					'success' => false,
					'error' => 'Job Order Repair Order Mechanic Not Found!',
				]);
			}

			//ACTUAL HOURS TO COMPLETE WORK
			$actual_hrs = JobOrderRepairOrder::where('id', $request->job_repair_order_id)->pluck('qty')->first();

			//Total Working hours of mechanic
			$mechanic_time_log = MechanicTimeLog::select('start_date_time', 'end_date_time')->where('repair_order_mechanic_id', $repair_order_mechanic->id)->get()->toArray();

			//EMPLOYEE TAKE TIME TO COMPLETE WORK
			if ($mechanic_time_log) {
				foreach ($mechanic_time_log as $key => $repair_order_mechanic_time_log) {
					$time1 = strtotime($repair_order_mechanic_time_log['start_date_time']);
					$time2 = strtotime($repair_order_mechanic_time_log['end_date_time']);
					if ($time2 < $time1) {
						$time2 += 86400;
					}
					$duration[] = date("H:i:s", strtotime("00:00") + ($time2 - $time1));
				}
				$total_duration = sum_mechanic_duration($duration);
				$format_change = explode(':', $total_duration);

				$hour = $format_change[0] . 'h';
				$minutes = $format_change[1] . 'm';
				// $seconds = $format_change[2] . 's';
				// $total_hours = $hour . ' ' . $minutes . ' ' . $seconds;
				$total_hours = $hour . ' ' . $minutes;
				unset($duration);
			}

			if ($request->type == 1) {

				DB::beginTransaction();

				//Check End Date empty or not.IF not empty update lost record
				$mechanic_end_time_log = MechanicTimeLog::where('repair_order_mechanic_id', $repair_order_mechanic->id)->orderby('id', 'DESC')->first();
				if ($mechanic_end_time_log->end_date_time) {
					$mechanic_end_time_log->end_date_time = Carbon::now();
					$mechanic_end_time_log->save();
				} else {
					$mechanic_time_log = MechanicTimeLog::where('repair_order_mechanic_id', $repair_order_mechanic->id)->whereNull('end_date_time')->update(['end_date_time' => Carbon::now()]);
				}

				//Mechanic Start Time
				$work_start_date_time = MechanicTimeLog::where('repair_order_mechanic_id', $repair_order_mechanic->id)->orderby('id', 'ASC')->select('start_date_time as work_start_time')->first();

				//Mechanic End Time
				$work_end_date_time = MechanicTimeLog::where('repair_order_mechanic_id', $repair_order_mechanic->id)->orderby('id', 'DESC')->select('end_date_time as work_end_time')->first();

				$work_logs['message'] = "Work Log Saved Successfully";
				$work_logs['work_start_date_time'] = $work_start_date_time->work_start_time;
				$work_logs['work_end_date_time'] = $work_end_date_time->work_end_time;
				$work_logs['actual_hrs'] = $actual_hrs;
				$work_logs['total_working_hours'] = $total_hours;

				DB::commit();
				return response()->json([
					'success' => true,
					'work_logs' => $work_logs,
				]);
			} else {
				DB::beginTransaction();

				//Update Worklog Status
				$mechanic_end_time_log = MechanicTimeLog::where('repair_order_mechanic_id', $repair_order_mechanic->id)->orderby('id', 'DESC')->first();
				$mechanic_end_time_log->status_id = 8263;
				$mechanic_end_time_log->save();

				//Update Work Status
				$update_repair_order_mechanic = RepairOrderMechanic::where('id', $repair_order_mechanic->id)->where('mechanic_id', $request->machanic_id)->update(['status_id' => $request->status_id]);

				if ($request->status_id == 8263) {
					$repair_order_status = RepairOrderMechanic::where('job_order_repair_order_id', $request->job_repair_order_id)->where('status_id', '!=', 8263)->count();
					if ($repair_order_status == 0) {
						JobOrderRepairOrder::where('id', $request->job_repair_order_id)->update(['status_id' => 8185]);
					} else {
						JobOrderRepairOrder::where('id', $request->job_repair_order_id)->update(['status_id' => 8183]);
					}

					$job_card = JobCard::find($request->job_card_id);

					$job_order = JobOrder::find($job_card->job_order_id);

					if ($job_card) {
						$mechanic_status = RepairOrderMechanic::join('job_order_repair_orders', 'job_order_repair_orders.id', 'repair_order_mechanics.job_order_repair_order_id')->where('job_order_repair_orders.job_order_id', $job_card->job_order_id)->where('repair_order_mechanics.status_id', '!=', 8263)->count();

						//Update Jobcard Status // Review Pending
						if ($mechanic_status == 0) {
							$job_card = Jobcard::where('id', $request->job_card_id)->update(['status_id' => 8222, 'updated_by' => Auth::user()->id, 'updated_at' => Carbon::now()]);
						}
					}
				}

				//GET SERVICE ADVISER MOBILE NUMBER
				$service_adviser_mobile = $job_order->serviceAdviser ? $job_order->serviceAdviser->contact_number : '';
				//GET FLOOR SUPERVISER MOBILE NUMBER
				$floor_superviser_mobile = $job_order->floorAdviser ? $job_order->floorAdviser->contact_number : '';

				$message = 'Labour Exceed the actual hours!';

				//EXTRA TIME TO COMPLETE WORK SMS SEND TO SUPERVISER AND FLOOR ADVISER
				if ($actual_hrs && $total_duration) {
					if (strtotime($total_duration) > strtotime($actual_hrs)) {
						if ($service_adviser_mobile) {
							$msg = sendSMSNotification($service_adviser_mobile, $message);
						}
						if ($floor_superviser_mobile) {
							$msg = sendSMSNotification($floor_superviser_mobile, $message);
						}
					}
				}

				DB::commit();

				return response()->json([
					'success' => true,
					'message' => "Work Log Saved Successfully",
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

	public function getMyJobCarduserDetails() {
		$user_id = Auth::user()->id;
		$user_details = User::with([
			'employee',
			'employee.outlet',
			'employee.outlet.state',
		])
			->find($user_id);
		return response()->json([
			'success' => true,
			'user_details' => $user_details,
		]);
	}

	//My jobcard Timesheet
	public function getTimeSheetQrCheck(Request $request) {
		try {
			$validator = Validator::make($request->all(), [
				'user_id' => [
					'required',
					'exists:users,id',
					'integer',
				],
			]);
			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'errors' => $validator->errors()->all(),
				]);
			}
			$user_details = User::with([
				'employee',
				'employee.outlet',
				'employee.outlet.state',
			])
				->find($request->user_id);

			return response()->json([
				'success' => true,
				'user_details' => $user_details,
			]);
		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	public function getMyJobCardtimeSheetList(Request $request) {
		$user_id = $request->user_id;
		$date = explode('to', $request->date);

		$my_job_timesheet = MechanicTimeLog::select([
			'job_cards.id',
			'job_cards.job_card_number as jc_number', 'outlets.code as outlet', 'repair_orders.code', 'mechanic_time_logs.end_date_time', 'mechanic_time_logs.start_date_time', 'mechanic_time_logs.created_at', DB::raw('concat(repair_orders.code," / ",repair_orders.name) as labour_work'),
		])
			->join('repair_order_mechanics', 'repair_order_mechanics.id', 'mechanic_time_logs.repair_order_mechanic_id')
			->join('job_order_repair_orders', 'job_order_repair_orders.id', 'repair_order_mechanics.job_order_repair_order_id')
			->join('repair_orders', 'repair_orders.id', 'job_order_repair_orders.repair_order_id')
			->join('job_cards', 'job_cards.job_order_id', 'job_order_repair_orders.job_order_id')
			->join('outlets', 'outlets.id', 'job_cards.outlet_id')
			->where('repair_order_mechanics.mechanic_id', $user_id)
		//->groupBy('job_order_repair_orders.job_order_id')
			->orderBy('mechanic_time_logs.created_at', 'DESC')

			->where(function ($query) use ($request, $date) {
				if (!empty($request->get('date'))) {
					$query->whereDate('mechanic_time_logs.created_at', '>=', date('Y-m-d', strtotime($date[0])))
						->whereDate('mechanic_time_logs.created_at', '<=', date('Y-m-d', strtotime($date[1])));
				} else {
					$query->whereYear('mechanic_time_logs.created_at', Carbon::now()->year)
						->whereMonth('mechanic_time_logs.created_at', Carbon::now()->month);
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->job_card_no)) {
					$query->where('job_cards.job_card_number', 'LIKE', '%' . $request->job_card_no . '%');
				}
			});

		return Datatables::of($my_job_timesheet)
			->addColumn('duration', function ($my_job_timesheet) {
				$time1 = strtotime($my_job_timesheet['start_date_time']);
				$time2 = strtotime($my_job_timesheet['end_date_time']);
				if ($time2 < $time1) {
					$time2 += 86400;
				}
				$duration[] = date("H:i:s", strtotime("00:00") + ($time2 - $time1));

				$total_duration = sum_mechanic_duration($duration);
				$format_change = explode(':', $total_duration);

				$hour = $format_change[0] . 'h';
				$minutes = $format_change[1] . 'm';
				$seconds = $format_change[2] . 's';
				$total_hours = $hour . ' ' . $minutes . ' ' . $seconds;
				return $total_hours;

			})

			->addColumn('start_time', function ($my_job_timesheet) {
				$start_time = date('H:i A', strtotime($my_job_timesheet['start_date_time']));
				// $time = date("g:i a", strtotime($start_time));
				return $start_time;
			})

			->addColumn('end_time', function ($my_job_timesheet) {
				$end_time = date('H:i A', strtotime($my_job_timesheet['end_date_time']));
				// $time = date("g:i a", strtotime($end_time));
				return $end_time;
			})

			->addColumn('created_at', function ($my_job_timesheet) {
				$created_at_date = date('d-m-Y', strtotime($my_job_timesheet['created_at']));
				// $created_at = date('H:i', strtotime($my_job_timesheet['created_at']));
				// $time = date("g:i a", strtotime($created_at));
				// return $created_at_date . " " . $time;
				return $created_at_date;

			})

			->make(true);
	}
}
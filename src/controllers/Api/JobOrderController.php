<?php

namespace Abs\GigoPkg\Api;

use Abs\BasicPkg\Traits\CrudTrait;
use Abs\GigoPkg\Bay;
use Abs\GigoPkg\GatePass;
use Abs\GigoPkg\GatePassDetail;
use Abs\GigoPkg\GatePassItem;
use Abs\GigoPkg\JobCard;
use Abs\GigoPkg\JobOrderIssuedPart;
use Abs\GigoPkg\JobOrderRepairOrder;
use Abs\GigoPkg\RepairOrder;
use Abs\PartPkg\Part;
use Abs\StatusPkg\Status;
use App\Config;
use App\Http\Controllers\Controller;
use App\JobOrder;
use App\JobOrderPart;
use App\VehicleInspectionItem;
use App\VehicleInspectionItemGroup;
use App\Vendor;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Validator;

class JobOrderController extends Controller {
	use CrudTrait;
	public $model = JobOrder::class;
	public $successStatus = 200;

	public function getJobCardList(Request $request) {
		try {
			/*$validator = Validator::make($request->all(), [
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
			*/
			//issue: query optimisation
			$job_card_list = JobCard::select([
				'job_cards.id as job_card_id',
				'job_cards.job_card_number',
				'job_cards.bay_id',
				'job_orders.id as job_order_id',
				DB::raw('DATE_FORMAT(job_cards.created_at,"%d/%m/%Y - %h:%i %p") as date'),
				'vehicles.registration_number',
				'models.model_name',
				'customers.name as customer_name',
				'status.name as status',
				'service_types.name as service_type',
				'quote_types.name as quote_type',
				'service_order_types.name as job_order_type',
				'gate_passes.id as gate_pass_id',

			])
				->leftJoin('job_orders', 'job_orders.id', 'job_cards.job_order_id')
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
				})
			//Floor Supervisor not Assigned =>8220
				->whereRaw("IF (job_cards.`status_id` = '8220', job_cards.`floor_supervisor_id` IS  NULL, job_cards.`floor_supervisor_id` = '" . $request->floor_supervisor_id . "')")
				->groupBy('job_cards.id')
				->get();

			return response()->json([
				'success' => true,
				'job_card_list' => $job_card_list,
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
			$extras = [
                'floor_supervisor_list' => collect(User::select([
                    'users.id',
                    DB::RAW('CONCAT(users.ecode," / ",users.name) as name'),
                ])
				->join('role_user','role_user.user_id','users.id')
				->join('permission_role','permission_role.role_id','role_user.role_id')
				->where('permission_role.permission_id', 5608) 
				->where('users.user_type_id', 1) //EMPLOYEE
				->where('users.company_id', $job_order->company_id)
				->where('users.working_outlet_id', $job_order->outlet_id)
				->groupBy('users.id')
				->orderBy('users.name','asc')
				->get())->prepend(['id' => '', 'name' => 'Select Floor Supervisor']),
            ];

			return response()->json([
				'success' => true,
				'job_order' => $job_order,
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

	//BAY ASSIGNMENT
	public function getBayFormData(Request $r) {
		try {
			$job_card = JobCard::with([
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
					//dd($bay->id);
					$bay->selected = true;
				} else {
					$bay->selected = false;
				}
			}
			//dd($bay_list);

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
		//dd($request->all());
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
					$bay->updated_by_id = Auth::user()->id;
					$bay->updated_at = Carbon::now();
					$bay->save();
				}
			}
			$job_card->bay_id = $request->bay_id;
			$job_card->updated_by = Auth::user()->id;
			$job_card->updated_at = Carbon::now();
			$job_card->save();

			$bay = Bay::find($request->bay_id);
			$bay->job_order_id = $job_card->job_order_id;
			$bay->status_id = 8241; //Assigned
			$bay->updated_by_id = Auth::user()->id;
			$bay->updated_at = Carbon::now();
			$bay->save();

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
			$vendor_details = Vendor::find($vendor_id);

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

	public function getLabourReviewData($id) {
		try {
			$job_card = JobCard::find($id);
			if (!$job_card) {
				return response()->json([
					'success' => false,
					'error' => 'Job Card Not Found!',
				]);
			}

			$labour_review_data = JobCard::with([
				'jobOrder',
				'jobOrder.JobOrderRepairOrders',
				'jobOrder.JobOrderRepairOrders.status',
				'jobOrder.JobOrderRepairOrders.repairOrderMechanics',
				'jobOrder.JobOrderRepairOrders.repairOrderMechanics.mechanic',
				'jobOrder.JobOrderRepairOrders.repairOrderMechanics.status',
				'jobOrder.JobOrderRepairOrders.repairOrderMechanics.mechanicTimeLogs',
				'jobOrder.JobOrderRepairOrders.repairOrderMechanics.mechanicTimeLogs.status',

			])
				->find($job_card->id);

			$job_card_repair_order_details = $labour_review_data->jobOrder->JobOrderRepairOrders;
			//dd($job_card_repair_order_details);
			$total_duration = 0;
			if ($job_card_repair_order_details) {
				foreach ($job_card_repair_order_details as $key => $job_card_repair_order) {
					$duration = [];
					//$total_duration=0;
					if ($job_card_repair_order->repairOrderMechanics) {
						foreach ($job_card_repair_order->repairOrderMechanics as $key1 => $repair_order_mechanic) {
							if ($repair_order_mechanic->mechanicTimeLogs) {
								foreach ($repair_order_mechanic->mechanicTimeLogs as $key2 => $mechanic_time_log) {
									$time1 = strtotime($mechanic_time_log->start_date_time);
									$time2 = strtotime($mechanic_time_log->end_date_time);
									if ($time2 < $time1) {
										$time2 += 86400;
									}
									$duration[] = date("H:i:s", strtotime("00:00") + ($time2 - $time1));

								}
							}
						}
						$total_duration = sum_mechanic_duration($duration);
						//dd($total_duration);
						$total_duration = date("H:i:s", strtotime($total_duration));
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
			//dd($labour_review_data);

			$status_ids = Config::where('config_type_id', 40)
				->where('id', '!=', 8185)
				->pluck('id')->toArray();
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

	public function getRoadTestObservation(Request $request) {
		$job_card = JobCard::find($request->id);
		if (!$job_card) {
			return response()->json([
				'success' => false,
				'error' => 'Validation Error',
				'errors' => ['Job Card Not Found!'],
			]);
		}

		$job_order = JobOrder::company()
			->with([
				'status',
				'roadTestDoneBy',
				'roadTestPreferedBy',
			])
			->select([
				'job_orders.*',
				DB::raw('DATE_FORMAT(job_orders.created_at,"%d/%m/%Y") as date'),
				DB::raw('DATE_FORMAT(job_orders.created_at,"%h:%i %p") as time'),
			])
			->find($job_card->job_order_id);

		return response()->json([
			'success' => true,
			'job_order' => $job_order,
		]);

	}

	public function getExpertDiagnosis(Request $request) {
		$job_card = JobCard::find($request->id);
		if (!$job_card) {
			return response()->json([
				'success' => false,
				'error' => 'Validation Error',
				'errors' => ['Job Card Not Found!'],
			]);
		}

		$job_order = JobOrder::company()->with([
			'expertDiagnosisReportBy',
		])
			->select([
				'job_orders.*',
				DB::raw('DATE_FORMAT(job_orders.created_at,"%d/%m/%Y") as date'),
				DB::raw('DATE_FORMAT(job_orders.created_at,"%h:%i %p") as time'),
			])
			->find($job_card->job_order_id);

		return response()->json([
			'success' => true,
			'job_order' => $job_order,
		]);
	}

	public function getDmsCheckList(Request $request) {
		$job_card = JobCard::find($request->id);
		if (!$job_card) {
			return response()->json([
				'success' => false,
				'error' => 'Validation Error',
				'errors' => ['Job Card Not Found!'],
			]);
		}

		$job_order = JobOrder::company()->with([
			'warrentyPolicyAttachment',
			'EWPAttachment',
			'AMCAttachment',
		])
			->select([
				'job_orders.*',
				DB::raw('DATE_FORMAT(job_orders.created_at,"%d/%m/%Y") as date'),
				DB::raw('DATE_FORMAT(job_orders.created_at,"%h:%i %p") as time'),
			])
			->find($job_card->job_order_id);

		return response()->json([
			'success' => true,
			'job_order' => $job_order,
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
		$job_card = JobCard::find($request->id);
		if (!$job_card) {
			return response()->json([
				'success' => false,
				'error' => 'Validation Error',
				'errors' => ['Job Card Not Found!'],
			]);
		}

		$job_order = JobOrder::company()->with([
			'customerApprovalAttachment',
			'customerESign',
		])
			->select([
				'job_orders.*',
				DB::raw('DATE_FORMAT(job_orders.created_at,"%d/%m/%Y") as date'),
				DB::raw('DATE_FORMAT(job_orders.created_at,"%h:%i %p") as time'),
			])
			->find($job_card->job_order_id);

		return response()->json([
			'success' => true,
			'job_order' => $job_order,
			'attachement_path' => url('storage/app/public/gigo/gate_in/attachments/'),
		]);

	}

	public function getEstimate(Request $request) {
		$job_card = JobCard::find($request->id);
		if (!$job_card) {
			return response()->json([
				'success' => false,
				'error' => 'Validation Error',
				'errors' => ['Job Card Not Found!'],
			]);
		}

		$job_order = JobOrder::find($job_card->job_order_id);

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

		]);

	}

	public function getPartsIndent(Request $request) {
		$job_card = JobCard::find($request->id);
		if (!$job_card) {
			return response()->json([
				'success' => false,
				'error' => 'Validation Error',
				'errors' => ['Job Card Not Found!'],
			]);
		}

		$part_list = collect(Part::select('id', 'name')->where('company_id', Auth::user()->company_id)->get())->prepend(['id' => '', 'name' => 'Select Part List']);

		$mechanic_list = collect(JobOrderRepairOrder::select('users.id', 'users.name')->leftJoin('repair_order_mechanics', 'repair_order_mechanics.job_order_repair_order_id', 'job_order_repair_orders.id')->leftJoin('users', 'users.id', 'repair_order_mechanics.mechanic_id')->where('job_order_repair_orders.job_order_id', $job_card->job_order_id)->distinct()->get())->prepend(['id' => '', 'name' => 'Select Mechanic']);

		$issued_mode = collect(Config::select('id', 'name')->where('config_type_id', 109)->get())->prepend(['id' => '', 'name' => 'Select Issue Mode']);

		$issued_parts_details = JobOrderIssuedPart::select('job_order_issued_parts.id as issued_id', 'parts.code', 'job_order_parts.id', 'job_order_parts.qty', 'job_order_issued_parts.issued_qty', DB::raw('DATE_FORMAT(job_order_issued_parts.created_at,"%d-%m-%Y") as date'), 'users.name as issued_to', 'configs.name as config_name', 'job_order_issued_parts.issued_mode_id', 'job_order_issued_parts.issued_to_id')
			->leftJoin('job_order_parts', 'job_order_parts.id', 'job_order_issued_parts.job_order_part_id')
			->leftJoin('parts', 'parts.id', 'job_order_parts.part_id')
			->leftJoin('users', 'users.id', 'job_order_issued_parts.issued_to_id')
			->leftJoin('configs', 'configs.id', 'job_order_issued_parts.issued_mode_id')
			->where('job_order_parts.job_order_id', $job_card->job_order_id)->groupBy('job_order_issued_parts.id')->get();

		return response()->json([
			'success' => true,
			'issued_parts_details' => $issued_parts_details,
			'part_list' => $part_list,
			'mechanic_list' => $mechanic_list,
			'issued_mode' => $issued_mode,
		]);

	}

	public function getScheduleMaintenance(Request $request) {
		$job_card = JobCard::find($request->id);
		if (!$job_card) {
			return response()->json([
				'success' => false,
				'error' => 'Validation Error',
				'errors' => ['Job Card Not Found!'],
			]);
		}

		$job_order = JobOrder::find($job_card->job_order_id);

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
		]);

	}

	public function getPayableLabourPart(Request $request) {
		$job_card = JobCard::find($request->id);
		if (!$job_card) {
			return response()->json([
				'success' => false,
				'error' => 'Validation Error',
				'errors' => ['Job Card Not Found!'],
			]);
		}

		$job_order = JobOrder::company()->with([
			'vehicle',
			'vehicle.model',
			'vehicle.status',
			'status',
			'gateLog',
		])
			->select([
				'job_orders.*',
				DB::raw('DATE_FORMAT(job_orders.created_at,"%d/%m/%Y") as date'),
				DB::raw('DATE_FORMAT(job_orders.created_at,"%h:%i %p") as time'),
			])
			->find($job_card->job_order_id);

		if (!$job_order) {
			return response()->json([
				'success' => false,
				'error' => 'Validation error',
				'errors' => ['Job Order Not found!'],
			]);
		}

		$part_details = JobOrderPart::with([
			'part',
			'part.uom',
			'part.taxCode',
			'splitOrderType',
			'status',
		])
			->where('job_order_id', $job_order->id)
			->get();

		$labour_details = JobOrderRepairOrder::with([
			'repairOrder',
			'repairOrder.repairOrderType',
			'repairOrder.uom',
			'repairOrder.taxCode',
			'repairOrder.skillLevel',
			'splitOrderType',
			'status',
		])
			->where('job_order_id', $job_order->id)
			->get();
		$parts_total_amount = 0;
		$labour_total_amount = 0;
		$total_amount = 0;

		if ($job_order->jobOrderRepairOrders) {
			foreach ($job_order->jobOrderRepairOrders as $key => $labour) {
				$labour_total_amount += $labour->amount;

			}
		}

		if ($job_order->jobOrderParts) {
			foreach ($job_order->jobOrderParts as $key => $part) {
				$parts_total_amount += $part->amount;

			}
		}
		$total_amount = $parts_total_amount + $labour_total_amount;

		return response()->json([
			'success' => true,
			'job_order' => $job_order,
			'part_details' => $part_details,
			'labour_details' => $labour_details,
			'total_amount' => number_format($total_amount, 2),
			'parts_total_amount' => number_format($parts_total_amount, 2),
			'labour_total_amount' => number_format($labour_total_amount, 2),
		]);

	}

	//VEHICLE INSPECTION GET FORM DATA
	public function getVehicleInspection(Request $request) {
		try {

			$job_card = JobCard::find($request->id);
			if (!$job_card) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => ['Job Card Not Found!'],
				]);
			}

			$job_order = JobOrder::company()
				->with([
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
				'jobOrder.serviceOrederType',
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
	public function getJobCardTimeLog($job_card_id) {
		try {
			$job_card_time_log = JobCard::with([
				'status',
				'jobOrder',
				// 'jobOrder.gateLog',
				// 'jobOrder.gateLog.vehicleDetail',
				// 'jobOrder.gateLog.vehicleDetail.vehicleModel',
				'jobOrder.JobOrderRepairOrders',
				'jobOrder.JobOrderRepairOrders.status',
				'jobOrder.JobOrderRepairOrders.repairOrderMechanics',
				'jobOrder.JobOrderRepairOrders.repairOrderMechanics.mechanic',
				'jobOrder.JobOrderRepairOrders.repairOrderMechanics.status',
				'jobOrder.JobOrderRepairOrders.repairOrderMechanics.mechanicTimeLogs',
				'jobOrder.JobOrderRepairOrders.repairOrderMechanics.mechanicTimeLogs.status',
			])
				->find($job_card_id);

			if (!$job_card_time_log) {
				return response()->json([
					'success' => false,
					'error' => 'Job Card Not found!',
				]);
			}

			$total_duration = 0;
			$overall_total_duration = [];
			if (!empty($job_card_time_log->jobOrder->JobOrderRepairOrders)) {
				foreach ($job_card_time_log->jobOrder->JobOrderRepairOrders as $key => $job_card_repair_order) {
					$duration = [];
					$job_card_repair_order->assigned_to_employee_count = count($job_card_repair_order->repairOrderMechanics);
					if ($job_card_repair_order->repairOrderMechanics) {
						foreach ($job_card_repair_order->repairOrderMechanics as $key1 => $repair_order_mechanic) {
							if ($repair_order_mechanic->mechanicTimeLogs) {
								$duration_difference = []; //FOR START TIME AND END TIME DIFFERENCE
								foreach ($repair_order_mechanic->mechanicTimeLogs as $key2 => $mechanic_time_log) {
									$time1 = strtotime($mechanic_time_log->start_date_time);
									$time2 = strtotime($mechanic_time_log->end_date_time);
									if ($time2 < $time1) {
										$time2 += 86400;
									}
									//PERTICULAR MECHANIC DATE
									$mechanic_time_log->date = date('d/m/Y', strtotime($mechanic_time_log->start_date_time));

									//PERTICULAR MECHANIC STATR TIME
									$mechanic_time_log->start_time = date('h:i:s a', strtotime($mechanic_time_log->start_date_time));

									//PERTICULAR MECHANIC END TIME
									$mechanic_time_log->end_time = date('h:i:s a', strtotime($mechanic_time_log->end_date_time));

									//TIME DURATION DIFFERENCE PERTICULAR MECHANIC DURATION
									$duration_difference[] = date("H:i:s", strtotime("00:00") + ($time2 - $time1));

									//TOTAL DURATION FOR PARTICLUAR EMPLOEE
									$duration[] = date("H:i:s", strtotime("00:00") + ($time2 - $time1));

									//OVERALL TOTAL WORKING DURATION
									$overall_total_duration[] = date("H:i:s", strtotime("00:00") + ($time2 - $time1));

									$mechanic_time_log->duration_difference = sum_mechanic_duration($duration_difference);
									unset($duration_difference);
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

			//OVERALL WORKING HOURS
			$overall_total_duration = sum_mechanic_duration($overall_total_duration);
			$overall_total_duration = date("H:i:s", strtotime($overall_total_duration));
			// dd($total_duration);
			$format_change = explode(':', $overall_total_duration);
			$hour = $format_change[0] . 'h';
			$minutes = $format_change[1] . 'm';
			$seconds = $format_change[2] . 's';
			$job_card_time_log->jobOrder['overall_total_duration'] = $hour . ' ' . $minutes . ' ' . $seconds;
			unset($overall_total_duration);

			$job_card_time_log->no_of_ROT = count($job_card_time_log->jobOrder->JobOrderRepairOrders);

			return response()->json([
				'success' => true,
				'job_card_time_log' => $job_card_time_log,
			]);

		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	//Material GatePass Detail Save
	public function saveMaterialGatePassDetail(Request $request) {
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
					'integer',
				],
				'work_order_description' => [
					'required',
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
			if (!$job_card) {
				return response()->json([
					'success' => false,
					'error' => 'Job Card Not Found!',
				]);
			}

			$status = Status::where('type_id', 8451)->where('name', 'Gate Out Pending')->first();
			/*if ($status) {
				return response()->json([
					'success' => false,
					'error' => 'Gate Out Pending Status Not Found!',
				]);
			}*/
			$gate_pass = GatePass::firstOrNew([
				'job_card_id' => $request->job_card_id,
			]);
			$gate_pass->type_id = 8281; //Material Gate Pass
			$gate_pass->status_id = $status->id; //Gate Out Pending
			$gate_pass->fill($request->all());
			$gate_pass->save();

			$gate_pass_detail = GatePassDetail::firstOrNew([
				'gate_pass_id' => $request->gate_pass_id,
				'work_order_no' => $request->work_order_no,
			]);
			$gate_pass_detail->vendor_type_id = $request->vendor_type_id;
			$gate_pass_detail->vendor_id = $request->vendor_id;
			$gate_pass_detail->vendor_contact_no = $request->vendor_contact_no;
			$gate_pass_detail->work_order_description = $request->work_order_description;
			// $gate_pass_detail->created_by = Auth::user()->id;
			$gate_pass_detail->save();

			DB::commit();

			return response()->json([
				'success' => true,
				'message' => 'Material Gate Pass Details Saved Successfully!!',
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
				'jobOrder.jobOrderRepairOrders',
				'jobOrder.jobOrderRepairOrders.repairOrder',
				'jobOrder.jobOrderRepairOrders.repairOrder.repairOrderType',
				'jobOrder.jobOrderRepairOrders.repairOrder.taxCode',
				'jobOrder.jobOrderParts',
				'jobOrder.jobOrderParts.part',
				'jobOrder.jobOrderParts.part.taxCode',
			])
				->find($request->id);

			if (!$job_card) {
				return response()->json([
					'success' => false,
					'error' => 'Job Card Not found!',
				]);
			}

			$job_card['creation_date'] = date('d/m/Y', strtotime($job_card->created_at));

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
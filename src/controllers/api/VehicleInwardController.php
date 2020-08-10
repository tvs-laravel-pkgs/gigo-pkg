<?php

namespace Abs\GigoPkg\Api;

use Abs\GigoPkg\JobOrderEstimate;
use Abs\GigoPkg\RepairOrder;
use Abs\GigoPkg\ServiceOrderType;
use Abs\GigoPkg\ShortUrl;
use Abs\SerialNumberPkg\SerialNumberGroup;
use Abs\TaxPkg\Tax;
use App\Attachment;
use App\Campaign;
use App\Config;
use App\Country;
use App\Customer;
use App\CustomerVoice;
use App\EstimationType;
use App\FinancialYear;
use App\GateLog;
use App\Http\Controllers\Controller;
use App\JobCard;
use App\JobOrder;
use App\JobOrderCampaign;
use App\JobOrderCampaignChassisNumber;
use App\JobOrderIssuedPart;
use App\JobOrderPart;
use App\JobOrderRepairOrder;
use App\JobOrderReturnedPart;
use App\Outlet;
use App\Part;
use App\PartsGrnDetail;
use App\PartsRequest;
use App\PartsRequestDetail;
use App\PartsRequestPart;
use App\QuoteType;
use App\RepairOrderType;
use App\ServiceType;
use App\SplitOrderType;
use App\State;
use App\User;
use App\VehicleInspectionItem;
use App\VehicleInspectionItemGroup;
use App\VehicleInventoryItem;
use App\VehicleModel;
use App\VehicleOwner;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use File;
use Illuminate\Http\Request;
use Storage;
use Validator;

class VehicleInwardController extends Controller {
	public $successStatus = 200;

	public function getGateInList(Request $request) {
		try {
			$validator = Validator::make($request->all(), [
				'service_advisor_id' => [
					'required',
					'exists:users,id',
					'integer',
				],
				'offset' => 'nullable|numeric',
				'limit' => 'nullable|numeric',
			]);
			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				]);
			}

			$vehicle_inward_list_get = JobOrder::join('gate_logs', 'gate_logs.job_order_id', 'job_orders.id')
				->leftJoin('vehicles', 'job_orders.vehicle_id', 'vehicles.id')
				->leftJoin('vehicle_owners', function ($join) {
					$join->on('vehicle_owners.vehicle_id', 'job_orders.vehicle_id')
						->whereRaw('vehicle_owners.from_date = (select MAX(vehicle_owners1.from_date) from vehicle_owners as vehicle_owners1 where vehicle_owners1.vehicle_id = job_orders.vehicle_id)');
				})
				->leftJoin('customers', 'customers.id', 'vehicle_owners.customer_id')
				->leftJoin('models', 'models.id', 'vehicles.model_id')
				->leftJoin('amc_members', 'amc_members.vehicle_id', 'vehicles.id')
				->leftJoin('amc_policies', 'amc_policies.id', 'amc_members.policy_id')
				->join('configs as status', 'status.id', 'job_orders.status_id')
				->select([
					'job_orders.id',
					DB::raw('IF(vehicles.is_registered = 1,"Registered Vehicle","Un-Registered Vehicle") as registration_type'),
					'vehicles.registration_number',
					'models.model_number',
					'gate_logs.number',
					'job_orders.status_id',
					DB::raw('DATE_FORMAT(gate_logs.gate_in_date,"%d/%m/%Y") as date'),
					DB::raw('DATE_FORMAT(gate_logs.gate_in_date,"%h:%i %p") as time'),
					'job_orders.driver_name',
					'job_orders.is_customer_agreed',
					'job_orders.driver_mobile_number as driver_mobile_number',
					DB::raw('GROUP_CONCAT(amc_policies.name) as amc_policies'),
					'status.name as status_name',
					'customers.name as customer_name',
				])
				->where(function ($query) use ($request) {
					if (!empty($request->search_key)) {
						$query->where('vehicles.registration_number', 'LIKE', '%' . $request->search_key . '%')
							->orWhere('customers.name', 'LIKE', '%' . $request->search_key . '%')
							->orWhere('vehicles.registration_number', 'LIKE', '%' . $request->search_key . '%')
							->orWhere('models.model_number', 'LIKE', '%' . $request->search_key . '%')
							->orWhere('amc_policies.name', 'LIKE', '%' . $request->search_key . '%')
							->orWhere('gate_logs.number', 'LIKE', '%' . $request->search_key . '%')
							->orWhere('status.name', 'LIKE', '%' . $request->search_key . '%')
						;
					}
				})
				->where(function ($query) use ($request) {
					if (!empty($request->gate_in_date)) {
						$query->whereDate('gate_logs.gate_in_date', date('Y-m-d', strtotime($request->gate_in_date)));
					}
				})
				->where(function ($query) use ($request) {
					if (!empty($request->reg_no)) {
						$query->where('vehicles.registration_number', 'LIKE', '%' . $request->reg_no . '%');
					}
				})
				->where(function ($query) use ($request) {
					if (!empty($request->membership)) {
						$query->where('amc_policies.name', 'LIKE', '%' . $request->membership . '%');
					}
				})
				->where(function ($query) use ($request) {
					if (!empty($request->gate_in_no)) {
						$query->where('gate_logs.number', 'LIKE', '%' . $request->gate_in_no . '%');
					}
				})
				->where(function ($query) use ($request) {
					if ($request->registration_type == '1' || $request->registration_type == '0') {
						$query->where('vehicles.is_registered', $request->registration_type);
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
						$query->where('job_orders.status_id', $request->status_id);
					}
				})
				->where('job_orders.company_id', Auth::user()->company_id)
			;
			/*if (!Entrust::can('view-overall-outlets-vehicle-inward')) {
				if (Entrust::can('view-mapped-outlet-vehicle-inward')) {
					$vehicle_inward_list_get->whereIn('job_orders.outlet_id', Auth::user()->employee->outlets->pluck('id')->toArray());
				} else {
					$vehicle_inward_list_get->where('job_orders.outlet_id', Auth::user()->employee->outlet_id)
						->whereRaw("IF (`job_orders`.`status_id` = '8460', `job_orders`.`service_advisor_id` IS  NULL, `job_orders`.`service_advisor_id` = '" . $request->service_advisor_id . "')");
				}
			}*/
			if (!Entrust::can('view-overall-outlets-vehicle-inward')) {
				if (Entrust::can('view-mapped-outlet-vehicle-inward')) {
					$outlet_ids = Auth::user()->employee->outlets->pluck('id')->toArray();
					array_push($outlet_ids, Auth::user()->employee->outlet_id);
					$vehicle_inward_list_get->whereIn('job_orders.outlet_id', $outlet_ids);
				} elseif (Entrust::can('view-own-outlet-vehicle-inward')) {
					$vehicle_inward_list_get->where('job_orders.outlet_id', Auth::user()->employee->outlet_id);
				} else {
					$vehicle_inward_list_get->where('job_orders.outlet_id', Auth::user()->employee->outlet_id)
						->whereRaw("IF (`job_orders`.`status_id` = '8460', `job_orders`.`service_advisor_id` IS  NULL, `job_orders`.`service_advisor_id` = '" . $request->service_advisor_id . "')");
				}
			}

			$vehicle_inward_list_get->groupBy('job_orders.id');
			$vehicle_inward_list_get->orderBy('job_orders.created_at', 'DESC');

			$total_records = $vehicle_inward_list_get->get()->count();

			if ($request->offset) {
				$vehicle_inward_list_get->offset($request->offset);
			}
			if ($request->limit) {
				$vehicle_inward_list_get->limit($request->limit);
			}

			$gate_logs = $vehicle_inward_list_get->get();

			return response()->json([
				'success' => true,
				'gate_logs' => $gate_logs,
				'total_records' => $total_records,
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

	//VEHICLE INWARD VIEW
	public function getVehicleInwardView(Request $r) {
		try {

			$customer_paid_type_id = SplitOrderType::where('paid_by_id', '10013')->pluck('id')->toArray();

			$job_order = JobOrder::with([
				'vehicle',
				'vehicle.model',
				'vehicle.status',
				'vehicle.currentOwner.customer',
				'vehicle.currentOwner.customer.address',
				'vehicle.currentOwner.customer.address.country',
				'vehicle.currentOwner.customer.address.state',
				'vehicle.currentOwner.customer.address.city',
				'vehicle.currentOwner.ownershipType',
				'vehicle.lastJobOrder',
				'vehicle.lastJobOrder.jobCard',
				'vehicleInventoryItem',
				'vehicleInspectionItems',
				'type',
				'outlet',
				'customerVoices',
				'quoteType',
				'serviceType',
				'kmReadingType',
				'status',
				'gateLog',
				'gateLog.createdBy',
				'roadTestDoneBy',
				'roadTestPreferedBy',
				'expertDiagnosisReportBy',
				'estimationType',
				'driverLicenseAttachment',
				'insuranceAttachment',
				'rcBookAttachment',
				'warrentyPolicyAttachment',
				'EWPAttachment',
				'AMCAttachment',
				'gateLog.driverAttachment',
				'gateLog.kmAttachment',
				'gateLog.vehicleAttachment',
				'gateLog.chassisAttachment',
				'customerApprovalAttachment',
				'customerESign',
			])
				->select([
					'job_orders.*',
					DB::raw('DATE_FORMAT(job_orders.created_at,"%d/%m/%Y") as date'),
					DB::raw('DATE_FORMAT(job_orders.created_at,"%h:%i %p") as time'),
				])
				->where('company_id', Auth::user()->company_id)
				->find($r->id);

			if (!$job_order) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Job Order Not Found!',
					],
				]);
			}

			//GET CAMPAIGNS
			$nameSpace = '\\App\\';
			$entity = 'JobOrderCampaign';
			$namespaceModel = $nameSpace . $entity;
			$job_order->campaigns = $this->compaigns($namespaceModel, $job_order, 1);

			if ($job_order->vehicle->currentOwner) {
				//Check which tax applicable for customer
				if ($job_order->outlet->state_id == $job_order->vehicle->currentOwner->customer->primaryAddress->state_id) {
					$tax_type = 1160; //Within State
				} else {
					$tax_type = 1161; //Inter State
				}
			} else {
				$tax_type = 1160; //Within State
			}

			//Count Tax Type
			$taxes = Tax::get();

			//SCHEDULE MAINTENANCE
			$labour_amount = 0;
			$part_amount = 0;

			$oem_recomentaion_labour_amount_include_tax = 0;
			$oem_recomentaion_part_amount_include_tax = 0;

			$repair_order_details = JobOrderRepairOrder::with([
				'repairOrder',
				'repairOrder.repairOrderType',
			])
			// ->where('job_order_repair_orders.is_recommended_by_oem', 1)
				->where('job_order_repair_orders.job_order_id', $r->id)
				->get();

			$total_schedule_labour_tax = 0;
			$total_schedule_labour_amount = 0;
			$total_schedule_without_tax_labour_amount = 0;
			$total_payable_labour_tax = 0;
			$total_payable_labour_amount = 0;
			$total_payable_without_tax_labour_amount = 0;

			$schedule_labour_details = array();
			$additional_labour_details = array();
			if ($repair_order_details) {
				foreach ($repair_order_details as $key => $value) {
					if ($value->is_recommended_by_oem == 1) {
						$schedule_labour_details[$key]['code'] = $value->repairOrder->code;
						$schedule_labour_details[$key]['name'] = $value->repairOrder->name;
						$schedule_labour_details[$key]['type'] = $value->repairOrder->repairOrderType ? $value->repairOrder->repairOrderType->short_name : '-';
						$schedule_labour_details[$key]['qty'] = $value->qty;
						$schedule_labour_details[$key]['amount'] = $value->amount;
						$schedule_labour_details[$key]['is_free_service'] = $value->is_free_service;
						$schedule_labour_details[$key]['split_order_type'] = $value->splitOrderType ? $value->splitOrderType->code . "|" . $value->splitOrderType->name : '-';
						$schedule_labour_details[$key]['removal_reason_id'] = $value->removal_reason_id;
						$schedule_labour_details[$key]['split_order_type_id'] = $value->split_order_type_id;
						$schedule_labour_details[$key]['is_fixed_schedule'] = $value->is_fixed_schedule;
						$total_amount = 0;
						if ($value->is_free_service != 1 && (in_array($value->split_order_type_id, $customer_paid_type_id) || !$value->split_order_type_id) && !$value->removal_reason_id) {
							$tax_values = array();
							if ($value->repairOrder->taxCode) {
								$tax_amount = 0;
								foreach ($value->repairOrder->taxCode->taxes as $tax_key => $tax) {
									$percentage_value = 0;
									if ($tax->type_id == $tax_type) {
										$percentage_value = ($value->amount * $tax->pivot->percentage) / 100;
										$percentage_value = number_format((float) $percentage_value, 2, '.', '');
									}
									$tax_amount += $percentage_value;
								}
								$total_schedule_labour_tax += $tax_amount;
								$total_amount = $tax_amount + $value->amount;
								$total_schedule_labour_amount += $total_amount;
							} else {
								$total_schedule_labour_amount += $value->amount;
							}
							$total_schedule_without_tax_labour_amount += $value->amount;
						} else {
							$schedule_labour_details[$key]['amount'] = '0.00';
						}
					} else {
						$additional_labour_details[$key]['code'] = $value->repairOrder->code;
						$additional_labour_details[$key]['name'] = $value->repairOrder->name;
						$additional_labour_details[$key]['type'] = $value->repairOrder->repairOrderType ? $value->repairOrder->repairOrderType->short_name : '-';
						$additional_labour_details[$key]['qty'] = $value->qty;
						$additional_labour_details[$key]['amount'] = $value->amount;
						$additional_labour_details[$key]['is_free_service'] = $value->is_free_service;
						$additional_labour_details[$key]['split_order_type'] = $value->splitOrderType ? $value->splitOrderType->code . "|" . $value->splitOrderType->name : '-';
						$additional_labour_details[$key]['removal_reason_id'] = $value->removal_reason_id;
						$additional_labour_details[$key]['split_order_type_id'] = $value->split_order_type_id;
						$additional_labour_details[$key]['is_fixed_schedule'] = $value->is_fixed_schedule;
						$total_amount = 0;
						if ($value->is_free_service != 1 && (in_array($value->split_order_type_id, $customer_paid_type_id) || !$value->split_order_type_id) && !$value->removal_reason_id) {
							$tax_values = array();
							if ($value->repairOrder->taxCode) {
								$tax_amount = 0;
								foreach ($value->repairOrder->taxCode->taxes as $tax_key => $tax) {
									$percentage_value = 0;
									if ($tax->type_id == $tax_type) {
										$percentage_value = ($value->amount * $tax->pivot->percentage) / 100;
										$percentage_value = number_format((float) $percentage_value, 2, '.', '');
									}
									$tax_amount += $percentage_value;
								}
								$total_payable_labour_tax += $tax_amount;
								$total_amount = $tax_amount + $value->amount;
								$total_payable_labour_amount += $total_amount;
							} else {
								$total_payable_labour_amount += $value->amount;
							}
							$total_payable_without_tax_labour_amount += $value->amount;
						} else {
							$additional_labour_details[$key]['amount'] = '0.00';
						}
					}
				}
			}

			$schedule_maintenance['labour_details'] = $schedule_labour_details;
			$schedule_maintenance['labour_amount'] = $total_schedule_labour_amount;
			$schedule_maintenance['without_tax_labour_amount'] = $total_schedule_without_tax_labour_amount;

			$payable_maintenance['labour_details'] = $additional_labour_details;
			$payable_maintenance['labour_amount'] = $total_payable_labour_amount;
			$payable_maintenance['without_tax_labour_amount'] = $total_payable_without_tax_labour_amount;

			$parts_details = JobOrderPart::with([
				'part',
				'part.taxCode',
			])
			// ->where('job_order_parts.is_oem_recommended', 1)
				->where('job_order_parts.job_order_id', $r->id)
				->get();

			$schedule_part_details = array();
			$additional_part_details = array();

			$total_schedule_part_amount = 0;
			$total_schedule_without_tax_part_amount = 0;
			$total_schedule_part_tax = 0;
			$total_payable_part_tax = 0;
			$total_payable_part_amount = 0;
			$total_payable_without_tax_part_amount = 0;

			if ($parts_details) {
				foreach ($parts_details as $key => $value) {
					if ($value->is_oem_recommended == 1) {
						$schedule_part_details[$key]['code'] = $value->part->code;
						$schedule_part_details[$key]['name'] = $value->part->name;
						$schedule_part_details[$key]['type'] = $value->part->taxCode ? $value->part->taxCode->code : '-';
						$schedule_part_details[$key]['rate'] = $value->rate;
						$schedule_part_details[$key]['qty'] = $value->qty;
						$schedule_part_details[$key]['amount'] = $value->amount;
						$schedule_part_details[$key]['is_free_service'] = $value->is_free_service;
						$schedule_part_details[$key]['split_order_type'] = $value->splitOrderType ? $value->splitOrderType->code . "|" . $value->splitOrderType->name : '-';
						$schedule_part_details[$key]['removal_reason_id'] = $value->removal_reason_id;
						$schedule_part_details[$key]['split_order_type_id'] = $value->split_order_type_id;
						$schedule_part_details[$key]['is_fixed_schedule'] = $value->is_fixed_schedule;
						$total_amount = 0;
						if ($value->is_free_service != 1 && (in_array($value->split_order_type_id, $customer_paid_type_id) || !$value->split_order_type_id) && !$value->removal_reason_id) {
							if ($value->part->taxCode) {
								$tax_amount = 0;
								foreach ($value->part->taxCode->taxes as $tax_key => $tax) {
									$percentage_value = 0;
									if ($tax->type_id == $tax_type) {
										$percentage_value = ($value->amount * $tax->pivot->percentage) / 100;
										$percentage_value = number_format((float) $percentage_value, 2, '.', '');
									}
									$tax_amount += $percentage_value;
								}
								$total_schedule_part_tax += $tax_amount;
								$total_amount = $tax_amount + $value->amount;
								$total_schedule_part_amount += $total_amount;
							} else {
								$total_schedule_part_amount += $value->amount;
							}
							$total_schedule_without_tax_part_amount += $value->amount;
						} else {
							$schedule_part_details[$key]['amount'] = '0.00';
						}
					} else {
						$additional_part_details[$key]['code'] = $value->part->code;
						$additional_part_details[$key]['name'] = $value->part->name;
						$additional_part_details[$key]['type'] = $value->part->taxCode ? $value->part->taxCode->code : '-';
						$additional_part_details[$key]['rate'] = $value->rate;
						$additional_part_details[$key]['qty'] = $value->qty;
						$additional_part_details[$key]['amount'] = $value->amount;
						$additional_part_details[$key]['is_free_service'] = $value->is_free_service;
						$additional_part_details[$key]['split_order_type'] = $value->splitOrderType ? $value->splitOrderType->code . "|" . $value->splitOrderType->name : '-';
						$additional_part_details[$key]['removal_reason_id'] = $value->removal_reason_id;
						$additional_part_details[$key]['split_order_type_id'] = $value->split_order_type_id;
						$additional_part_details[$key]['is_fixed_schedule'] = $value->is_fixed_schedule;
						$total_amount = 0;
						if ($value->is_free_service != 1 && (in_array($value->split_order_type_id, $customer_paid_type_id) || !$value->split_order_type_id) && !$value->removal_reason_id) {
							if ($value->part->taxCode) {
								$tax_amount = 0;
								foreach ($value->part->taxCode->taxes as $tax_key => $tax) {
									$percentage_value = 0;
									if ($tax->type_id == $tax_type) {
										$percentage_value = ($value->amount * $tax->pivot->percentage) / 100;
										$percentage_value = number_format((float) $percentage_value, 2, '.', '');
									}
									$tax_amount += $percentage_value;
								}
								$total_payable_part_tax += $tax_amount;
								$total_amount = $tax_amount + $value->amount;
								$total_payable_part_amount += $total_amount;
							} else {
								$total_payable_part_amount += $value->amount;
							}
							$total_payable_without_tax_part_amount += $value->amount;
						} else {
							$additional_part_details[$key]['amount'] = '0.00';
						}
					}

				}
			}

			$schedule_maintenance['part_details'] = $schedule_part_details;
			$schedule_maintenance['part_amount'] = $total_schedule_part_amount;
			$schedule_maintenance['without_tax_part_amount'] = $total_schedule_without_tax_part_amount;

			$payable_maintenance['part_details'] = $additional_part_details;
			$payable_maintenance['part_amount'] = $total_payable_part_amount;
			$payable_maintenance['without_tax_part_amount'] = $total_payable_without_tax_part_amount;

			//TOTAL
			$schedule_maintenance['total_amount'] = $schedule_maintenance['labour_amount'] + $schedule_maintenance['part_amount'];
			$schedule_maintenance['tax_amount'] = $total_schedule_labour_tax + $total_schedule_part_tax;
			$schedule_maintenance['without_tax_total_amount'] = $total_schedule_without_tax_part_amount + $total_schedule_without_tax_labour_amount;

			$payable_maintenance['total_amount'] = $payable_maintenance['labour_amount'] + $payable_maintenance['part_amount'];
			$payable_maintenance['tax_amount'] = $total_payable_labour_tax + $total_payable_part_tax;
			$payable_maintenance['without_tax_total_amount'] = $total_payable_without_tax_labour_amount + $total_payable_without_tax_part_amount;

			//TOTAL ESTIMATE
			$total_estimate_labour_amount['labour_amount'] = $total_schedule_labour_amount + $total_payable_labour_amount;
			$total_estimate_part_amount['part_amount'] = $total_schedule_part_amount + $total_payable_part_amount;
			$total_estimate_amount = $total_estimate_labour_amount['labour_amount'] + $total_estimate_part_amount['part_amount'];
			$total_tax_amount = $schedule_maintenance['tax_amount'] + $payable_maintenance['tax_amount'];

			//VEHICLE INSPECTION ITEM
			$vehicle_inspection_item_groups = array();
			if (count($job_order->vehicleInspectionItems) > 0) {
				$vehicle_inspection_item_group = VehicleInspectionItemGroup::where('company_id', Auth::user()->company_id)->select('id', 'name')->get();

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
				'job_order' => $job_order,
				'extras' => $extras,
				'schedule_maintenance' => $schedule_maintenance,
				'payable_maintenance' => $payable_maintenance,
				'total_estimate_labour_amount' => $total_estimate_labour_amount,
				'total_estimate_part_amount' => $total_estimate_part_amount,
				'total_estimate_amount' => round($total_estimate_amount),
				'total_tax_amount' => round($total_tax_amount),
				'vehicle_inspection_item_groups' => $vehicle_inspection_item_groups,
				'inventory_list' => VehicleInventoryItem::getInventoryList($r->id, $inventory_params),
				'attachement_path' => url('storage/app/public/gigo/gate_in/attachments/'),
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

	public function getPartIndentVehicleDetail(Request $r) {
		// dd($r->all());
		try {
			$job_order = JobOrder::with([
				'vehicle',
				'vehicle.model',
				'vehicle.status',
				'tradePlate',
				'status',
				'gateLog',
				'jobCard',
			])
				->select([
					'job_orders.*',
					DB::raw('DATE_FORMAT(job_orders.created_at,"%d/%m/%Y") as date'),
					DB::raw('DATE_FORMAT(job_orders.created_at,"%h:%i %p") as time'),
				])
				->where('company_id', Auth::user()->company_id)
				->find($r->id);

			if (!$job_order) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Job Order Not Found!',
					],
				]);
			}

			return response()->json([
				'success' => true,
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
	public function getRepairOrders(Request $r) {
		// dd($r->all());
		try {

			$job_order = JobOrder::with([
				'jobCard',
				'vehicle',
				'vehicle.model',
				'vehicle.status',
				'status',
				'serviceType',
				'jobOrderRepairOrders',
				'jobOrderRepairOrders.repairOrder',
				'jobOrderRepairOrders.repairOrder.repairOrderType',
				'jobOrderRepairOrders.splitOrderType',
			])
				->select([
					'job_orders.*',
					DB::raw('DATE_FORMAT(job_orders.created_at,"%d/%m/%Y") as date'),
					DB::raw('DATE_FORMAT(job_orders.created_at,"%h:%i %p") as time'),
				])
				->where('company_id', Auth::user()->company_id)
				->where('id', $r->id)->first();

			if (!$job_order) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Job Order Not Found',
					],
				]);
			}

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
					$labour_details[$key]['repair_order'] = $value->repairOrder;
					$labour_details[$key]['is_fixed_schedule'] = $value->is_fixed_schedule;
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

			$job_order->labour_details = $labour_details;

			return response()->json([
				'success' => true,
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
	public function getInwardPartIndentViewData(Request $r) {
		try {
			$job_order = JobOrder::with([
				'jobOrderRepairOrders',
				'jobOrderRepairOrders.repairOrder',
				'jobCard',
				'vehicle',
				'vehicle.model',
				'vehicle.status',
				'vehicle.currentOwner.customer',
				'vehicle.currentOwner.customer.address',
				'vehicle.currentOwner.customer.address.country',
				'vehicle.currentOwner.customer.address.state',
				'vehicle.currentOwner.customer.address.city',
				'vehicle.currentOwner.ownershipType',
				'type',
				'outlet',
				'status',
			])
				->select([
					'job_orders.*',
					DB::raw('DATE_FORMAT(job_orders.created_at,"%d/%m/%Y") as date'),
					DB::raw('DATE_FORMAT(job_orders.created_at,"%h:%i %p") as time'),
				])
				->where('company_id', Auth::user()->company_id)
				->find($r->id);

			if (!$job_order) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Job Order Not Found!',
					],
				]);
			}

			$part_amount = 0;

			$part_details = array();
			if ($job_order->jobOrderParts) {
				foreach ($job_order->jobOrderParts as $key => $value) {
					if ($value->removal_reason_id != 10021) {

						$issued_qty = JobOrderIssuedPart::where('job_order_part_id', $value->id)->select(DB::raw('IFNULL(SUM(job_order_issued_parts.issued_qty),0) as issued_qty'))->first();

						$returned_qty = JobOrderReturnedPart::where('job_order_part_id', $value->id)->select(DB::raw('IFNULL(SUM(job_order_returned_parts.returned_qty),0) as returned_qty'))->first();

						$part_details[$key]['id'] = $value->part_id;
						$part_details[$key]['job_order_part_id'] = $value->id;
						$part_details[$key]['code'] = $value->part->code;
						$part_details[$key]['name'] = $value->part->name;
						$part_details[$key]['part_detail'] = $value->part->code . ' | ' . $value->part->name;
						$part_details[$key]['type'] = $value->part->taxCode ? $value->part->taxCode->code : '-';
						$part_details[$key]['rate'] = $value->rate;
						$part_details[$key]['qty'] = $value->qty;
						$part_details[$key]['amount'] = $value->amount;
						$part_details[$key]['is_free_service'] = $value->is_free_service;
						$part_details[$key]['is_fixed_schedule'] = $value->is_fixed_schedule;
						if ($value->splitOrderType) {
							$part_details[$key]['split_order_type'] = $value->splitOrderType->code . "|" . $value->splitOrderType->name;
						} else {
							$part_details[$key]['split_order_type'] = '';

						}
						$part_details[$key]['split_order_type_id'] = $value->split_order_type_id;

						$part_details[$key]['removal_reason_id'] = $value->removal_reason_id;
						$part_details[$key]['issued_qty'] = $issued_qty->issued_qty;
						$part_details[$key]['returned_qty'] = $returned_qty->returned_qty;
						$part_details[$key]['pending_qty'] = $value->qty - ($issued_qty->issued_qty - $returned_qty->returned_qty);
						$part_details[$key]['repair_order'] = $value->part->repair_order_parts;

						// if (in_array($value->split_order_type_id, $customer_paid_type)) {
						// if ($value->is_free_service != 1 && $value->removal_reason_id == null) {
						// 	$part_amount += $value->amount;
						// }
					}
				}
			}

			$job_order_parts = array();
			$repair_order_mechanics = array();
			$indent_part_logs = array();

			if ($job_order->jobCard) {
				$job_order_parts = Part::leftJoin('job_order_parts', 'job_order_parts.part_id', 'parts.id')->select('parts.*', 'job_order_parts.id as job_order_part_id')->where('job_order_parts.job_order_id', $r->id)->whereNull('removal_reason_id')->get();

				$repair_order_mechanics = User::leftJoin('repair_order_mechanics', 'repair_order_mechanics.mechanic_id', 'users.id')
					->leftJoin('job_order_repair_orders', 'job_order_repair_orders.id', 'repair_order_mechanics.job_order_repair_order_id')
					->select('users.*')
					->whereNull('job_order_repair_orders.removal_reason_id')
					->where('job_order_repair_orders.job_order_id', $r->id)->groupBy('users.id')->get();

				$indent_part_logs_issues = JobOrderPart::join('job_order_issued_parts as joip', 'joip.job_order_part_id', 'job_order_parts.id')
					->join('parts', 'job_order_parts.part_id', 'parts.id')
					->join('configs', 'joip.issued_mode_id', 'configs.id')
					->join('users', 'joip.issued_to_id', 'users.id')
					->where('job_order_id', $r->id)
				// ->whereNotIn('job_order_parts.removal_reason_id', [10021])
					->select(
						DB::raw('"Issue" as transaction_type'),
						'parts.name',
						'parts.code',
						'joip.issued_qty as qty',
						DB::raw('DATE_FORMAT(joip.created_at,"%d/%m/%Y") as date'),
						'configs.name as issue_mode',
						'users.name as mechanic',
						'joip.id as job_order_part_increment_id',
						'users.id as employee_id',
						'job_order_parts.id as job_order_part_id',
						'parts.id as part_id',
						'job_order_parts.removal_reason_id'
					);

				$indent_part_logs = JobOrderPart::join('job_order_returned_parts as jorp', 'jorp.job_order_part_id', 'job_order_parts.id')
					->join('parts', 'job_order_parts.part_id', 'parts.id')
					->join('users', 'jorp.returned_to_id', 'users.id')
					->where('job_order_id', $r->id)
				// ->whereNotIn('job_order_parts.removal_reason_id', [10021])
					->select(
						DB::raw('"Return" as transaction_type'),
						'parts.name',
						'parts.code',
						'jorp.returned_qty as qty',
						DB::raw('DATE_FORMAT(jorp.created_at,"%d/%m/%Y") as date'),
						DB::raw('"-" as issue_mode'),
						'users.name as mechanic',
						'jorp.id as job_order_part_increment_id',
						'users.id as employee_id',
						'job_order_parts.id as job_order_part_id',
						'parts.id as part_id',
						'job_order_parts.removal_reason_id'

					)->union($indent_part_logs_issues)->orderBy('date', 'DESC')->get();
			}

			return response()->json([
				'success' => true,
				'job_order' => $job_order,
				'part_details' => $part_details,
				// 'part_amount' => $part_amount,
				'job_order_parts' => $job_order_parts,
				'repair_order_mechanics' => $repair_order_mechanics,
				'indent_part_logs' => $indent_part_logs,
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
	//SAVE RETURN PART
	public function saveReturnPart(Request $request) {
		// dd($request->all());
		try {
			$validator = Validator::make($request->all(), [
				'job_order_part_id' => [
					'required',
					'integer',
					'exists:job_order_parts,id',
				],
				'returned_to_id' => [
					'required',
					'integer',
					'exists:users,id',
				],
				'returned_qty' => [
					'required',
					'numeric',
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
			// $job_order_returned_part = JobOrderReturnedPart::where('job_order_part_id', $request->job_order_part_id)->first();
			$job_order_returned_part = JobOrderReturnedPart::find($request->job_order_returned_part_id);
			if ($job_order_returned_part == null) {
				$job_order_returned_part = new JobOrderReturnedPart;
				$job_order_returned_part->created_by_id = Auth::id();
				$job_order_returned_part->created_at = Carbon::now();
			} else {
				$job_order_returned_part->updated_by_id = Auth::id();
				$job_order_returned_part->updated_at = Carbon::now();
			}
			$job_order_returned_part->fill($request->all());
			$job_order_returned_part->save();

			$issued_qty = JobOrderIssuedPart::where('job_order_part_id', $request->job_order_part_id)->sum('issued_qty');

			$returned_qty = JobOrderReturnedPart::where('job_order_part_id', $request->job_order_part_id)->sum('returned_qty');

			$job_order_part_qty = JobOrderPart::find($request->job_order_part_id);

			if ($returned_qty > $issued_qty) {
				return response()->json([
					'success' => false,
					'message' => 'Returning Quantity should not exceed Issued Quantity',
				]);
			}

			DB::commit();

			return response()->json([
				'success' => true,
				'message' => 'Return Entry saved Successfully!!',
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

	// DELETE ISSUE AND RETURN PARTS
	public function deletePartLogs(Request $request) {
		// dd($request->all());
		try {
			DB::beginTransaction();
			if ($request->type == 'Return') {
				$validator = Validator::make($request->all(), [
					'id' => [
						'required',
						'integer',
						'exists:job_order_returned_parts,id',
					],
				]);

				if ($validator->fails()) {
					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => $validator->errors()->all(),
					]);
				}

				$delete_part_log = JobOrderReturnedPart::find($request->id)->forceDelete();

			} else {
				$validator = Validator::make($request->all(), [
					'id' => [
						'required',
						'integer',
						'exists:job_order_issued_parts,id',
					],
				]);

				if ($validator->fails()) {
					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => $validator->errors()->all(),
					]);
				}

				$delete_part_log = JobOrderIssuedPart::find($request->id)->forceDelete();

			}
			// dd($delete_part_log);
			DB::commit();

			return response()->json([
				'success' => true,
				'message' => 'Log Deleted Successfully',
			]);

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
	//ISSUE PART FORM DATA
	public function getInwardPartIndentIssuePartFormData(Request $request) {
		// dd($request->all());
		try {

			$job_order_parts = Part::leftJoin('job_order_parts', 'job_order_parts.part_id', 'parts.id')->select('parts.*', 'job_order_parts.id as job_order_part_id')->where('job_order_parts.job_order_id', $request->id)->whereNull('removal_reason_id')->get();

			$repair_order_mechanics = User::leftJoin('repair_order_mechanics', 'repair_order_mechanics.mechanic_id', 'users.id')
				->leftJoin('job_order_repair_orders', 'job_order_repair_orders.id', 'repair_order_mechanics.job_order_repair_order_id')->select('users.*')
				->whereNull('job_order_repair_orders.removal_reason_id')
				->where('job_order_repair_orders.job_order_id', $request->id)->groupBy('users.id')->get();

			$issue_modes = Config::where('config_type_id', 109)->select('id', 'name')->get();
			$issue_data = JobOrderIssuedPart::join('job_order_parts', 'job_order_issued_parts.job_order_part_id', 'job_order_parts.id')
				->where('job_order_issued_parts.id', $request->issue_part_id)
				->select(
					'job_order_issued_parts.issued_qty',
					'job_order_issued_parts.issued_to_id',
					'job_order_issued_parts.issued_mode_id',
					'job_order_parts.part_id',
					'job_order_issued_parts.job_order_part_id'
				)
				->first();

			$responseArr = array(
				'success' => true,
				'job_order_parts' => $job_order_parts,
				'repair_order_mechanics' => $repair_order_mechanics,
				'issue_modes' => $issue_modes,
				'issue_data' => $issue_data,
				'issue_to_user' => ($issue_data) ? $issue_data->issuedTo : null,
			);

			return response()->json($responseArr);
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
	public function getPartDetailPias(Request $request) {
		// dd($request->all());
		try {
			$available_qty = 0;
			$part_code = $request->code;
			$user_outlet_code = Auth::user()->outlet->code;
			$company_id = Auth::user()->company_id;
			$db2 = config('database.connections.pias.database');
			$error = '';

			$part = DB::table($db2 . '.parts as pias_parts')
				->join('parts', 'parts.code', 'pias_parts.code')
				->select('parts.code', 'pias_parts.id')
				->where('parts.code', $part_code)
				->first();
			$outlet = DB::table($db2 . '.outlets as pias_outlets')
				->join('outlets', 'outlets.code', 'pias_outlets.code')
				->select('outlets.code', 'pias_outlets.id')
				->where(['outlets.code' => $user_outlet_code, 'outlets.company_id' => $company_id])
				->first();

			if (!$part) {
				$error = 'Part';
			}
			if (!$outlet) {
				$error = 'Outlet';
			}
			if ($error != '') {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [$error . ' Details cannot be found'],
				]);
			}

			$stock_details = DB::table($db2 . '.stock_details')
				->select('stock_details.outlet_id', 'stock_details.part_id', 'stock_details.available_quantity')
				->where(['stock_details.outlet_id' => $outlet->id, 'stock_details.part_id' => $part->id])
				->first();

			if ($stock_details) {
				$available_qty = $stock_details->available_quantity;
			}

			//Mentioned Parts Total Request Quantity
			$total_request_qty = JobOrderPart::join('parts', 'parts.id', 'job_order_parts.part_id')->where('job_order_parts.job_order_id', $request->job_order_id)->where('parts.code', $request->code)->pluck('job_order_parts.qty')->first();

			//Mentioned Parts Total Issued Quantity
			$total_issued_qty = JobOrderPart::join('parts', 'parts.id', 'job_order_parts.part_id')->join('job_order_issued_parts', 'job_order_issued_parts.job_order_part_id', 'job_order_parts.id')->where('job_order_parts.job_order_id', $request->job_order_id)->where('parts.code', $request->code)->sum('job_order_issued_parts.issued_qty');

			$total_balance_qty = $total_request_qty - $total_issued_qty;

			$responseArr = array(
				'success' => true,
				'available_quantity' => $available_qty,
				'total_request_qty' => $total_request_qty,
				'total_issued_qty' => $total_issued_qty,
				'total_balance_qty' => $total_balance_qty,
			);

			return response()->json($responseArr);

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
	//SAVE ISSUED PART
	public function saveIssuedPart(Request $request) {
		// dd($request->all());
		try {
			$validator = Validator::make($request->all(), [
				'job_order_part_id' => [
					'required',
					'integer',
					'exists:job_order_parts,id',
				],
				'issued_to_id' => [
					'required',
					'integer',
					'exists:users,id',
				],
				'issued_qty' => [
					'required',
					'numeric',
				],
				'issued_mode_id' => [
					'required',
					'exists:configs,id',
				],

			]);

			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				]);
			}

			$issued_qty = JobOrderIssuedPart::where('job_order_part_id', $request->job_order_part_id)->select(DB::raw('IFNULL(SUM(job_order_issued_parts.issued_qty),0) as issued_qty'))->first();

			$returned_qty = JobOrderReturnedPart::where('job_order_part_id', $request->job_order_part_id)->select(DB::raw('IFNULL(SUM(job_order_returned_parts.returned_qty),0) as returned_qty'))->first();

			$job_order_part_qty = JobOrderPart::find($request->job_order_part_id);

			if ($request->issued_mode_id == 8480) {
				$pias_available_qty = $request->available_qty;
				if (intval($request->issued_qty) > intval($pias_available_qty)) {
					return response()->json([
						'success' => false,
						'message' => 'Issue Quantity should not exceed Available Quantity',
					]);
				}

				$pending_qty = $job_order_part_qty->qty - ($issued_qty->issued_qty + $returned_qty->returned_qty);
				if ($pending_qty < $request->issued_qty) {
					return response()->json([
						'success' => false,
						'message' => 'Returning Quantity should not exceed Pending Quantity',
					]);
				}

			}

			DB::beginTransaction();
			// dd($request->issue_mode_id);
			if ($request->issued_mode_id != 8480 && $request->job_order_issued_part_id == null) {

				$db2 = config('database.connections.pias.database');

				$parts = DB::table($db2 . '.parts as pias_parts')
					->join('parts', 'parts.code', 'pias_parts.code')
					->select('parts.code', 'pias_parts.id')
					->where('parts.id', $request->part_id)
					->first();

				if (!$parts) {
					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => ['Parts Details cannot be found'],
					]);
				}

				if (date('m') > 3) {
					$year = date('Y') + 1;
				} else {
					$year = date('Y');
				}

				$financial_year = FinancialYear::where('from', $year)->first();
				if (!$financial_year) {
					return response()->json(['success' => false, 'errors' => ['No Serial number found!!!']]);
				}
				$branch = Outlet::where('id', Auth::user()->employee->outlet->id)->first();

				$generateNumber = SerialNumberGroup::generateNumber(19, $financial_year->id, $branch->state_id, $branch->id);

				if (!$generateNumber['success']) {
					return response()->json(['success' => false, 'errors' => ['No Serial number found']]);
				}
				// $job_card = JobCard::where('job_order_id', $request->job_order_id)->first();
				$job_order = JobOrder::with([
					'jobCard',
				])->find($request->job_order_id);

				$parts_request = new PartsRequest;
				$parts_request->request_type_id = 8500;
				$parts_request->number = $generateNumber['number'];
				$parts_request->remarks = $request->remarks;
				$parts_request->advance_amount_received_details = $request->advance_amount_received_details;
				$parts_request->warranty_approved_reasons = $request->warranty_approved_reasons;
				$parts_request->created_by_id = Auth::id();
				$parts_request->created_at = Carbon::now();
				$parts_request->updated_at = NULL;
				$parts_request->status_id = 8520;

				if ($job_order && $job_order->jobCard) {
					$parts_request->job_card_id = $job_order->jobCard->id;
				}
				$parts_request->customer_id = $job_order->customer_id;
				$parts_request->save();

				$local_purchase_count = PartsRequestDetail::where('parts_request_id', $parts_request->id)->where('request_type_id', 8541)->count();
				$local_purchase_count++;
				$number_format = sprintf("%03d", $local_purchase_count);

				$parts_request_detail = new PartsRequestDetail;
				$parts_request_detail->parts_request_id = $parts_request->id;
				$parts_request_detail->number = $parts_request->number . "_LP_" . $number_format;
				$parts_request_detail->request_type_id = 8541;
				$parts_request_detail->status_id = 8520;
				$parts_request_detail->save();

				// $parts_detail = PartsRequestPart::firstOrNew(['parts_request_detail_id' => $parts_request_detail->id, 'part_id' => $request->job_order_part_id]);
				$parts_detail = new PartsRequestPart;
				$parts_detail->parts_request_detail_id = $parts_request_detail->id;
				$parts_detail->part_id = $parts->id;
				$parts_detail->dbm_part_id = $parts->id;
				$parts_detail->request_qty = $request->quantity;
				$parts_detail->unit_price = $request->unit_price;
				$parts_detail->mrp = $request->mrp;
				$parts_detail->total_price = $request->total;
				$parts_detail->tax_percentage = $request->tax_percentage;
				$parts_detail->tax_amount = $request->tax_amount;
				$parts_detail->total_amount = $request->total_amount;
				$parts_detail->status_id = 8520;
				$parts_detail->created_by_id = Auth::id();
				$parts_detail->created_at = Carbon::now();
				$parts_detail->save();

				$parts_grn = new PartsGrnDetail;
				$parts_grn->parts_request_detail_id = $parts_request_detail->id;
				$parts_grn->supplier_id = $request->supplier_id;
				$parts_grn->po_number = $request->po_number;
				$parts_grn->po_amount = $request->po_amount;
				$parts_grn->created_by_id = Auth::id();
				$parts_grn->created_at = Carbon::now();
				$parts_grn->save();
			} else {
				$job_order_isssued_part = JobOrderIssuedPart::find($request->job_order_issued_part_id);
				if ($job_order_isssued_part == null) {
					$job_order_isssued_part = new JobOrderIssuedPart;
					$job_order_isssued_part->created_by_id = Auth::id();
					$job_order_isssued_part->created_at = Carbon::now();
				} else {
					$job_order_isssued_part->updated_by_id = Auth::id();
					$job_order_isssued_part->updated_at = Carbon::now();
				}

				$job_order_isssued_part->fill($request->all());
				$job_order_isssued_part->save();
			}

			DB::commit();

			return response()->json([
				'success' => true,
				'message' => 'Issued Part saved Successfully!!',
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
	//VEHICLE INWARD VIEW DATA
	public function getVehicleInwardViewData(Request $r) {
		try {
			$gate_log = GateLog::with([
				'vehicle',
				'vehicle.model',
				'vehicle.status',
				'vehicle.currentOwner.customer',
				'vehicle.currentOwner.ownerShipDetail',
				'status',
				'driverAttachment',
				'kmAttachment',
				'vehicleAttachment',
			])
				->select([
					'gate_logs.*',
					DB::raw('DATE_FORMAT(gate_logs.created_at,"%d/%m/%Y") as date'),
					DB::raw('DATE_FORMAT(gate_logs.created_at,"%h:%i %p") as time'),
				])
				->where('company_id', Auth::user()->company_id)
				->find($r->id);

			if (!$gate_log) {
				return response()->json([
					'success' => false,
					'message' => 'Gate Log Not Found!',
				]);
			}

			//Job card details need to get future
			return response()->json([
				'success' => true,
				'gate_log' => $gate_log,
				'attachement_path' => url('storage/app/public/gigo/gate_in/attachments/'),
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

	//VEHICLE DETAILS
	public function getVehicleDetail(Request $r) {
		try {
			$validator = Validator::make($r->all(), [
				'service_advisor_id' => [
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

			$job_order = JobOrder::with([
				'vehicle',
				'vehicle.model',
				'vehicle.status',
				'tradePlate',
				'status',
				'gateLog',
			])
				->select([
					'job_orders.*',
					DB::raw('DATE_FORMAT(job_orders.created_at,"%d/%m/%Y") as date'),
					DB::raw('DATE_FORMAT(job_orders.created_at,"%h:%i %p") as time'),
				])
				->where('company_id', Auth::user()->company_id)
				->find($r->id);

			if (!$job_order) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Job Order Not Found!',
					],
				]);
			}
			//MAPPING SERVICE ADVISOR
			$job_order->service_advisor_id = $r->service_advisor_id;
			$job_order->status_id = 8463;
			$job_order->save();

			//UPDATE GATE LOG STATUS
			$job_order->gateLog()->update(['status_id' => 8121]);

			//ENABLE ESTIMATE STATUS
			$inward_process_check = $job_order->inwardProcessChecks()->where('is_form_filled', 0)->first();
			if ($inward_process_check) {
				$job_order->enable_estimate_status = false;
			} else {
				$job_order->enable_estimate_status = true;
			}
			//Job card details need to get future
			return response()->json([
				'success' => true,
				'job_order' => $job_order,
				'extras' => [
					'model_list' => VehicleModel::getDropDownList(),
				],
				'attachement_path' => url('storage/app/public/gigo/gate_in/attachments/'),
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

	//CUSTOMER DETAILS
	public function getCustomerDetail(Request $r) {
		try {
			$job_order = JobOrder::with([
				'vehicle',
				'vehicle.model',
				'vehicle.status',
				'vehicle.currentOwner.customer',
				'vehicle.currentOwner.customer.address',
				'vehicle.currentOwner.customer.address.country',
				'vehicle.currentOwner.customer.address.state',
				'vehicle.currentOwner.customer.address.city',
				'vehicle.currentOwner.ownershipType',
				'status',
				'jobCard',
			])
				->select([
					'job_orders.*',
					DB::raw('DATE_FORMAT(job_orders.created_at,"%d/%m/%Y") as date'),
					DB::raw('DATE_FORMAT(job_orders.created_at,"%h:%i %p") as time'),
				])
				->where('job_orders.company_id', Auth::user()->company_id)
				->find($r->id);

			if (!$job_order) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Job Order Not Found!',
					],
				]);
			}
			//ENABLE ESTIMATE STATUS
			$inward_process_check = $job_order->inwardProcessChecks()->where('is_form_filled', 0)->first();
			if ($inward_process_check) {
				$job_order->enable_estimate_status = false;
			} else {
				$job_order->enable_estimate_status = true;
			}
			//DEDAULT COUNTRY
			$job_order->country = Country::find(1);
			//DEDAULT STATE
			$job_order->state = State::find(Auth::user()->employee->outlet->state_id);

			return response()->json([
				'success' => true,
				'job_order' => $job_order,
				'extras' => [
					'country_list' => Country::getDropDownList(),
					'state_list' => [], //State::getDropDownList(),
					'city_list' => [], //City::getDropDownList(),
					'ownership_type_list' => Config::getDropDownList(['config_type_id' => 39, 'default_text' => 'Select Ownership', 'orderBy' => 'id']),
				],
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

	public function saveCustomerDetail(Request $request) {
		try {

			DB::beginTransaction();

			$job_order = JobOrder::find($request->job_order_id);
			if (!$job_order) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Job Order Not Found!',
					],
				]);
			}

			$vehicle = $job_order->vehicle;

			if (!$vehicle) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Vehicle Not Found!',
					],
				]);
			}

			$error_messages = [
				'ownership_type_id.unique' => "Ownership ID is already taken",
			];

			$validator = Validator::make($request->all(), [
				'ownership_type_id' => [
					'required',
					'integer',
					'exists:configs,id',
					'unique:vehicle_owners,ownership_id,' . $request->id . ',customer_id,vehicle_id,' . $vehicle->id,
				],
				'code' => [
					'required',
					'min:3',
					'max:255',
					'unique:customers,code,' . $request->id,
				],
				'name' => [
					'required',
					'min:3',
					'max:255',
					'string',
				],
				'mobile_no' => [
					'required',
					'min:10',
					'max:10',
					'string',
				],
				'email' => [
					'nullable',
					'string',
					'max:255',
					'unique:customers,email,' . $request->id,
				],
				'address_line1' => [
					'required',
					'min:3',
					'max:32',
					'string',
				],
				'address_line2' => [
					'nullable',
					'min:3',
					'max:64',
					'string',
				],
				'country_id' => [
					'required',
					'integer',
					'exists:countries,id',
				],
				'state_id' => [
					'required',
					'integer',
					'exists:states,id',
				],
				'city_id' => [
					'required',
					'integer',
					'exists:cities,id',
				],
				'pincode' => [
					'required',
					'min:6',
					'max:6',
				],
				'gst_number' => [
					'nullable',
					'min:15',
					'max:15',
				],
				'pan_number' => [
					'nullable',
					'min:10',
					'max:10',
				],
			], $error_messages);

			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				]);
			}

			$customer = Customer::saveCustomer($request->all());
			$customer->saveAddress($request->all());

			if (!$request->id) {
				//NEW OWNER
				$vehicle_owner = new VehicleOwner;
				// $vehicle_owner->created_by_id = Auth::id();
				$vehicle_owner->vehicle_id = $vehicle->id;
				$vehicle_owner->from_date = Carbon::now();
				$vehicle_owner->created_by_id = Auth::id();
			} else {
				//NEW OWNER
				$vehicle_owner = VehicleOwner::where([
					'vehicle_id' => $vehicle->id,
					'customer_id' => $customer->id,
				])->first();
				$vehicle_owner->updated_by_id = Auth::id();
				$vehicle_owner->updated_at = Carbon::now();
			}

			$vehicle_owner->customer_id = $customer->id;
			$vehicle_owner->ownership_id = $request->ownership_type_id;
			$vehicle_owner->save();

			// INWARD PROCESS CHECK - CUSTOMER DETAIL
			$job_order->inwardProcessChecks()->where('tab_id', 8701)->update(['is_form_filled' => 1]);
			//CUSTOMER MAPPING
			$job_order->customer_id = $customer->id;
			$job_order->save();

			DB::commit();

			return response()->json([
				'success' => true,
				'message' => 'Customer detail saved Successfully!!',
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

	//JOB ORDER
	public function getOrderFormData(Request $r) {
		try {
			$job_order = JobOrder::with([
				'vehicle',
				'vehicle.model',
				'vehicle.status',
				'vehicle.lastJobOrder',
				'vehicle.lastJobOrder.jobCard',
				'type',
				'quoteType',
				'serviceType',
				'kmReadingType',
				'status',
				'driverLicenseAttachment',
				'insuranceAttachment',
				'rcBookAttachment',
			])
				->select([
					'job_orders.*',
					DB::raw('DATE_FORMAT(job_orders.created_at,"%d/%m/%Y") as date'),
					DB::raw('DATE_FORMAT(job_orders.created_at,"%h:%i %p") as time'),
				])
				->where('company_id', Auth::user()->company_id)
				->find($r->id);
			if (!$job_order) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Order Not Found!',
					],
				]);
			}
			//ENABLE ESTIMATE STATUS
			$inward_process_check = $job_order->inwardProcessChecks()->where('is_form_filled', 0)->first();
			if ($inward_process_check) {
				$job_order->enable_estimate_status = false;
			} else {
				$job_order->enable_estimate_status = true;
			}

			//Get Previous Service Types in Vehicle
			$service_type_ids = JobOrder::where('vehicle_id', $job_order->vehicle_id)
				->where('id', '!=', $job_order->id)
				->whereNotNull('service_type_id')
				->pluck('service_type_id')->toArray();

			$params['service_type_ids'] = $service_type_ids;
			$params['job_order_id'] = $r->id;

			if ($job_order->vehicle && $job_order->vehicle->model && $job_order->vehicle->model->vehicleSegment && $job_order->vehicle->model->vehicleSegment->vehicle_service_schedule) {
				$params['vehicle_service_schedule_id'] = $job_order->vehicle->model->vehicleSegment->vehicle_service_schedule->id;
			}

			$extras = [
				'job_order_type_list' => ServiceOrderType::getDropDownList(),
				'quote_type_list' => QuoteType::getDropDownList(),
				'service_type_list' => ServiceType::getDropDownList($params),
				'reading_type_list' => Config::getDropDownList([
					'config_type_id' => 33,
					'default_text' => 'Select Reading type',
				]),
			];

			//Job card details need to get future
			return response()->json([
				'success' => true,
				'job_order' => $job_order,
				'extras' => $extras,
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

	public function saveOrderDetail(Request $request) {
		// dd($request->all());
		try {

			//JOB ORDER SAVE
			$job_order = JobOrder::find($request->job_order_id);

			if (!$job_order) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Job Order Not Found!',
					],
				]);
			}

			$error_messages = [
				'service_type_id.unique' => "Service Type is already Processed",
			];

			$validator = Validator::make($request->all(), [
				'job_order_id' => [
					'required',
					'integer',
					'exists:job_orders,id',
				],
				'driver_name' => [
					'required',
					'string',
					'max:64',
				],
				'driver_mobile_number' => [
					'required',
					'min:10',
					'max:10',
					'string',
				],
				'km_reading_type_id' => [
					'required',
					'integer',
					'exists:configs,id',
				],
				'km_reading' => [
					'required_if:km_reading_type_id,==,8040',
					'numeric',
				],
				'hr_reading' => [
					'required_if:km_reading_type_id,==,8041',
					'numeric',
				],
				'type_id' => [
					'required',
					'integer',
					'exists:service_order_types,id',
				],
				'quote_type_id' => [
					'required',
					'integer',
					'exists:quote_types,id',
				],
				// 'service_type_id' => [
				// 	'required',
				// 	'integer',
				// 	'exists:service_types,id',
				// 	'unique:job_orders,service_type_id,' . $request->job_order_id . ',id,vehicle_id,' . $job_order->vehicle_id,
				// ],
				'contact_number' => [
					'nullable',
					'min:10',
					'max:10',
				],
				'driver_license_expiry_date' => [
					'required',
					'date',
				],
				'insurance_expiry_date' => [
					'required',
					'date',
				],
				'driving_license_image' => [
					'nullable',
					'mimes:jpeg,jpg,png',
				],
				'insurance_image' => [
					'nullable',
					'mimes:jpeg,jpg,png',
				],
				'rc_book_image' => [
					'nullable',
					'mimes:jpeg,jpg,png',
				],
			], $error_messages);

			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				]);
			}

			DB::beginTransaction();

			//Check Service Type changed or not.If changed remove all schedule maintenace
			if ($job_order->service_type_id != $request->service_type_id) {
				JobOrderPart::where('job_order_id', $request->job_order_id)->where('is_oem_recommended', 1)->forceDelete();
				JobOrderRepairOrder::where('job_order_id', $request->job_order_id)->where('is_recommended_by_oem', 1)->forceDelete();
				if ($job_order->vehicle && $job_order->vehicle->model && $job_order->vehicle->model->vehicleSegment && $job_order->vehicle->model->vehicleSegment->vehicle_service_schedule && $job_order->vehicle->model->vehicleSegment->vehicle_service_schedule->vehicle_service_schedule_service_types) {

					$estimate_id = JobOrderEstimate::where('job_order_id', $job_order->id)->where('status_id', 10071)->first();
					if ($estimate_id) {
						$estimate_order_id = $estimate_id->id;
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
						$branch = Outlet::where('id', $job_order->outlet_id)->first();

						//GENERATE GATE IN VEHICLE NUMBER
						$generateNumber = SerialNumberGroup::generateNumber(104, $financial_year->id, $branch->state_id, $branch->id);
						if (!$generateNumber['success']) {
							return response()->json([
								'success' => false,
								'error' => 'Validation Error',
								'errors' => [
									'No Estimate Reference number found for FY : ' . $financial_year->year . ', State : ' . $outlet->code . ', Outlet : ' . $outlet->code,
								],
							]);
						}

						$estimate = new JobOrderEstimate;
						$estimate->job_order_id = $job_order->id;
						$estimate->number = $generateNumber['number'];
						$estimate->status_id = 10071;
						$estimate->created_by_id = Auth::user()->id;
						$estimate->created_at = Carbon::now();
						$estimate->save();

						$estimate_order_id = $estimate->id;
					}

					foreach ($job_order->vehicle->model->vehicleSegment->vehicle_service_schedule->vehicle_service_schedule_service_types as $key => $value) {

						//Save Repair Orders
						if ($value->service_type_id == $request->service_type_id && $value->repair_orders) {
							foreach ($value->repair_orders as $rkey => $rvalue) {
								$repair_order = JobOrderRepairOrder::firstOrNew(['job_order_id' => $request->job_order_id, 'repair_order_id' => $rvalue->id]);

								$repair_order->is_recommended_by_oem = 1;
								$repair_order->is_fixed_schedule = 1;
								$repair_order->is_customer_approved = 0;
								$repair_order->split_order_type_id = $rvalue->pivot->split_order_type_id;
								$repair_order->qty = $rvalue->hours;
								$repair_order->amount = $rvalue->amount;
								$repair_order->is_free_service = $value->is_free;
								$repair_order->status_id = 8180; //Customer Approval Pending
								$repair_order->estimate_order_id = $estimate_order_id;
								$repair_order->created_by_id = Auth::user()->id;
								$repair_order->save();
							}
						}

						//Save Parts
						if ($value->service_type_id == $request->service_type_id && $value->parts) {
							foreach ($value->parts as $pkey => $pvalue) {
								$part_order = JobOrderPart::firstOrNew(['job_order_id' => $request->job_order_id, 'part_id' => $pvalue->id]);

								$part_order->qty = $pvalue->pivot->quantity;
								$part_order->split_order_type_id = $pvalue->pivot->split_order_type_id;
								$part_order->rate = $pvalue->rate;
								$part_order->amount = $pvalue->pivot->amount;
								$part_order->is_free_service = $value->is_free;
								$part_order->status_id = 8200; //Customer Approval Pending
								$part_order->is_oem_recommended = 1;
								$part_order->is_fixed_schedule = 1;
								$part_order->is_customer_approved = 0;
								$part_order->estimate_order_id = $estimate_order_id;
								$part_order->created_by_id = Auth::user()->id;
								$part_order->save();
							}
						}
					}
				}
			}

			$job_order->fill($request->all());
			$job_order->status_id = 8463;
			$job_order->updated_by_id = Auth::user()->id;
			$job_order->updated_at = Carbon::now();
			$job_order->save();

			//CREATE DIRECTORY TO STORAGE PATH
			$attachment_path = storage_path('app/public/gigo/job_order/attachments/');
			Storage::makeDirectory($attachment_path, 0777);

			//SAVE DRIVER PHOTO ATTACHMENT
			if (!empty($request->driving_license_image)) {
				$attachment = $request->driving_license_image;
				$entity_id = $job_order->id;
				$attachment_of_id = 227; //Job order
				$attachment_type_id = 251; //Driver License
				saveAttachment($attachment_path, $attachment, $entity_id, $attachment_of_id, $attachment_type_id);
			}
			//SAVE INSURANCE PHOTO ATTACHMENT
			if (!empty($request->insurance_image)) {
				$attachment = $request->insurance_image;
				$entity_id = $job_order->id;
				$attachment_of_id = 227; //Job order
				$attachment_type_id = 252; //Vehicle Insurance
				saveAttachment($attachment_path, $attachment, $entity_id, $attachment_of_id, $attachment_type_id);
			}
			//SAVE RC BOOK PHOTO ATTACHMENT
			if (!empty($request->rc_book_image)) {
				$attachment = $request->rc_book_image;
				$entity_id = $job_order->id;
				$attachment_of_id = 227; //Job order
				$attachment_type_id = 250; //RC Book
				saveAttachment($attachment_path, $attachment, $entity_id, $attachment_of_id, $attachment_type_id);
			}

			// INWARD PROCESS CHECK - ORDER DETAIL
			$job_order->inwardProcessChecks()->where('tab_id', 8702)->update(['is_form_filled' => 1]);

			DB::commit();

			return response()->json([
				'success' => true,
				'message' => 'Order Detail saved successfully!!',
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

	//Add Part Save
	public function saveAddtionalPart(Request $request) {
		// dd($request->all());
		try {
			$validator = Validator::make($request->all(), [
				'job_order_id' => [
					'required',
					'integer',
					'exists:job_orders,id',
				],
				'part_id' => [
					'required',
					'integer',
					'exists:parts,id',
				],

				/*'split_order_id' => [
					'required',
					'integer',
					'exists:split_order_types,id',
				],*/
				'qty' => [
					'required',
					'numeric',
				],

			]);

			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				]);
			}

			//Estimate Order ID
			$job_order = JobOrder::find($request->job_order_id);

			if (!$job_order) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Job Order Not Found!',
					],
				]);
			}

			$estimate_id = JobOrderEstimate::where('job_order_id', $job_order->id)->where('status_id', 10071)->first();
			if ($estimate_id) {
				$estimate_order_id = $estimate_id->id;
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
				$branch = Outlet::where('id', $job_order->outlet_id)->first();

				//GENERATE GATE IN VEHICLE NUMBER
				$generateNumber = SerialNumberGroup::generateNumber(104, $financial_year->id, $branch->state_id, $branch->id);
				if (!$generateNumber['success']) {
					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => [
							'No Estimate Reference number found for FY : ' . $financial_year->year . ', State : ' . $outlet->code . ', Outlet : ' . $outlet->code,
						],
					]);
				}

				$estimate = new JobOrderEstimate;
				$estimate->job_order_id = $job_order->id;
				$estimate->number = $generateNumber['number'];
				$estimate->status_id = 10071;
				$estimate->created_by_id = Auth::user()->id;
				$estimate->created_at = Carbon::now();
				$estimate->save();

				$estimate_order_id = $estimate->id;
			}

			DB::beginTransaction();
			$part = Part::where('id', $request->part_id)->first();

			if (!empty($request->job_order_part_id)) {
				$job_order_part = JobOrderPart::find($request->job_order_part_id);
			} else {
				$job_order_part = new JobOrderPart;
				$job_order_part->estimate_order_id = $estimate_order_id;
				$job_order_part->is_customer_approved = 0;
			}
			$job_order_part->job_order_id = $request->job_order_id;
			$job_order_part->part_id = $request->part_id;
			$job_order_part->qty = $request->qty;
			$job_order_part->rate = $part->mrp;
			$job_order_part->is_free_service = 0;
			if ($request->type == 'scheduled') {
				$job_order_part->is_oem_recommended = 1;
			} else {
				$job_order_part->is_oem_recommended = 0;
			}
			$job_order_part->split_order_type_id = $request->split_order_type_id;
			$job_order_part->amount = $request->qty * $part->mrp;
			$job_order_part->status_id = 8200; //Customer Approval Pending
			$job_order_part->created_by_id = Auth::user()->id;
			$job_order_part->save();

			$repair_order_part_array = [];
			$trim_data = str_replace('[', '', $request->repair_orders);
			$trim_data = str_replace(']', '', $trim_data);
			if ($trim_data != '') {
				$repair_orders = explode(',', $trim_data);
			} else {
				$repair_orders = [];
			}

			if (sizeof($repair_orders) > 0) {
				foreach ($repair_orders as $key => $value) {

					$repair_order_part_array[$key]['repair_order_id'] = $value;
					// $job_order_repair_order_part_array[$key]['job_order_part_id'] = $job_order_part->id;
					$repair_order_part_array[$key]['part_id'] = $request->part_id;
				}
				$repair_order_part_obj = Part::find($request->part_id);
				$repair_order_part_obj->repair_order_parts()->detach();

				$repair_order_part_obj->repair_order_parts()->sync($repair_order_part_array);
			}

			DB::commit();

			return response()->json([
				'success' => true,
				'message' => 'Part detail saved Successfully!!',
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

	public function sendRequestPartsIntent(Request $request) {
		// dd($request->all());
		try {

			if ($request->type_id == 4) {
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

				DB::beginTransaction();

				$job_card->status_id = 8224;
				$job_card->updated_by = Auth::user()->id;
				$job_card->updated_at = Carbon::now();
				$job_card->save();

				DB::commit();

			} else {
				$job_order = JobOrder::find($request->id);

				if (!$job_order) {
					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => [
							'Job Order Not Found!',
						],
					]);
				}

				DB::beginTransaction();

				if ($request->type_id == 1) {
					// $job_order->part_intent_status_id = 10070;
					$job_order->status_id = 8472;
				} elseif ($request->type_id == 2) {
					// $job_order->part_intent_status_id = 10071;
					$job_order->status_id = 8463;
					$job_order->part_intent_confirmed_date = Carbon::now();
				} else {
					// $job_order->part_intent_status_id = 10073;
					$job_order->part_intent_confirmed_date = Carbon::now();
				}

				$job_order->updated_by_id = Auth::user()->id;
				$job_order->updated_at = Carbon::now();
				$job_order->save();

				DB::commit();

			}

			return response()->json([
				'success' => true,
				'message' => 'Request Sent successfully!!',
			]);

		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}
	public function saveAddtionalLabour(Request $request) {
		// dd($request->all());
		try {
			$error_messages = [
				'rot_id.unique' => 'Labour is already taken',
			];

			$validator = Validator::make($request->all(), [
				'job_order_id' => [
					'required',
					'integer',
					'exists:job_orders,id',
				],
				'rot_id' => [
					'required',
					'integer',
					'exists:repair_orders,id',
					'unique:job_order_repair_orders,repair_order_id,' . $request->job_order_repair_order_id . ',id,job_order_id,' . $request->job_order_id,
				],
				'split_order_type_id' => [
					'required',
					'integer',
					'exists:split_order_types,id',
				],
			], $error_messages);

			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				]);
			}

			//Estimate Order ID
			$job_order = JobOrder::find($request->job_order_id);

			if (!$job_order) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Job Order Not Found!',
					],
				]);
			}

			$estimate_id = JobOrderEstimate::where('job_order_id', $job_order->id)->where('status_id', 10071)->first();
			if ($estimate_id) {
				$estimate_order_id = $estimate_id->id;
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
				$branch = Outlet::where('id', $job_order->outlet_id)->first();

				//GENERATE GATE IN VEHICLE NUMBER
				$generateNumber = SerialNumberGroup::generateNumber(104, $financial_year->id, $branch->state_id, $branch->id);
				if (!$generateNumber['success']) {
					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => [
							'No Estimate Reference number found for FY : ' . $financial_year->year . ', State : ' . $outlet->code . ', Outlet : ' . $outlet->code,
						],
					]);
				}

				$estimate = new JobOrderEstimate;
				$estimate->job_order_id = $job_order->id;
				$estimate->number = $generateNumber['number'];
				$estimate->status_id = 10071;
				$estimate->created_by_id = Auth::user()->id;
				$estimate->created_at = Carbon::now();
				$estimate->save();

				$estimate_order_id = $estimate->id;
			}

			$repair_order = RepairOrder::find($request->rot_id);

			DB::beginTransaction();

			if (!empty($request->job_order_repair_order_id)) {
				$job_order_repair_order = JobOrderRepairOrder::find($request->job_order_repair_order_id);
			} else {
				$job_order_repair_order = new JobOrderRepairOrder;
				$job_order_repair_order->estimate_order_id = $estimate_order_id;
				$job_order_repair_order->is_customer_approved = 0;
			}

			$job_order_repair_order->job_order_id = $request->job_order_id;
			$job_order_repair_order->repair_order_id = $request->rot_id;
			$job_order_repair_order->qty = $repair_order->hours;
			$job_order_repair_order->split_order_type_id = $request->split_order_type_id;
			$job_order_repair_order->amount = $repair_order->amount;
			$job_order_repair_order->is_free_service = 0;
			if ($request->type == 'scheduled') {
				$job_order_repair_order->is_recommended_by_oem = 1;
			} else {
				$job_order_repair_order->is_recommended_by_oem = 0;
			}
			$job_order_repair_order->status_id = 8180; //Customer Approval Pending
			$job_order_repair_order->created_by_id = Auth::user()->id;
			$job_order_repair_order->save();

			DB::commit();

			return response()->json([
				'success' => true,
				'message' => 'Repair order detail saved successfully!!',
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

	//INVENTORY
	public function getInventoryFormData(Request $r) {
		//dd($r->all());
		try {
			$job_order = JobOrder::with([
				'vehicle',
				'vehicle.model',
				'vehicle.status',
				'status',
			])
				->select([
					'job_orders.*',
					DB::raw('DATE_FORMAT(job_orders.created_at,"%d/%m/%Y") as date'),
					DB::raw('DATE_FORMAT(job_orders.created_at,"%h:%i %p") as time'),
				])
				->where('company_id', Auth::user()->company_id)
				->find($r->id);

			if (!$job_order) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Job Order Not Found!',
					],
				]);
			}
			$params['field_type_id'] = [11, 12];
			$extras = [
				'inventory_type_list' => VehicleInventoryItem::getInventoryList($job_order->id, $params),
			];
			//ENABLE ESTIMATE STATUS
			$inward_process_check = $job_order->inwardProcessChecks()->where('is_form_filled', 0)->first();
			if ($inward_process_check) {
				$job_order->enable_estimate_status = false;
			} else {
				$job_order->enable_estimate_status = true;
			}

			return response()->json([
				'success' => true,
				'job_order' => $job_order,
				'extras' => $extras,
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

	public function saveInventoryItem(Request $request) {
		// dd($request->all());
		try {
			$validator = Validator::make($request->all(), [
				'job_order_id' => [
					'required',
					'integer',
					'exists:job_orders,id',
				],
				// 'vehicle_inventory_items.*.id' => [
				// 	'required',
				// 	'numeric',
				// 	'exists:vehicle_inventory_items,id',
				// ],
				'vehicle_inventory_items.*.is_available' => [
					'required',
					'numeric',
				],
				'vehicle_inventory_items.*.remarks' => [
					'nullable',
					'string',
				],
			]);

			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				]);
			}
			// $vehicle_inventory_items_count = count($request->vehicle_inventory_items);
			// $vehicle_inventory_unique_items_count = count(array_unique(array_column($request->vehicle_inventory_items, 'inventory_item_id')));
			// if ($vehicle_inventory_items_count != $vehicle_inventory_unique_items_count) {
			// 	return response()->json([
			// 		'success' => false,
			// 		'error' => 'Validation Error',
			// 		'errors' => ['Inventory items are not unique'],
			// 	]);
			// }

			//issue: saravanan - validations syntax wrong
			/*$items_validator = Validator::make($request->vehicle_inventory_items, [
				'inventory_item_id.*' => [
					'required',
					'numeric',
					'exists:vehicle_inventory_items,id',
				],
				'is_available.*' => [
					'required',
					'numeric',
				],
				'remarks.*' => [
					'nullable',
					'string',
				],

			]);
			if ($items_validator->fails()) {
				return response()->json(['success' => false, 'errors' => $items_validator->errors()->all()]);
			}*/

			$job_order = JobOrder::find($request->job_order_id);
			$job_order->status_id = 8463;
			$job_order->save();
			if (!$job_order) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Job order Not found!',
					],
				]);
			}

			DB::beginTransaction();

			$job_order->vehicleInventoryItem()->sync([]);
			// if (isset($request->vehicle_inventory_items) && count($request->vehicle_inventory_items) > 0) {
			//dd($request->vehicle_inventory_items);
			// $job_order->vehicleInventoryItem()->detach();
			// //Inserting Inventory Items
			// foreach ($request->vehicle_inventory_items as $key => $vehicle_inventory_item) {
			// 	$job_order->vehicleInventoryItem()
			// 		->attach(
			// 			$vehicle_inventory_item['inventory_item_id'],
			// 			[
			// 				'is_available' => $vehicle_inventory_item['is_available'],
			// 				'remarks' => $vehicle_inventory_item['remarks'],
			// 			]
			// 		);
			// }

			// }
			if ($request->vehicle_inventory_items) {
				foreach ($request->vehicle_inventory_items as $key => $vehicle_inventory_item) {
					if (isset($vehicle_inventory_item['inventory_item_id']) && $vehicle_inventory_item['is_available'] == 1) {
						$job_order->vehicleInventoryItem()
							->attach(
								$vehicle_inventory_item['inventory_item_id'],
								[
									'is_available' => 1,
									'remarks' => $vehicle_inventory_item['remarks'],
								]
							);
					}
				}
			}
			DB::commit();
			return response()->json([
				'success' => true,
				'message' => 'Vehicle inventory items saved successfully',
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

	//DMS GET FORM DATA
	public function getDmsCheckListFormData(Request $r) {
		try {

			$job_order = JobOrder::
				with([
				'vehicle',
				'vehicle.model',
				'vehicle.status',
				'vehicle.lastJobOrder',
				'vehicle.lastJobOrder.jobCard',
				'type',
				'quoteType',
				'serviceType',
				'kmReadingType',
				'status',
				'warrentyPolicyAttachment',
				'EWPAttachment',
				'AMCAttachment',
			])
				->select([
					'job_orders.*',
					DB::raw('DATE_FORMAT(job_orders.created_at,"%d/%m/%Y") as date'),
					DB::raw('DATE_FORMAT(job_orders.created_at,"%h:%i %p") as time'),
				])
				->find($r->id);

			if (!$job_order) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Job order Not found!',
					],
				]);
			}

			if (!$job_order->is_campaign_carried) {
				$nameSpace = '\\App\\';
				$entity = 'Campaign';
				$namespaceModel = $nameSpace . $entity;
				$campaigns = $this->compaigns($namespaceModel, $job_order, 0);
			} else {
				$nameSpace = '\\App\\';
				$entity = 'JobOrderCampaign';
				$namespaceModel = $nameSpace . $entity;
				$campaigns = $this->compaigns($namespaceModel, $job_order, 1);
			}

			//ENABLE ESTIMATE STATUS
			$inward_process_check = $job_order->inwardProcessChecks()->where('is_form_filled', 0)->first();
			if ($inward_process_check) {
				$job_order->enable_estimate_status = false;
			} else {
				$job_order->enable_estimate_status = true;
			}

			return response()->json([
				'success' => true,
				'job_order' => $job_order,
				'campaigns' => $campaigns,
				'attachement_path' => url('storage/app/public/gigo/job_order/attachments/'),
			]);
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

	//DMS CHECKLIST SAVE
	public function saveDmsCheckList(Request $request) {
		// dd($request->all());
		// $request['warranty_expiry_date'] = date('d-m-Y', strtotime($request->warranty_expiry_date));
		$request['ewp_expiry_date'] = date('d-m-Y', strtotime($request->ewp_expiry_date));
		try {
			$validator = Validator::make($request->all(), [
				'job_order_id' => [
					'required',
					'integer',
					'exists:job_orders,id',
				],
				// 'warranty_expiry_date' => [
				// 	"required_if:warrany_status,==,1",
				// 	'date_format:"d-m-Y',
				// ],
				'amc_starting_date' => [
					"required_if:amc_status,==,1",
					'date_format:"d-m-Y',
				],
				'amc_ending_date' => [
					"required_if:amc_status,==,1",
					'date_format:"d-m-Y',
				],
				'warranty_expiry_attachment' => [
					// "required_if:warrany_status,==,1",
					'mimes:jpeg,jpg,png',
				],
				'ewp_expiry_date' => [
					"required_if:exwarrany_status,==,1",
					'date_format:"d-m-Y',
				],
				'ewp_expiry_attachment' => [
					// "required_if:exwarrany_status,==,1",
					'mimes:jpeg,jpg,png',
				],
				'membership_attachment.*' => [
					'nullable',
					'mimes:jpeg,jpg,png',
				],
				'is_verified' => [
					// 'nullable',
					'numeric',
				],
				'starting_km' => [
					"required_if:amc_status,==,1",
				],
				'ending_km' => [
					"required_if:amc_status,==,1",
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
			$job_order = JobOrder::find($request->job_order_id);

			if (!$job_order) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Job order Not found!',
					],
				]);
			}

			// if ($request->warrany_status == 0) {
			// 	// $job_order->warranty_expiry_date = NULL;

			// } else {
			// 	$job_order->warranty_expiry_date = $request->warranty_expiry_date;
			// }

			if ($request->exwarrany_status == 0) {
				$job_order->ewp_expiry_date = NULL;
				$attachment = Attachment::where('id', $request->job_order_id)->where('attachment_of_id', 227)->where('attachment_type_id', 257)->forceDelete();
			} else {
				$job_order->ewp_expiry_date = $request->ewp_expiry_date;
			}

			if ($request->amc_status == 1 && $request->warrany_status == 1) {
				if (strtotime($request->amc_starting_date) > strtotime($request->amc_ending_date)) {
					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => [
							'AMC Ending Date should be greater than AMC Starting Date',
						],
					]);
				}

				$job_order->amc_status = 1;
				$job_order->starting_km = $request->starting_km;
				$job_order->ending_km = $request->ending_km;
				$job_order->amc_starting_date = date('Y-m-d', strtotime($request->amc_starting_date));
				$job_order->amc_ending_date = date('Y-m-d', strtotime($request->amc_ending_date));
			} else {

				if ($request->warrany_status == 1) {
					$job_order->amc_status = 0;
				} else {
					$job_order->amc_status = NULL;
				}
				$job_order->amc_starting_date = NULL;
				$job_order->amc_ending_date = NULL;
				$job_order->starting_km = NULL;
				$job_order->ending_km = NULL;

				$attachment = Attachment::where('id', $request->job_order_id)->where('attachment_of_id', 227)->where('attachment_type_id', 256)->forceDelete();
			}

			$job_order->is_dms_verified = $request->is_verified;
			$job_order->status_id = 8463;
			if (isset($request->is_campaign_carried)) {
				$job_order->is_campaign_carried = $request->is_campaign_carried;
			}
			$job_order->campaign_not_carried_remarks = isset($request->campaign_not_carried_remarks) ? $request->campaign_not_carried_remarks : NULL;
			$job_order->save();

			if (isset($request->is_campaign_carried) && $request->is_campaign_carried == 1) {
				if ($job_order->campaigns()->count() == 0) {
					if (isset($request->campaign_ids)) {
						$campaigns = Campaign::with([
							'chassisNumbers',
							'complaintType',
							'campaignLabours',
							'campaignParts',
						])
							->whereIn('id', $request->campaign_ids)
							->get();
						// dd($campaigns);
						if (!empty($campaigns)) {
							foreach ($campaigns as $key => $campaign) {
								//SAVE JobOrderCampaign
								$job_order_campaign = new JobOrderCampaign;
								$job_order_campaign->job_order_id = $job_order->id;
								$job_order_campaign->campaign_id = $campaign->id;
								$job_order_campaign->authorisation_no = $campaign->authorisation_no;
								$job_order_campaign->complaint_id = $campaign->complaint_id;
								$job_order_campaign->fault_id = $campaign->fault_id;
								$job_order_campaign->claim_type_id = $campaign->claim_type_id;
								$job_order_campaign->campaign_type = $campaign->campaign_type;
								$job_order_campaign->vehicle_model_id = $campaign->vehicle_model_id;
								$job_order_campaign->manufacture_date = $campaign->manufacture_date;
								$job_order_campaign->created_by_id = Auth::user()->id;
								$job_order_campaign->created_at = Carbon::now();
								$job_order_campaign->save();

								//SAVE JobOrderCampaign Repair Orders
								$job_order_campaign->campaignLabours()->sync([]);
								if (count($campaign->campaignLabours) > 0) {
									foreach ($campaign->campaignLabours as $key => $labour) {
										$job_order_campaign->campaignLabours()->attach($labour->id, [
											'amount' => $labour->pivot->amount,
										]);
									}
								}

								//SAVE JobOrderCampaign Parts
								$job_order_campaign->campaignParts()->sync([]);
								if (count($campaign->campaignParts) > 0) {
									foreach ($campaign->campaignParts as $key => $part) {
										$job_order_campaign->campaignParts()->attach($part->id);
									}
								}
								//SAVE JobOrderCampaign Chassis Number
								if (count($campaign->chassisNumbers) > 0) {
									$job_order_campaign_chassis_number = new JobOrderCampaignChassisNumber;
									$job_order_campaign_chassis_number->job_order_campaign_id = $job_order_campaign->id;
									$job_order_campaign_chassis_number->chassis_number = $job_order->vehicle->chassis_number;
									$job_order_campaign_chassis_number->created_by_id = Auth::user()->id;
									$job_order_campaign_chassis_number->created_at = Carbon::now();
									$job_order_campaign_chassis_number->save();
								}
							}
						}
					}
				}
			} else {
				//REMOVE LAST JOB ORDER CAMPAIGNS
				$job_order->campaigns()->forceDelete();
			}

			//CREATE DIRECTORY TO STORAGE PATH
			$attachment_path = storage_path('app/public/gigo/job_order/attachments/');
			Storage::makeDirectory($attachment_path, 0777);

			//SAVE WARRANTY EXPIRY PHOTO ATTACHMENT
			if (!empty($request->warranty_expiry_attachment)) {
				$attachment = $request->warranty_expiry_attachment;
				$entity_id = $job_order->id;
				$attachment_of_id = 227; //Job order
				$attachment_type_id = 256; //Warranty Policy
				saveAttachment($attachment_path, $attachment, $entity_id, $attachment_of_id, $attachment_type_id);
			}
			if (!empty($request->ewp_expiry_attachment)) {
				$attachment = $request->ewp_expiry_attachment;
				$entity_id = $job_order->id;
				$attachment_of_id = 227; //Job order
				$attachment_type_id = 257; //EWP
				saveAttachment($attachment_path, $attachment, $entity_id, $attachment_of_id, $attachment_type_id);
			}

			//MULTIPLE ATTACHMENT REMOVAL
			$attachment_removal_ids = json_decode($request->attachment_removal_ids);
			if (!empty($attachment_removal_ids)) {
				Attachment::whereIn('id', $attachment_removal_ids)->forceDelete();
			}

			if (!empty($request->membership_attachments)) {
				foreach ($request->membership_attachments as $key => $membership_attachment) {
					$value = rand(1, 100);
					$image = $membership_attachment;

					$file_name_with_extension = $image->getClientOriginalName();
					$file_name = pathinfo($file_name_with_extension, PATHINFO_FILENAME);
					$extension = $image->getClientOriginalExtension();
					$name = $job_order->id . '_' . $file_name . '_' . rand(10, 1000) . '.' . $extension;

					$membership_attachment->move(storage_path('app/public/gigo/job_order/attachments/'), $name);
					$attachement = new Attachment;
					$attachement->attachment_of_id = 227; //Job order
					$attachement->attachment_type_id = 258; //AMC
					$attachement->entity_id = $job_order->id;
					$attachement->name = $name;
					$attachement->save();
				}
			}

			// INWARD PROCESS CHECK - DMS CHECKLIST
			$job_order->inwardProcessChecks()->where('tab_id', 8704)->update(['is_form_filled' => 1]);

			DB::commit();
			return response()->json([
				'success' => true,
				'message' => 'Vehicle DMS checklist saved successfully',
			]);
		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'message' => 'Server Error',
				'errors' => [
					'Error : ' . $e->getMessage() . '. Line : ' . $e->getLine() . '. File : ' . $e->getFile(),
				],
			]);
		}
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
				$labour_details[$key]['repair_order'] = $value->repairOrder;
				$labour_details[$key]['is_fixed_schedule'] = $value->is_fixed_schedule;
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
				$part_details[$key]['part'] = $value->part;
				$part_details[$key]['is_fixed_schedule'] = $value->is_fixed_schedule;
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

		$result['job_order'] = $job_order;
		$result['labour_details'] = $labour_details;
		$result['part_details'] = $part_details;
		$result['labour_amount'] = $labour_amount;
		$result['part_amount'] = $part_amount;
		$result['total_amount'] = $total_amount;

		return $result;
	}

	//ScheduleMaintenance Form Data
	public function getScheduleMaintenanceFormData(Request $r) {
		// dd($r->all());
		try {

			$job_order = JobOrder::find($r->id);

			if (!$job_order) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Job Order Not Found',
					],
				]);
			}

			$params['job_order_id'] = $r->id;
			$params['type_id'] = 1;

			$result = $this->getLabourPartsData($params);

			//ENABLE ESTIMATE STATUS
			$inward_process_check = $job_order->inwardProcessChecks()->where('is_form_filled', 1)->first();
			if ($inward_process_check) {
				$job_order->enable_estimate_status = false;
			} else {
				$job_order->enable_estimate_status = true;
			}

			$job_order->inwardProcessChecks()->where('tab_id', 8705)->update(['is_form_filled' => 1]);

			return response()->json([
				'success' => true,
				'job_order' => $result['job_order'],
				'part_details' => $result['part_details'],
				'labour_details' => $result['labour_details'],
				'total_amount' => $result['total_amount'],
				'labour_amount' => $result['labour_amount'],
				'parts_rate' => $result['part_amount'],
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

	public function saveScheduleMaintenance(Request $request) {
		// dd($request->all());
		try {
			$validator = Validator::make($request->all(), [
				'job_order_id' => [
					'required',
					'integer',
					'exists:job_orders,id',
				],
				'job_order_parts.*.part_id' => [
					'required:true',
					'integer',
					'exists:parts,id',
				],
				'job_order_parts.*.qty' => [
					'required',
					'numeric',
					'regex:/^\d+(\.\d{1,2})?$/',
				],
				'job_order_parts.*.rate' => [
					'required',
					'numeric',
					'regex:/^\d+(\.\d{1,2})?$/',
				],
				'job_order_repair_orders.*.repair_order_id' => [
					'required:true',
					'integer',
					'exists:repair_orders,id',
				],
				/*'job_order_repair_orders.*.qty' => [
					'required',
					'numeric',
					'regex:/^\d+(\.\d{1,2})?$/',
				],*/
				'job_order_repair_orders.*.amount' => [
					'required',
					'numeric',
					'regex:/^\d+(\.\d{1,2})?$/',
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

			$customer_paid_type = SplitOrderType::where('paid_by_id', '10013')->first();

			// //Remove Schedule Part Details
			// if (!empty($request->parts_removal_ids)) {
			// 	$parts_removal_ids = json_decode($request->parts_removal_ids, true);
			// 	JobOrderPart::whereIn('part_id', $parts_removal_ids)->where('job_order_id', $request->job_order_id)->forceDelete();
			// }
			// //Remove Schedule Labour Details
			// if (!empty($request->labour_removal_ids)) {
			// 	$labour_removal_ids = json_decode($request->labour_removal_ids, true);
			// 	JobOrderRepairOrder::whereIn('repair_order_id', $labour_removal_ids)->where('job_order_id', $request->job_order_id)->forceDelete();
			// }

			if (isset($request->job_order_parts) && count($request->job_order_parts) > 0) {
				//Inserting Job order parts
				foreach ($request->job_order_parts as $key => $part) {
					// dd($part);
					$job_order_part = JobOrderPart::firstOrNew([
						'part_id' => $part['part_id'],
						'job_order_id' => $request->job_order_id,
					]);
					$job_order_part->fill($part);
					// $job_order_part->split_order_type_id = $customer_paid_type ? $customer_paid_type->id : NULL;
					$job_order_part->is_oem_recommended = 1;
					$job_order_part->status_id = 8200; //Customer Approval Pending
					$job_order_part->save();
				}
			}

			if (isset($request->job_order_repair_orders) && count($request->job_order_repair_orders) > 0) {
				//Inserting Job order repair orders
				foreach ($request->job_order_repair_orders as $key => $repair) {
					// dd($repair);
					$job_order_repair_order = JobOrderRepairOrder::firstOrNew([
						'repair_order_id' => $repair['repair_order_id'],
						'job_order_id' => $request->job_order_id,
					]);
					$job_order_repair_order->fill($repair);
					// $job_order_repair_order->split_order_type_id = $customer_paid_type ? $customer_paid_type->id : NULL;
					$job_order_repair_order->is_recommended_by_oem = 1;
					$job_order_repair_order->is_customer_approved = 0;
					$job_order_repair_order->status_id = 8180; //Customer Approval Pending
					$job_order_repair_order->save();
				}
			}
			// INWARD PROCESS CHECK - Schedule Maintenance
			$job_order = JobOrder::find($request->job_order_id);
			$job_order->status_id = 8463;
			$job_order->save();
			$job_order->inwardProcessChecks()->where('tab_id', 8705)->update(['is_form_filled' => 1]);

			DB::commit();
			return response()->json([
				'success' => true,
				'message' => 'Schedule Maintenance saved successfully',
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

	//Addtional Rot & Part GetList
	public function addtionalRotPartGetList(Request $r) {
		// dd($r->all());
		try {

			$job_order = JobOrder::find($r->id);

			if (!$job_order) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Job Order Not Found',
					],
				]);
			}

			$params['job_order_id'] = $r->id;
			$params['type_id'] = 0;

			$result = $this->getLabourPartsData($params);

			//ENABLE ESTIMATE STATUS
			$inward_process_check = $job_order->inwardProcessChecks()->where('is_form_filled', 0)->first();
			if ($inward_process_check) {
				$job_order->enable_estimate_status = false;
			} else {
				$job_order->enable_estimate_status = true;
			}

			$total_labour_count = JobOrderRepairOrder::where('job_order_id', $r->id)->whereNull('removal_reason_id')->count();

			return response()->json([
				'success' => true,
				'job_order' => $result['job_order'],
				'part_details' => $result['part_details'],
				'labour_details' => $result['labour_details'],
				'total_amount' => $result['total_amount'],
				'total_labour_count' => $total_labour_count,
				'labour_total_amount' => $result['labour_amount'],
				'parts_total_amount' => $result['part_amount'],
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

	public function saveAddtionalRotPart(Request $request) {
		// dd($request->all());
		try {
			$validator = Validator::make($request->all(), [
				'job_order_id' => [
					'required',
					'integer',
					'exists:job_orders,id',
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

			//DELETE Job Order Repair Orders
			if (isset($request->delete_job_order_repair_order_ids) && !empty($request->delete_job_order_repair_order_ids)) {
				$delete_job_order_repair_order_ids = json_decode($request->delete_job_order_repair_order_ids);
				JobOrderRepairOrder::whereIn('id', $delete_job_order_repair_order_ids)->forceDelete();
			}

			//DELETE Job Order Parts
			if (isset($request->delete_job_order_part_ids) && !empty($request->delete_job_order_part_ids)) {
				$delete_job_order_part_ids = json_decode($request->delete_job_order_part_ids);
				JobOrderPart::whereIn('id', $delete_job_order_part_ids)->forceDelete();
			}

			DB::commit();
			return response()->json([
				'success' => true,
				'message' => 'Other Labour & Parts details saved successfully!!',
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

	//Get Addtional Part Form Data
	public function getPartList(Request $r) {
		try {
			$job_order = JobOrder::with(['jobcard'])->find($r->id);
			if (!$job_order) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Job Order Not Found!',
					],
				]);
			}

			$extras = [
				'part_list' => Part::getList(),
				'split_order_list' => SplitOrderType::get(),
			];

			return response()->json([
				'success' => true,
				'job_order' => $job_order,
				'extras' => $extras,
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

	//Get Addtional Rot Form Data
	public function getRepairOrderTypeList(Request $r) {
		try {
			$job_order = JobOrder::with([
				'jobCard',
			])->find($r->id);

			if (!$job_order) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Job Order Not Found!',
					],
				]);
			}
			$extras = [
				'rot_type_list' => RepairOrderType::getList(),
				'split_order_list' => SplitOrderType::get(),
			];
			return response()->json([
				'success' => true,
				'job_order' => $job_order,
				'extras' => $extras,
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

	//Get Addtional Rot List
	public function getAddtionalRotList(Request $r) {
		//dd($r->all());
		try {
			$repair_order_type = RepairOrderType::find($r->id);
			if (!$repair_order_type) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Repair order type not found!',
					],
				]);
			}
			$rot_list = RepairOrder::roList($repair_order_type->id);

			$extras_list = [
				'rot_list' => $rot_list,
				'split_order_list' => SplitOrderType::get(),
			];

			return response()->json([
				'success' => true,
				'extras_list' => $extras_list,
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

	//Get Addtional Rot
	public function getRepairOrderData(Request $r) {
		try {
			$repair_order = RepairOrder::with([
				'repairOrderType',
				'uom',
				'taxCode',
				'skillLevel',
			])
				->find($r->id);
			if (!$repair_order) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Repair order not found!',
					],
				]);
			}

			return response()->json([
				'success' => true,
				'repair_order' => $repair_order,
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

	//Get Addtional Rot
	public function getJobOrderRepairOrderData(Request $r) {
		try {
			$job_order_repair_order = JobOrderRepairOrder::with([
				'repairOrder',
				'repairOrder.repairOrderType',
				'repairOrder.uom',
				'repairOrder.taxCode',
				'repairOrder.skillLevel',
			])
				->find($r->id);
			if (!$job_order_repair_order) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Job order repair order not found!',
					],
				]);
			}

			return response()->json([
				'success' => true,
				'job_order_repair_order' => $job_order_repair_order,
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

	//Get Addtional Part
	public function getPartData(Request $r) {
		try {
			$job_order = JobOrder::with('jobcard')->find($r->job_order_id);
			if (!$job_order) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Job Order Not Found!',
					],
				]);
			}

			$part = Part::with([
				'uom',
				'taxCode',
			])
				->find($r->id);
			if (!$part) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Part not found!',
					],
				]);
			}
			return response()->json([
				'success' => true,
				'part' => $part,
				'job_order' => $job_order,
				'split_order_list' => SplitOrderType::get(),
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

	//Get Job Order Part
	public function getJobOrderPartData(Request $r) {
		try {
			$job_order = JobOrder::with(['jobcard'])->find($r->job_order_id);
			if (!$job_order) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Job Order Not Found!',
					],
				]);
			}

			$job_order_part = JobOrderPart::with([
				'part',
				'part.uom',
				'part.taxCode',
			])
				->find($r->id);
			if (!$job_order_part) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Job order part not found!',
					],
				]);
			}
			return response()->json([
				'success' => true,
				'job_order_part' => $job_order_part,
				'job_order' => $job_order,
				'split_order_list' => SplitOrderType::get(),
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

	//GET STATE BASED COUNTRY
	public function getState($country_id) {
		$this->data = Country::getState($country_id);
		$this->data['success'] = true;
		return response()->json($this->data);
	}

	//GET CITY BASED STATE
	public function getcity($state_id) {
		$this->data = State::getCity($state_id);
		$this->data['success'] = true;
		return response()->json($this->data);
	}

	//VOICE OF CUSTOMER(VOC) GET FORM DATA
	public function getVocFormData(Request $r) {
		try {

			$job_order = JobOrder::with([
				'vehicle',
				'vehicle.model',
				'vehicle.model.customerVoices',
				'vehicle.status',
				'status',
				'customerVoices',
			])
				->select([
					'job_orders.*',
					DB::raw('DATE_FORMAT(job_orders.created_at,"%d/%m/%Y") as date'),
					DB::raw('DATE_FORMAT(job_orders.created_at,"%h:%i %p") as time'),
				])
				->where('company_id', Auth::user()->company_id)
				->find($r->id);

			if (!$job_order) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Job Order Not Found',
					],
				]);
			}
			if ($job_order->customerVoices->count() > 0) {
				$action = 'edit';
			} else {
				$action = 'add';
			}

			$customer_voice_list = $job_order->vehicle->model->customerVoices->toArray();
			$customer_voice_other = CustomerVoice::where('code', 'OTH')->get()->toArray();

			if ($customer_voice_other) {
				//GET CUSTOMER VOICE OTHERS ID OF OTH
				$customer_voice_other_id = $customer_voice_other[0]['id'];
				$job_order['OTH_ID'] = $customer_voice_other_id;

				$customer_voice_list_merge = array_merge($customer_voice_list, $customer_voice_other);
				$customer_voice_list = collect($customer_voice_list_merge);
			}

			$extras = [
				'customer_voice_list' => $customer_voice_list,
			];

			//ENABLE ESTIMATE STATUS
			$inward_process_check = $job_order->inwardProcessChecks()->where('is_form_filled', 0)->first();
			if ($inward_process_check) {
				$job_order->enable_estimate_status = false;
			} else {
				$job_order->enable_estimate_status = true;
			}

			return response()->json([
				'success' => true,
				'extras' => $extras,
				'action' => $action,
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

	//VOICE OF CUSTOMER(VOC) SAVE
	public function saveVoc(Request $request) {
		// dd($request->all());
		DB::beginTransaction();
		try {
			$validator = Validator::make($request->all(), [
				'job_order_id' => [
					'required',
					'exists:job_orders,id',
					'integer',
				],
				'customer_voices.*.id' => [
					'required',
					'integer',
					'exists:customer_voices,id',
					// 'distinct',
				],
				'customer_voices.*.details' => [
					'required_if:customer_voices.*.id,' . $request->OTH_ID,
					'string',
				],
			]);

			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				]);
			}

			$job_order = JobOrder::with(['customerVoices'])->find($request->job_order_id);
			$job_order->status_id = 8463;
			$job_order->save();

			$customer_voice_ids = collect($request->customer_voices)->pluck('id')->toArray();

			//REMOVE REPAIR ORDER WHILE CHANGING VOC
			foreach ($job_order->customerVoices as $customer_voice) {
				if (!in_array($customer_voice->id, $customer_voice_ids)) {
					$delete_job_repair_order = JobOrderRepairOrder::where('job_order_id', $request->job_order_id)->where('repair_order_id', $customer_voice->repair_order_id)->where('is_recommended_by_oem', 0)->forceDelete();
				}
			}

			$job_order->customerVoices()->sync([]);

			if (!empty($request->customer_voices)) {
				//UNIQUE CHECK
				$customer_voices = collect($request->customer_voices)->pluck('id')->count();
				$unique_customer_voices = collect($request->customer_voices)->pluck('id')->unique()->count();
				if ($customer_voices != $unique_customer_voices) {
					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => [
							'Voice Of Customer already taken',
						],
					]);
				}

				$customer_paid_type = SplitOrderType::where('paid_by_id', '10013')->first();

				//Estimate Order ID
				$estimate_id = JobOrderEstimate::where('job_order_id', $job_order->id)->where('status_id', 10071)->first();
				if ($estimate_id) {
					$estimate_order_id = $estimate_id->id;
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
					$branch = Outlet::where('id', $job_order->outlet_id)->first();

					//GENERATE GATE IN VEHICLE NUMBER
					$generateNumber = SerialNumberGroup::generateNumber(104, $financial_year->id, $branch->state_id, $branch->id);
					if (!$generateNumber['success']) {
						return response()->json([
							'success' => false,
							'error' => 'Validation Error',
							'errors' => [
								'No Estimate Reference number found for FY : ' . $financial_year->year . ', State : ' . $outlet->code . ', Outlet : ' . $outlet->code,
							],
						]);
					}

					$estimate = new JobOrderEstimate;
					$estimate->job_order_id = $job_order->id;
					$estimate->number = $generateNumber['number'];
					$estimate->status_id = 10071;
					$estimate->created_by_id = Auth::user()->id;
					$estimate->created_at = Carbon::now();
					$estimate->save();

					$estimate_order_id = $estimate->id;
				}

				foreach ($request->customer_voices as $key => $voice) {
					$customer_voice = CustomerVoice::with(['repair_order'])
						->where('id', $voice['id'])
						->first();

					if ($customer_voice->repair_order_id) {

						$skip_job_repair_order = JobOrderRepairOrder::where('job_order_id', $request->job_order_id)->where('repair_order_id', $customer_voice->repair_order->id)
							->first();

						$job_order->customerVoices()->attach($voice['id'], [
							'details' => isset($voice['details']) ? $voice['details'] : NULL,
						]);

						if ($skip_job_repair_order) {
							continue;
						} else {
							$job_repair_order = new JobOrderRepairOrder;
							$job_repair_order->job_order_id = $request->job_order_id;
							$job_repair_order->repair_order_id = $customer_voice->repair_order->id;
							$job_repair_order->is_recommended_by_oem = 0;
							$job_repair_order->is_customer_approved = 0;
							$job_repair_order->estimate_order_id = $estimate_order_id;
							$job_repair_order->split_order_type_id = $customer_paid_type->id;
							$job_repair_order->qty = $customer_voice->repair_order->hours;
							$job_repair_order->amount = $customer_voice->repair_order->amount;
							$job_repair_order->status_id = 8180;
							$job_repair_order->save();
						}
					} else {
						$job_order->customerVoices()->attach($voice['id'], [
							'details' => isset($voice['details']) ? $voice['details'] : NULL,
						]);
					}
				}
			}

			DB::commit();

			return response()->json([
				'success' => true,
				'message' => 'VOC Saved Successfully',
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

	//ROAD TEST OBSERVATION GET FORM DATA
	public function getRoadTestObservationFormData(Request $r) {
		try {
			$job_order = JobOrder::with([
				'vehicle',
				'vehicle.model',
				'vehicle.status',
				'status',
				'roadTestDoneBy',
				'roadTestPreferedBy',
			])
				->select([
					'job_orders.*',
					DB::raw('DATE_FORMAT(job_orders.created_at,"%d/%m/%Y") as date'),
					DB::raw('DATE_FORMAT(job_orders.created_at,"%h:%i %p") as time'),
				])
				->where('company_id', Auth::user()->company_id)
				->find($r->id);

			if (!$job_order) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Job Order Not Found',
					],
				]);
			}
			//ENABLE ESTIMATE STATUS
			$inward_process_check = $job_order->inwardProcessChecks()->where('is_form_filled', 0)->first();
			if ($inward_process_check) {
				$job_order->enable_estimate_status = false;
			} else {
				$job_order->enable_estimate_status = true;
			}

			$extras = [
				'road_test_by' => Config::getDropDownList(['config_type_id' => 36, 'add_default' => false]), //ROAD TEST DONE BY
				'user_list' => User::getUserEmployeeList(['road_test' => true]),
			];

			return response()->json([
				'success' => true,
				'extras' => $extras,
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

	//ROAD TEST OBSERVATION SAVE
	public function saveRoadTestObservation(Request $request) {
		// dd($request->all());
		DB::beginTransaction();
		try {
			$validator = Validator::make($request->all(), [
				'is_road_test_required' => [
					'required',
					'integer',
					'max:1',
				],
				'job_order_id' => [
					'required',
					'integer',
					'exists:job_orders,id',
				],
				'road_test_done_by_id' => [
					'required_if:is_road_test_required,1',
					'exists:configs,id',
					'integer',
				],
				'road_test_performed_by_id' => [
					'nullable',
					'integer',
					'exists:users,id',
				],
				// 'road_test_report' => [
				// 	'required_if:is_road_test_required,1',
				// 	'string',
				// ],
			]);

			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				]);
			}
			//EMPLOYEE
			if ($request->is_road_test_required == 1 && $request->road_test_done_by_id == 8101) {
				if (!$request->road_test_performed_by_id) {
					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => [
							'Driver for Road Test is required.',
						],
					]);
				}
			}

			$job_order = JobOrder::find($request->job_order_id);
			if ($request->is_road_test_required == 1) {
				$job_order->is_road_test_required = $request->is_road_test_required;
				$job_order->road_test_done_by_id = $request->road_test_done_by_id;
				if ($request->road_test_done_by_id == 8101) {
					// EMPLOYEE
					$job_order->road_test_performed_by_id = $request->road_test_performed_by_id;
				} else {
					$job_order->road_test_performed_by_id = NULL;
				}
				$job_order->road_test_report = $request->road_test_report;
			} else {
				$job_order->is_road_test_required = $request->is_road_test_required;
				$job_order->road_test_done_by_id = NULL;
				$job_order->road_test_performed_by_id = NULL;
				$job_order->road_test_report = NULL;
			}
			$job_order->updated_by_id = Auth::user()->id;
			$job_order->updated_at = Carbon::now();
			$job_order->status_id = 8463;
			$job_order->save();

			// INWARD PROCESS CHECK - ROAD TEST OBSERVATIONS
			$job_order->inwardProcessChecks()->where('tab_id', 8707)->update(['is_form_filled' => 1]);

			DB::commit();
			return response()->json([
				'success' => true,
				'message' => 'Road Test Observation Saved Successfully',
			]);
		} catch (\Exception $e) {
			// DB::rollBack();
			return response()->json([
				'success' => false,
				'error' => 'Server Error',
				'errors' => [
					'Error : ' . $e->getMessage() . '. Line : ' . $e->getLine() . '. File : ' . $e->getFile(),
				],
			]);
		}
	}

	//EXPERT DIAGNOSIS REPORT GET FORM DATA
	public function getExpertDiagnosisReportFormData(Request $r) {
		try {
			$job_order = JobOrder::with([
				'vehicle',
				'vehicle.model',
				'vehicle.status',
				'status',
				'expertDiagnosisReportBy',
			])
				->select([
					'job_orders.*',
					DB::raw('DATE_FORMAT(job_orders.created_at,"%d/%m/%Y") as date'),
					DB::raw('DATE_FORMAT(job_orders.created_at,"%h:%i %p") as time'),
				])
				->where('company_id', Auth::user()->company_id)
				->find($r->id);
			if (!$job_order) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Job Order Not Found',
					],
				]);
			}
			//ENABLE ESTIMATE STATUS
			$inward_process_check = $job_order->inwardProcessChecks()->where('is_form_filled', 0)->first();
			if ($inward_process_check) {
				$job_order->enable_estimate_status = false;
			} else {
				$job_order->enable_estimate_status = true;
			}

			$extras = [
				'user_list' => User::getUserEmployeeList(['road_test' => false]),
			];

			return response()->json([
				'success' => true,
				'extras' => $extras,
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

	//EXPERT DIAGNOSIS REPORT SAVE
	public function saveExpertDiagnosisReport(Request $request) {
		// dd($request->all());
		try {
			$validator = Validator::make($request->all(), [
				'job_order_id' => [
					'required_if:is_expert_diagnosis_required,1',
					'integer',
					'exists:job_orders,id',
				],
				'expert_diagnosis_report_by_id' => [
					'required_if:is_expert_diagnosis_required,1',
					'exists:users,id',
					'integer',
				],
				'expert_diagnosis_report' => [
					'required_if:is_expert_diagnosis_required,1',
					'string',
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

			$job_order = JobOrder::find($request->job_order_id);
			if ($request->is_expert_diagnosis_required == 1) {
				$job_order->expert_diagnosis_report = $request->expert_diagnosis_report;
				$job_order->expert_diagnosis_report_by_id = $request->expert_diagnosis_report_by_id;
			} else {
				$job_order->expert_diagnosis_report = NULL;
				$job_order->expert_diagnosis_report_by_id = NULL;
			}

			$job_order->is_expert_diagnosis_required = $request->is_expert_diagnosis_required;
			$job_order->status_id = 8463;
			$job_order->updated_by_id = Auth::user()->id;
			$job_order->updated_at = Carbon::now();
			$job_order->save();

			// INWARD PROCESS CHECK - EXPERT DIAGNOSIS REPORT
			$job_order->inwardProcessChecks()->where('tab_id', 8703)->update(['is_form_filled' => 1]);

			DB::commit();

			return response()->json([
				'success' => true,
				'message' => 'Expert Diagnosis Report Saved Successfully',
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

	//VEHICLE INSPECTION GET FORM DATA
	public function getVehicleInspectiongetFormData(Request $r) {
		try {

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
				->find($r->id);

			if (!$job_order) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Job Order Not Found',
					],
				]);
			}

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

			//ENABLE ESTIMATE STATUS
			$inward_process_check = $job_order->inwardProcessChecks()->where('is_form_filled', 0)->first();
			if ($inward_process_check) {
				$job_order->enable_estimate_status = false;
			} else {
				$job_order->enable_estimate_status = true;
			}

			$params['config_type_id'] = 32;
			$params['add_default'] = false;
			$extras = [
				'inspection_results' => Config::getDropDownList($params), //VEHICLE INSPECTION RESULTS
			];

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

	//VEHICLE INSPECTION SAVE
	public function saveVehicleInspection(Request $request) {
		// dd($request->all());
		DB::beginTransaction();
		try {
			$validator = Validator::make($request->all(), [
				'job_order_id' => [
					'required',
					'integer',
					'exists:job_orders,id',
				],
				// 'vehicle_inspection_groups.*.vehicle_inspection_item_id' => [
				// 	'required',
				// 	'exists:vehicle_inspection_items,id',
				// 	'integer',
				// ],
				// 'vehicle_inspection_groups.*.vehicle_inspection_result_status_id' => [
				// 	'required',
				// 	'exists:configs,id',
				// 	'integer',
				// ],
				'vehicle_inspection_items' => 'required|array',
			]);

			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				]);
			}

			$job_order = jobOrder::find($request->job_order_id);
			$job_order->status_id = 8463;
			$job_order->save();
			// if ($request->vehicle_inspection_groups) {
			// 	$job_order->vehicleInspectionItems()->sync([]);
			// 	foreach ($request->vehicle_inspection_groups as $key => $vehicle_inspection_group) {
			// 		$job_order->vehicleInspectionItems()->attach($vehicle_inspection_group['vehicle_inspection_item_id'],
			// 			[
			// 				'status_id' => $vehicle_inspection_group['vehicle_inspection_result_status_id'],
			// 			]);
			// 	}
			// }
			if ($request->vehicle_inspection_items) {
				$job_order->vehicleInspectionItems()->sync([]);
				foreach ($request->vehicle_inspection_items as $key => $vehicle_inspection_item) {
					$job_order->vehicleInspectionItems()->attach($key,
						[
							'status_id' => $vehicle_inspection_item,
						]);
				}
			}

			DB::commit();

			return response()->json([
				'success' => true,
				'message' => 'Vehicle Inspection Added Successfully',
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

	//ESTIMATE GET FORM DATA
	public function getEstimateFormData(Request $r) {
		// dd($r->all());
		try {
			$customer_paid_type_id = SplitOrderType::where('paid_by_id', '10013')->pluck('id')->toArray();

			$job_order = JobOrder::with([
				'vehicle',
				'vehicle.model',
				'jobOrderRepairOrders' => function ($q) use ($customer_paid_type_id) {
					$q->whereNull('removal_reason_id');
				},
				'jobOrderParts' => function ($q) use ($customer_paid_type_id) {
					$q->whereNull('removal_reason_id');
					// $q->whereIn('split_order_type_id', $customer_paid_type_id)->whereNull('removal_reason_id');
				},
				'type',
				'quoteType',
				'serviceType',
				'status',
			])
				->select([
					'job_orders.*',
					DB::raw('DATE_FORMAT(job_orders.created_at,"%d/%m/%Y") as date'),
					DB::raw('DATE_FORMAT(job_orders.created_at,"%h:%i %p") as time'),
				])
				->find($r->id);

			if (!$job_order) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Job Order Not Found',
					],
				]);
			}

			if (!$job_order->vehicle->currentOwner) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Customer Details not found',
					],
				]);
			}

			//Check which tax applicable for customer
			if ($job_order->outlet->state_id == $job_order->vehicle->currentOwner->customer->primaryAddress->state_id) {
				$tax_type = 1160; //Within State
			} else {
				$tax_type = 1161; //Inter State
			}

			//Count Tax Type
			$taxes = Tax::get();

			$oem_recomentaion_labour_amount = 0;
			$additional_rot_and_parts_labour_amount = 0;

			$oem_recomentaion_labour_amount_include_tax = 0;
			$additional_rot_and_parts_labour_amount_include_tax = 0;
			$total_labour_hours = JobOrderRepairOrder::where('job_order_id', $r->id)->sum('qty');

			$total_schedule_labour_tax = 0;
			$total_schedule_labour_amount = 0;
			$total_payable_labour_tax = 0;
			$total_payable_labour_amount = 0;

			//Repair Orders
			if ($job_order->jobOrderRepairOrders) {
				foreach ($job_order->jobOrderRepairOrders as $key => $labour) {
					if (in_array($labour->split_order_type_id, $customer_paid_type_id) || !$labour->split_order_type_id) {
						//SCHEDULE MAINTANENCE
						if ($labour->is_recommended_by_oem == 1 && $labour->is_free_service != 1) {
							if ($labour->repairOrder->taxCode) {
								$tax_amount = 0;
								$total_amount = 0;
								foreach ($labour->repairOrder->taxCode->taxes as $tax_key => $value) {
									$percentage_value = 0;
									if ($value->type_id == $tax_type) {
										$percentage_value = ($labour->amount * $value->pivot->percentage) / 100;
										$percentage_value = number_format((float) $percentage_value, 2, '.', '');
									}
									$tax_amount += $percentage_value;
								}
								$total_schedule_labour_tax += $tax_amount;
								$total_amount = $tax_amount + $labour->amount;
								$total_schedule_labour_amount += $total_amount;
							} else {
								$total_schedule_labour_amount += $labour->amount;
							}
						}
						//PAYABLE
						if ($labour->is_recommended_by_oem == 0 && $labour->is_free_service != 1) {
							if ($labour->repairOrder->taxCode) {
								$tax_amount = 0;
								$total_amount = 0;
								foreach ($labour->repairOrder->taxCode->taxes as $tax_key => $value) {
									$percentage_value = 0;
									if ($value->type_id == $tax_type) {
										$percentage_value = ($labour->amount * $value->pivot->percentage) / 100;
										$percentage_value = number_format((float) $percentage_value, 2, '.', '');
									}
									$tax_amount += $percentage_value;
								}
								$total_payable_labour_tax += $tax_amount;
								$total_amount = $tax_amount + $labour->amount;
								$total_payable_labour_amount += $total_amount;
							} else {
								$total_payable_labour_amount += $labour->amount;
							}
						}
					}
				}
			}

			$total_schedule_part_amount = 0;
			$total_schedule_part_tax = 0;
			$total_payable_part_tax = 0;
			$total_payable_part_amount = 0;

			//Parts
			if ($job_order->jobOrderParts) {
				foreach ($job_order->jobOrderParts as $key => $parts) {
					if (in_array($parts->split_order_type_id, $customer_paid_type_id) || !$parts->split_order_type_id) {
						//SCHEDULE MAINTANENCE
						if ($parts->is_oem_recommended == 1 && $parts->is_free_service != 1) {
							if ($parts->part->taxCode) {
								$tax_amount = 0;
								$total_amount = 0;
								foreach ($parts->part->taxCode->taxes as $tax_key => $value) {
									$percentage_value = 0;
									if ($value->type_id == $tax_type) {
										$percentage_value = ($parts->amount * $value->pivot->percentage) / 100;
										$percentage_value = number_format((float) $percentage_value, 2, '.', '');
									}
									$tax_amount += $percentage_value;
								}
								$total_schedule_part_tax += $tax_amount;
								$total_amount = $tax_amount + $parts->amount;
								$total_schedule_part_amount += $total_amount;
							} else {
								$total_schedule_part_amount += $parts->amount;
							}
						}
						if ($parts->is_oem_recommended == 0 && $parts->is_free_service != 1) {
							if ($parts->part->taxCode) {
								$tax_amount = 0;
								$total_amount = 0;
								foreach ($parts->part->taxCode->taxes as $tax_key => $value) {
									$percentage_value = 0;
									if ($value->type_id == $tax_type) {
										$percentage_value = ($parts->amount * $value->pivot->percentage) / 100;
										$percentage_value = number_format((float) $percentage_value, 2, '.', '');
									}
									$tax_amount += $percentage_value;
								}
								$total_payable_part_tax += $tax_amount;
								$total_amount = $tax_amount + $parts->amount;
								$total_payable_part_amount += $total_amount;
							} else {
								$total_payable_part_amount += $parts->amount;
							}
						}
					}
				}
			}

			$schedule_tax_total = $total_schedule_labour_tax + $total_schedule_part_tax;

			$payable_tax_total = $total_payable_labour_tax + $total_payable_part_tax;

			$total_amount = $total_schedule_labour_amount + $total_schedule_part_amount + $total_payable_labour_amount + $total_payable_part_amount;
			$total_tax_amount = $schedule_tax_total + $payable_tax_total;

			//OEM RECOMENTATION LABOUR AND PARTS AND SUB TOTAL
			$job_order->oem_recomentation_labour_amount = $total_schedule_labour_amount;
			$job_order->oem_recomentation_part_amount = $total_schedule_part_amount;
			$job_order->oem_recomentation_tax_total = $schedule_tax_total;
			$job_order->oem_recomentation_sub_total = $total_schedule_labour_amount + $total_schedule_part_amount;

			//ADDITIONAL ROT & PARTS LABOUR AND PARTS AND SUB TOTAL
			$job_order->additional_rot_parts_labour_amount = $total_payable_labour_amount;
			$job_order->additional_rot_parts_part_amount = $total_payable_part_amount;
			$job_order->additional_rot_parts_tax_total = $payable_tax_total;
			$job_order->additional_rot_parts_sub_total = $total_payable_labour_amount + $total_payable_part_amount;

			//TOTAL ESTIMATE
			$job_order->total_estimate_labour_amount = $total_schedule_labour_amount + $total_payable_labour_amount;

			$job_order->total_estimate_parts_amount = $total_schedule_part_amount + $total_payable_part_amount;

			$job_order->total_tax_amount = $total_tax_amount;
			$job_order->total_estimate_amount = round($total_amount);

			if (empty($job_order->estimated_amount)) {
				$job_order->min_estimated_amount = $job_order->total_estimate_amount;
				$job_order->estimated_amount = $job_order->total_estimate_amount;
			} else {
				$job_order->min_estimated_amount = $job_order->estimated_amount;
			}

			//ENABLE ESTIMATE STATUS
			$inward_process_check = $job_order->inwardProcessChecks()->where('is_form_filled', 0)->first();
			if ($inward_process_check) {
				$job_order->enable_estimate_status = false;
			} else {
				$job_order->enable_estimate_status = true;
			}

			$job_order->total_labour_hours = round($total_labour_hours);

			$estimation_date = date("Y-m-d H:i:s", strtotime('+' . $job_order->total_labour_hours . ' hours', strtotime($job_order->created_at)));
			// dd($job_order->created_at, $estimation_date);
			$job_order->est_date = date("d-m-Y", strtotime($estimation_date));
			$job_order->est_time = date("h:i a", strtotime($estimation_date));

			return response()->json([
				'success' => true,
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

	//ESTIMATE SAVE
	public function saveEstimate(Request $request) {
		// dd($request->all());
		DB::beginTransaction();
		try {
			$validator = Validator::make($request->all(), [
				'job_order_id' => [
					'required',
					'integer',
					'exists:job_orders,id',
				],
				'estimated_amount' => [
					'required',
					'string',
				],
				'est_delivery_date' => [
					'required',
					'string',
				],
				'est_delivery_time' => [
					'required',
					'string',
				],
				//WAITING FOR CONFIRMATION -- NOT CONFIRMED
				'is_customer_agreed' => [
					'nullable',
				],
			]);

			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'message' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				]);
			}

			//CHECK ALL INWARD MANDATORY FORM ARE FILLED
			$job_order = jobOrder::find($request->job_order_id);

			$inward_process_check = $job_order->inwardProcessChecks()
				->where('tab_id', '!=', 8706)
				->where('is_form_filled', 0)
				->first();
			if ($inward_process_check) {
				return response()->json([
					'success' => false,
					'message' => 'Validation Error',
					'errors' => [
						'Please Save ' . $inward_process_check->name,
					],
				]);
			}

			if ($job_order->is_road_test_required == 1 && !$job_order->road_test_report) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Please Update Road Test Observations',
					],
				]);
			}

			$job_order->estimated_amount = $request->estimated_amount;
			$estimated_delivery_date = $request->est_delivery_date . ' ' . $request->est_delivery_time;
			$job_order->estimated_delivery_date = date('Y-m-d H:i:s', strtotime($estimated_delivery_date));
			$job_order->is_customer_agreed = $request->is_customer_agreed;
			$job_order->updated_by_id = Auth::user()->id;
			$job_order->status_id = 8463;
			$job_order->updated_at = Carbon::now();
			$job_order->save();

			// INWARD PROCESS CHECK - ESTIMATE
			$job_order->inwardProcessChecks()->where('tab_id', 8706)->update(['is_form_filled' => 1]);

			DB::commit();

			return response()->json([
				'success' => true,
				'job_order' => $job_order,
				'message' => 'Estimate Details Saved Successfully',
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

	public function sendCustomerOtp(Request $request) {
		// dd($request->all());
		try {
			$job_order = JobOrder::with([
				'customer',
			])
				->find($request->id);

			if (!$job_order) {
				return response()->json([
					'success' => false,
					'error' => 'Job Order Found!',
				]);
			}

			$customer_mobile = $job_order->customer->mobile_no;

			if (!$customer_mobile) {
				return response()->json([
					'success' => false,
					'error' => 'Customer Mobile Number Not Found',
				]);
			}

			DB::beginTransaction();

			$job_order_otp_update = JobOrder::where('id', $request->id)
				->update([
					'otp_no' => mt_rand(111111, 999999),
					'status_id' => 8469, //Waiting for Customer Approval
					'is_customer_approved' => 0,
					'updated_by_id' => Auth::user()->id,
					'updated_at' => Carbon::now(),
				]);

			DB::commit();
			if (!$job_order_otp_update) {
				return response()->json([
					'success' => false,
					'error' => 'Job Card OTP Update Failed',
				]);
			}

			$job_order = JobOrder::find($request->id);

			$otp = $job_order->otp_no;

			$message = 'OTP is ' . $otp . ' for Job Card Approve On Behalf of Customer. Please enter OTP to verify your Job Card Approval';

			$msg = sendSMSNotification($customer_mobile, $message);

			return response()->json([
				'success' => true,
				'mobile_number' => $customer_mobile,
				'message' => 'OTP Sent successfully!!',
			]);
		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	public function verifyOtp(Request $request) {
		// dd($request->all());
		try {

			$validator = Validator::make($request->all(), [
				'job_order_id' => [
					'required',
					'exists:job_orders,id',
					'integer',
				],
				'otp_no' => [
					'required',
					'min:8',
					'integer',
				],
				'verify_otp' => [
					'required',
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

			$job_order = JobOrder::with([
				'gateLog',
			])
				->find($request->job_order_id);

			if (!$job_order) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => ['Job Order Not Found!'],
				]);
			}

			DB::beginTransaction();

			$otp_validate = JobOrder::where('id', $request->job_order_id)
				->where('otp_no', '=', $request->otp_no)
				->first();
			if (!$otp_validate) {
				return response()->json([
					'success' => false,
					'error' => 'Job Order Approve Behalf of Customer OTP is wrong. Please try again.',
				]);
			}

			//UPDATE JOB ORDER STATUS
			$job_order_status_update = JobOrder::find($request->job_order_id);
			$job_order_status_update->status_id = 8463; //Vehicle Inward Inprogress
			$job_order_status_update->is_customer_approved = 1;
			$job_order_status_update->updated_at = Carbon::now();
			$job_order_status_update->save();

			//UPDATE JOB ORDER REPAIR ORDER STATUS
			JobOrderRepairOrder::where('job_order_id', $request->job_order_id)->where('is_customer_approved', 0)->update(['is_customer_approved' => 1, 'status_id' => 8181, 'updated_by_id' => Auth::user()->id, 'updated_at' => Carbon::now()]);

			//UPDATE JOB ORDER PARTS STATUS
			JobOrderPart::where('job_order_id', $request->job_order_id)->where('is_customer_approved', 0)->update(['is_customer_approved' => 1, 'status_id' => 8201, 'updated_by_id' => Auth::user()->id, 'updated_at' => Carbon::now()]);

			JobOrderEstimate::where('job_order_id', $request->job_order_id)->where('status_id', 10071)->update(['status_id' => 10072, 'updated_by_id' => Auth::user()->id, 'updated_at' => Carbon::now()]);

			DB::commit();

			return response()->json([
				'success' => true,
				'message' => 'Customer Approved Successfully',
			]);
		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	public function generateUrl(Request $request) {
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
			$job_order->is_customer_approved = 0;
			$job_order->updated_by_id = Auth::user()->id;
			$job_order->updated_at = Carbon::now();
			$job_order->save();

			$url = url('/') . '/vehicle-inward/estimate/customer/view/' . $request->job_order_id . '/' . $job_order->otp_no;

			$short_url = ShortUrl::createShortLink($url, $maxlength = "7");

			$message = 'Dear Customer,Kindly click below link to approve for TVS job order ' . $short_url . ' Vehicle Reg Number : ' . $vehicle_no;

			$msg = sendSMSNotification($customer_mobile, $message);

			//Update JobOrder Estimate
			$job_order_estimate = JobOrderEstimate::where('job_order_id', $job_order->id)->orderBy('id', 'DESC')->first();
			$job_order_estimate->status_id = 10071;
			$job_order_estimate->updated_by_id = Auth::user()->id;
			$job_order_estimate->updated_at = Carbon::now();
			$job_order_estimate->save();

			DB::commit();

			return response()->json([
				'success' => true,
				'message' => 'URL send to Customer Successfully',
			]);
		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Network Down!',
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}

	// ESTIMATION DENIED GET FORM DATA
	public function getEstimationDeniedFormData(Request $r) {
		try {

			$job_order = JobOrder::with([
				'vehicle',
				'vehicle.model',
				'jobOrderRepairOrders',
				'jobOrderParts',
				'type',
				'quoteType',
				'serviceType',
				'status',
			])
				->select([
					'job_orders.*',
					DB::raw('DATE_FORMAT(job_orders.created_at,"%d/%m/%Y") as date'),
					DB::raw('DATE_FORMAT(job_orders.created_at,"%h:%i %p") as time'),
				])
				->find($r->id);

			if (!$job_order) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Job Order Not Found',
					],
				]);
			}
			$estimation_type = collect(EstimationType::select(
				'name',
				'id',
				'minimum_amount'
			)
					->where('company_id', Auth::user()->company_id)
					->get())
				->prepend(['id' => '', 'name' => 'Select Estimation Type', 'minimum_amount' => '']);

			//ENABLE ESTIMATE STATUS
			$inward_process_check = $job_order->inwardProcessChecks()->where('is_form_filled', 0)->first();
			if ($inward_process_check) {
				$job_order->enable_estimate_status = false;
			} else {
				$job_order->enable_estimate_status = true;
			}

			return response()->json([
				'success' => true,
				'estimation_type' => $estimation_type,
				'job_order' => $job_order,
			]);
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

	//ESTIMATION DENIED SAVE
	public function saveEstimateDenied(Request $request) {
		// dd($request->all());
		DB::beginTransaction();
		try {
			$validator = Validator::make($request->all(), [
				'job_order_id' => [
					'required',
					'integer',
					'exists:job_orders,id',
				],
				'estimation_type_id' => [
					'required',
					'integer',
					'exists:estimation_types,id',
				],
				'minimum_payable_amount' => [
					'required',
					'numeric',
				],
			]);

			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				]);
			}

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

			//GENERATE GATE IN VEHICLE NUMBER
			$generateNumber = SerialNumberGroup::generateNumber(25, $financial_year->id, $branch->state_id, $branch->id);
			if (!$generateNumber['success']) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'No Estimate Reference number found for FY : ' . $financial_year->year . ', State : ' . $outlet->code . ', Outlet : ' . $outlet->code,
					],
				]);
			}

			$job_order = JobOrder::find($request->job_order_id);
			$job_order->estimation_type_id = $request->estimation_type_id;
			$job_order->minimum_payable_amount = $request->minimum_payable_amount;
			$job_order->estimate_ref_no = $generateNumber['number'];
			$job_order->status_id = 8470;
			$job_order->updated_by_id = Auth::user()->id;
			$job_order->updated_at = Carbon::now();
			$job_order->save();

			//Update Gatelog Status
			$gate_log = Gatelog::where('job_order_id', $job_order->id)
				->update([
					'status_id' => 8122, //Vehicle Inward Completed
					'updated_by_id' => Auth::user()->id,
					'updated_at' => Carbon::now(),
				]);

			$customer_detail = Customer::select('customers.name', 'customers.mobile_no', 'vehicles.registration_number')
				->join('vehicle_owners', 'vehicle_owners.customer_id', 'customers.id')
				->join('vehicles', 'vehicle_owners.vehicle_id', 'vehicles.id')
				->join('job_orders', 'job_orders.vehicle_id', 'vehicles.id')
				->where('job_orders.id', $job_order->id)
				->orderBy('vehicle_owners.from_date', 'DESC')
				->first();

			if (!$customer_detail) {
				return response()->json([
					'success' => false,
					'error' => 'Customer Details Not Found!',
				]);
			}

			$mobile_number = $customer_detail->mobile_no;

			if (!$mobile_number) {
				return response()->json([
					'success' => false,
					'error' => 'Customer Mobile Number Not Found',
				]);
			}

			$url = url('/') . '/vehicle-inward/estimate/view/' . $job_order->id;

			$short_url = ShortUrl::createShortLink($url, $maxlength = "7");

			$message = 'Dear Customer,Kindly click below link to pay for TVS job order ' . $short_url . ' Vehicle Reg Number : ' . $customer_detail->registration_number;

			$msg = sendSMSNotification($mobile_number, $message);

			DB::commit();

			return response()->json([
				'success' => true,
				'job_order' => $job_order,
				'message' => 'Estimation Details Sent to Cusotmer Successfully',
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

	// CUSTOMER CONFIRMATION GET FORM DATA
	public function getCustomerConfirmationFormData(Request $r) {
		try {
			$job_order = JobOrder::with([
				'vehicle',
				'vehicle.model',
				'jobOrderRepairOrders',
				'jobOrderParts',
				'type',
				'quoteType',
				'serviceType',
				'status',
				'customerApprovalAttachment',
				'customerESign',
			])
				->select([
					'job_orders.*',
					DB::raw('DATE_FORMAT(job_orders.created_at,"%d/%m/%Y") as date'),
					DB::raw('DATE_FORMAT(job_orders.created_at,"%h:%i %p") as time'),
				])
				->find($r->id);

			if (!$job_order) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'Job Order Not Found',
					],
				]);
			}

			$extras = [
				'base_url' => url('/'),
			];

			//ENABLE ESTIMATE STATUS
			$inward_process_check = $job_order->inwardProcessChecks()->where('is_form_filled', 0)->first();
			if ($inward_process_check) {
				$job_order->enable_estimate_status = false;
			} else {
				$job_order->enable_estimate_status = true;
			}

			return response()->json([
				'success' => true,
				'job_order' => $job_order,
				'extras' => $extras,
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

	public function saveCustomerConfirmation(Request $request) {
		// dd($request->all());
		try {
			if ($request->web == 'website') {
				$validator = Validator::make($request->all(), [
					'job_order_id' => [
						'required',
						'integer',
						'exists:job_orders,id',
					],
					'customer_photo' => [
						'required_if:customer_photo_exist,0',
						// 'mimes:jpeg,jpg,png',
					],
					'customer_e_sign' => [
						'required_if:customer_photo_exist,0',
						// 	// 'mimes:jpeg,jpg,png',
					],
				]);
			} else {
				$validator = Validator::make($request->all(), [
					'job_order_id' => [
						'required',
						'integer',
						'exists:job_orders,id',
					],
					'customer_photo' => [
						'required',
						'mimes:jpeg,jpg,png',
					],
					'customer_e_sign' => [
						'required',
						'mimes:jpeg,jpg,png',
					],
				]);
			}
			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				]);
			}

			$attachment_path = storage_path('app/public/gigo/job_order/customer-confirmation/');
			Storage::makeDirectory($attachment_path, 0777);

			DB::beginTransaction();

			if ($request->web == 'website') {
				//CUSTOMER SIGN
				if (!empty($request->customer_photo)) {
					$remove_previous_attachment = Attachment::where([
						'entity_id' => $request->job_order_id,
						'attachment_of_id' => 227,
						'attachment_type_id' => 254,
					])->forceDelete();

					$customer_photo = str_replace('data:image/jpeg;base64,', '', $request->customer_photo);
					$customer_photo = str_replace(' ', '+', $customer_photo);

					$filename = "webcam_customer_photo_" . strtotime("now") . ".jpeg";

					File::put($attachment_path . $filename, base64_decode($customer_photo));

					//SAVE ATTACHMENT
					$attachment = new Attachment;
					$attachment->attachment_of_id = 227; //JOB ORDER
					$attachment->attachment_type_id = 254; //CUSTOMER SIGN PHOTO
					$attachment->entity_id = $request->job_order_id;
					$attachment->name = $filename;
					$attachment->created_by = auth()->user()->id;
					$attachment->created_at = Carbon::now();
					$attachment->save();
				}
				//CUSTOMER E SIGN
				if (!empty($request->customer_e_sign)) {
					$remove_previous_attachment = Attachment::where([
						'entity_id' => $request->job_order_id,
						'attachment_of_id' => 227,
						'attachment_type_id' => 253,
					])->forceDelete();

					$customer_sign = str_replace('data:image/png;base64,', '', $request->customer_e_sign);
					$customer_sign = str_replace(' ', '+', $customer_sign);

					$filename = "webcam_customer_sign_" . strtotime("now") . ".png";

					File::put($attachment_path . $filename, base64_decode($customer_sign));

					//SAVE ATTACHMENT
					$attachment = new Attachment;
					$attachment->attachment_of_id = 227; //JOB ORDER
					$attachment->attachment_type_id = 253; //CUSTOMER E SIGN
					$attachment->entity_id = $request->job_order_id;
					$attachment->name = $filename;
					$attachment->created_by = auth()->user()->id;
					$attachment->created_at = Carbon::now();
					$attachment->save();
				}
			} else {
				if (!empty($request->customer_photo)) {
					$attachment = $request->customer_photo;
					$entity_id = $request->job_order_id;
					$attachment_of_id = 227; //JOB ORDER
					$attachment_type_id = 254; //CUSTOMER SIGN PHOTO
					saveAttachment($attachment_path, $attachment, $entity_id, $attachment_of_id, $attachment_type_id);
				}
				if (!empty($request->customer_e_sign)) {
					$attachment = $request->customer_e_sign;
					$entity_id = $request->job_order_id;
					$attachment_of_id = 227; //JOB ORDER
					$attachment_type_id = 253; //CUSTOMER E SIGN
					saveAttachment($attachment_path, $attachment, $entity_id, $attachment_of_id, $attachment_type_id);
				}
			}
			//UPDATE JOB ORDER REPAIR ORDER STATUS UPDATE
			//issue: readability
			$job_order_repair_order_status_update = JobOrderRepairOrder::where('job_order_id', $request->job_order_id)
				->update([
					'status_id' => 8181, //MACHANIC NOT ASSIGNED
					'updated_by_id' => Auth::user()->id,
					'updated_at' => Carbon::now(),
				]);

			//UPDATE JOB ORDER PARTS STATUS UPDATE
			//issue: readability
			$job_order_parts_status_update = JobOrderPart::where('job_order_id', $request->job_order_id)
				->update([
					'status_id' => 8201, //NOT ISSUED
					'updated_by_id' => Auth::user()->id,
					'updated_at' => Carbon::now(),
				]);

			// //UPDATE GATE LOG STATUS
			// $gate_log = GateLog::where('id', $request->gate_log_id)->update(['status_id', 8122, 'updated_by_id' => Auth::user()->id, 'updated_at' => Carbon::now()]); //VEHICLE INWARD COMPLETED

			//GET TOTAL AMOUNT IN PARTS AND LABOUR
			$request['id'] = $request->job_order_id; // ID ADDED FOR BELOW FUNCTION TO FIND BASED ON ID
			$repair_order_and_parts_detils = $this->getEstimateFormData($request);

			DB::commit();

			return response()->json([
				'success' => true,
				'message' => 'Vehicle Inwarded Successfully',
				'repair_order_and_parts_detils' => $repair_order_and_parts_detils,
			]);
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

	//INITIATE NEW JOB
	public function saveInitiateJob(Request $request) {
		// dd($request->all());
		DB::beginTransaction();
		try {
			$validator = Validator::make($request->all(), [
				'job_order_id' => [
					'required',
					'integer',
					'exists:job_orders,id',
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
				'gateLog',
			])
				->find($request->job_order_id);
			$job_order->status_id = 8461;
			$job_order->part_intent_confirmed_date = NULL;
			$job_order->save();

			//UPDATE GATE LOG STATUS
			$job_order->gateLog()->update([
				'status_id' => 8122, //VEHICLE INWARD COMPLETED
				'updated_by_id' => Auth::user()->id,
				'updated_at' => Carbon::now(),
			]);

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

			//GENERATE GATE IN VEHICLE NUMBER
			$generateNumber = SerialNumberGroup::generateNumber(23, $financial_year->id, $branch->state_id, $branch->id);
			if (!$generateNumber['success']) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => [
						'No Job Card Serial number found for FY : ' . $financial_year->from . ', State : ' . $branch->state->code . ', Outlet : ' . $branch->code,
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
					'unique:job_cards,local_job_card_number,' . $job_order->id . ',job_order_id,company_id,' . Auth::user()->company_id,
				],
			], $error_messages_1);

			if ($validator_1->fails()) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator_1->errors()->all(),
				]);
			}

			//JOB Card SAVE
			$job_card = JobCard::firstOrNew([
				'job_order_id' => $job_order->id,
			]);
			$job_card->local_job_card_number = $generateNumber['number'];
			$job_card->date = date('Y-m-d');
			$job_card->outlet_id = $job_order->outlet_id;
			$job_card->company_id = Auth::user()->company_id;
			$job_card->created_by = Auth::user()->id;
			$job_card->created_at = Carbon::now();
			$job_card->save();

			DB::commit();

			return response()->json([
				'success' => true,
				'message' => 'JOB Initiated Successfully',
			]);
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

	//deleteLabourPartsStatusUpdate
	public function deleteLabourParts(Request $request) {
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
					'message' => 'Labour Deleted Successfully',
				]);
			} else {
				return response()->json([
					'success' => true,
					'message' => 'Part Deleted Successfully',
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

	//GATE IN DETAIL
	// public function getGateInDetail(Request $r) {
	// 	try {
	// 		$gate_log = GateLog::company()->with([
	// 			'driverAttachment',
	// 			'kmAttachment',
	// 			'vehicleAttachment',
	// 			'outlet',
	// 		])
	// 			->select([
	// 				'gate_logs.*',
	// 				DB::raw('DATE_FORMAT(gate_logs.created_at,"%d/%m/%Y") as date'),
	// 				DB::raw('DATE_FORMAT(gate_logs.created_at,"%h:%i %p") as time'),
	// 			])
	// 			->find($r->id);

	// 		if (!$gate_log) {
	// 			return response()->json([
	// 				'success' => false,
	// 				'message' => 'Gate Log Not Found!',
	// 			]);
	// 		}

	// 		//Job card details need to get future
	// 		return response()->json([
	// 			'success' => true,
	// 			'gate_log' => $gate_log,
	// 			'attachement_path' => url('storage/app/public/gigo/gate_in/attachments/'),
	// 		]);

	// 	} catch (\Exception $e) {
	// 		return response()->json([
	// 			'success' => false,
	// 			'message' => 'Error : ' . $e->getMessage() . '. Line : ' . $e->getLine() . '. File : ' . $e->getFile(),
	// 		]);
	// 	}
	// }
}

<?php

namespace Abs\GigoPkg;
use App\City;
use App\Config;
use App\Customer;
use App\Http\Controllers\Controller;
use App\JobOrder;
use App\Part;
use App\VehicleModel;
use App\JobOrderRepairOrder;
use Auth;
use DB;
use Entrust;
use Excel;
use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;

class VehicleInwardController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	public function getVehicleInwardFilter() {
		$params = [
			'config_type_id' => 49,
			'add_default' => true,
			'default_text' => "Select Status",
		];
		$this->data['extras'] = [
			'registration_type_list' => [
				['id' => '', 'name' => 'Select Registration Type'],
				['id' => '1', 'name' => 'Registered Vehicle'],
				['id' => '0', 'name' => 'Un-Registered Vehicle'],
			],
			'status_list' => Config::getDropDownList($params),
		];
		return response()->json($this->data);
	}

	public function getVehicleInwardList(Request $request) {
		if ($request->date_range) {
			$date_range = explode(' to ', $request->date_range);
			$start_date = date('Y-m-d', strtotime($date_range[0]));
			$start_date = $start_date . ' 00:00:00';

			$end_date = date('Y-m-d', strtotime($date_range[1]));
			$end_date = $end_date . ' 23:59:59';
		} else {
			$start_date = date('Y-m-01 00:00:00');
			$end_date = date('Y-m-t 23:59:59');
		}
		$vehicle_inwards = JobOrder::join('gate_logs', 'gate_logs.job_order_id', 'job_orders.id')
			->leftJoin('vehicles', 'job_orders.vehicle_id', 'vehicles.id')
			->leftJoin('vehicle_owners', function ($join) {
				$join->on('vehicle_owners.vehicle_id', 'job_orders.vehicle_id')
					->whereRaw('vehicle_owners.from_date = (select MAX(vehicle_owners1.from_date) from vehicle_owners as vehicle_owners1 where vehicle_owners1.vehicle_id = job_orders.vehicle_id)');
			})
			->leftJoin('customers', 'customers.id', 'vehicle_owners.customer_id')
			->leftJoin('models', 'models.id', 'vehicles.model_id')
			->leftJoin('amc_members', 'amc_members.vehicle_id', 'vehicles.id')
			->leftJoin('amc_policies', 'amc_policies.id', 'amc_members.policy_id')
			->join('configs', 'configs.id', 'job_orders.status_id')
			->join('outlets', 'outlets.id', 'job_orders.outlet_id')
			->select(
				'job_orders.id',
				DB::raw('IF(vehicles.is_registered = 1,"Registered Vehicle","Un-Registered Vehicle") as registration_type'),
				'vehicles.registration_number',
				DB::raw('COALESCE(models.model_number, "-") as model_number'),
				'gate_logs.number',
				'job_orders.status_id',
				DB::raw('DATE_FORMAT(gate_logs.gate_in_date,"%d/%m/%Y, %h:%i %p") as date'),
				'job_orders.driver_name',
				'job_orders.driver_mobile_number as driver_mobile_number',
				'job_orders.is_customer_agreed',
				DB::raw('COALESCE(GROUP_CONCAT(amc_policies.name), "-") as amc_policies'),
				'configs.name as status',
				'outlets.code as outlet_code',
				DB::raw('COALESCE(customers.name, "-") as customer_name')
			)
			->where(function ($query) use ($start_date, $end_date) {
				$query->whereDate('gate_logs.gate_in_date', '>=', $start_date)
					->whereDate('gate_logs.gate_in_date', '<=', $end_date);
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

		if (!Entrust::can('view-overall-outlets-vehicle-inward')) {
			if (Entrust::can('view-mapped-outlet-vehicle-inward')) {
				$outlet_ids = Auth::user()->employee->outlets->pluck('id')->toArray();
				array_push($outlet_ids, Auth::user()->employee->outlet_id);
				$vehicle_inwards->whereIn('job_orders.outlet_id', $outlet_ids);
			} else if (Entrust::can('view-own-outlet-vehicle-inward')) {
				$vehicle_inwards->where('job_orders.outlet_id', Auth::user()->employee->outlet_id)
					->whereRaw("IF (`job_orders`.`status_id` = '8460', `job_orders`.`service_advisor_id` IS  NULL, `job_orders`.`service_advisor_id` = '" . $request->service_advisor_id . "')");
			} else {
				$vehicle_inwards->where('job_orders.service_advisor_id', Auth::user()->id);
			}
		}

		$vehicle_inwards->groupBy('job_orders.id');
		$vehicle_inwards->orderBy('job_orders.created_at', 'DESC');

		return Datatables::of($vehicle_inwards)
			->rawColumns(['status', 'action'])
			->filterColumn('registration_type', function ($query, $keyword) {
				$sql = 'IF(vehicles.is_registered = 1,"Registered Vehicle","Un-Registered Vehicle")  like ?';
				$query->whereRaw($sql, ["%{$keyword}%"]);
			})
			->editColumn('status', function ($vehicle_inward) {
				$status = $vehicle_inward->status_id == '8460' || $vehicle_inward->status_id == '8469' || $vehicle_inward->status_id == '8471' || $vehicle_inward->status_id == '8472' ? 'blue' : 'green';
				return '<span class="text-' . $status . '">' . $vehicle_inward->status . '</span>';
			})
			->addColumn('action', function ($vehicle_inward) {
				$img1 = asset('public/themes/' . $this->data['theme'] . '/img/content/table/view.svg');
				$img1_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/view.svg');
				$output = '';
				$output .= '<a href="#!/inward-vehicle/view/' . $vehicle_inward->id . '" id = "" title="View"><img src="' . $img1 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img1 . '" onmouseout=this.src="' . $img1 . '"></a>';
				if ($vehicle_inward->status_id == 8460) {
					$output .= '<a href="#!/inward-vehicle/vehicle-detail/' . $vehicle_inward->id . '" id = "" title="Initiate" class="btn btn-secondary-dark btn-xs">Initiate</a>';
				}
				if ($vehicle_inward->status_id == 8461 && $vehicle_inward->is_customer_agreed == 1) {
					$output .= '<a href="#!/inward-vehicle/update-jc/form/' . $vehicle_inward->id . '" id = "" title="Update JC" class="btn btn-secondary-dark btn-xs">Update JC</a>';
				}
				return $output;
			})
			->make(true);
	}

	public function getCustomerSearchList(Request $request) {
		return Customer::searchCustomer($request);
	}

	public function getVehicleModelSearchList(Request $request) {
		return VehicleModel::searchVehicleModel($request);
	}

	public function getCitySearchList(Request $r) {
		City::deleteCityWithoutState();
		return City::searchCity($r);
	}

	public function getPartSearchList(Request $r) {
		return Part::searchPart($r);
	}

	 public function export(Request $request) {
	 	// dd($request->all());
        try {
        	ob_end_clean();
			ob_start();

            if($request->report_wise_info === '1')
			{

				$job_order = JobOrder::with([
					'jobOrderRepairOrders' => function ($q) {
						$q->whereNull('removal_reason_id');
					},
					'jobOrderRepairOrders.repairOrder',
					'jobOrderRepairOrders.repairOrder.repairOrderType',
					'jobOrderParts' => function ($q) {
						$q->whereNull('removal_reason_id');
					},
					'jobOrderParts.part',
				])->find($request->report_job_order_id);
				

				if ($job_order->customerAddress) {
					//Check which tax applicable for customer
					if ($job_order->outlet->state_id == $job_order->customerAddress->state_id) {
						$tax_type = 1160; //Within State
					} else {
						$tax_type = 1161; //Inter State
					}
				} else {
					$tax_type = 1160; //Within State
				}

				$labour_details = array();
				$part_details = array();
				
				$customer_paid_type_id = SplitOrderType::where('paid_by_id', '10013')
						->pluck('id')
						->toArray();

					if ($job_order->jobOrderRepairOrders) {
						foreach ($job_order->jobOrderRepairOrders as $key => $value) {
							$labour_details[$key]['code'] = $value->repairOrder->code;
							$labour_details[$key]['name'] = $value->repairOrder->name;
							$labour_details[$key]['type'] = $value->repairOrder->repairOrderType ? $value->repairOrder->repairOrderType->short_name : '-';
							$labour_details[$key]['split_order_type'] = $value->splitOrderType ? $value->splitOrderType->code . "|" . $value->splitOrderType->name : '-';

							$labour_details[$key]['qty'] = $value->qty;
							// if ($value->repairOrder->is_editable == 1) {
							//     $labour_details[$key]['rate'] = $value->amount;
							// } else {
							//     $labour_details[$key]['rate'] = $value->repairOrder->amount;
							// }

							if ($value->is_free_service != 1 && (in_array($value->split_order_type_id, $customer_paid_type_id) || !$value->split_order_type_id) && !$value->removal_reason_id) {
									$tax_amount = 0;
									if ($value->repairOrder->taxCode) {
										foreach ($value->repairOrder->taxCode->taxes as $tax_key => $tax) {
											$percentage_value = 0;
											if ($tax->type_id == $tax_type) {
												$percentage_value = ($value->amount * $tax->pivot->percentage) / 100;
												$percentage_value = number_format((float) $percentage_value, 2, '.', '');
											}
											$tax_amount += $percentage_value;
										}
									
									} 
									$labour_details[$key]['amount'] = ($value->amount + $tax_amount);
							} else {
								$labour_details[$key]['amount'] = '0.00';
							}
						}
					}

					if ($job_order->jobOrderParts) {
						foreach ($job_order->jobOrderParts as $key => $value) {
							$part_details[$key]['code'] = $value->part->code;
							$part_details[$key]['name'] = $value->part->name;
							$part_details[$key]['type'] = $value->part->taxCode ? $value->part->taxCode->code : '-';
							$part_details[$key]['split_order_type'] = $value->splitOrderType ? $value->splitOrderType->code . "|" . $value->splitOrderType->name : '-';
							$part_details[$key]['qty'] = $value->qty;
							// $part_details[$key]['rate'] = $value->rate;
						
						if ($value->is_free_service != 1 && (in_array($value->split_order_type_id, $customer_paid_type_id) || !$value->split_order_type_id) && !$value->removal_reason_id) {
								$tax_amount = 0;
								if ($value->part->taxCode) {
									foreach ($value->part->taxCode->taxes as $tax_key => $tax) {
										$percentage_value = 0;
										if ($tax->type_id == $tax_type) {
											$percentage_value = ($value->amount * $tax->pivot->percentage) / 100;
											$percentage_value = number_format((float) $percentage_value, 2, '.', '');
										}
										$tax_amount += $percentage_value;
									}
								} 
								$part_details[$key]['amount'] = ($value->amount + $tax_amount);
							} else {
								$part_details[$key]['amount'] = '0.00';
							}
						}
					}
				
				$labours_header = [
					'Code',
					'Name',
					'Type',
					'Quantity',
					// 'Rate',
					'Amount',
				];
			
				$parts_header = [
					'Code',
					'Name',
					'Type',
					'Quantity',
					// 'Rate',
					'Amount',
				];

				dd($labour_details);
				$time_stamp = date('Y_m_d_h_i_s');
				Excel::create('Schedule Report - ' . $time_stamp, function ($excel) use ($labours_header, $parts_header, $labour_details, $part_details) {
					$excel->sheet('Labour', function ($sheet) use ($labours_header, $labour_details) {
						$sheet->fromArray($labour_details, null, 'A1');
						$sheet->row(1, $labours_header);
						$sheet->row(1, function ($row) {
							$row->setBackground('#bbc0c9');
							$row->setAlignment('center');
							$row->setFontSize(10);
							$row->setFontFamily('Work Sans');
							$row->setFontWeight('bold');
						});
						$sheet->cell('A:F', function ($row) {
							$row->setAlignment('center');
							$row->setFontFamily('Work Sans');
							$row->setFontSize(10);
						});
						$sheet->setAutoSize(true);
					});

					$excel->sheet('Parts', function ($sheet) use ($parts_header, $part_details) {
						$sheet->fromArray($part_details, null, 'A1');
						$sheet->row(1, $parts_header);
						$sheet->row(1, function ($row) {
							$row->setBackground('#bbc0c9');
							$row->setAlignment('center');
							$row->setFontSize(10);
							$row->setFontFamily('Work Sans');
							$row->setFontWeight('bold');
						});
						$sheet->cell('A:F', function ($row) {
							$row->setAlignment('center');
							$row->setFontFamily('Work Sans');
							$row->setFontSize(10);
						});
						$sheet->setAutoSize(true);
					});
				})->export('xlsx');
			} else
			{
				//REPAIR ORDER
				$job_order_repair_orders = JobOrderRepairOrder::with([
					'repairOrder',
					'jobOrder',
					'jobOrder.vehicle',
					'repairOrderMechanics',
					'repairOrderMechanics.mechanic',
					'repairOrderMechanics.status',
					'repairOrderMechanics.mechanicTimeLogs',
					'repairOrderMechanics.mechanicTimeLogs.status',
					'repairOrderMechanics.mechanicTimeLogs.reason',
				])
					->where('job_order_id',$request->report_job_order_id)
					->get();
										
				$mechanics_time_logs_summary = array();
				$mechanics_time_logs_detailed = array();

				if (!empty($job_order_repair_orders)) {
					foreach($job_order_repair_orders as $job_order_repair_order){
						// dd($job_order_repair_order);
						if ($job_order_repair_order->repairOrderMechanics) {
							foreach ($job_order_repair_order->repairOrderMechanics as $repair_order_mechanic) {
								// dd($repair_order_mechanic);
								$mechanics_time_log_summary = array();
								$mechanics_time_log_detailed = array();
								
								$mechanics_time_log_summary['mechanic_code'] = $repair_order_mechanic->mechanic->ecode;
								$mechanics_time_log_summary['mechanic_name'] = $repair_order_mechanic->mechanic->name;
								$mechanics_time_log_summary['code'] = $job_order_repair_order->repairOrder->code;
								$mechanics_time_log_summary['name'] = $job_order_repair_order->repairOrder->name;

								if ($repair_order_mechanic->mechanicTimeLogs) 
								{
									$duration_difference = []; 
									$duration = [];
									foreach ($repair_order_mechanic->mechanicTimeLogs as $key2 => $mechanic_time_log) {

										$mechanics_time_log_detailed = $mechanics_time_log_summary;

										// PARTICULAR MECHANIC DATE
										$mechanics_time_log_detailed['date'] = date('d-m-Y', strtotime($mechanic_time_log->start_date_time));
	
										//PARTICULAR MECHANIC STATR TIME
										$mechanics_time_log_detailed['start_time'] = date('h:i A', strtotime($mechanic_time_log->start_date_time));
	
										//PARTICULAR MECHANIC END TIME
										$mechanics_time_log_detailed['end_time'] = $mechanic_time_log->end_date_time ? date('h:i A', strtotime($mechanic_time_log->end_date_time)) : '-';
	
										if ($mechanic_time_log->end_date_time) {
											// dump('if');
											$time1 = strtotime($mechanic_time_log->start_date_time);
											$time2 = strtotime($mechanic_time_log->end_date_time);
											if ($time2 < $time1) {
												$time2 += 86400;
											}
	
											//TIME DURATION DIFFERENCE PARTICULAR MECHANIC DURATION
											$duration_difference[] = date("H:i:s", strtotime("00:00") + ($time2 - $time1));
	
											//TOTAL DURATION FOR PARTICLUAR EMPLOEE
											$duration[] = date("H:i:s", strtotime("00:00") + ($time2 - $time1));
	
											//OVERALL TOTAL WORKING DURATION
											$overall_total_duration[] = date("H:i:s", strtotime("00:00") + ($time2 - $time1));
	
											$mechanics_time_log_detailed['total_hours_worked'] = sum_mechanic_duration($duration_difference);
											unset($duration_difference);
										} else {
											//TOTAL DURATION FOR PARTICULAR EMPLOEE
											$duration[] = '-';
											$mechanics_time_log_detailed['total_hours_worked'] = '-';
										}

										$mechanics_time_logs_detailed[] = $mechanics_time_log_detailed;
									}

									//TOTAL WORKING HOURS PER EMPLOYEE
									$total_duration = sum_mechanic_duration($duration);
									$total_duration = date("H:i:s", strtotime($total_duration));
									$format_change = explode(':', $total_duration);
									$hour = $format_change[0] . 'h';
									$minutes = $format_change[1] . 'm';
									$seconds = $format_change[2] . 's';
									$mechanics_time_log_summary['total_duration'] = $hour . ' ' . $minutes; // . ' ' . $seconds;

									unset($duration);
								} else{
									$mechanics_time_log_summary['total_duration'] = '';
								}

								$mechanics_time_logs_summary[] = $mechanics_time_log_summary;
							}
						}
					}
				}

				$summary_header = [
					'Employee Code',
					'Employee Name',
					'ROT Code',
					'ROT Name',
					'Total Hours Worked',
				];
			
				$detailed_work_log_header = [
					'Employee Code',
					'Employee Name',
					'ROT Code',
					'ROT Name',
					'Date',
					'Start Time',
					'End Time',
					'Total Hours Worked',
				];

				dd( $mechanics_time_logs_summary, $mechanics_time_logs_detailed);

				$time_stamp = date('Y_m_d_h_i_s');
				Excel::create('Individual Report - ' . $time_stamp, function ($excel) use ($summary_header, $detailed_work_log_header, $mechanics_time_logs_summary, $mechanics_time_logs_detailed) {
					$excel->sheet('Summary', function ($sheet) use ($summary_header, $mechanics_time_logs_summary) {
						$sheet->fromArray($mechanics_time_logs_summary, null, 'A1');
						$sheet->row(1, $summary_header);
						$sheet->row(1, function ($row) {
							$row->setBackground('#bbc0c9');
							$row->setAlignment('center');
							$row->setFontSize(10);
							$row->setFontFamily('Work Sans');
							$row->setFontWeight('bold');
						});
						$sheet->cell('A:F', function ($row) {
							$row->setAlignment('center');
							$row->setFontFamily('Work Sans');
							$row->setFontSize(10);
						});
						$sheet->setAutoSize(true);
					});

					$excel->sheet('Detailed', function ($sheet) use ($detailed_work_log_header, $mechanics_time_logs_detailed) {
						$sheet->fromArray($mechanics_time_logs_detailed, null, 'A1');
						$sheet->row(1, $detailed_work_log_header);
						$sheet->row(1, function ($row) {
							$row->setBackground('#bbc0c9');
							$row->setAlignment('center');
							$row->setFontSize(10);
							$row->setFontFamily('Work Sans');
							$row->setFontWeight('bold');
						});
						$sheet->cell('A:F', function ($row) {
							$row->setAlignment('center');
							$row->setFontFamily('Work Sans');
							$row->setFontSize(10);
						});
						$sheet->setAutoSize(true);
					});
				})->export('xlsx');
			}
        } catch (Exception $e) {
            print_r($e);
        }
    }
}
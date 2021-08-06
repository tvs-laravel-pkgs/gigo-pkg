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

class GigoReportController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	 public function mechanicWorkLogExport(Request $request) {
	 	dd($request->all());
        try {

            if(!$request->export_date){
                $response = 'Estimation Rejected successfully.';
                $request->session()->flash('success', $response);
            }

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
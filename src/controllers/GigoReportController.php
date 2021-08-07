<?php

namespace Abs\GigoPkg;
use App\City;
use App\Config;
use App\Customer;
use App\Http\Controllers\Controller;
use App\JobOrder;
use App\OutletShift;
use App\EmployeeShift;
use App\Employee;
use App\Part;
use App\AttendanceLog;
use App\VehicleModel;
use App\RepairOrderMechanic;
use App\MechanicTimeLog;
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
	 	// dd($request->all());
        try {

            if ($request->export_date) {
                $date_range = explode(' to ', $request->export_date);
                $start_date = $date_range[0];    
                $end_date = $date_range[1];
            } else {
                $start_date = date('01-m-Y');
                $end_date = date('t-m-Y');
            }

            $employees = Employee::select([
                'users.id',
                'employees.code as employee_code',
                'users.name as employee_name',
                'employees.outlet_id',
                // 'employees.shift_id',
                'employees.id as employee_id'
            ])
                ->join('users', 'users.entity_id', 'employees.id')
                ->join('outlets', 'outlets.id', 'employees.outlet_id')
                ->where('employees.is_mechanic', 1)
                ->where('users.user_type_id', 1) //EMPLOYEE
                ->where('employees.outlet_id', 110)
                ->where('users.id', 191) //EMPLOYEE
                ->orderBy('users.name', 'asc')
            ->limit(10)
            ->get();
            
            // dd($employees);
            $summary_details = array();
            $work_logs_details = array();
            
            if($employees){
                foreach($employees as $key => $employee){
                    // dd($employee);
                    $start = strtotime($start_date);
		            $end = strtotime($end_date);
                    $overall_lunch_hours = []; 
                    $overall_work_hours = []; 
                    $overall_employee_work_hours = []; 
                    $overall_idle_hours = []; 
                    while (date('Y-m-d', $start) <= date('Y-m-d', $end)) {
                        // dump(date('Y-m-d', $start));
                        // dump(date("l",$start));
                        // dump($employee->id);
                        $summary_detail['date'] = date('d-m-Y', $start);
                        $summary_detail['employee_code'] = $employee->employee_code;
                        $summary_detail['employee_name'] = $employee->employee_name;
                        
                        $summary_detail['total_hours'] = '';
                        $summary_detail['working_hours'] = '';
                        $summary_detail['idle_hours'] = '';
                        
                        //Get Employye SHift
                        $employee_shift = EmployeeShift::join('shifts','shifts.id','employee_shifts.shift_id')->where('date',date('Y-m-d', $start))->where('employee_id',$employee->employee_id)->select('shifts.name as shift_name','employee_shifts.shift_id')->first();
                        
                        $lunch_hour = '00:00:00';
                        $total_working_hours = '00:00:00';

                        $punch_in_time = AttendanceLog::where('user_id',$employee->id)->whereDate('attendance_logs.date',date('Y-m-d', $start))->pluck('in_time')->first();

                        if($employee_shift){
                            //Outlet Shift
                            if(date("l",$start) == 'Sunday'){
                                $outlet_working_hours = OutletShift::where('shift_id',$employee_shift->shift_id)->where('outlet_id',$employee->outlet_id)->where('shift_type_id',12282)->first();
                            }elseif(date("l",$start) == 'Saturday'){
                                $outlet_working_hours = OutletShift::where('shift_id',$employee_shift->shift_id)->where('outlet_id',$employee->outlet_id)->where('shift_type_id',12281)->first();
                            }else{
                                $outlet_working_hours = OutletShift::where('shift_id',$employee_shift->shift_id)->where('outlet_id',$employee->outlet_id)->where('shift_type_id',12280)->first();
                            }

                            $outlet_shift_lunch_hours = OutletShift::where('shift_id',$employee_shift->shift_id)->where('outlet_id',$employee->outlet_id)->where('shift_type_id',12283)->first(); 
                            
                            if($outlet_shift_lunch_hours){
                                $array1 = explode(':', $outlet_shift_lunch_hours->start_time);
                                $array2 = explode(':', $outlet_shift_lunch_hours->end_time);
                                $minutes1 = ($array1[0] * 60.0 + $array1[1]);
                                $minutes2 = ($array2[0] * 60.0 + $array2[1]);
                                $diff = $minutes2 - $minutes1;

                                $lunch_hour = intdiv($diff, 60) . ':' . ($diff % 60) . ':00';

                                $overall_lunch_hours[] = $lunch_hour;
                            }

                            if($outlet_working_hours){
                                $array1 = explode(':', $outlet_working_hours->start_time);
                                $array2 = explode(':', $outlet_working_hours->end_time);
                                $minutes1 = ($array1[0] * 60.0 + $array1[1]);
                                $minutes2 = ($array2[0] * 60.0 + $array2[1]);
                                $diff = $minutes2 - $minutes1;

                                $total_working_hours = intdiv($diff, 60) . ':' . ($diff % 60) . ':00';

                                $summary_detail['total_hours'] = intdiv($diff, 60) . '.' . ($diff % 60);

                                $overall_work_hours[] = $total_working_hours;
                            }
                        }
                        
                        $summary_detail['user_id'] = $employee->id;

                        //Get Mechanic Worklog
                        $mechanic_time_logs  = MechanicTimeLog::join('repair_order_mechanics','repair_order_mechanics.id','mechanic_time_logs.repair_order_mechanic_id')
                        ->join('job_order_repair_orders','job_order_repair_orders.id','repair_order_mechanics.job_order_repair_order_id')
                        ->join('repair_orders','repair_orders.id','job_order_repair_orders.repair_order_id')
                        ->join('job_orders','job_orders.id','job_order_repair_orders.job_order_id')
                        ->join('vehicles','vehicles.id','job_orders.vehicle_id')
                        ->where('repair_order_mechanics.mechanic_id',$employee->id)
                        ->whereDate('mechanic_time_logs.start_date_time',date('Y-m-d', $start))
                        // ->whereNotNull('mechanic_time_logs.end_date_time')
                        ->select('mechanic_time_logs.start_date_time','mechanic_time_logs.end_date_time','repair_order_mechanics.job_order_repair_order_id',
                        'repair_orders.code','repair_orders.name','job_orders.job_card_number','vehicles.registration_number'
                        )
                        ->get();
                        
                        // dd($mechanic_time_logs);

                        $employee_work_hour = '00:00:00';
                        if($mechanic_time_logs){
                            $duration_difference = []; 
							$duration = [];

                            foreach($mechanic_time_logs as $mechanic_time_log){
                                $work_logs_detail = array();
                                // dd($mechanic_time_log);
                                // dd(date('h:i', strtotime($mechanic_time_log->start_date_time)));
                                //Employee Detail Report
                                $work_logs_detail['date'] = date('d-m-Y', $start);
                                $work_logs_detail['employee_code'] = $employee->employee_code;
                                $work_logs_detail['employee_name'] = $employee->employee_name;
                                $work_logs_detail['shift'] = $employee_shift ? $employee_shift->shift_name : '';
                                $work_logs_detail['punch_in_time'] = $punch_in_time;
                                $work_logs_detail['rot_code'] = $mechanic_time_log->code;
                                $work_logs_detail['rot_name'] = $mechanic_time_log->name;
                                $work_logs_detail['job_card_number'] = $mechanic_time_log->job_card_number;
                                $work_logs_detail['reg_number'] = $mechanic_time_log->registration_number;
                                $work_logs_detail['start_time'] = date('h:i', strtotime($mechanic_time_log->start_date_time));
                                $work_logs_detail['end_time'] = $mechanic_time_log->end_date_time ? date('h:i', strtotime($mechanic_time_log->end_date_time)) : '-';
                                // $work_logs_detail['idle_hours'] = ;
                                // $work_logs_detail['rot_hours'] = ;
                                // $work_logs_detail['remarks'] = ;

                                dd($work_logs_detail);
                                if($mechanic_time_log->end_date_time){
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

                                    $total_hours_worked = sum_mechanic_duration($duration_difference);

                                    // $summary_detail['working_hours'] = $total_hours_worked;
                                    unset($duration_difference);
                                }
                                
                            }

                            //TOTAL WORKING HOURS PER EMPLOYEE
                            $total_duration = sum_mechanic_duration($duration);
                            $total_duration = date("H:i:s", strtotime($total_duration));
                            $format_change = explode(':', $total_duration);
                            $hour = $format_change[0];
                            $minutes = $format_change[1];
                            $seconds = $format_change[2];

                            $employee_work_hour = $hour . ':' . $minutes . ':' . $seconds;
                            $overall_employee_work_hours[] = $employee_work_hour;
                            $summary_detail['working_hours'] = $hour . '.' . $minutes;
                            
                            unset($duration);

                            // //Add Working & Lunch Hours
                            // $array1 = explode(':', $employee_work_hour);
                            // $array2 = explode(':', $lunch_hour);

                            // $minutes1 = ($array1[0] * 60.0 + $array1[1]);
                            // $minutes2 = ($array2[0] * 60.0 + $array2[1]);
                            // $diff = $minutes2 + $minutes1;

                            // $total_employee_working_hours = intdiv($diff, 60) . ':' . ($diff % 60) . ':00';

                            if($total_working_hours != '00:00:00'){
                                //Find Total Idle Hours
                                $array1 = explode(':', $employee_work_hour);
                                $array2 = explode(':', $total_working_hours);

                                $minutes1 = ($array1[0] * 60.0 + $array1[1]);
                                $minutes2 = ($array2[0] * 60.0 + $array2[1]);
                                $diff = $minutes2 - $minutes1;
                                // $total_idle_hours = intdiv($diff, 60) . ':' . ($diff % 60) . ':00';
                                $total_idle_hours = intdiv($diff, 60) . '.' . ($diff % 60);
                                $summary_detail['idle_hours'] = $total_idle_hours;

                                $overall_idle_hours[] = intdiv($diff, 60) . ':' . ($diff % 60) . ':00';;
                            }
                        }

                        $summary_details[] = $summary_detail;

                        //Add Employees Overall Total
                        if(date('Y-m-d', $start) == date('Y-m-d', $end)){
                            $summary_detail = [];
                            $summary_detail['date'] = 'Grand Total';
                            $summary_detail['employee_code'] = '';
                            $summary_detail['employee_name'] = '';                            

                            //TOTAL OVERALL HOURS PER EMPLOYEE
                            $total_duration = sum_mechanic_duration($overall_work_hours);
                            $format_change = explode(':', $total_duration);
                            $hour = $format_change[0];
                            $minutes = $format_change[1];
                            $seconds = $format_change[2];
                            $summary_detail['total_hours'] = $hour . '.' . $minutes;

                            //TOTAL OVERALL WORKING HOURS PER EMPLOYEE
                            $total_duration = sum_mechanic_duration($overall_employee_work_hours);
                            $format_change = explode(':', $total_duration);
                            $hour = $format_change[0];
                            $minutes = $format_change[1];
                            $seconds = $format_change[2];
                            $summary_detail['working_hours'] = $hour . '.' . $minutes;

                            //TOTAL OVERALL IDLE HOURS PER EMPLOYEE
                            $total_duration = sum_mechanic_duration($overall_idle_hours);
                            $format_change = explode(':', $total_duration);
                            $hour = $format_change[0];
                            $minutes = $format_change[1];
                            $seconds = $format_change[2];
                            $summary_detail['idle_hours'] = $hour . '.' . $minutes;

                            $summary_details[] = $summary_detail;
                        }

                        $start = strtotime("+1 day", $start);
                    }
                }
                // dd();
            }

            dump('.....');
            dd($summary_details);

            

            dd('---');

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
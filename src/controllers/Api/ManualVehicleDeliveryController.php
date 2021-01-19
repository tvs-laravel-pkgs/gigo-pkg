<?php

namespace Abs\GigoPkg\Api;

use Abs\SerialNumberPkg\SerialNumberGroup;
use App\Config;
use App\Customer;
use App\Employee;
use App\FinancialYear;
use App\GateLog;
use App\GatePass;
use App\GigoManualInvoice;
use App\Http\Controllers\Controller;
use App\Http\Controllers\WpoSoapController;
use App\JobOrder;
use App\Outlet;
use App\Payment;
use App\Receipt;
use App\MailConfiguration;
use App\User;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Validator;
use App\Mail\VehicleDeliveryRequestMail;
use Mail;

class ManualVehicleDeliveryController extends Controller
{
    public $successStatus = 200;

    public function __construct(WpoSoapController $getSoap = null)
    {
        $this->getSoap = $getSoap;
    }

    public function getGateInList(Request $request)
    {
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
                    'vehicles.chassis_number',
                    'vehicles.engine_number',
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
                            ->orWhere('vehicles.chassis_number', 'LIKE', '%' . $request->search_key . '%')
                            ->orWhere('vehicles.engine_number', 'LIKE', '%' . $request->search_key . '%')
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
                    $vehicle_inward_list_get->where('job_orders.outlet_id', Auth::user()->employee->outlet_id)
                        ->whereRaw("IF (`job_orders`.`status_id` = '8460', `job_orders`.`service_advisor_id` IS  NULL, `job_orders`.`service_advisor_id` = '" . $request->service_advisor_id . "')");
                } else {
                    $vehicle_inward_list_get->where('job_orders.service_advisor_id', Auth::user()->id);
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

            $params = [
                'config_type_id' => 49,
                'add_default' => true,
                'default_text' => "Select Status",
            ];
            $extras = [
                'registration_type_list' => [
                    ['id' => '', 'name' => 'Select Registration Type'],
                    ['id' => '1', 'name' => 'Registered Vehicle'],
                    ['id' => '0', 'name' => 'Un-Registered Vehicle'],
                ],
                'status_list' => Config::getDropDownList($params),
            ];

            return response()->json([
                'success' => true,
                'gate_logs' => $gate_logs,
                'extras' => $extras,
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

    public function getFormData(Request $request)
    {
        // dd($request->all());
        $job_order = JobOrder::with([
            'vehicle',
            'vehicle.model',
            'vehicle.currentOwner.customer',
            'vehicle.currentOwner.customer.address',
            'vehicle.currentOwner.customer.address.country',
            'vehicle.currentOwner.customer.address.state',
            'vehicle.currentOwner.customer.address.city',
            'vehicle.currentOwner.ownershipType',
            'outlet',
            'gateLog',
            'gateLog.createdBy',
            'gateLog.driverAttachment',
            'gateLog.kmAttachment',
            'gateLog.vehicleAttachment',
            'gateLog.chassisAttachment',
            'manualDeliveryLabourInvoice',
            'manualDeliveryPartsInvoice',
            'manualDeliveryReceipt',
            'status',
        ])
            ->select([
                'job_orders.*',
                DB::raw('DATE_FORMAT(job_orders.created_at,"%d/%m/%Y") as date'),
                DB::raw('DATE_FORMAT(job_orders.created_at,"%h:%i %p") as time'),
            ])
            ->find($request->id);

        // dd($job_order);

        if (!$job_order) {
            return response()->json([
                'success' => false,
                'error' => 'Validation Error',
                'errors' => [
                    'Job Order Not Found!',
                ],
            ]);
        }

        if($job_order->manual_delivery_labour_invoice)
        {
            $invoice_date = $job_order->manual_delivery_labour_invoice->invoice_date;
        }
        else{
            $invoice_date = date('d-m-Y');
        }
        $this->data['success'] = true;
        $this->data['job_order'] = $job_order;
        $this->data['invoice_date'] = $invoice_date;

        $extras = [
            'purpose_list' => Config::getDropDownList([
                'config_type_id' => 421,
                'orderBy' => 'id',
                'default_text' => 'Select Purpose',
            ]),
            'parts_category_list' => Config::getDropDownList([
                'config_type_id' => 422,
                'orderBy' => 'id',
                'default_text' => 'Select Category',
            ]),
        ];

        $this->data['extras'] = $extras;

        return response()->json($this->data);

    }

    public function save(Request $request)
    {
        // dd($request->all());
        try {

            if ($request->type_id == 1) {
                $error_messages = [
                    'vehicle_delivery_request_remarks.required_if' => "Vehicle Delivery Request Remarks is required",
                ];
                $validator = Validator::make($request->all(), [
                    'job_order_id' => [
                        'required',
                        'integer',
                        'exists:job_orders,id',
                    ],
                    // 'invoice_number' => [
                    //     'required',
                    // ],
                    'invoice_date' => [
                        'required',
                    ],
                    // 'invoice_amount' => [
                    //     'required',
                    // ],
                    'labour_invoice_number' => [
                        'required',
                        'unique:gigo_manual_invoices,number,' . $request->job_order_id . ',invoiceable_id,invoice_type_id,1',
                    ],
                    'labour_amount' => [
                        'required',
                    ],
                    'parts_invoice_number' => [
                        'required',
                        'unique:gigo_manual_invoices,number,' . $request->job_order_id . ',invoiceable_id,invoice_type_id,2',
                    ],
                    'parts_amount' => [
                        'required',
                    ],
                    'receipt_number' => [
                        'required_if:vehicle_payment_status,==,1',
                    ],
                    'receipt_date' => [
                        'required_if:vehicle_payment_status,==,1',
                    ],
                    'receipt_amount' => [
                        'required_if:vehicle_payment_status,==,1',
                    ],
                    'vehicle_delivery_request_remarks' => [
                        'required_if:vehicle_payment_status,==,0',
                    ],
                ], $error_messages);

                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Validation Error',
                        'errors' => $validator->errors()->all(),
                    ]);
                }

                if($request->vehicle_payment_status == 1)
                {   
                    $validator = Validator::make($request->all(), [
                        'receipt_number' => [
                            'required',
                            'unique:receipts,temporary_receipt_no,' . $request->job_order_id . ',entity_id,receipt_of_id,7622',
                            'unique:receipts,permanent_receipt_no,' . $request->job_order_id . ',entity_id,receipt_of_id,7622',
                        ],
                    ]);
    
                    if ($validator->fails()) {
                        return response()->json([
                            'success' => false,
                            'error' => 'Validation Error',
                            'errors' => $validator->errors()->all(),
                        ]);
                    }

                    if (strtotime($request->invoice_date) > strtotime($request->receipt_date)) {
                        return response()->json([
                            'success' => false,
                            'error' => 'Validation Error',
                            'errors' => [
                                'Receipt Date should be greater than Invoice Date',
                            ],
                        ]);
                    }
                }

                $job_order = JobOrder::with('gateLog')->find($request->job_order_id);

                if (!$job_order) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Validation Error',
                        'errors' => [
                            'Job Order Not Found!',
                        ],
                    ]);
                }

                $gate_in_date = $job_order->gateLog->gate_in_date;
                $gate_in_date = date('d-m-Y',strtotime($gate_in_date));

                if (strtotime($gate_in_date) > strtotime($request->invoice_date)) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Validation Error',
                        'errors' => [
                            'Invoice Date should be greater than Gate In Date',
                        ],
                    ]);
                }

                DB::beginTransaction();

                $job_order->vehicle_payment_status = $request->vehicle_payment_status;
                if ($request->vehicle_payment_status == 1) {
                    $job_order->vehicle_delivery_requester_id = null;
                    $job_order->vehicle_delivery_request_remarks = null;
                    $job_order->status_id = 8468;
                    $payment_status_id = 2;

                    $message = "Vehicle delivery request saved successfully!";
                } else {
                    $job_order->vehicle_delivery_requester_id = Auth::user()->id;
                    $job_order->vehicle_delivery_request_remarks = $request->vehicle_delivery_request_remarks;
                    $job_order->status_id = 8477;
                    $payment_status_id = 1;

                    $message = "Vehicle delivery request sent to service head for successfully!";
                }

                $job_order->updated_by_id = Auth::user()->id;
                $job_order->updated_at = Carbon::now();
                $job_order->save();

                //Delete previous receipt
                $remove_receipt = Receipt::where('receipt_of_id', 7622)->where('entity_id', $job_order->id)->forceDelete();

                //Delete previous Invoice
                $remove_invoice = GigoManualInvoice::where('invoiceable_type', 'App\JobOrder')->where('invoiceable_id', $job_order->id)->forceDelete();

                $receipt_id = null;
                if ($payment_status_id == 2) {
                    //Save Receipt
                    $customer = Customer::find($job_order->customer_id);

                    $receipt = new Receipt;
                    $receipt->company_id = Auth::user()->company_id;
                    $receipt->temporary_receipt_no = $request->receipt_number;
                    $receipt->date = date('Y-m-d', strtotime($request->receipt_date));
                    $receipt->outlet_id = $job_order->outlet_id;
                    $receipt->receipt_of_id = 7622;
                    $receipt->entity_id = $job_order->id;
                    $receipt->permanent_receipt_no = $request->receipt_number;
                    $receipt->amount = $request->receipt_amount;
                    $receipt->settled_amount = $request->receipt_amount;
                    $receipt->created_at = Carbon::now();

                    $customer->receipt()->save($receipt);

                    $receipt_id = $customer->receipt ? $customer->receipt[0] ? $customer->receipt[0]->id : null : null;

                    //Save Payment
                    $payment = new Payment;
                    // dd($payment);
                    $payment->entity_type_id = 8434;
                    $payment->entity_id = $job_order->id;
                    $payment->received_amount = $request->receipt_amount;
                    $payment->receipt_id = $receipt_id;
                    $job_order->payment()->save($payment);

                    // dd($job_order->payment);
                }

                //Save Labour Invoice Details
                $invoice_detail = new GigoManualInvoice;
                $invoice_detail->number = $request->labour_invoice_number;
                $invoice_detail->invoice_type_id = 1;
                $invoice_detail->outlet_id = $job_order->outlet_id;
                $invoice_detail->customer_id = $job_order->customer_id;
                $invoice_detail->amount = $request->labour_amount;
                $invoice_detail->invoice_date = date('Y-m-d', strtotime($request->invoice_date));
                $invoice_detail->payment_status_id = $payment_status_id;
                $invoice_detail->created_by_id = Auth::user()->id;
                $invoice_detail->created_at = Carbon::now();
                $invoice_detail->receipt_id = $receipt_id;

                $job_order->invoice()->save($invoice_detail);

                // dump($job_order->invoice);

                //Save Parts Invoice Details
                $invoice_detail = new GigoManualInvoice;
                $invoice_detail->number = $request->parts_invoice_number;
                $invoice_detail->customer_id = 45;
                $invoice_detail->invoice_type_id = 2;
                $invoice_detail->amount = $request->parts_amount;
                $invoice_detail->outlet_id = $job_order->outlet_id;
                $invoice_detail->customer_id = $job_order->customer_id;
                $invoice_detail->invoice_date = date('Y-m-d', strtotime($request->invoice_date));
                $invoice_detail->payment_status_id = $payment_status_id;
                $invoice_detail->created_by_id = Auth::user()->id;
                $invoice_detail->created_at = Carbon::now();
                $invoice_detail->receipt_id = $receipt_id;

                $job_order->invoice()->save($invoice_detail);

                // dump($job_order->invoice);
                if ($job_order->status_id == 8478) {
                    $gate_pass = $this->generateGatePass($job_order);
                }

                DB::commit();

                //Send Mail for Serivice Head
                if ($request->vehicle_payment_status == 0) {
                    $this->vehiceRequestMail($job_order->id);
                }

                // dd(111);

                return response()->json([
                    'success' => true,
                    'message' => $message,
                ]);
            } else if ($request->type_id == 2) {
                $error_messages = [
                    'approved_remarks.required' => "Vehicle Delivery Approval Remarks is required",
                ];
                $validator = Validator::make($request->all(), [
                    'job_order_id' => [
                        'required',
                        'integer',
                        'exists:job_orders,id',
                    ],
                    'approved_remarks' => [
                        'required',
                    ],

                ], $error_messages);

                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Validation Error',
                        'errors' => $validator->errors()->all(),
                    ]);
                }

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

                DB::beginTransaction();

                $job_order->approver_id = Auth::user()->id;
                $job_order->approved_remarks = $request->approved_remarks;
                $job_order->approved_date_time = Carbon::now();
                $job_order->status_id = 8478;
                $job_order->save();

                $gate_pass = $this->generateGatePass($job_order);

                $message = "Manual Vehicle Delivery Approved Successfully!";

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => $message,
                ]);
            } else {
                $validator = Validator::make($request->all(), [
                    'job_order_id' => [
                        'required',
                        'integer',
                        'exists:job_orders,id',
                    ],
                    'receipt_number' => [
                        'required',
                        'unique:receipts,temporary_receipt_no,' . $request->job_order_id . ',entity_id,receipt_of_id,7622',
                        'unique:receipts,permanent_receipt_no,' . $request->job_order_id . ',entity_id,receipt_of_id,7622',
                    ],
                    'receipt_date' => [
                        'required',
                    ],
                    'receipt_amount' => [
                        'required',
                    ],
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Validation Error',
                        'errors' => $validator->errors()->all(),
                    ]);
                }

                if (strtotime($request->receipt_date) < strtotime($request->invoice_date)) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Validation Error',
                        'errors' => [
                            'Receipt Date should be greater than or equal to Invoice Date',
                        ],
                    ]);
                }

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

                DB::beginTransaction();

                $job_order->vehicle_payment_status = 1;
                $job_order->updated_by_id = Auth::user()->id;
                $job_order->updated_at = Carbon::now();
                $job_order->status_id = 8468;
                $job_order->save();

                //Save Receipt
                $customer = Customer::find($job_order->customer_id);

                //Delete previous receipt
                $remove_receipt = Receipt::where('receipt_of_id', 7622)->where('entity_id', $job_order->id)->forceDelete();

                $receipt = new Receipt;
                $receipt->company_id = Auth::user()->company_id;
                $receipt->temporary_receipt_no = $request->receipt_number;
                $receipt->date = date('Y-m-d', strtotime($request->receipt_date));
                $receipt->outlet_id = $job_order->outlet_id;
                $receipt->receipt_of_id = 7622;
                $receipt->entity_id = $job_order->id;
                $receipt->permanent_receipt_no = $request->receipt_number;
                $receipt->amount = $request->receipt_amount;
                $receipt->settled_amount = $request->receipt_amount;
                $receipt->created_at = Carbon::now();

                $customer->receipt()->save($receipt);

                $receipt_id = $customer->receipt ? $customer->receipt[0] ? $customer->receipt[0]->id : null : null;

                //Save Payment
                $payment = new Payment;
                // dd($payment);
                $payment->entity_type_id = 8434;
                $payment->entity_id = $job_order->id;
                $payment->received_amount = $request->receipt_amount;
                $payment->receipt_id = $receipt_id;
                $job_order->payment()->save($payment);

                
                //Updare Invoice
                $update_invoice = GigoManualInvoice::where('invoiceable_type', 'App\JobOrder')->where('invoiceable_id', $job_order->id)->update(['receipt_id'=>$receipt_id]);

                DB::commit();

                $message = 'Receipt Details saved succesfully!';

                return response()->json([
                    'success' => true,
                    'message' => $message,
                ]);
            }

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

    public function vehiceRequestMail($job_order_id) {
        $job_order = JobOrder::with([
            'vehicle',
            'vehicle.model',
            'vehicle.currentOwner.customer',
            'vehicle.currentOwner.customer.address',
            'vehicle.currentOwner.customer.address.country',
            'vehicle.currentOwner.customer.address.state',
            'vehicle.currentOwner.customer.address.city',
            'vehicle.currentOwner.ownershipType',
            'outlet',
            'gateLog',
            'gateLog.createdBy',
            'gateLog.driverAttachment',
            'gateLog.kmAttachment',
            'gateLog.vehicleAttachment',
            'gateLog.chassisAttachment',
            'manualDeliveryLabourInvoice',
            'manualDeliveryPartsInvoice',
            'manualDeliveryReceipt',
            'status',
        ])
            ->select([
                'job_orders.*',
                DB::raw('DATE_FORMAT(job_orders.created_at,"%d-%m-%Y") as date'),
                DB::raw('DATE_FORMAT(job_orders.created_at,"%h:%i %p") as time'),
            ])
            ->find($job_order_id);
        
            $total_amount = $job_order->manualDeliveryLabourInvoice->amount + $job_order->manualDeliveryPartsInvoice->amount;
            
            $job_order->total_amount = $total_amount;
            if($job_order)
            {
                $user_details = MailConfiguration::where('config_id',3011)->pluck('to_email')->first();
                $to_email = explode(',',$user_details);
                if(!$user_details || count($to_email) == 0)
                {
                    $to_email = ['0' => 'parthiban@uitoux.in'];
                }
            }

		if ($to_email) {
			$cc_email = [];
            $approver_view_url = url('/') . '/#!/manual-vehicle-delivery/view/'.$job_order->id;        
			$arr['job_order'] = $job_order;
			$arr['subject'] = 'GIGO – Need approval for Vehicle Delivery';
			$arr['to_email'] = $to_email;
            $arr['cc_email'] = $cc_email;
            $arr['approver_view_url'] = $approver_view_url;

			$MailInstance = new VehicleDeliveryRequestMail($arr);
			$Mail = Mail::send($MailInstance);
		}

	}

    public function generateGatePass($job_order)
    {
        // dd($job_order);
        $gate_log = GateLog::where('job_order_id', $job_order->id)->first();
        // dd($gate_log);
        if ($gate_log) {

            if (date('m') > 3) {
                $year = date('Y') + 1;
            } else {
                $year = date('Y');
            }
            //GET FINANCIAL YEAR ID
            $financial_year = FinancialYear::where('from', $year)
                ->where('company_id', $gate_log->company_id)
                ->first();

            $branch = Outlet::where('id', $gate_log->outlet_id)->first();

            if ($branch && $financial_year) {

                //GENERATE GatePASS
                $generateNumber = SerialNumberGroup::generateNumber(29, $financial_year->id, $branch->state_id, $branch->id);

                if ($generateNumber['success']) {
                    $gate_pass = GatePass::firstOrNew(['job_order_id' => $job_order->id, 'type_id' => 8280]); //VEHICLE GATE PASS

                    $gate_pass->gate_pass_of_id = 11280;
                    $gate_pass->entity_id = $job_order->id;

                    if (!$gate_pass->exists) {
                        $gate_pass->updated_at = Carbon::now();
                        $gate_pass->updated_by_id = Auth::user()->id;
                    } else {
                        $gate_pass->created_at = Carbon::now();
                        $gate_pass->created_by_id = Auth::user()->id;
                    }

                    $gate_pass->company_id = $gate_log->company_id;
                    $gate_pass->number = $generateNumber['number'];
                    $gate_pass->status_id = 8340; //GATE OUT PENDING
                    $gate_pass->save();

                    $gate_log->gate_pass_id = $gate_pass->id;
                    $gate_log->status_id = 8123; //GATE OUT PENDING
                    $gate_log->updated_by_id = Auth::user()->id;
                    $gate_log->updated_at = Carbon::now();
                    $gate_log->save();
                }

                //Generate GatePass PDF
                $generate_estimate_gatepass_pdf = JobOrder::generateEstimateGatePassPDF($job_order->id, $type = 'GateIn');
                // $generate_covering_pdf = JobOrder::generateCoveringLetterPDF($job_order->id);
            }
        }

        return true;
    }
}

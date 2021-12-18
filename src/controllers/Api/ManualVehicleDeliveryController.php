<?php

namespace Abs\GigoPkg\Api;

use Abs\GigoPkg\AmcMember;
use Abs\SerialNumberPkg\SerialNumberGroup;
use App\AmcAggregateCoupon;
use App\Attachment;
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
use App\JobOrderPaymentDetail;
use App\JobOrderWarrantyDetail;
use App\MailConfiguration;
use App\Mail\VehicleDeliveryRequestMail;
use App\Outlet;
use App\Payment;
use App\PaymentMode;
use App\PendingReason;
use App\Receipt;
use App\User;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Mail;
use Storage;
use Validator;

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
            // 'manualDeliveryReceipt',
            // 'manualDeliveryReceipt.paymentMode',
            'status',
            'pendingReason',
            'amcMember',
            'amcMember.amcPolicy',
            'amcMember.amcCustomer',
            'amcMember.amcCustomer.amcAggreagteCoupon',
            'amcMember.amcCustomer.activeAmcAggreagteCoupon',
            'transcationAttachment',
            'billingType',
            'inwardCancelReasonType',
            'warrantyDetail',
            'paymentDetail',
            'paymentDetail.paymentMode',
            'aggregateCoupon',
            'tvsOneApprovalStatus',
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

        $customer_id = $job_order->jv_customer_id ? $job_order->jv_customer_id : $job_order->customer_id;
        //get customer address
        $customer = Customer::with([
            'primaryAddress',
        ])->withTrashed()->find($customer_id);
        $job_order->customer = $customer;

        if ($job_order->manualDeliveryLabourInvoice) {
            $invoice_date = $job_order->manualDeliveryLabourInvoice->invoice_date;
        } else {
            $invoice_date = date('d-m-Y');
        }

        if ($job_order->warrantyDetail) {
            $warranty_date = $job_order->warrantyDetail->warranty_date;
        } else {
            $warranty_date = date('d-m-Y');
        }

        //Labour Amount
        $labour_amount = $job_order->manualDeliveryLabourInvoice ? $job_order->manualDeliveryLabourInvoice->amount : 0;
        $parts_amount = $job_order->manualDeliveryPartsInvoice ? $job_order->manualDeliveryPartsInvoice->amount : 0;

        //Paid amount
        $paid_amount = 0;
        if (count($job_order->paymentDetail) > 0) {
            foreach ($job_order->paymentDetail as $deliveryReceipt) {
                $paid_amount += $deliveryReceipt['amount'];
            }
        }

        $customer_paid_labour_amount = 0;
        if ($labour_amount > 0 && $job_order->labour_discount_amount > 0) {
            $customer_paid_labour_amount = $labour_amount - $job_order->labour_discount_amount;
            $customer_paid_labour_amount = number_format((float) $customer_paid_labour_amount, 2, '.', '');
        }

        $customer_paid_parts_amount = 0;
        if ($parts_amount > 0 && $job_order->part_discount_amount > 0) {
            $customer_paid_parts_amount = $parts_amount - $job_order->part_discount_amount;
            $customer_paid_parts_amount = number_format((float) $customer_paid_parts_amount, 2, '.', '');
        }

        $balance_amount = ($labour_amount + $parts_amount) - $paid_amount;
        $job_order->balance_amount = $balance_amount;
        $job_order->customer_paid_labour_amount = $customer_paid_labour_amount;
        $job_order->customer_paid_parts_amount = $customer_paid_parts_amount;

        $aggregate_work = '';
        $active_aggregate_coupons = 0;
        $aggregate_coupons = '';
        $membership_id = '';
        // $aggregate_processed = 0;
        if ($job_order && $job_order->amcMember) {
            $aggregate_works = $job_order->getAggregateWorkList($job_order->id, $job_order->amcMember->amcPolicy->id);
            $aggregate_work = $aggregate_works['aggregate_works'];

            if ($job_order->amcMember->amcCustomer && $job_order->amcMember->amcCustomer->amcAggreagteCoupon) {
                $aggregate_coupons = $job_order->amcMember->amcCustomer->amcAggreagteCoupon;
                if (count($aggregate_coupons) > 0) {
                    $coupons = [];
                    $available_coupon_count = 0;
                    foreach ($aggregate_coupons as $aggregate_coupon) {
                        // dd($aggregate_coupon);
                        if ($aggregate_coupon->status_id == 1 || ($aggregate_coupon->job_order_id == $job_order->id)) {
                            $coupon = [];
                            $coupon['id'] = $aggregate_coupon->id;
                            $coupon['coupon_code'] = $aggregate_coupon->coupon_code;
                            $coupon['job_order_id'] = $aggregate_coupon->job_order_id;

                            $coupons[] = $coupon;
                            $available_coupon_count++;
                        }
                    }
                    if ($available_coupon_count > 0) {
                        $aggregate_coupons = $coupons;
                    } else {
                        $aggregate_coupons = '';
                    }
                } else {
                    $aggregate_coupons = '';
                }
            }

            $membership_id = $job_order->amcMember->amcPolicy->id;
        }

        $job_order->aggregate_works = $aggregate_work;

        //Used for Aggregate Coupon calculate/validate
        $job_order->membership_id = $membership_id;

        $this->data['success'] = true;
        $this->data['job_order'] = $job_order;
        $this->data['invoice_date'] = $invoice_date;
        $this->data['warranty_date'] = $warranty_date;

        //Check Vehicle Membership
        // $vehicle_membership = AmcMember::join('amc_policies', 'amc_policies.id', 'amc_members.policy_id')->whereIn('amc_policies.name', ['TVS ONE', 'TVS CARE'])->where('amc_members.vehicle_id', $job_order->vehicle_id)->first();
        if($job_order->amcMember){
            $vehicle_membership = AmcMember::where('id', $job_order->amcMember->id)->orderBy('id','desc')->first();
        }else{
            $vehicle_membership = AmcMember::where('vehicle_id', $job_order->vehicle_id)->orderBy('id','desc')->first();
        }

        if ($vehicle_membership) {
            if (strtotime($invoice_date) > strtotime($vehicle_membership->expiry_date)) {
                $pending_reasons = collect(PendingReason::where('company_id', Auth::user()->company_id)->where('id', '!=', 2)->select('pending_reasons.id', 'pending_reasons.name')->get())->prepend(['id' => '', 'name' => 'Select Reason']);
            } else {
                $pending_reasons = collect(PendingReason::where('company_id', Auth::user()->company_id)->select('pending_reasons.id', 'pending_reasons.name')->get())->prepend(['id' => '', 'name' => 'Select Reason']);
            }
        } else {
            $pending_reasons = collect(PendingReason::where('company_id', Auth::user()->company_id)->where('id', '!=', 2)->select('pending_reasons.id', 'pending_reasons.name')->get())->prepend(['id' => '', 'name' => 'Select Reason']);
        }

        $extras = [
            'aggregate_works' => $aggregate_work,
            'aggregate_coupons' => $aggregate_coupons,
            'active_aggregate_coupons' => $active_aggregate_coupons,
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
            'payment_modes' => collect(PaymentMode::where('company_id', Auth::user()->company_id)->whereNotIn('id', [9, 10, 11])
                    ->select('payment_modes.id', 'payment_modes.name')->get())->prepend(['id' => '', 'name' => 'Select Payment Mode']),
            'pending_reasons' => $pending_reasons,
            'billing_types' => Config::getDropDownList([
                'config_type_id' => 454,
                'orderBy' => 'id',
                'default_text' => 'Select Type',
            ]),
            'inward_cancel_reasons' => Config::getDropDownList([
                'config_type_id' => 455,
                'orderBy' => 'id',
                'default_text' => 'Select Reason',
            ]),
            'tvs_one_request_reject_reasons' => [
                ['id' => '1', 'name' => 'Invalid Invoice Amount'],
                ['id' => '2', 'name' => 'Other Reasons'],
            ],
        ];

        $this->data['extras'] = $extras;

        return response()->json($this->data);

    }

    public function updateVehicleStatus(Request $request)
    {
        // dd($request->all());
        $validator = Validator::make($request->all(), [
            'job_order_id' => [
                'required',
                'integer',
                'exists:job_orders,id',
            ],
            'vehicle_delivery_status_id' => [
                'required',
                'integer',
                'exists:vehicle_delivery_statuses,id',
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation Error',
                'errors' => $validator->errors()->all(),
            ]);
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

        if (!$job_order->gateLog) {
            return response()->json([
                'success' => false,
                'error' => 'Validation Error',
                'errors' => [
                    'Gate Log Not Found!',
                ],
            ]);
        }

        DB::beginTransaction();

        if ($request->vehicle_delivery_status_id == 4) {
            $job_order->vehicle_delivery_status_id = 4;
            $job_order->status_id = 8470;
        } else {
            if ($job_order->gateLog->status_id == 8124) {
                $job_order->vehicle_delivery_status_id = 3;
            } else {
                $job_order->vehicle_delivery_status_id = $request->vehicle_delivery_status_id;
            }
        }

        $job_order->updated_by_id = Auth::user()->id;
        $job_order->updated_at = Carbon::now();
        $job_order->save();

        DB::commit();
        $message = "Vehicle Status Updated successfully!";

        return response()->json([
            'success' => true,
            'message' => $message,
        ]);
    }
    public function save(Request $request)
    {
        // dd($request->all());
        try {

            if ($request->type_id == 1) {

                if ($request->vehicle_service_status == 1) {
                    if ($request->billing_type_id == 11520) {
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
                            // 'receipt_number' => [
                            //     'required_if:vehicle_payment_status,==,1',
                            // ],
                            // 'receipt_date' => [
                            //     'required_if:vehicle_payment_status,==,1',
                            // ],
                            // 'receipt_amount' => [
                            //     'required_if:vehicle_payment_status,==,1',
                            // ],
                            'vehicle_delivery_request_remarks' => [
                                'required_if:vehicle_payment_status,==,0',
                            ],
                            'job_card_number' => [
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

                        if (!$job_order->customer_id) {
                            return response()->json([
                                'success' => false,
                                'error' => 'Validation Error',
                                'errors' => [
                                    'Customer Not Found!',
                                ],
                            ]);
                        }

                        $gate_in_date = $job_order->gateLog->gate_in_date;
                        $gate_in_date = date('d-m-Y', strtotime($gate_in_date));

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

                        // if ($job_order->tvs_one_approval_status_id != 2) {
                        if ($request->labour_discount_amount > 0 || $request->part_discount_amount) {
                            $job_order->tvs_one_approval_status_id = 1;
                        } else {
                            $job_order->tvs_one_approval_status_id = null;
                        }
                        // }

                        //Check Invoice,Receipt amount
                        $labour_amount = $request->labour_amount;
                        $parts_amount = $request->parts_amount;
                        // $receipt_amount = $request->receipt_amount ? $request->receipt_amount : 0;
                        $receipt_amount = 0;
                        $payment_status = 0;
                        $status_id = 8477;

                        //Check Paid Amount
                        if ($request->payment) {
                            foreach ($request->payment as $payment) {
                                if ($payment['receipt_amount'] > 0) {
                                    $receipt_amount += $payment['receipt_amount'];
                                }
                            }
                        }
                        // dd($receipt_amount);
                        if ($receipt_amount) {
                            if ($receipt_amount == ($labour_amount + $parts_amount)) {
                                $payment_status = 1;
                                $status_id = 8468;
                            } else if ($receipt_amount > ($labour_amount + $parts_amount)) {
                                return response()->json([
                                    'success' => false,
                                    'error' => 'Validation Error',
                                    'errors' => [
                                        'Paid Amount should be less than or equal to total bill amount',
                                    ],
                                ]);
                            } else {
                                $payment_status = 0;
                                $status_id = 8477;
                            }

                            //Check Reason
                            $pending_reason = PendingReason::find($request->pending_reason_id);
                            if ($pending_reason) {
                                if ($pending_reason->need_verification == 0) {
                                    $payment_status = 1;
                                    $status_id = 8467;
                                }
                            }
                        }

                        $job_order->jv_customer_id = null;
                        if ($request->pending_reason_id == 4) {
                            $job_order->jv_customer_id = $request->jv_customer_id;

                            if ($job_order->customer_id == $request->jv_customer_id) {
                                return response()->json([
                                    'success' => false,
                                    'error' => 'Validation Error',
                                    'errors' => [
                                        'JV Customer should be different from the Actual Customer!',
                                    ],
                                ]);
                            }
                        }

                        if ($payment_status) {
                            $job_order->pending_reason_id = $request->pending_reason_id ? $request->pending_reason_id : null;
                            $job_order->pending_remarks = $request->pending_remarks ? $request->pending_remarks : null;
                            $job_order->status_id = $status_id;

                            $message = "Vehicle delivery request saved successfully!";
                        } else {
                            $job_order->pending_reason_id = $request->pending_reason_id;
                            $job_order->pending_remarks = $request->pending_remarks;
                            $job_order->status_id = $status_id;

                            $message = "Vehicle delivery request sent to service head for successfully!";
                        }

                        $job_order->vehicle_payment_status = $request->vehicle_payment_status;
                        $job_order->vehicle_delivery_requester_id = Auth::user()->id;
                        $job_order->job_card_number = $request->job_card_number;
                        $job_order->labour_discount_amount = $request->labour_discount_amount;
                        $job_order->part_discount_amount = $request->part_discount_amount;

                        if ($request->vehicle_payment_status == 1) {
                            $job_order->vehicle_delivery_request_remarks = null;
                            // $job_order->status_id = 8468;
                            $payment_status_id = 2;
                        } else {
                            $job_order->vehicle_delivery_request_remarks = $request->vehicle_delivery_request_remarks;
                            // $job_order->status_id = 8477;
                            $payment_status_id = 1;
                        }

                        $job_order->inward_cancel_reason_id = null;
                        $job_order->inward_cancel_reason = null;
                        $job_order->billing_type_id = $request->billing_type_id;
                        $job_order->is_aggregate_work = $request->is_aggregate_work;

                        // if ($job_order->tvs_one_approval_status_id != 2 && $job_order->tvs_one_approval_status_id != 3) {
                        //     if ($request->labour_discount_amount > 0 || $request->part_discount_amount) {
                        //         $job_order->tvs_one_approval_status_id = 1;
                        //     } else {
                        //         $job_order->tvs_one_approval_status_id = null;
                        //     }
                        // }
                        // if ($request->pending_reason_id == 2) {
                        //     $job_order->tvs_one_approval_status_id = 1;
                        // } else {
                        //     $job_order->tvs_one_approval_status_id = null;
                        // }
                        $job_order->inward_cancel_reason_id = null;
                        $job_order->inward_cancel_reason = null;
                        $job_order->updated_by_id = Auth::user()->id;
                        $job_order->updated_at = Carbon::now();
                        $job_order->save();

                        $job_order->aggregateWork()->sync([]);

                        $amc_aggregate_coupon = AmcAggregateCoupon::where('job_order_id', $job_order->id)->update(['job_order_id' => null, 'status_id' => 1]);

                        //Save Aggregate Coupons
                        if ($request->aggregate_coupon) {
                            foreach ($request->aggregate_coupon as $key => $aggregate_coupon) {
                                if (isset($aggregate_coupon['coupon_status'])) {
                                    $amc_aggregate_coupon = AmcAggregateCoupon::find($aggregate_coupon['coupon_id']);
                                    if ($amc_aggregate_coupon && $amc_aggregate_coupon->status_id == 1) {
                                        $amc_aggregate_coupon->job_order_id = $job_order->id;
                                        $amc_aggregate_coupon->status_id = 2;
                                        $amc_aggregate_coupon->updated_by_id = Auth::user()->id;
                                        $amc_aggregate_coupon->updated_at = Carbon::now();
                                        $amc_aggregate_coupon->save();
                                    } else {
                                        return response()->json([
                                            'success' => false,
                                            'error' => 'Validation Error',
                                            'errors' => [
                                                'Aggregate Coupon not found / Aggregate Coupon already used for the another job order!',
                                            ],
                                        ]);
                                    }
                                }
                            }
                        }

                        //Save Aggregate Work
                        if ($request->aggregate_work) {
                            foreach ($request->aggregate_work as $key => $aggregate_work) {
                                if (isset($aggregate_work['amount'])) {
                                    $job_order->aggregateWork()->attach(
                                        $aggregate_work['aggregate_work_id'],
                                        [
                                            'amount' => $aggregate_work['amount'],
                                        ]
                                    );
                                }
                            }
                        }

                        //Delete previous receipt
                        // $remove_receipt = Receipt::where('receipt_of_id', 7622)->where('entity_id', $job_order->id)->forceDelete();

                        //Delete previous Invoice
                        $remove_invoice = GigoManualInvoice::where('invoiceable_type', 'App\JobOrder')->where('invoiceable_id', $job_order->id)->forceDelete();

                        $receipt_id = null;
                        if ($payment_status_id == 2) {
                            $labour_amount = $request->labour_amount;
                            $parts_amount = $request->parts_amount;

                            //Save Receipt
                            $customer = Customer::find($job_order->customer_id);

                            //Save Payment
                            $payment = new Payment;
                            // dd($payment);
                            $payment->entity_type_id = 8434;
                            $payment->entity_id = $job_order->id;
                            $payment->received_amount = $receipt_amount;
                            $payment->receipt_id = null;
                            $job_order->payment()->save($payment);

                            $remove_payment = JobOrderPaymentDetail::where('job_order_id', $job_order->id)->forceDelete();

                            //Check Paid Amount
                            if ($request->payment) {
                                foreach ($request->payment as $payment) {

                                    //Check Receipt Number alreay saved or not
                                    $receipt_number = JobOrderPaymentDetail::where('transaction_number', $payment['receipt_number'])->first();

                                    if ($receipt_number) {
                                        return response()->json([
                                            'success' => false,
                                            'error' => 'Validation Error',
                                            'errors' => [
                                                'Receipt number has already been taken!',
                                            ],
                                        ]);
                                    }

                                    //save payment detail
                                    $job_order_payment = new JobOrderPaymentDetail;
                                    $job_order_payment->payment_mode_id = $payment['payment_mode_id'];
                                    $job_order_payment->job_order_id = $job_order->id;
                                    $job_order_payment->transaction_number = $payment['receipt_number'];
                                    $job_order_payment->transaction_date = date('Y-m-d', strtotime($payment['receipt_date']));
                                    $job_order_payment->amount = $payment['receipt_amount'];
                                    $job_order_payment->save();
                                }
                            }

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
                        $invoice_detail->receipt_id = null;

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
                        $invoice_detail->receipt_id = null;

                        $job_order->invoice()->save($invoice_detail);

                        //CREATE DIRECTORY TO STORAGE PATH
                        $attachment_path = storage_path('app/public/gigo/job_order/attachments/');
                        Storage::makeDirectory($attachment_path, 0777);

                        //MULTIPLE ATTACHMENT REMOVAL
                        $attachment_removal_ids = json_decode($request->attachment_removal_ids);
                        if (!empty($attachment_removal_ids)) {
                            Attachment::whereIn('id', $attachment_removal_ids)->forceDelete();
                        }

                        if (!empty($request->transaction_attachments)) {
                            foreach ($request->transaction_attachments as $key => $transaction_attachment) {
                                $value = rand(1, 20);
                                $image = $transaction_attachment;

                                $file_name_with_extension = $image->getClientOriginalName();
                                $file_name = pathinfo($file_name_with_extension, PATHINFO_FILENAME);
                                $extension = $image->getClientOriginalExtension();
                                $name = $job_order->id . '_Transcation_Attachment_' . date('Y_m_d_h_i_s') . '_' . $value . '.' . $extension;

                                $transaction_attachment->move(storage_path('app/public/gigo/job_order/attachments/'), $name);
                                $attachement = new Attachment;
                                $attachement->attachment_of_id = 227; //Job order
                                $attachement->attachment_type_id = 11342; //GIGO Transcation Attachment
                                $attachement->entity_id = $job_order->id;
                                $attachement->name = $name;
                                $attachement->save();
                            }
                        }

                        // dump($job_order->invoice);
                        if ($payment_status) {
                            $gate_pass = $this->generateGatePass($job_order);
                        }

                        DB::commit();

                        //Send Mail for Serivice Head
                        if (!$payment_status) {
                            $this->vehiceRequestMail($job_order->id, $type = 1);
                        }

                    } elseif ($request->billing_type_id == 11523) {
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
                            'job_card_number' => [
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

                        if (!$job_order->customer_id) {
                            return response()->json([
                                'success' => false,
                                'error' => 'Validation Error',
                                'errors' => [
                                    'Customer Not Found!',
                                ],
                            ]);
                        }

                        $gate_in_date = $job_order->gateLog->gate_in_date;
                        $gate_in_date = date('d-m-Y', strtotime($gate_in_date));

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

                        $job_order->billing_type_id = $request->billing_type_id;
                        $job_order->inward_cancel_reason_id = null;
                        $job_order->inward_cancel_reason = null;
                        $job_order->vehicle_payment_status = null;
                        $job_order->pending_reason_id = null;
                        $job_order->jv_customer_id = null;
                        $job_order->pending_remarks = null;
                        $job_order->vehicle_delivery_requester_id = Auth::user()->id;
                        $job_order->vehicle_delivery_request_remarks = null;
                        $job_order->approver_id = null;
                        $job_order->approved_remarks = null;
                        $job_order->approved_date_time = null;
                        $job_order->warranty_reason = null;
                        $job_order->status_id = 8470;
                        $job_order->job_card_number = $request->job_card_number;
                        $job_order->labour_discount_amount = null;
                        $job_order->part_discount_amount = null;
                        $job_order->is_aggregate_work = null;
                        $job_order->tvs_one_approval_status_id = null;

                        $job_order->updated_by_id = Auth::user()->id;
                        $job_order->updated_at = Carbon::now();
                        $job_order->save();

                        $job_order->aggregateWork()->sync([]);

                        $amc_aggregate_coupon = AmcAggregateCoupon::where('job_order_id', $job_order->id)->update(['job_order_id' => null, 'status_id' => 1]);

                        //Delete previous receipt
                        // $remove_receipt = Receipt::where('receipt_of_id', 7622)->where('entity_id', $job_order->id)->forceDelete();

                        //Delete previous Invoice
                        $remove_invoice = GigoManualInvoice::where('invoiceable_type', 'App\JobOrder')->where('invoiceable_id', $job_order->id)->forceDelete();

                        //Delete previous payment
                        $remove_payment = JobOrderPaymentDetail::where('job_order_id', $job_order->id)->forceDelete();

                        //Save Labour Invoice Details
                        $invoice_detail = new GigoManualInvoice;
                        $invoice_detail->number = $request->labour_invoice_number;
                        $invoice_detail->invoice_type_id = 1;
                        $invoice_detail->outlet_id = $job_order->outlet_id;
                        $invoice_detail->customer_id = $job_order->customer_id;
                        $invoice_detail->amount = $request->labour_amount;
                        $invoice_detail->invoice_date = date('Y-m-d', strtotime($request->invoice_date));
                        $invoice_detail->payment_status_id = 1;
                        $invoice_detail->created_by_id = Auth::user()->id;
                        $invoice_detail->created_at = Carbon::now();
                        $invoice_detail->receipt_id = null;

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
                        $invoice_detail->payment_status_id = 1;
                        $invoice_detail->created_by_id = Auth::user()->id;
                        $invoice_detail->created_at = Carbon::now();
                        $invoice_detail->receipt_id = null;

                        $job_order->invoice()->save($invoice_detail);

                        //CREATE DIRECTORY TO STORAGE PATH
                        $attachment_path = storage_path('app/public/gigo/job_order/attachments/');
                        Storage::makeDirectory($attachment_path, 0777);

                        //MULTIPLE ATTACHMENT REMOVAL
                        $attachment_removal_ids = json_decode($request->attachment_removal_ids);
                        if (!empty($attachment_removal_ids)) {
                            Attachment::whereIn('id', $attachment_removal_ids)->forceDelete();
                        }

                        if (!empty($request->transaction_attachments)) {
                            foreach ($request->transaction_attachments as $key => $transaction_attachment) {
                                $value = rand(1, 20);
                                $image = $transaction_attachment;

                                $file_name_with_extension = $image->getClientOriginalName();
                                $file_name = pathinfo($file_name_with_extension, PATHINFO_FILENAME);
                                $extension = $image->getClientOriginalExtension();
                                $name = $job_order->id . '_Transcation_Attachment_' . date('Y_m_d_h_i_s') . '_' . $value . '.' . $extension;

                                $transaction_attachment->move(storage_path('app/public/gigo/job_order/attachments/'), $name);
                                $attachement = new Attachment;
                                $attachement->attachment_of_id = 227; //Job order
                                $attachement->attachment_type_id = 11342; //GIGO Transcation Attachment
                                $attachement->entity_id = $job_order->id;
                                $attachement->name = $name;
                                $attachement->save();
                            }
                        }

                        $gate_pass = $this->generateGatePass($job_order);

                        DB::commit();

                        $message = "Vehicle delivery request saved successfully!";

                    } else {
                        $validator = Validator::make($request->all(), [
                            'job_order_id' => [
                                'required',
                                'integer',
                                'exists:job_orders,id',
                            ],
                            'billing_type_id' => [
                                'required',
                                'integer',
                                'exists:configs,id',
                            ],
                            'warranty_reason' => [
                                'required',
                            ],
                            'job_card_number' => [
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

                        // if (empty($request->transaction_attachments) || count($request->transaction_attachments) == 0) {
                        //     return response()->json([
                        //         'success' => false,
                        //         'error' => 'Validation Error',
                        //         'errors' => [
                        //             'Attachment Not Found!',
                        //         ],
                        //     ]);
                        // }

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

                        if (!$job_order->customer_id) {
                            return response()->json([
                                'success' => false,
                                'error' => 'Validation Error',
                                'errors' => [
                                    'Customer Not Found!',
                                ],
                            ]);
                        }

                        DB::beginTransaction();

                        if ($request->warranty_number) {

                            $validator = Validator::make($request->all(), [
                                'warranty_date' => [
                                    'required',
                                ],
                                'warranty_parts_amount' => [
                                    'required',
                                ],
                                'warranty_labour_amount' => [
                                    'required',
                                ],
                                'warranty_number' => [
                                    'unique:job_order_warranty_details,number,' . $request->job_order_id . ',job_order_id',
                                ],
                            ]);

                            if ($validator->fails()) {
                                return response()->json([
                                    'success' => false,
                                    'error' => 'Validation Error',
                                    'errors' => $validator->errors()->all(),
                                ]);
                            }

                            $gate_in_date = $job_order->gateLog->gate_in_date;
                            $gate_in_date = date('d-m-Y', strtotime($gate_in_date));

                            if (strtotime($gate_in_date) > strtotime($request->warranty_date)) {
                                return response()->json([
                                    'success' => false,
                                    'error' => 'Validation Error',
                                    'errors' => [
                                        'Warranty Date should be greater than Gate In Date',
                                    ],
                                ]);
                            }

                            //Save Warranty Detail
                            $warranty_detail = JobOrderWarrantyDetail::firstorNew(['job_order_id' => $job_order->id]);
                            $warranty_detail->number = $request->warranty_number;
                            $warranty_detail->labour_amount = $request->warranty_labour_amount;
                            $warranty_detail->parts_amount = $request->warranty_parts_amount;
                            $warranty_detail->warranty_date = date('Y-m-d', strtotime($request->warranty_date));
                            $warranty_detail->save();
                        }

                        $job_order->billing_type_id = $request->billing_type_id;
                        $job_order->job_card_number = $request->job_card_number;
                        $job_order->inward_cancel_reason_id = null;
                        $job_order->inward_cancel_reason = null;
                        $job_order->vehicle_payment_status = null;
                        $job_order->pending_reason_id = null;
                        $job_order->jv_customer_id = null;
                        $job_order->pending_remarks = null;
                        $job_order->vehicle_delivery_requester_id = Auth::user()->id;
                        $job_order->vehicle_delivery_request_remarks = null;
                        $job_order->approver_id = null;
                        $job_order->approved_remarks = null;
                        $job_order->approved_date_time = null;
                        $job_order->warranty_reason = $request->warranty_reason;
                        $job_order->status_id = 8470;
                        $job_order->labour_discount_amount = null;
                        $job_order->part_discount_amount = null;
                        $job_order->is_aggregate_work = null;
                        $job_order->tvs_one_approval_status_id = null;
                        $job_order->updated_by_id = Auth::user()->id;
                        $job_order->updated_at = Carbon::now();
                        $job_order->save();

                        $job_order->aggregateWork()->sync([]);

                        $amc_aggregate_coupon = AmcAggregateCoupon::where('job_order_id', $job_order->id)->update(['job_order_id' => null, 'status_id' => 1]);

                        //CREATE DIRECTORY TO STORAGE PATH
                        $attachment_path = storage_path('app/public/gigo/job_order/attachments/');
                        Storage::makeDirectory($attachment_path, 0777);

                        //MULTIPLE ATTACHMENT REMOVAL
                        $attachment_removal_ids = json_decode($request->attachment_removal_ids);
                        if (!empty($attachment_removal_ids)) {
                            Attachment::whereIn('id', $attachment_removal_ids)->forceDelete();
                        }

                        if (!empty($request->transaction_attachments)) {
                            foreach ($request->transaction_attachments as $key => $transaction_attachment) {
                                $value = rand(1, 20);
                                $image = $transaction_attachment;

                                $file_name_with_extension = $image->getClientOriginalName();
                                $file_name = pathinfo($file_name_with_extension, PATHINFO_FILENAME);
                                $extension = $image->getClientOriginalExtension();
                                $name = $job_order->id . '_Transcation_Attachment_' . date('Y_m_d_h_i_s') . '_' . $value . '.' . $extension;

                                $transaction_attachment->move(storage_path('app/public/gigo/job_order/attachments/'), $name);
                                $attachement = new Attachment;
                                $attachement->attachment_of_id = 227; //Job order
                                $attachement->attachment_type_id = 11342; //GIGO Transcation Attachment
                                $attachement->entity_id = $job_order->id;
                                $attachement->name = $name;
                                $attachement->save();
                            }
                        }

                        $gate_pass = $this->generateGatePass($job_order);

                        //Delete previous receipt
                        // $remove_receipt = Receipt::where('receipt_of_id', 7622)->where('entity_id', $job_order->id)->forceDelete();

                        //Delete previous Invoice
                        $remove_invoice = GigoManualInvoice::where('invoiceable_type', 'App\JobOrder')->where('invoiceable_id', $job_order->id)->forceDelete();

                        //Delete previous payment
                        $remove_payment = JobOrderPaymentDetail::where('job_order_id', $job_order->id)->forceDelete();

                        DB::commit();
                        $message = "Vehicle delivery request saved successfully!";
                    }
                } else {
                    $validator = Validator::make($request->all(), [
                        'job_order_id' => [
                            'required',
                            'integer',
                            'exists:job_orders,id',
                        ],
                        'inward_cancel_reason_id' => [
                            'required',
                            'integer',
                            'exists:configs,id',
                        ],
                        'inward_cancel_reason' => [
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

                    if (!$job_order->customer_id) {
                        return response()->json([
                            'success' => false,
                            'error' => 'Validation Error',
                            'errors' => [
                                'Customer Not Found!',
                            ],
                        ]);
                    }

                    DB::beginTransaction();

                    $job_order->status_id = 8470;

                    $job_order->billing_type_id = null;
                    $job_order->inward_cancel_reason_id = $request->inward_cancel_reason_id;
                    $job_order->inward_cancel_reason = $request->inward_cancel_reason;
                    $job_order->vehicle_payment_status = null;
                    $job_order->pending_reason_id = null;
                    $job_order->jv_customer_id = null;
                    $job_order->pending_remarks = null;
                    $job_order->vehicle_delivery_requester_id = Auth::user()->id;
                    $job_order->vehicle_delivery_request_remarks = null;
                    $job_order->approver_id = null;
                    $job_order->approved_remarks = null;
                    $job_order->approved_date_time = null;
                    $job_order->warranty_reason = null;
                    $job_order->is_aggregate_work = null;
                    $job_order->tvs_one_approval_status_id = null;
                    $job_order->updated_by_id = Auth::user()->id;
                    $job_order->updated_at = Carbon::now();
                    $job_order->save();

                    $job_order->aggregateWork()->sync([]);

                    $amc_aggregate_coupon = AmcAggregateCoupon::where('job_order_id', $job_order->id)->update(['job_order_id' => null, 'status_id' => 1]);

                    $gate_pass = $this->generateGatePass($job_order);

                    //Delete previous receipt
                    $remove_receipt = Receipt::where('receipt_of_id', 7622)->where('entity_id', $job_order->id)->forceDelete();

                    //Delete previous Invoice
                    $remove_invoice = GigoManualInvoice::where('invoiceable_type', 'App\JobOrder')->where('invoiceable_id', $job_order->id)->forceDelete();

                    DB::commit();
                    $message = "Vehicle delivery request saved successfully!";
                }

                return response()->json([
                    'success' => true,
                    'message' => $message,
                ]);

            } else if ($request->type_id == 2) {
                if ($request->status == 'Reject') {
                    $error_messages = [
                        'rejected_remarks.required' => "Vehicle Delivery Reject Remarks is required",
                    ];
                    $validator = Validator::make($request->all(), [
                        'job_order_id' => [
                            'required',
                            'integer',
                            'exists:job_orders,id',
                        ],
                        'rejected_remarks' => [
                            'required',
                        ],

                    ], $error_messages);
                } else {
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
                }

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

                if ($request->status == 'Reject') {
                    $job_order->status_id = 8479;
                    $job_order->rejected_remarks = $request->rejected_remarks;
                    $message = "Manual Vehicle Delivery Rejected Successfully!";
                } else {
                    $job_order->status_id = 8478;
                    $job_order->approved_remarks = $request->approved_remarks;
                    $job_order->approved_date_time = Carbon::now();
                    $message = "Manual Vehicle Delivery Approved Successfully!";
                }
                $job_order->save();

                if ($request->status == 'Approve') {
                    $gate_pass = $this->generateGatePass($job_order);
                }

                DB::commit();

                //Send Approved Mail for user
                $this->vehiceRequestMail($job_order->id, $type = 2);

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
                        // 'unique:receipts,temporary_receipt_no,' . $request->job_order_id . ',entity_id,receipt_of_id,7622',
                        // 'unique:receipts,permanent_receipt_no,' . $request->job_order_id . ',entity_id,receipt_of_id,7622',
                        // 'unique:receipts,temporary_receipt_no',
                        // 'unique:receipts,permanent_receipt_no',
                        'unique:job_order_payment_details,transaction_number',
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
                // dd($request->receipt_amount,$request->balance_amount);
                if ($request->receipt_amount != $request->balance_amount) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Validation Error',
                        'errors' => [
                            'Receipt amount should be equal to Remaining Invoice amount',
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

                $invoice_amount = GigoManualInvoice::where('invoiceable_type', 'App\JobOrder')->where('invoiceable_id', $job_order->id)->sum('amount');

                $receipt_amount = $request->receipt_amount;

                // if ($receipt_amount != $invoice_amount) {
                //     return response()->json([
                //         'success' => false,
                //         'error' => 'Validation Error',
                //         'errors' => [
                //             'Receipt amount should be equal to Invoice amount!',
                //         ],
                //     ]);
                // }

                DB::beginTransaction();

                $job_order->vehicle_payment_status = 1;
                $job_order->updated_by_id = Auth::user()->id;
                $job_order->updated_at = Carbon::now();
                $job_order->status_id = 8468;
                $job_order->save();

                //Save Receipt
                $customer = Customer::find($job_order->customer_id);

                if ($job_order->pending_reason_id == 2 || $job_order->pending_reason_id == 3) {
                    $payment_mode_id = 9;
                } elseif ($job_order->pending_reason_id == 4) {
                    $payment_mode_id = 10;
                } elseif ($job_order->pending_reason_id == 5) {
                    $payment_mode_id = 11;
                } else {
                    $payment_mode_id = $request->payment_mode_id;
                }

                //Save Payment
                $payment = new Payment;
                // dd($payment);
                $payment->entity_type_id = 8434;
                $payment->entity_id = $job_order->id;
                $payment->received_amount = $request->receipt_amount;
                $payment->receipt_id = null;
                $job_order->payment()->save($payment);

                //save payment detail
                $payment = new JobOrderPaymentDetail;
                $payment->payment_mode_id = $payment_mode_id;
                $payment->job_order_id = $job_order->id;
                $payment->transaction_number = $request->receipt_number;
                $payment->transaction_date = date('Y-m-d', strtotime($request->receipt_date));
                $payment->amount = $request->receipt_amount;
                $payment->save();

                //Updare Invoice
                // $update_invoice = GigoManualInvoice::where('invoiceable_type', 'App\JobOrder')->where('invoiceable_id', $job_order->id)->update(['receipt_id' => $receipt_id]);

                //CREATE DIRECTORY TO STORAGE PATH
                $attachment_path = storage_path('app/public/gigo/job_order/attachments/');
                Storage::makeDirectory($attachment_path, 0777);

                if (!empty($request->transaction_attachments)) {
                    foreach ($request->transaction_attachments as $key => $transaction_attachment) {
                        $value = rand(1, 20);
                        $image = $transaction_attachment;

                        $file_name_with_extension = $image->getClientOriginalName();
                        $file_name = pathinfo($file_name_with_extension, PATHINFO_FILENAME);
                        $extension = $image->getClientOriginalExtension();
                        $name = $job_order->id . '_Transcation_Attachment_' . date('Y_m_d_h_i_s') . '_' . $value . '.' . $extension;

                        $transaction_attachment->move(storage_path('app/public/gigo/job_order/attachments/'), $name);
                        $attachement = new Attachment;
                        $attachement->attachment_of_id = 227; //Job order
                        $attachement->attachment_type_id = 11342; //GIGO Transcation Attachment
                        $attachement->entity_id = $job_order->id;
                        $attachement->name = $name;
                        $attachement->save();
                    }
                }
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

    public function vehiceRequestMail($job_order_id, $type)
    {
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
            // 'manualDeliveryReceipt',
            'status',
            'outlet',
            'vehicleDeliveryRequestUser',
        ])
            ->select([
                'job_orders.*',
                DB::raw('DATE_FORMAT(job_orders.created_at,"%d-%m-%Y") as date'),
                DB::raw('DATE_FORMAT(job_orders.created_at,"%h:%i %p") as time'),
            ])
            ->find($job_order_id);

        $total_amount = $job_order->manualDeliveryLabourInvoice->amount + $job_order->manualDeliveryPartsInvoice->amount;

        //Total Discount Amount
        $discount_amount = ($job_order->labour_discount_amount > 0 ? $job_order->labour_discount_amount : 0) + ($job_order->part_discount_amount > 0 ? $job_order->part_discount_amount : 0);

        $job_order->customer_to_paid_amount = $total_amount - $discount_amount;

        $total_amount = number_format($total_amount, 2);
        $job_order->total_amount = $total_amount;

        $discount_amount = number_format($discount_amount, 2);
        $job_order->discount_amount = $discount_amount;
        // dd($total_amount, $discount_amount);

        if ($job_order) {
            $user_details = MailConfiguration::where('config_id', 3011)->pluck('to_email')->first();
            $to_email = explode(',', $user_details);
            if (!$user_details || count($to_email) == 0) {
                $to_email = ['0' => 'parthiban@uitoux.in'];
            }

            $user_details = MailConfiguration::where('config_id', 3011)->pluck('cc_email')->first();
            $cc_email = explode(',', $user_details);
            if (!$user_details || count($cc_email) == 0) {
                $cc_email = ['0' => 'parthiban@uitoux.in'];
            }

            $outlet = $job_order->outlet->ax_name ? $job_order->outlet->ax_name : $job_order->outlet->name;

            $subject = 'GIGO ' . $outlet . ' - ' . $job_order->vehicle->currentOwner->customer->name . ' vehicle need approval for delivery';

            if ($type == 2) {
                $to_email = [];
                $user = User::where('id', $job_order->vehicle_delivery_requester_id)->first();
                $cc_email = [];
                if ($user && $user->email) {
                    $to_email = ['0' => $user->email];
                }
                if ($job_order->status_id == 8478) {
                    $subject = 'GIGO ' . $outlet . ' - ' . $job_order->vehicle->currentOwner->customer->name . ' vehicle approved for delivery';
                } else {
                    $subject = 'GIGO ' . $outlet . ' - ' . $job_order->vehicle->currentOwner->customer->name . ' vehicle rejected for delivery';
                }
            }
            // dd($subject);
            if ($to_email) {
                // $cc_email = [];
                $approver_view_url = url('/') . '/#!/manual-vehicle-delivery/view/' . $job_order->id;
                $arr['job_order'] = $job_order;
                // $arr['subject'] = 'GIGO – Need approval for Vehicle Delivery';
                $arr['subject'] = $subject;
                $arr['to_email'] = $to_email;
                $arr['cc_email'] = $cc_email;
                $arr['type'] = $type;
                $arr['approver_view_url'] = $approver_view_url;

                $MailInstance = new VehicleDeliveryRequestMail($arr);
                $Mail = Mail::send($MailInstance);
            }
        }
    }

    public function generateGatePass($job_order)
    {
        // dd($job_order);
        $gate_log = GateLog::where('job_order_id', $job_order->id)->orderBy('id', 'DESC')->first();
        // dd($gate_log);
        if ($gate_log) {

            if (date('m') > 3) {
                $year = date('Y') + 1;
            } else {
                $year = date('Y');
            }
            //GET FINANCIAL YEAR ID
            $financial_year = FinancialYear::where('from', $year)
                // ->where('company_id', $gate_log->company_id)
                ->first();

            $branch = Outlet::where('id', $gate_log->outlet_id)->first();

            if ($branch && $financial_year) {

                //GENERATE GatePASS
                $generateNumber = SerialNumberGroup::generateNumber(29, $financial_year->id, $branch->state_id, $branch->id);

                if ($generateNumber['success']) {
                    $gate_pass = GatePass::firstOrNew(['job_order_id' => $job_order->id, 'type_id' => 8280]); //VEHICLE GATE PASS

                    if ($gate_pass->exists) {
                        $gate_pass->updated_at = Carbon::now();
                        $gate_pass->updated_by_id = Auth::user()->id;
                    } else {
                        $gate_log->status_id = 8123; //GATE OUT PENDING
                        $gate_pass->status_id = 8340; //GATE OUT PENDING
                        $gate_pass->created_at = Carbon::now();
                        $gate_pass->created_by_id = Auth::user()->id;
                    }

                    $gate_pass->gate_pass_of_id = 11280;
                    $gate_pass->entity_id = $job_order->id;

                    $gate_pass->company_id = $gate_log->company_id;
                    $gate_pass->number = $generateNumber['number'];
                    $gate_pass->save();

                    $gate_log->gate_pass_id = $gate_pass->id;
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

    //TVS Discount Save
    public function tvsOneDiscountSave(Request $request)
    {
        // dd($request->all());
        try {
            if ($request->status == 'Reject') {
                $error_messages = [
                    'rejected_remarks.required' => "Vehicle Delivery Reject Remarks is required",
                ];
                $validator = Validator::make($request->all(), [
                    'job_order_id' => [
                        'required',
                        'integer',
                        'exists:job_orders,id',
                    ],
                    'rejected_remarks' => [
                        'required',
                    ],

                ], $error_messages);
            } else {
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
            }

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation Error',
                    'errors' => $validator->errors()->all(),
                ]);
            }

            $job_order = JobOrder::with([
                'amcMember',
                'amcMember.amcPolicy',
            ])->find($request->job_order_id);

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

            if ($request->status == 'Reject') {
                $job_order->tvs_one_approval_status_id = 3;
                $job_order->tvs_one_rejected_remarks = $request->rejected_remarks;
                $message = "TVS ONE Discount Rejected Successfully!";

                //Labour amount updates
                if ($request->labour_amount) {

                    //Labour discount percentage
                    if ($job_order->amcMember && $job_order->amcMember->amcPolicy && $job_order->amcMember->amcPolicy->labour_discount_percentage) {
                        $labour_discount_percentage = $job_order->amcMember->amcPolicy->labour_discount_percentage;
                        $labour_discount_value = ($request->labour_amount * $labour_discount_percentage) / 100;

                        $job_order->labour_discount_amount = $labour_discount_value;
                    } else {
                        $job_order->labour_discount_amount = null;
                    }

                    // $labour_discount_percentage =
                    $labour_invoice = GigoManualInvoice::find($request->labour_id);
                    if ($labour_invoice) {
                        $labour_invoice->amount = $request->labour_amount;
                        $labour_invoice->updated_by_id = Auth::user()->id;
                        $labour_invoice->updated_at = Carbon::now();
                        $labour_invoice->save();

                        //Update Job Order
                        if ($job_order->status_id != 8477 && $job_order->status_id != 8479) {
                            $job_order->status_id = 8467;
                            $job_order->save();
                        }
                    }
                }

                //Parts amount updates
                if ($request->parts_amount) {

                    //Parts discount percentage
                    if ($job_order->amcMember && $job_order->amcMember->amcPolicy && $job_order->amcMember->amcPolicy->part_discount_percentage) {
                        $part_discount_percentage = $job_order->amcMember->amcPolicy->part_discount_percentage;
                        $parts_discount_value = ($request->parts_amount * $part_discount_percentage) / 100;

                        $job_order->part_discount_amount = $parts_discount_value;
                    } else {
                        $job_order->part_discount_amount = null;
                    }

                    $parts_invoice = GigoManualInvoice::find($request->part_id);
                    if ($parts_invoice) {
                        $parts_invoice->amount = $request->parts_amount;
                        $parts_invoice->updated_by_id = Auth::user()->id;
                        $parts_invoice->updated_at = Carbon::now();
                        $parts_invoice->save();

                        //Update Job Order
                        if ($job_order->status_id != 8477 && $job_order->status_id != 8479) {
                            $job_order->status_id = 8467;
                            $job_order->save();
                        }
                    }
                }
            } else {
                $job_order->tvs_one_approval_status_id = 2;
                $job_order->tvs_one_approved_remarks = $request->approved_remarks;
                $message = "TVS ONE Discount Approved Successfully!";
            }

            $job_order->updated_at = Carbon::now();
            $job_order->updated_by_id = Auth::user()->id;
            $job_order->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $message,
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

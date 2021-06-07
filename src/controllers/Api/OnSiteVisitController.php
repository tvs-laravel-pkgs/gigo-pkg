<?php

namespace Abs\GigoPkg\Api;

use Abs\SerialNumberPkg\SerialNumberGroup;
use Abs\TaxPkg\Tax;
use App\Address;
use App\AmcCustomer;
use App\Attachment;
use App\City;
use App\Company;
use App\Config;
use App\Country;
use App\Customer;
use App\Employee;
use App\Entity;
use App\FinancialYear;
use App\Http\Controllers\Controller;
use App\Http\Controllers\WpoSoapController;
use App\OnSiteOrder;
use App\OnSiteOrderEstimate;
use App\OnSiteOrderIssuedPart;
use App\OnSiteOrderPart;
use App\OnSiteOrderRepairOrder;
use App\OnSiteOrderReturnedPart;
use App\OnSiteOrderTimeLog;
use App\Otp;
use App\Outlet;
use App\Part;
use App\PartStock;
use App\QRPaymentApp;
use App\RepairOrder;
use App\ShortUrl;
use App\SplitOrderType;
use App\State;
use App\User;
use Auth;
use Carbon\Carbon;
use DB;
use File;
use Illuminate\Http\Request;
use phpseclib\Crypt\RSA as Crypt_RSA;
use QRCode;
use Storage;
use Validator;

class OnSiteVisitController extends Controller
{
    public $successStatus = 200;

    public function __construct(WpoSoapController $getSoap = null)
    {
        $this->getSoap = $getSoap;
    }

    public function getLabourPartsData($params)
    {

        $result = array();

        $site_visit = OnSiteOrder::with([
            'company',
            'outlet',
            'onSiteVisitUser',
            'customer',
            'customer.amcCustomer',
            'address',
            'address.country',
            'address.state',
            'address.city',
            'outlet',
            'status',
            'onSiteOrderRepairOrders',
            'onSiteOrderRepairOrders.status',
            'onSiteOrderParts',
            'onSiteOrderParts.status',
            'photos',
        ])->where('id', $params['on_site_order_id'])->first();

        $customer_paid_type = SplitOrderType::where('paid_by_id', '10013')->pluck('id')->toArray();

        $labour_amount = 0;
        $part_amount = 0;

        $labour_details = array();
        $labours = array();

        $not_approved_labour_parts_count = 0;

        if ($site_visit->onSiteOrderRepairOrders) {
            foreach ($site_visit->onSiteOrderRepairOrders as $key => $value) {
                $labour_details[$key]['id'] = $value->id;
                $labour_details[$key]['labour_id'] = $value->repair_order_id;
                $labour_details[$key]['code'] = $value->repairOrder->code;
                $labour_details[$key]['name'] = $value->repairOrder->name;
                $labour_details[$key]['type'] = $value->repairOrder->repairOrderType ? $value->repairOrder->repairOrderType->short_name : '-';
                $labour_details[$key]['qty'] = $value->qty;
                $repair_order = $value->repairOrder;
                if ($value->repairOrder->is_editable == 1) {
                    $labour_details[$key]['rate'] = $value->amount;
                    $repair_order->amount = $value->amount;
                } else {
                    $labour_details[$key]['rate'] = $value->repairOrder->amount;
                }

                $labour_details[$key]['amount'] = $value->amount;
                $labour_details[$key]['split_order_type'] = $value->splitOrderType ? $value->splitOrderType->code . "|" . $value->splitOrderType->name : '-';
                $labour_details[$key]['removal_reason_id'] = $value->removal_reason_id;
                $labour_details[$key]['split_order_type_id'] = $value->split_order_type_id;
                $labour_details[$key]['repair_order'] = $repair_order;
                $labour_details[$key]['customer_voice'] = $value->customerVoice;
                $labour_details[$key]['customer_voice_id'] = $value->customer_voice_id;
                $labour_details[$key]['status_id'] = $value->status_id;
                $labour_details[$key]['status'] = $value->status->name;
                if (in_array($value->split_order_type_id, $customer_paid_type) || !$value->split_order_type_id) {
                    if ($value->is_free_service != 1 && $value->removal_reason_id == null) {
                        $labour_amount += $value->amount;
                        if ($value->is_customer_approved == 0) {
                            $not_approved_labour_parts_count++;
                        }
                    } else {
                        $labour_details[$key]['amount'] = 0;
                    }
                } else {
                    $labour_details[$key]['amount'] = 0;
                }

                $labours[$key]['id'] = $value->repair_order_id;
                $labours[$key]['code'] = $value->repairOrder->code;
                $labours[$key]['name'] = $value->repairOrder->name;
            }
        }

        $part_details = array();
        if ($site_visit->onSiteOrderParts) {
            foreach ($site_visit->onSiteOrderParts as $key => $value) {
                $part_details[$key]['id'] = $value->id;
                $part_details[$key]['part_id'] = $value->part_id;
                $part_details[$key]['code'] = $value->part->code;
                $part_details[$key]['name'] = $value->part->name;
                $part_details[$key]['type'] = $value->part->partType ? $value->part->partType->name : '-';
                $part_details[$key]['rate'] = $value->rate;
                $part_details[$key]['qty'] = $value->qty;
                $part_details[$key]['amount'] = $value->amount;
                $part_details[$key]['total_amount'] = $value->amount;
                $part_details[$key]['split_order_type'] = $value->splitOrderType ? $value->splitOrderType->code . "|" . $value->splitOrderType->name : '-';
                $part_details[$key]['removal_reason_id'] = $value->removal_reason_id;
                $part_details[$key]['split_order_type_id'] = $value->split_order_type_id;
                $part_details[$key]['part'] = $value->part;
                $part_details[$key]['status_id'] = $value->status_id;
                $part_details[$key]['status'] = $value->status->name;
                $part_details[$key]['customer_voice'] = $value->customerVoice;
                $part_details[$key]['customer_voice_id'] = $value->customer_voice_id;
                $part_details[$key]['repair_order'] = $value->part->repair_order_parts;

                if (in_array($value->split_order_type_id, $customer_paid_type) || !$value->split_order_type_id) {
                    if ($value->is_free_service != 1 && $value->removal_reason_id == null) {
                        $part_amount += $value->amount;
                        if ($value->is_customer_approved == 0) {
                            $not_approved_labour_parts_count++;
                        }
                    } else {
                        $part_details[$key]['amount'] = 0;
                    }
                } else {
                    $part_details[$key]['amount'] = 0;
                }
            }
        }

        $total_amount = $part_amount + $labour_amount;

        $result['site_visit'] = $site_visit;
        $result['labour_details'] = $labour_details;
        $result['part_details'] = $part_details;
        $result['labour_amount'] = $labour_amount;
        $result['part_amount'] = $part_amount;
        $result['total_amount'] = $total_amount;
        $result['labours'] = $labours;
        $result['not_approved_labour_parts_count'] = $not_approved_labour_parts_count;

        return $result;
    }

    public function getCustomerAddress(Request $request)
    {
        // dd($request->all());
        if ($request->customer_code) {
            $company_name = Company::where('id', Auth::user()->company_id)
                ->pluck('ax_company_code')->first();

            $customer_address = $this->getSoap->getNewCustomerAddressSearch($request->customer_code, $company_name);
            if ($customer_address) {
                $customer = Customer::where('code', $request->customer_code)->first();

                if ($customer) {
                    $address = Address::firstOrNew(['entity_id' => $customer->id, 'ax_id' => $customer_address['recid']]);
                    $address->company_id = Auth::user()->company_id;
                    $address->entity_id = $customer->id;
                    $address->ax_id = $customer_address['recid'];
                    $address->gst_number = $customer_address['gst_number'];
                    $address->is_primary = 1;
                    $address->address_of_id = 24;
                    $address->address_type_id = 40;
                    $address->name = 'Primary Address_' . $customer_address['recid'];
                    $address->address_line1 = str_replace('""', '', $customer_address['address']);
                    $city = City::where('name', $customer_address['city'])->first();
                    $state = State::where('code', $customer_address['state'])->first();
                    $address->country_id = $state ? $state->country_id : null;
                    $address->state_id = $state ? $state->id : null;
                    $address->city_id = $city ? $city->id : null;
                    $address->pincode = $customer_address['pincode'];
                    $address->save();
                }
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation Error',
                    'errors' => [
                        'Site Visit Detail Not Found!',
                    ],
                ]);
            }

            return response()->json([
                'success' => true,
                'customer_address' => $address,
                'customer' => $customer,
            ]);

        } else {
            return response()->json([
                'success' => false,
                'error' => 'Validation Error',
                'errors' => [
                    'Customer Detail Not Found!',
                ],
            ]);
        }

    }

    public function chmsLogin()
    {
        $username = 'SPA3938';
        $password = '123456';

        $login_url = 'https://tvsconnect.in/cemhs/apis/felogin?';
        $auth_url = $login_url . 'userId=' . $username . '&password=' . $password . '&userType=4';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $auth_url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $login_response = curl_exec($ch);

        $login_encode = json_encode($login_response);
        $login_data = json_decode($login_response, true);

        if ($login_data && $login_data['loginSuccessful'] == 'true') {
            $api_token = Config::firstOrNew(['config_type_id' => 465]);
            $api_token->name = $login_data['authenticationToken'];
            $api_token->save();

            $api_token = $login_data['authenticationToken'];
            return $api_token;
        } else {
            return false;
            //     return response()->json([
            //         'success' => false,
            //         'error' => 'Validation Error',
            //         'errors' => [
            //             'Login Details mismatched!',
            //         ],
            //     ]);
        }
    }

    public function chmsPartStock($token, $part_code, $outlet_code)
    {
        $part_stock_url = 'https://tvsconnect.in/cemhs/apis/getStockDetails?';
        $part_content = $part_stock_url . 'apiKey=' . $token . '&partCode=' . $part_code . '&branch=' . $outlet_code;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $part_content);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $part_stock_response = curl_exec($ch);

        $stock_data = json_encode($part_stock_response);
        $stock_data = json_decode($part_stock_response, true);

        return $stock_data;
    }

    public function getPartStockDetails(Request $request)
    {
        // dd($request->all());
        $part = Part::find($request->part_id);
        $outlet = Outlet::find($request->outlet_id);

        $api_token = Config::where('config_type_id', 465)->pluck('name')->first();
        if (!$api_token) {
            $api_token = $this->chmsLogin();
        }

        if ($api_token) {
            $part_stock_detail = $this->chmsPartStock($api_token, $part->code, $outlet->code);

            if ($part_stock_detail && isset($part_stock_detail['status']) && ($part_stock_detail['status'] == 'failiure' || $part_stock_detail['status'] == 'failure')) {
                $api_token = $this->chmsLogin();
                $part_stock_detail = $this->chmsPartStock($api_token, $part->code, $outlet->code);
            }

            if ($part_stock_detail && isset($part_stock_detail['AvailableStock']) && $part_stock_detail['AvailableStock'] > 0) {

                $part_stock = PartStock::firstOrNew(['company_id' => Auth::user()->company_id, 'outlet_id' => $outlet->id, 'part_id' => $part->id]);

                if ($part_stock->exists) {
                    $part_stock->updated_by_id = Auth::user()->id;
                    $part_stock->updated_at = Carbon::now();
                } else {
                    $part_stock->created_by_id = Auth::user()->id;
                    $part_stock->created_at = Carbon::now();
                    $part_stock->updated_at = null;
                }

                $part_stock->stock = $part_stock_detail['AvailableStock'];
                $part_stock->mrp = $part_stock_detail['mrp'];
                // $part_stock->rate = $part_stock_detail['rate'];
                $part_stock->cost_price = $part_stock_detail['cost'];
                $part_stock->save();
            }
        }

        $part = Part::with([
            'uom',
            'partStock' => function ($query) use ($outlet) {
                $query->where('outlet_id', $outlet->id);
            },
            'taxCode',
            'taxCode.taxes',
        ])
            ->find($request->part_id);

        $data['part'] = $part;

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function getFormData(Request $request)
    {
        // dd($request->all());
        if ($request->id) {
            $site_visit = OnSiteOrder::find($request->id);

            if (!$site_visit) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation Error',
                    'errors' => [
                        'Site Visit Detail Not Found!',
                    ],
                ]);
            }

            $params['on_site_order_id'] = $request->id;

            $result = $this->getLabourPartsData($params);

            $site_visit = $result['site_visit'];
            $amc_customer_status = 0;
            if ($site_visit && $site_visit->amc_customer_id) {
                $amc_customer_status = 1;
            }

            //Check Estimate PDF Available or not
            $directoryPath = storage_path('app/public/on-site-visit/pdf/' . $site_visit->number . '_estimate.pdf');
            if (file_exists($directoryPath)) {
                $site_visit->estimate_pdf = url('storage/app/public/on-site-visit/pdf/' . $site_visit->number . '_estimate.pdf');
            } else {
                $site_visit->estimate_pdf = '';
            }

            //Check Revised Estimate PDF Available or not
            $directoryPath = storage_path('app/public/on-site-visit/pdf/' . $site_visit->number . '_revised_estimate.pdf');
            if (file_exists($directoryPath)) {
                $site_visit->revised_estimate_pdf = url('storage/app/public/on-site-visit/pdf/' . $site_visit->number . '_revised_estimate.pdf');
            } else {
                $site_visit->revised_estimate_pdf = '';
            }

            //Check Labour PDF Available or not
            $directoryPath = storage_path('app/public/on-site-visit/pdf/' . $site_visit->number . '_labour_invoice.pdf');
            if (file_exists($directoryPath)) {
                $site_visit->labour_pdf = url('storage/app/public/on-site-visit/pdf/' . $site_visit->number . '_labour_invoice.pdf');
            } else {
                $site_visit->labour_pdf = '';
            }

            //Check Part PDF Available or not
            $directoryPath = storage_path('app/public/on-site-visit/pdf/' . $site_visit->number . '_parts_invoice.pdf');
            if (file_exists($directoryPath)) {
                $site_visit->part_pdf = url('storage/app/public/on-site-visit/pdf/' . $site_visit->number . '_parts_invoice.pdf');
            } else {
                $site_visit->part_pdf = '';
            }

            //Check Bill Detail PDF Available or not
            $directoryPath = storage_path('app/public/on-site-visit/pdf/' . $site_visit->number . '_bill_details.pdf');
            if (file_exists($directoryPath)) {
                $site_visit->bill_detail_pdf = url('storage/app/public/on-site-visit/pdf/' . $site_visit->number . '_bill_details.pdf');
            } else {
                $site_visit->bill_detail_pdf = '';
            }

        } else {
            $site_visit = new OnSiteOrder;
            // $previous_number = OnSiteOrder::where('outlet_id',Auth::user()->working_outlet_id)->orderBy('id','desc')->first();
            // $site_visit->number =
            $result['site_visit'] = $site_visit;
            $result['part_details'] = [];
            $result['labour_details'] = [];
            $result['total_amount'] = 0;
            $result['labour_amount'] = 0;
            $result['part_amount'] = 0;
            $result['labours'] = [];
            $result['not_approved_labour_parts_count'] = 0;
            $amc_customer_status = 0;
        }

        $this->data['success'] = true;

        $extras = [
            'country_list' => Country::getDropDownList(),
            'state_list' => [], //State::getDropDownList(),
            'city_list' => [], //City::getDropDownList(),
        ];

        // $this->data['site_visit'] = $site_visit;
        $this->data['extras'] = $extras;

        return response()->json([
            'success' => true,
            'site_visit' => $result['site_visit'],
            'part_details' => $result['part_details'],
            'labour_details' => $result['labour_details'],
            'total_amount' => $result['total_amount'],
            'labour_amount' => $result['labour_amount'],
            'parts_rate' => $result['part_amount'],
            'labours' => $result['labours'],
            'not_approved_labour_parts_count' => $result['not_approved_labour_parts_count'],
            'extras' => $extras,
            'amc_customer_status' => $amc_customer_status,
            'country' => Country::find(1),
        ]);
    }

    public function saveLabourDetail(Request $request)
    {
        // dd($request->all());
        try {
            $error_messages = [
                'rot_id.unique' => 'Labour is already taken',
            ];

            $validator = Validator::make($request->all(), [
                'on_site_order_id' => [
                    'required',
                    'integer',
                    'exists:on_site_orders,id',
                ],
                'rot_id' => [
                    'required',
                    'integer',
                    'exists:repair_orders,id',
                    'unique:on_site_order_repair_orders,repair_order_id,' . $request->on_site_repair_order_id . ',id,on_site_order_id,' . $request->on_site_order_id,
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
            $on_site_order = OnSiteOrder::find($request->on_site_order_id);

            $customer_paid_type = SplitOrderType::where('paid_by_id', '10013')->pluck('id')->toArray();

            if (!$on_site_order) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation Error',
                    'errors' => [
                        'On Site Visit Not Found!',
                    ],
                ]);
            }

            DB::beginTransaction();

            $on_site_order->is_customer_approved = 0;
            // $on_site_order->status_id = 8463;
            $on_site_order->save();

            $estimate_id = OnSiteOrderEstimate::where('on_site_order_id', $on_site_order->id)->where('status_id', 10071)->first();
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
                $branch = Outlet::where('id', $on_site_order->outlet_id)->first();

                //GENERATE GATE IN VEHICLE NUMBER
                $generateNumber = SerialNumberGroup::generateNumber(151, $financial_year->id, $branch->state_id, $branch->id);
                if (!$generateNumber['success']) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Validation Error',
                        'errors' => [
                            'No Estimate Reference number found for FY : ' . $financial_year->year . ', State : ' . $branch->state->code . ', Outlet : ' . $branch->code,
                        ],
                    ]);
                }

                $estimate = new OnSiteOrderEstimate;
                $estimate->on_site_order_id = $on_site_order->id;
                $estimate->number = $generateNumber['number'];
                $estimate->status_id = 10071;
                $estimate->created_by_id = Auth::user()->id;
                $estimate->created_at = Carbon::now();
                $estimate->save();
                $estimate_order_id = $estimate->id;
            }

            $repair_order = RepairOrder::find($request->rot_id);
            if (!$repair_order) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation Error',
                    'errors' => [
                        'Repair order / Labour Not Found!',
                    ],
                ]);
            }

            if (!empty($request->on_site_repair_order_id)) {
                $on_site_repair_order = OnSiteOrderRepairOrder::find($request->on_site_repair_order_id);
                if ($on_site_repair_order) {
                    $on_site_repair_order->updated_by_id = Auth::user()->id;
                    $on_site_repair_order->updated_at = Carbon::now();
                    $on_site_repair_order->removal_reason_id = null;
                    $on_site_repair_order->removal_reason = null;
                } else {
                    $on_site_repair_order = new OnSiteOrderRepairOrder;
                    $on_site_repair_order->created_by_id = Auth::user()->id;
                    $on_site_repair_order->created_at = Carbon::now();
                }
            } else {
                $on_site_repair_order = new OnSiteOrderRepairOrder;
                $on_site_repair_order->created_by_id = Auth::user()->id;
                $on_site_repair_order->created_at = Carbon::now();
            }

            $on_site_repair_order->on_site_order_id = $request->on_site_order_id;
            $on_site_repair_order->repair_order_id = $request->rot_id;
            $on_site_repair_order->qty = $repair_order->hours;
            $on_site_repair_order->split_order_type_id = $request->split_order_type_id;
            $on_site_repair_order->estimate_order_id = $estimate_order_id;
            // if ($request->repair_order_description) {
            $on_site_repair_order->amount = isset($request->repair_order_amount) ? $request->repair_order_amount : $repair_order->amount;
            // } else {
            // $on_site_repair_order->amount = $repair_order->amount;
            // }

            if (in_array($request->split_order_type_id, $customer_paid_type)) {
                $on_site_repair_order->status_id = 8180; //Customer Approval Pending
                $on_site_repair_order->is_customer_approved = 0;
            } else {
                $on_site_repair_order->is_customer_approved = 1;
                $on_site_repair_order->status_id = 8181; //Mechanic Not Assigned
            }

            $on_site_repair_order->save();

            if ($on_site_order->is_customer_approved == 1) {
                $result = $this->getApprovedLabourPartsAmount($on_site_order->id);

                if ($result['status'] == 'true') {
                    if (in_array($request->split_order_type_id, $customer_paid_type)) {
                        $on_site_order->status_id = 8200; //Customer Approval Pending
                        $on_site_order->is_customer_approved = 0;
                        $on_site_order->save();
                    }
                } else {
                    OnSiteOrderPart::where('on_site_order_id', $on_site_order->id)->where('is_customer_approved', 0)->where('status_id', 8200)->whereNull('removal_reason_id')->update(['is_customer_approved' => 1, 'status_id' => 8201, 'updated_at' => Carbon::now()]);

                    OnSiteOrderRepairOrder::where('on_site_order_id', $on_site_order->id)->where('is_customer_approved', 0)->where('status_id', 8180)->whereNull('removal_reason_id')->update(['is_customer_approved' => 1, 'status_id' => 8181, 'updated_at' => Carbon::now()]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Labour detail saved successfully!!',
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

    public function savePartsDetail(Request $request)
    {
        // dd($request->all());
        try {
            $validator = Validator::make($request->all(), [
                'on_site_order_id' => [
                    'required',
                    'integer',
                    'exists:on_site_orders,id',
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
            $on_site_order = OnSiteOrder::find($request->on_site_order_id);

            $customer_paid_type = SplitOrderType::where('paid_by_id', '10013')->pluck('id')->toArray();

            if (!$on_site_order) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation Error',
                    'errors' => [
                        'On Site Visit Not Found!',
                    ],
                ]);
            }

            DB::beginTransaction();

            $on_site_order->is_customer_approved = 0;
            // $on_site_visit->status_id = 8463;
            $on_site_order->save();

            $estimate_id = OnSiteOrderEstimate::where('on_site_order_id', $on_site_order->id)->where('status_id', 10071)->first();
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
                $branch = Outlet::where('id', $on_site_order->outlet_id)->first();

                //GENERATE GATE IN VEHICLE NUMBER
                $generateNumber = SerialNumberGroup::generateNumber(151, $financial_year->id, $branch->state_id, $branch->id);
                if (!$generateNumber['success']) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Validation Error',
                        'errors' => [
                            'No Estimate Reference number found for FY : ' . $financial_year->year . ', State : ' . $branch->state->code . ', Outlet : ' . $branch->code,
                        ],
                    ]);
                }

                $estimate = new OnSiteOrderEstimate;
                $estimate->on_site_order_id = $on_site_order->id;
                $estimate->number = $generateNumber['number'];
                $estimate->status_id = 10071;
                $estimate->created_by_id = Auth::user()->id;
                $estimate->created_at = Carbon::now();
                $estimate->save();

                $estimate_order_id = $estimate->id;
            }

            $customer_paid_type = SplitOrderType::where('paid_by_id', '10013')->pluck('id')->toArray();

            $part = Part::with(['partStock'])->where('id', $request->part_id)->first();
            if (!$part) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation Error',
                    'errors' => [
                        'Part Not Found',
                    ],
                ]);
            }

            $request_qty = $request->qty;

            if (!empty($request->on_site_part_id)) {
                $on_site_part = OnSiteOrderPart::find($request->on_site_part_id);
                if ($on_site_part) {
                    $on_site_part->updated_by_id = Auth::user()->id;
                    $on_site_part->updated_at = Carbon::now();
                    $on_site_part->removal_reason_id = null;
                    $on_site_part->removal_reason = null;
                } else {
                    $on_site_part = new OnSiteOrderPart;
                    $on_site_part->created_by_id = Auth::user()->id;
                    $on_site_part->created_at = Carbon::now();
                }
            } else {
                //Check Request parts are already requested or not.
                $on_site_part = OnSiteOrderPart::where('on_site_order_id', $request->on_site_order_id)->where('part_id', $request->part_id)->where('status_id', 8200)->where('is_customer_approved', 0)->whereNull('removal_reason_id')->first();
                if ($on_site_part) {
                    $request_qty = $on_site_part->qty + $request->qty;
                    $on_site_part->updated_by_id = Auth::user()->id;
                    $on_site_part->updated_at = Carbon::now();
                } else {
                    $on_site_part = new OnSiteOrderPart;
                    $on_site_part->created_by_id = Auth::user()->id;
                    $on_site_part->created_at = Carbon::now();
                }
                $on_site_part->estimate_order_id = $estimate_order_id;
            }

            $part_mrp = $request->mrp ? $request->mrp : 0;
            $on_site_part->on_site_order_id = $request->on_site_order_id;
            $on_site_part->part_id = $request->part_id;

            $on_site_part->rate = $part_mrp;
            $on_site_part->qty = $request_qty;
            $on_site_part->split_order_type_id = $request->split_order_type_id;
            $on_site_part->amount = $request_qty * $part_mrp;

            if (!$request->split_order_type_id || in_array($request->split_order_type_id, $customer_paid_type)) {
                $on_site_part->status_id = 8200; //Customer Approval Pending
                $on_site_part->is_customer_approved = 0;
            } else {
                $on_site_part->is_customer_approved = 1;
                $on_site_part->status_id = 8201; //Not Issued
            }

            $on_site_part->save();

            if ($on_site_order->is_customer_approved == 1) {
                $result = $this->getApprovedLabourPartsAmount($on_site_order->id);

                if ($result['status'] == 'true') {
                    if (in_array($request->split_order_type_id, $customer_paid_type)) {
                        $on_site_order->status_id = 8200; //Customer Approval Pending
                        $on_site_order->is_customer_approved = 0;
                        $on_site_order->save();
                    }
                } else {
                    OnSiteOrderPart::where('on_site_order_id', $on_site_order->id)->where('is_customer_approved', 0)->where('status_id', 8200)->whereNull('removal_reason_id')->update(['is_customer_approved' => 1, 'status_id' => 8201, 'updated_at' => Carbon::now()]);

                    OnSiteOrderRepairOrder::where('on_site_order_id', $on_site_order->id)->where('is_customer_approved', 0)->where('status_id', 8180)->whereNull('removal_reason_id')->update(['is_customer_approved' => 1, 'status_id' => 8181, 'updated_at' => Carbon::now()]);
                }
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

    public function getApprovedLabourPartsAmount($site_visit_id)
    {

        $customer_paid_type = SplitOrderType::where('paid_by_id', '10013')->pluck('id')->toArray();

        $site_visit = OnSiteOrder::with([
            'company',
            'outlet',
            'onSiteVisitUser',
            'customer',
            'customer.address',
            'status',
            'onSiteOrderRepairOrders' => function ($q) {
                $q->whereNull('removal_reason_id');
            },
            'onSiteOrderParts' => function ($q) {
                $q->whereNull('removal_reason_id');
            },
        ])->find($site_visit_id);

        if ($site_visit->customer->primaryAddress) {
            //Check which tax applicable for customer
            if ($site_visit->outlet->state_id == $site_visit->customer->primaryAddress->state_id) {
                $tax_type = 1160; //Within State
            } else {
                $tax_type = 1161; //Inter State
            }
        } else {
            $tax_type = 1160; //Within State
        }

        $taxes = Tax::whereIn('id', [1, 2, 3])->get();

        $parts_amount = 0;
        $labour_amount = 0;
        $total_billing_amount = 0;

        if ($site_visit->onSiteOrderRepairOrders) {
            foreach ($site_visit->onSiteOrderRepairOrders as $key => $labour) {
                if ($labour->is_free_service != 1 && (in_array($labour->split_order_type_id, $customer_paid_type) || !$labour->split_order_type_id)) {
                    $total_amount = 0;
                    $tax_amount = 0;
                    if ($labour->repairOrder->taxCode) {
                        foreach ($labour->repairOrder->taxCode->taxes as $tax_key => $value) {
                            $percentage_value = 0;
                            if ($value->type_id == $tax_type) {
                                $percentage_value = ($labour->amount * $value->pivot->percentage) / 100;
                                $percentage_value = number_format((float) $percentage_value, 2, '.', '');
                            }
                            $tax_amount += $percentage_value;
                        }
                    }

                    $total_amount = $tax_amount + $labour->amount;
                    $total_amount = number_format((float) $total_amount, 2, '.', '');
                    $labour_amount += $total_amount;
                }
            }
        }

        if ($site_visit->onSiteOrderParts) {
            foreach ($site_visit->onSiteOrderParts as $key => $parts) {
                if ($parts->is_free_service != 1 && (in_array($parts->split_order_type_id, $customer_paid_type) || !$parts->split_order_type_id)) {
                    $total_amount = 0;

                    $tax_amount = 0;
                    if ($parts->part->taxCode) {
                        if (count($parts->part->taxCode->taxes) > 0) {
                            foreach ($parts->part->taxCode->taxes as $tax_key => $value) {
                                $percentage_value = 0;
                                if ($value->type_id == $tax_type) {
                                    $percentage_value = ($parts->amount * $value->pivot->percentage) / 100;
                                    $percentage_value = number_format((float) $percentage_value, 2, '.', '');
                                }
                                $tax_amount += $percentage_value;
                            }
                        }
                    }

                    // $total_amount = $tax_amount + $parts->amount;
                    $total_amount = $parts->amount;
                    $total_amount = number_format((float) $total_amount, 2, '.', '');
                    $parts_amount += $total_amount;
                }
            }
        }

        $total_billing_amount = $parts_amount + $labour_amount;

        $total_billing_amount = round($total_billing_amount);

        if ($total_billing_amount > $site_visit->approved_amount) {
            // return $total_billing_amount;
            $result['status'] = 'true';
            $result['total_billing_amount'] = $total_billing_amount;
        } else {
            $result['status'] = 'false';
            $result['total_billing_amount'] = $total_billing_amount;
        }

        return $result;
    }

    //Send SMS to Customer for AMC Request
    //Save AMC Customer details
    public function amcCustomerSave(Request $request)
    {
        // dd($request->all());
        try {

            $on_site_order = OnSiteOrder::with(['customer'])->find($request->id);

            if (!$on_site_order) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation Error',
                    'errors' => [
                        'On Site Visit Not Found!',
                    ],
                ]);
            }

            if ($request->type_id == 1) {
                if (!$on_site_order->customer) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Validation Error',
                        'errors' => ['Customer Not Found!'],
                    ]);
                }

                $customer_mobile = $on_site_order->customer->mobile_no;

                if (!$customer_mobile) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Validation Error',
                        'errors' => ['Customer Mobile Number Not Found!'],
                    ]);
                }

                DB::beginTransaction();

                $message = 'Thanks for the interest';

                $msg = sendSMSNotification($customer_mobile, $message);

                DB::commit();

                $message = 'Message Sent successfully!!';

            } elseif ($request->type_id == 2) {

                if (strtotime($request->amc_starting_date) >= strtotime($request->amc_ending_date)) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Validation Error',
                        'errors' => [
                            'AMC Ending Date should be greater than AMC Starting Date',
                        ],
                    ]);
                }

                DB::beginTransaction();

                $amc_customer = AmcCustomer::firstOrNew(['customer_id' => $request->customer_id, 'amc_customer_type_id' => 2, 'tvs_one_customer_code' => $request->amc_customer_code]);

                if ($amc_customer->exists) {
                    $amc_customer->updated_by_id = Auth::user()->id;
                    $amc_customer->updated_at = Carbon::now();
                } else {
                    $amc_customer->total_services = 12;
                    $amc_customer->remaining_services = 12;
                    $amc_customer->created_by_id = Auth::user()->id;
                    $amc_customer->created_at = Carbon::now();
                    $amc_customer->outlet_id = Auth::user()->working_outlet_id;
                    $amc_customer->updated_at = null;
                }
                $amc_customer->start_date = date('Y-m-d', strtotime($request->amc_starting_date));
                $amc_customer->expiry_date = date('Y-m-d', strtotime($request->amc_ending_date));
                $amc_customer->save();

                //Update AMC Remaining Count
                $amc_customer_status = 0;
                if (date('Y-m-d', strtotime($amc_customer->expiry_date)) >= date('Y-m-d')) {
                    $amc_customer_status = 1;
                }

                if (date('Y-m-d', strtotime($amc_customer->start_date)) <= date('Y-m-d')) {
                    if ($amc_customer_status == 1) {
                        $amc_customer_status = 1;
                    } else {
                        $amc_customer_status = 0;
                    }
                } else {
                    $amc_customer_status = 0;
                }

                $amc_available = 0;
                if (is_null($amc_customer->remaining_services)) {
                    $remaining_services = $amc_customer->total_services - 1;
                    $amc_available = 1;
                } elseif ($amc_customer->remaining_services > 0) {
                    $remaining_services = $amc_customer->remaining_services - 1;
                    $amc_available = 1;
                }

                if ($amc_available && $amc_customer_status) {
                    $amc_customer->remaining_services = $remaining_services;
                    $amc_customer->save();

                    //Update On Site Visit AMC Customer
                    $on_site_order->amc_customer_id = $amc_customer->id;
                    $on_site_order->updated_by_id = Auth::user()->id;
                    $on_site_order->updated_at = Carbon::now();
                    $on_site_order->save();
                }

                DB::commit();

                $message = 'AMC Customer details saved successfully!';
            }

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

    public function sendCustomerOtp(Request $request)
    {
        // dd($request->all());
        try {

            $on_site_order = OnSiteOrder::with(['customer'])->find($request->id);

            if (!$on_site_order) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation Error',
                    'errors' => [
                        'On Site Visit Not Found!',
                    ],
                ]);
            }

            if (!$on_site_order->customer) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation Error',
                    'errors' => ['Customer Not Found!'],
                ]);
            }

            $customer_mobile = $on_site_order->customer->mobile_no;

            if (!$customer_mobile) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation Error',
                    'errors' => ['Customer Mobile Number Not Found!'],
                ]);
            }

            DB::beginTransaction();

            if ($on_site_order->notification_sent_status == 1) {

                $otp_no = mt_rand(111111, 999999);
                $on_site_order_otp_update = OnSiteOrder::where('id', $request->id)
                    ->update([
                        'otp_no' => $otp_no,
                        'status_id' => 6, //Waiting for Customer Approval
                        'is_customer_approved' => 0,
                        'updated_by_id' => Auth::user()->id,
                        'updated_at' => Carbon::now(),
                    ]);

                $site_visit_estimates = OnSiteOrderEstimate::where('on_site_order_id', $on_site_order->id)->count();

                //Type 1 -> Estimate
                //Type 2 -> Revised Estimate
                $type = 1;
                if ($site_visit_estimates > 1) {
                    $type = 2;
                }

                //Generate PDF
                $generate_on_site_estimate_pdf = OnSiteOrder::generateEstimatePDF($on_site_order->id, $type);

                if (!$on_site_order_otp_update) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Validation Error',
                        'errors' => ['On Site Order OTP Update Failed!'],
                    ]);
                }

                $current_time = date("Y-m-d H:m:s");

                $expired_time = Entity::where('entity_type_id', 32)->select('name')->first();
                if ($expired_time) {
                    $expired_time = date("Y-m-d H:i:s", strtotime('+' . $expired_time->name . ' hours', strtotime($current_time)));
                } else {
                    $expired_time = date("Y-m-d H:i:s", strtotime('+1 hours', strtotime($current_time)));
                }

                //Otp Save
                $otp = new Otp;
                $otp->entity_type_id = 10113;
                $otp->entity_id = $on_site_order->id;
                $otp->otp_no = $otp_no;
                $otp->created_by_id = Auth::user()->id;
                $otp->created_at = $current_time;
                $otp->expired_at = $expired_time;
                $otp->outlet_id = Auth::user()->employee->outlet_id;
                $otp->save();

                $message = 'OTP is ' . $otp_no . ' for Job Order Estimate. Please show this SMS to Our Service Advisor to verify your Job Order Estimate - TVS';

                $msg = sendSMSNotification($customer_mobile, $message);

                $message = 'OTP Sent successfully!!';
                $notify_type = 2;

            } else {
                $on_site_order->status_id = 13;
                $on_site_order->updated_by_id = Auth::user()->id;
                $on_site_order->updated_at = Carbon::now();
                $on_site_order->save();

                //UPDATE REPAIR ORDER STATUS
                OnSiteOrderRepairOrder::where('on_site_order_id', $on_site_order->id)->where('is_customer_approved', 0)->whereNull('removal_reason_id')->update(['is_customer_approved' => 1, 'status_id' => 8181, 'updated_by_id' => Auth::user()->id, 'updated_at' => Carbon::now()]);

                //UPDATE PARTS STATUS
                OnSiteOrderPart::where('on_site_order_id', $on_site_order->id)->where('is_customer_approved', 0)->whereNull('removal_reason_id')->update(['is_customer_approved' => 1, 'status_id' => 8201, 'updated_by_id' => Auth::user()->id, 'updated_at' => Carbon::now()]);

                $result = $this->getApprovedLabourPartsAmount($on_site_order->id);
                $on_site_order->is_customer_approved = 1;
                // dd($result);
                if ($result['status'] == 'true') {
                    $on_site_order->approved_amount = $result['total_billing_amount'];
                    $on_site_order->save();
                }

                $message = 'Ready for Start Work';
                $notify_type = 1;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'mobile_number' => $customer_mobile,
                'message' => $message,
                'notify_type' => $notify_type,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Server Network Down!',
                'errors' => ['Exception Error' => $e->getMessage()],
            ]);
        }
    }

    public function verifyOtp(Request $request)
    {
        // dd($request->all());
        try {

            $validator = Validator::make($request->all(), [
                'on_site_order_id' => [
                    'required',
                    'exists:on_site_orders,id',
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

            $on_site_order = OnSiteOrder::find($request->on_site_order_id);

            if (!$on_site_order) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation Error',
                    'errors' => ['On Site Order Not Found!'],
                ]);
            }

            DB::beginTransaction();

            $otp_validate = OnSiteOrder::where('id', $request->on_site_order_id)
                ->where('otp_no', '=', $request->otp_no)
                ->first();
            if (!$otp_validate) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation Error',
                    'errors' => ['On Site Order Approve Behalf of Customer OTP is wrong. Please try again.'],
                ]);
            }

            $current_time = date("Y-m-d H:m:s");

            //Validate OTP -> Expired or Not
            $otp_validate = OTP::where('entity_type_id', 10113)->where('entity_id', $request->on_site_order_id)->where('otp_no', '=', $request->otp_no)->where('expired_at', '>=', $current_time)
                ->first();
            if (!$otp_validate) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation Error',
                    'errors' => ['OTP Expired!'],
                ]);
            }

            //UPDATE STATUS
            if ($on_site_order->status_id == 6) {
                // $on_site_order->status_id = 8; //Estimation approved onbehalf of customer
                $on_site_order->status_id = 13; //Ready for Start Work
            }
            $on_site_order->is_customer_approved = 1;
            // if ($request->revised_estimate_amount) {
            //     $job_order_status_update->estimated_amount = $request->revised_estimate_amount;
            // }
            $on_site_order->customer_approved_date_time = Carbon::now();
            $on_site_order->updated_at = Carbon::now();
            $on_site_order->save();

            //UPDATE REPAIR ORDER STATUS
            OnSiteOrderRepairOrder::where('on_site_order_id', $request->on_site_order_id)->where('is_customer_approved', 0)->whereNull('removal_reason_id')->update(['is_customer_approved' => 1, 'status_id' => 8181, 'updated_by_id' => Auth::user()->id, 'updated_at' => Carbon::now()]);

            //UPDATE PARTS STATUS
            OnSiteOrderPart::where('on_site_order_id', $request->on_site_order_id)->where('is_customer_approved', 0)->whereNull('removal_reason_id')->update(['is_customer_approved' => 1, 'status_id' => 8201, 'updated_by_id' => Auth::user()->id, 'updated_at' => Carbon::now()]);

            OnSiteOrderEstimate::where('on_site_order_id', $request->on_site_order_id)->where('status_id', 10071)->update(['status_id' => 10072, 'updated_by_id' => Auth::user()->id, 'updated_at' => Carbon::now()]);

            $result = $this->getApprovedLabourPartsAmount($on_site_order->id);
            if ($result['status'] == 'true') {
                $on_site_order->approved_amount = $result['total_billing_amount'];
                $on_site_order->is_customer_approved = 1;
                $on_site_order->save();
            }

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

    public function save(Request $request)
    {
        // dd($request->all());
        try {
            if ($request->save_type_id == 1) {
                $error_messages = [
                    'customer_remarks.required' => "Customer Remarks is required",
                ];
                $validator = Validator::make($request->all(), [
                    'customer_remarks' => [
                        'required',
                    ],
                    'planned_visit_date' => [
                        'required',
                    ],
                    'code' => [
                        'required',
                    ],
                    'name' => [
                        'required',
                    ],
                    'mobile_no' => [
                        'required',
                        'max:10',
                    ],
                    'address_line1' => [
                        'required',
                        'max:100',
                    ],
                    'country_id' => [
                        'required',
                    ],
                    'state_id' => [
                        'required',
                    ],
                    'city_id' => [
                        'required',
                    ],
                    'pincode' => [
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

                //Check GSTIN Valid Or Not
                if ($request->gst_number) {
                    $gstin = Customer::getGstDetail($request->gst_number);

                    $gstin_encode = json_encode($gstin);
                    $gst_data = json_decode($gstin_encode, true);
                    $gst_response = $gst_data['original'];

                    if (isset($gst_response) && $gst_response['success'] == true) {
                        $customer_name = strtolower($request->name);
                        $trade_name = strtolower($gst_response['trade_name']);
                        $legal_name = strtolower($gst_response['legal_name']);

                        if ($trade_name || $legal_name) {
                            if ($customer_name === $legal_name) {
                                $e_invoice_registration = 1;
                            } elseif ($customer_name === $trade_name) {
                                $e_invoice_registration = 1;
                            } else {
                                $message = 'GSTIN Registered Legal Name: ' . strtoupper($legal_name) . ', and GSTIN Registered Trade Name: ' . strtoupper($trade_name) . '. Check GSTIN Number and Customer details';
                                return response()->json([
                                    'success' => false,
                                    'error' => 'Validation Error',
                                    'errors' => [
                                        $message,
                                    ],
                                ]);

                            }
                        } else {
                            return response()->json([
                                'success' => false,
                                'error' => 'Validation Error',
                                'errors' => [
                                    'Check GSTIN Number!',
                                ],
                            ]);

                        }
                    } else {
                        return response()->json([
                            'success' => false,
                            'error' => 'Validation Error',
                            'errors' => [
                                $gst_response['error'],
                            ],
                        ]);
                    }
                } else {
                    $e_invoice_registration = 0;
                }

                DB::beginTransaction();

                if ($request->on_site_order_id) {
                    //If status 1 means AMC Remaining count already updated
                    $status = 1;

                    $site_visit = OnSiteOrder::find($request->on_site_order_id);
                    if (!$site_visit) {
                        return response()->json([
                            'success' => false,
                            'error' => 'Validation Error',
                            'errors' => [
                                'Site Visit Detail Not Found!',
                            ],
                        ]);
                    }
                    $site_visit->updated_by_id = Auth::id();
                    $site_visit->updated_at = Carbon::now();
                    $site_visit->on_site_visit_user_id = Auth::user()->id;
                } else {
                    //If status 2 means AMC Remaining count need to update
                    $status = 2;

                    $site_visit = new OnSiteOrder;
                    $site_visit->company_id = Auth::user()->company_id;
                    $site_visit->outlet_id = Auth::user()->working_outlet_id;
                    $site_visit->on_site_visit_user_id = Auth::user()->id;

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
                    $branch = Outlet::where('id', Auth::user()->working_outlet_id)->first();

                    //GENERATE GATE IN VEHICLE NUMBER
                    $generateNumber = SerialNumberGroup::generateNumber(152, $financial_year->id, $branch->state_id, $branch->id);
                    if (!$generateNumber['success']) {
                        return response()->json([
                            'success' => false,
                            'error' => 'Validation Error',
                            'errors' => [
                                'No Site Visit number found for FY : ' . $financial_year->year . ', State : ' . $branch->state->code . ', Outlet : ' . $branch->code,
                            ],
                        ]);
                    }

                    // dd($generateNumber);
                    $site_visit->number = $generateNumber['number'];
                    $site_visit->created_by_id = Auth::id();
                    $site_visit->created_at = Carbon::now();
                    $site_visit->updated_at = null;
                    $site_visit->status_id = 1;
                }

                //save customer
                $customer = Customer::saveCustomer($request->all());
                if ($request->address_id) {
                    $address = Address::find($request->address_id);
                    if (!$address) {
                        $address = Address::firstOrNew([
                            'company_id' => Auth::user()->company_id,
                            'address_of_id' => 24, //CUSTOMER
                            'entity_id' => $customer->id,
                            'address_type_id' => 40, //PRIMARY ADDRESS
                        ]);

                    }
                } else {
                    $address = Address::firstOrNew([
                        'company_id' => Auth::user()->company_id,
                        'address_of_id' => 24, //CUSTOMER
                        'entity_id' => $customer->id,
                        'address_type_id' => 40, //PRIMARY ADDRESS
                    ]);
                }

                $address->fill($request->all());
                $address->save();
                // $customer->saveAddress($request->all());

                $site_visit->sbu_id = Auth::user()->employee ? Auth::user()->employee->sbu_id : null;
                $site_visit->e_invoice_registration = $e_invoice_registration;
                $site_visit->customer_id = $customer->id;
                $site_visit->address_id = $address->id;
                $site_visit->planned_visit_date = date('Y-m-d', strtotime($request->planned_visit_date));
                $site_visit->customer_remarks = $request->customer_remarks;
                $site_visit->notification_sent_status = $request->notification_sent_status;

                $site_visit->save();

                if ($status == 2) {
                    $amc_customer = AmcCustomer::where('customer_id', $customer->id)->where('amc_customer_type_id', 2)->orderBy('id', 'desc')->first();

                    if ($amc_customer) {
                        $amc_customer_status = 0;
                        if (date('Y-m-d', strtotime($amc_customer->expiry_date)) >= date('Y-m-d')) {
                            $amc_customer_status = 1;
                        }

                        if (date('Y-m-d', strtotime($amc_customer->start_date)) <= date('Y-m-d')) {
                            if ($amc_customer_status == 1) {
                                $amc_customer_status = 1;
                            } else {
                                $amc_customer_status = 0;
                            }
                        } else {
                            $amc_customer_status = 0;
                        }

                        $amc_available = 0;
                        if (is_null($amc_customer->remaining_services)) {
                            $remaining_services = $amc_customer->total_services - 1;
                            $amc_available = 1;
                        } elseif ($amc_customer->remaining_services > 0) {
                            $remaining_services = $amc_customer->remaining_services - 1;
                            $amc_available = 1;
                        }

                        if ($amc_available && $amc_customer_status) {
                            $amc_customer->remaining_services = $remaining_services;
                            $amc_customer->save();

                            //Update On Site Visit AMC Customer OD
                            $site_visit->amc_customer_id = $amc_customer->id;
                            $site_visit->updated_by_id = Auth::user()->id;
                            $site_visit->updated_at = Carbon::now();
                            $site_visit->save();
                        }
                    }
                }

                $message = "On Site Visit Saved Successfully!";

                DB::commit();

            } elseif ($request->save_type_id == 2) {
                $validator = Validator::make($request->all(), [
                    'se_remarks' => [
                        'required',
                    ],
                    'parts_requirements' => [
                        'required',
                    ],
                    'on_site_order_id' => [
                        'required',
                        'exists:on_site_orders,id',
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

                $site_visit = OnSiteOrder::find($request->on_site_order_id);
                if (!$site_visit) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Validation Error',
                        'errors' => [
                            'Site Visit Detail Not Found!',
                        ],
                    ]);
                }

                // if (!$site_visit->actual_visit_date) {
                //     $site_visit->actual_visit_date = date('Y-m-d');
                // }

                $site_visit->se_remarks = $request->se_remarks;
                $site_visit->parts_requirements = $request->parts_requirements;
                $site_visit->status_id = 2;
                $site_visit->updated_by_id = Auth::id();
                $site_visit->updated_at = Carbon::now();
                $site_visit->save();

                //REMOVE ATTACHMENTS
                if (isset($request->attachment_removal_ids)) {
                    $attachment_removal_ids = json_decode($request->attachment_removal_ids);
                    if (!empty($attachment_removal_ids)) {
                        Attachment::whereIn('id', $attachment_removal_ids)->forceDelete();
                    }
                }

                //Save Attachments
                $attachement_path = storage_path('app/public/gigo/on-site/');
                Storage::makeDirectory($attachement_path, 0777);
                // dd($request->all());
                if (isset($request->photos)) {
                    foreach ($request->photos as $key => $photo) {

                        $value = rand(1, 100);
                        $image = $photo;
                        $file_name_with_extension = $image->getClientOriginalName();
                        $file_name = pathinfo($file_name_with_extension, PATHINFO_FILENAME);
                        $extension = $image->getClientOriginalExtension();

                        $name = $site_visit->id . '_' . $file_name . '_' . rand(10, 1000) . '.' . $extension;

                        $photo->move($attachement_path, $name);
                        $attachement = new Attachment;
                        $attachement->attachment_of_id = 9124;
                        $attachement->attachment_type_id = 244;
                        $attachement->entity_id = $site_visit->id;
                        $attachement->name = $name;
                        $attachement->path = isset($request->attachment_descriptions[$key]) ? $request->attachment_descriptions[$key] : null;
                        $attachement->save();
                    }
                }

                $message = "On Site Visit Updated Successfully!";

                DB::commit();

            } else {
                $validator = Validator::make($request->all(), [
                    'job_card_number' => [
                        'required',
                        'unique:on_site_orders,job_card_number,' . $request->on_site_order_id . ',id,company_id,' . Auth::user()->company_id,
                    ],
                    'on_site_order_id' => [
                        'required',
                        'exists:on_site_orders,id',
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

                $site_visit = OnSiteOrder::find($request->on_site_order_id);
                if (!$site_visit) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Validation Error',
                        'errors' => [
                            'Site Visit Detail Not Found!',
                        ],
                    ]);
                }

                $site_visit->job_card_number = $request->job_card_number;
                $site_visit->status_id = 13;
                $site_visit->updated_by_id = Auth::id();
                $site_visit->updated_at = Carbon::now();
                $site_visit->save();

                $message = "Job Card Number Saved Successfully!";

                DB::commit();
            }

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

    //BULK ISSUE PART FORM DATA
    public function getBulkIssuePartFormData(Request $request)
    {
        // dd($request->all());
        try {
            $site_visit = OnSiteOrder::with([
                'company',
                'outlet',
                'onSiteVisitUser',
                'customer',
                'customer.address',
                'customer.address.country',
                'customer.address.state',
                'customer.address.city',
                'outlet',
                'status',
                'onSiteOrderRepairOrders',
                'onSiteOrderParts',
            ])->find($request->id);

            if (!$site_visit) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation Error',
                    'errors' => [
                        'On Site Visit Not Found!',
                    ],
                ]);
            }

            $on_site_order_parts = Part::join('on_site_order_parts', 'on_site_order_parts.part_id', 'parts.id')->where('on_site_order_parts.on_site_order_id', $request->id)->whereNull('on_site_order_parts.removal_reason_id')
            // ->where('on_site_order_parts.is_customer_approved', 1)
                ->select('on_site_order_parts.id as on_site_order_part_id', 'on_site_order_parts.qty', 'parts.code', 'parts.name', 'parts.id')->get();

            $parts_data = array();

            // dd($on_site_order_parts);
            if ($on_site_order_parts) {
                foreach ($on_site_order_parts as $key => $parts) {
                    // dump($parts->code, $parts->id);

                    //Issued Qty
                    $issued_qty = OnSiteOrderIssuedPart::where('on_site_order_part_id', $parts->on_site_order_part_id)->sum('issued_qty');

                    //Returned Qty
                    $returned_qty = OnSiteOrderReturnedPart::where('on_site_order_part_id', $parts->on_site_order_part_id)->sum('returned_qty');

                    //Available Qty
                    $avail_qty = PartStock::where('part_id', $parts->id)->where('outlet_id', $site_visit->outlet_id)->pluck('stock')->first();

                    $total_remain_qty = ($parts->qty + $returned_qty) - $issued_qty;
                    $total_issued_qty = $issued_qty - $returned_qty;

                    // dump($avail_qty, $total_remain_qty);
                    // if ($avail_qty && $avail_qty > 0 && $total_remain_qty > 0) {
                    if ($total_remain_qty > 0) {
                        $parts_data[$key]['part_id'] = $parts->id;
                        $parts_data[$key]['code'] = $parts->code;
                        $parts_data[$key]['name'] = $parts->name;
                        $parts_data[$key]['on_site_order_part_id'] = $parts->on_site_order_part_id;
                        $parts_data[$key]['total_avail_qty'] = $avail_qty;
                        $parts_data[$key]['total_request_qty'] = $parts->qty;
                        $parts_data[$key]['total_issued_qty'] = $total_issued_qty;
                        $parts_data[$key]['total_remaining_qty'] = $total_remain_qty;
                    }
                }
            }

            $responseArr = array(
                'success' => true,
                'site_visit' => $site_visit,
                'on_site_order_parts' => $parts_data,
                'mechanic_id' => $site_visit->on_site_visit_user_id,
                // 'issue_modes' => $issue_modes,
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

    //SAVE STOCK INCHAGRE > ISSUED PART
    public function saveIssuedPart(Request $request)
    {
        // dd($request->all());
        try {
            if ($request->part_type == 3) {
                $validator = Validator::make($request->all(), [
                    'on_site_order_id' => [
                        'required',
                        'exists:on_site_orders,id',
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

                if ($request->issued_part) {
                    foreach ($request->issued_part as $key => $issued_part) {
                        if (isset($issued_part['qty'])) {
                            $on_site_order_isssued_part = new OnSiteOrderIssuedPart;
                            $on_site_order_isssued_part->on_site_order_part_id = $issued_part['on_site_order_part_id'];

                            $on_site_order_isssued_part->issued_qty = $issued_part['qty'];
                            $on_site_order_isssued_part->issued_mode_id = 8480;
                            $on_site_order_isssued_part->issued_to_id = $request->issued_to_id;
                            $on_site_order_isssued_part->created_by_id = Auth::user()->id;
                            $on_site_order_isssued_part->created_at = Carbon::now();
                            $on_site_order_isssued_part->save();

                            $on_site_order_part = OnSiteOrderPart::find($issued_part['on_site_order_part_id']);
                            $on_site_order_part->status_id = 8202; //Issued
                            $on_site_order_part->save();
                        }
                    }
                } else {
                    return response()->json([
                        'success' => false,
                        'error' => 'Validation Error',
                        'errors' => ['Parts not found!'],
                    ]);
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Part Issued Successfully!!',
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

    //START LABOUR WORK & ISSUE PART
    public function processLabourPart(Request $request)
    {
        // dd($request->all());
        try {

            if ($request->type == 'labour') {
                $validator = Validator::make($request->all(), [
                    'id' => [
                        'required',
                        'exists:on_site_order_repair_orders,id',
                    ],
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Validation Error',
                        'errors' => $validator->errors()->all(),
                    ]);
                }

                $on_site_order_repair_order = OnSiteOrderRepairOrder::find($request->id);
                $on_site_order_repair_order->status_id = 8183; //Start Work
                $on_site_order_repair_order->updated_by_id = Auth::user()->id;
                $on_site_order_repair_order->updated_at = Carbon::now();
                $on_site_order_repair_order->save();

                $site_visit = OnSiteOrder::where('id', $on_site_order_repair_order->on_site_order_id)->first();
                $site_visit->status_id = 14;
                $site_visit->updated_by_id = Auth::user()->id;
                $site_visit->updated_at = Carbon::now();
                $site_visit->save();

                //Check Previous entry closed or not
                $travel_log = OnSiteOrderTimeLog::where('on_site_order_id', $site_visit->id)->where('work_log_type_id', 2)->whereNull('end_date_time')->first();
                if (!$travel_log) {
                    $travel_log = new OnSiteOrderTimeLog;
                    $travel_log->on_site_order_id = $site_visit->id;
                    $travel_log->work_log_type_id = 2;
                    $travel_log->start_date_time = Carbon::now();
                    $travel_log->created_by_id = Auth::user()->id;
                    $travel_log->created_at = Carbon::now();
                    $travel_log->save();
                }
                $message = 'Work Started Successfully!';
            } else {
                $validator = Validator::make($request->all(), [
                    'id' => [
                        'required',
                        'exists:on_site_order_parts,id',
                    ],

                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Validation Error',
                        'errors' => $validator->errors()->all(),
                    ]);
                }

                $on_site_order_part = OnSiteOrderPart::find($request->id);
                $on_site_order_part->status_id = 8202; //Issued
                $on_site_order_part->updated_by_id = Auth::user()->id;
                $on_site_order_part->updated_at = Carbon::now();
                $on_site_order_part->save();

                $message = 'Part issued Successfully!';
            }

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

    //DELETE ISSUE/RETURN PART DATA
    public function deleteIssueReturnParts(Request $request)
    {
        // dd($request->all());
        try {

            if ($request->type == 'Part Returned') {
                $validator = Validator::make($request->all(), [
                    'id' => [
                        'required',
                        'exists:on_site_order_returned_parts,id',
                    ],

                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Validation Error',
                        'errors' => $validator->errors()->all(),
                    ]);
                }

                $returned_part = OnSiteOrderReturnedPart::where('id', $request->id)->forceDelete();

            } else {
                $validator = Validator::make($request->all(), [
                    'id' => [
                        'required',
                        'exists:on_site_order_issued_parts,id',
                    ],

                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Validation Error',
                        'errors' => $validator->errors()->all(),
                    ]);
                }

                $issued_part = OnSiteOrderIssuedPart::where('id', $request->id)->forceDelete();

            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Part Deleted Successfully Successfully!!',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Server Network Down!',
                'errors' => ['Exception Error' => $e->getMessage()],
            ]);
        }
    }

    //Return Part save form
    public function returnParts(Request $request)
    {
        // dd($request->all());
        try {
            if ($request->type_id == 2) {
                $validator = Validator::make($request->all(), [
                    'on_site_order_id' => [
                        'required',
                        'exists:on_site_orders,id',
                    ],
                    'on_site_order_part_id' => [
                        'required',
                        'exists:on_site_order_parts,id',
                    ],

                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Validation Error',
                        'errors' => $validator->errors()->all(),
                    ]);
                }

                $site_visit = OnSiteOrder::find($request->on_site_order_id);

                if (!$site_visit) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Validation Error',
                        'errors' => [
                            'On Site Visit Not Found!',
                        ],
                    ]);
                }

                if ($request->returned_qty) {
                    DB::beginTransaction();

                    //Total Qty
                    $parts = OnSiteOrderPart::where('id', $request->on_site_order_part_id)->first();
                    $total_qty = $parts->qty;

                    //Issued Qty
                    $issued_qty = OnSiteOrderIssuedPart::where('on_site_order_part_id', $parts->id)->sum('issued_qty');

                    //Returned Qty
                    $returned_qty = OnSiteOrderReturnedPart::where('on_site_order_part_id', $parts->id)->sum('returned_qty');

                    $total_remain_qty = ($issued_qty + $returned_qty);

                    if ($total_remain_qty >= $request->returned_qty) {
                        $returned_part = new OnSiteOrderReturnedPart;
                        $returned_part->on_site_order_part_id = $parts->id;
                        $returned_part->returned_qty = $request->returned_qty;
                        $returned_part->returned_to_id = $site_visit->on_site_visit_user_id;
                        $returned_part->remarks = $request->remarks;
                        $returned_part->created_by_id = Auth::user()->id;
                        $returned_part->created_at = Carbon::now();
                        $returned_part->save();
                    } else {
                        return response()->json([
                            'success' => false,
                            'error' => 'Validation Error',
                            'errors' => [
                                'Invalid Returned Qty!',
                            ],
                        ]);
                    }

                    DB::commit();
                } else {
                    return response()->json([
                        'success' => false,
                        'error' => 'Validation Error',
                        'errors' => [
                            'Invalid Returned Qty!',
                        ],
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Part Returned Successfully!!',
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

    //Get issue/return form data
    public function getPartsData(Request $request)
    {
        // dd($request->all());
        try {
            $site_visit = OnSiteOrder::find($request->id);

            if (!$site_visit) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation Error',
                    'errors' => [
                        'On Site Visit Not Found!',
                    ],
                ]);
            }

            $on_site_order_parts = OnSiteOrderPart::join('parts', 'on_site_order_parts.part_id', 'parts.id')->where('on_site_order_parts.on_site_order_id', $request->id)->whereNull('on_site_order_parts.removal_reason_id')->select('parts.name', 'parts.code', 'on_site_order_parts.id as on_site_order_part_id')->get();

            $part_logs = array();
            $issued_parts = 0;

            if ($on_site_order_parts) {

                $parts_issue_logs = OnSiteOrderIssuedPart::join('on_site_order_parts', 'on_site_order_parts.id', 'on_site_order_issued_parts.on_site_order_part_id')
                    ->join('parts', 'on_site_order_parts.part_id', 'parts.id')
                    ->join('configs', 'on_site_order_issued_parts.issued_mode_id', 'configs.id')
                    ->join('users', 'on_site_order_issued_parts.issued_to_id', 'users.id')
                    ->where('on_site_order_parts.on_site_order_id', $request->id)
                    ->select(DB::raw('"Part Issued" as transaction_type'),
                        'parts.name',
                        'parts.code',
                        'on_site_order_issued_parts.issued_qty as qty',
                        DB::raw('"-" as remarks'),
                        DB::raw('DATE_FORMAT(on_site_order_issued_parts.created_at,"%d/%m/%Y") as date'),
                        'configs.name as issue_mode',
                        'users.name as mechanic',
                        'users.id as employee_id',
                        'on_site_order_issued_parts.id as job_order_part_issue_return_id',
                        'parts.id as part_id')
                // ->get()
                ;

                $issued_parts = $parts_issue_logs->get()->count();

                $parts_return_logs = OnSiteOrderReturnedPart::join('on_site_order_parts', 'on_site_order_parts.id', 'on_site_order_returned_parts.on_site_order_part_id')
                    ->join('parts', 'on_site_order_parts.part_id', 'parts.id')
                    ->join('users', 'on_site_order_returned_parts.returned_to_id', 'users.id')
                    ->where('on_site_order_parts.on_site_order_id', $request->id)
                    ->select(
                        DB::raw('"Part Returned" as transaction_type'),
                        'parts.name',
                        'parts.code',
                        'on_site_order_returned_parts.returned_qty as qty',
                        'on_site_order_returned_parts.remarks',
                        DB::raw('DATE_FORMAT(on_site_order_returned_parts.created_at,"%d/%m/%Y") as date'),
                        DB::raw('"-" as issue_mode'),
                        'users.name as mechanic',
                        'users.id as employee_id',
                        'on_site_order_returned_parts.id as job_order_part_issue_return_id',
                        'parts.id as part_id'

                    )->union($parts_issue_logs)->orderBy('date', 'DESC')->get();

                $part_logs = $parts_return_logs;
            }

            return response()->json([
                'success' => true,
                'part_logs' => $part_logs,
                // 'issued_parts' => $issued_parts,
                'on_site_order_parts' => $on_site_order_parts,
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

    public function updateStatus(Request $request)
    {
        // dd($request->all());
        try {
            $site_visit = OnSiteOrder::with([
                'customer',
                'address',
                'customer.amcCustomer',
            ])->find($request->id);

            if (!$site_visit) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation Error',
                    'errors' => [
                        'Site Visit Not Found!',
                    ],
                ]);
            }

            DB::beginTransaction();

            //Send Request to parts incharge for Add parts
            if ($request->type_id == 1) {
                $site_visit->status_id = 4;
                $message = 'On Site Visit Updated Successfully!';
            }
            //parts Estimation Completed
            elseif ($request->type_id == 2) {
                $site_visit->status_id = 5;
                $message = 'On Site Visit Updated Successfully!';
            }
            //Send message to customer for approve the estimate
            elseif ($request->type_id == 3) {

                $site_visit_estimates = OnSiteOrderEstimate::where('on_site_order_id', $site_visit->id)->count();

                //Type 1 -> Estimate
                //Type 2 -> Revised Estimate
                $type = 1;
                if ($site_visit_estimates > 1) {
                    $type = 2;
                }

                //Generate PDF
                $generate_on_site_estimate_pdf = OnSiteOrder::generateEstimatePDF($site_visit->id, $type);

                //Update OnSiteOrder Estimate
                $on_site_order_estimate = OnSiteOrderEstimate::where('on_site_order_id', $site_visit->id)->orderBy('id', 'DESC')->first();

                if ($site_visit->notification_sent_status == 1) {

                    $site_visit->status_id = 6;
                    $otp_no = mt_rand(111111, 999999);
                    $site_visit->otp_no = $otp_no;

                    $url = url('/') . '/on-site-visit/estimate/customer/view/' . $site_visit->id . '/' . $otp_no;
                    $short_url = ShortUrl::createShortLink($url, $maxlength = "7");

                    $message = 'Dear Customer, Kindly click on this link to approve for TVS job order ' . $short_url . ' Job Order Number : ' . $site_visit->number . ' - TVS';

                    if (!$site_visit->customer) {
                        return response()->json([
                            'success' => false,
                            'error' => 'Validation Error',
                            'errors' => ['Customer Not Found!'],
                        ]);
                    }

                    $customer_mobile = $site_visit->customer->mobile_no;

                    if (!$customer_mobile) {
                        return response()->json([
                            'success' => false,
                            'error' => 'Validation Error',
                            'errors' => ['Customer Mobile Number Not Found!'],
                        ]);
                    }

                    $msg = sendSMSNotification($customer_mobile, $message);

                    $on_site_order_estimate->status_id = 10071;
                    $message = 'Estimation sent to customer successfully!';
                } else {
                    $on_site_order_estimate->status_id = 10072;

                    $site_visit->status_id = 13;

                    //UPDATE REPAIR ORDER STATUS
                    OnSiteOrderRepairOrder::where('on_site_order_id', $site_visit->id)->where('is_customer_approved', 0)->whereNull('removal_reason_id')->update(['is_customer_approved' => 1, 'status_id' => 8181, 'updated_by_id' => Auth::user()->id, 'updated_at' => Carbon::now()]);

                    //UPDATE PARTS STATUS
                    OnSiteOrderPart::where('on_site_order_id', $site_visit->id)->where('is_customer_approved', 0)->whereNull('removal_reason_id')->update(['is_customer_approved' => 1, 'status_id' => 8201, 'updated_by_id' => Auth::user()->id, 'updated_at' => Carbon::now()]);

                    $site_visit->is_customer_approved = 1;
                    $result = $this->getApprovedLabourPartsAmount($site_visit->id);
                    if ($result['status'] == 'true') {
                        $site_visit->approved_amount = $result['total_billing_amount'];
                    }

                    $message = 'Ready for Start Work';

                }

                $on_site_order_estimate->updated_by_id = Auth::user()->id;
                $on_site_order_estimate->updated_at = Carbon::now();
                $on_site_order_estimate->save();

            }
            //Work completed // Waiting for parts confirmation
            elseif ($request->type_id == 4) {
                $site_visit->status_id = 16;

                OnSiteOrderRepairOrder::where('on_site_order_id', $site_visit->id)->where('status_id', 8183)->whereNull('removal_reason_id')->update(['status_id' => 8187, 'updated_at' => Carbon::now()]);

                $travel_log = OnSiteOrderTimeLog::where('on_site_order_id', $site_visit->id)->where('work_log_type_id', 2)->whereNull('end_date_time')->first();
                if ($travel_log) {
                    $travel_log->end_date_time = Carbon::now();
                    $travel_log->updated_by_id = Auth::user()->id;
                    $travel_log->updated_at = Carbon::now();
                    $travel_log->save();
                }
                $message = 'On Site Visit Work Completed Successfully!';
            }
            //returned parts confirmed
            elseif ($request->type_id == 6) {
                $site_visit->status_id = 9;
                $message = 'Parts Confirmed Successfully!';
            }
            //Send sms to customer for payment
            elseif ($request->type_id == 5) {

                if (empty($site_visit->address->pincode)) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Validation Error',
                        'errors' => ['Customer Pincode Required. Customer Pincode Not Found!'],
                    ]);
                }

                if (empty($site_visit->address->state_id)) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Validation Error',
                        'errors' => ['Customer State Required. Customer State Not Found!'],
                    ]);
                }

                if (strlen(preg_replace('/\r|\n|:|"/', ",", $site_visit->address->address_line1)) > 100) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Validation Error',
                        'errors' => ['Customer Address Maximum Allowed Length 100!'],
                    ]);
                }

                if (!$site_visit->customer) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Validation Error',
                        'errors' => ['Customer Not Found!'],
                    ]);
                }

                $customer_mobile = $site_visit->customer->mobile_no;

                if (!$customer_mobile) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Validation Error',
                        'errors' => ['Customer Mobile Number Not Found!'],
                    ]);
                }

                // $site_visit->status_id = 10;
                $site_visit->qr_image = $site_visit->number . '.jpg';

                $otp_no = mt_rand(111111, 999999);
                $site_visit->otp_no = $otp_no;

                $url = url('/') . '/on-site-visit/view/bill-details/' . $site_visit->id . '/' . $otp_no;
                $short_url = ShortUrl::createShortLink($url, $maxlength = "7");

                $message = 'Dear Customer, Kindly click on this link to pay for the TVS job order ' . $short_url . ' Job Card Number : ' . $site_visit->number . ' - TVS';

                $msg = sendSMSNotification($customer_mobile, $message);

                $message = 'On Site Visit Completed Successfully!';
            } else {
                // $site_visit->status_id = 8;
                $message = 'On Site Visit Updated Successfully!';
            }

            $site_visit->updated_by_id = Auth::user()->id;
            $site_visit->updated_at = Carbon::now();
            $site_visit->save();

            DB::commit();

            //PDF Generate
            if ($request->type_id == 5) {
                //Generate Bill Details PDF
                $generate_on_site_estimate_pdf = OnSiteOrder::generateBillingPDF($site_visit->id);

                //Generate Labour PDF
                $generate_on_site_estimate_pdf = OnSiteOrder::generateLabourPDF($site_visit->id);

                //Generate Part PDF
                $generate_on_site_estimate_pdf = OnSiteOrder::generatePartPDF($site_visit->id);
            }

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

    public function saveRequest(Request $request)
    {
        // dd($request->all());
        try {

            $customer_paid_type_id = SplitOrderType::where('paid_by_id', '10013')->pluck('id')->toArray();

            $site_visit = OnSiteOrder::with([
                'customer',
                'onSiteOrderRepairOrders' => function ($q) use ($customer_paid_type_id) {
                    $q->where('status_id', 8187)->whereNull('removal_reason_id')->whereIn('split_order_type_id', $customer_paid_type_id);
                },
                'onSiteOrderParts' => function ($q) use ($customer_paid_type_id) {
                    $q->where('status_id', 8202)->whereNull('removal_reason_id')->whereIn('split_order_type_id', $customer_paid_type_id);
                },
            ])->find($request->id);

            if (!$site_visit) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation Error',
                    'errors' => [
                        'Site Visit Not Found!',
                    ],
                ]);
            }

            $outlet = Outlet::find($site_visit->outlet_id);
            if (!$outlet) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation Error',
                    'errors' => [
                        'Outlet Not Found!',
                    ],
                ]);
            }

            $address = Address::find($site_visit->address_id);
            if (!$address) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation Error',
                    'errors' => [
                        'Address Not Found!',
                    ],
                ]);
            }

            DB::beginTransaction();

            if (empty($address->pincode)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation Error',
                    'errors' => ['Customer Pincode Required. Customer Pincode Not Found!'],
                ]);
            }

            if (empty($address->state_id)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation Error',
                    'errors' => ['Customer State Required. Customer State Not Found!'],
                ]);
            }

            if (strlen(preg_replace('/\r|\n|:|"/', ",", $address->address_line1)) > 100) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation Error',
                    'errors' => ['Customer Address Maximum Allowed Length 100!'],
                ]);
            }

            if (!$site_visit->customer) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation Error',
                    'errors' => ['Customer Not Found!'],
                ]);
            }

            $customer_mobile = $site_visit->customer->mobile_no;

            if (!$customer_mobile) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation Error',
                    'errors' => ['Customer Mobile Number Not Found!'],
                ]);
            }

            $total_inv_amount = 0;

            if ($site_visit->address) {
                //Check which tax applicable for customer
                if ($site_visit->outlet->state_id == $address->state_id) {
                    $tax_type = 1160; //Within State
                } else {
                    $tax_type = 1161; //Inter State
                }
            } else {
                $tax_type = 1160; //Within State
            }

            //Count Tax Type
            $taxes = Tax::whereIn('id', [1, 2, 3])->get();

            $cgst_total = 0;
            $sgst_total = 0;
            $igst_total = 0;
            $cgst_amt = 0;
            $sgst_amt = 0;
            $igst_amt = 0;
            $tcs_total = 0;
            $cess_on_gst_total = 0;

            $items = [];
            if ($site_visit->onSiteOrderRepairOrders) {
                $i = 1;
                foreach ($site_visit->onSiteOrderRepairOrders as $key => $labour) {
                    $total_amount = 0;
                    $tax_amount = 0;
                    $cgst_percentage = 0;
                    $sgst_percentage = 0;
                    $igst_percentage = 0;

                    // dd($labour->repairOrder->taxCode->code);
                    if ($labour->repairOrder->taxCode) {
                        foreach ($labour->repairOrder->taxCode->taxes as $tax_key => $value) {
                            $percentage_value = 0;
                            if ($value->type_id == $tax_type) {
                                $percentage_value = ($labour->amount * $value->pivot->percentage) / 100;
                                $percentage_value = number_format((float) $percentage_value, 2, '.', '');

                                //FOR CGST
                                if ($value->name == 'CGST') {
                                    $cgst_amt = $percentage_value;
                                    $cgst_total += $cgst_amt;
                                    $cgst_percentage = $value->pivot->percentage;
                                }
                                //FOR SGST
                                if ($value->name == 'SGST') {
                                    $sgst_amt = $percentage_value;
                                    $sgst_total += $sgst_amt;
                                    $sgst_percentage = $value->pivot->percentage;
                                }
                                //FOR CGST
                                if ($value->name == 'IGST') {
                                    $igst_amt = $percentage_value;
                                    $igst_total += $igst_amt;
                                    $igst_percentage = $value->pivot->percentage;
                                }

                            }

                            $tax_amount += $percentage_value;
                        }
                    } else {

                    }

                    $total_amount = $tax_amount + $labour->amount;

                    $total_inv_amount += $total_amount;

                    $item['SlNo'] = $i; //Statically assumed
                    $item['PrdDesc'] = $labour->repairOrder->name;
                    $item['IsServc'] = "Y"; //ALWAYS Y
                    $item['HsnCd'] = $labour->repairOrder->taxCode ? $labour->repairOrder->taxCode->code : null;

                    //BchDtls
                    $item['BchDtls']["Nm"] = null;
                    $item['BchDtls']["Expdt"] = null;
                    $item['BchDtls']["wrDt"] = null;

                    $item['Barcde'] = null;
                    $item['Qty'] = 1;
                    $item['FreeQty'] = 0;
                    $item['Unit'] = "NOS";
                    $item['UnitPrice'] = number_format($labour->amount, 2);
                    $item['TotAmt'] = number_format($labour->amount, 2);
                    $item['Discount'] = 0; //Always value will be "0"
                    $item['PreTaxVal'] = number_format($labour->amount, 2);
                    $item['AssAmt'] = number_format($labour->amount, 2);
                    $item['IgstRt'] = number_format($igst_percentage, 2);
                    $item['IgstAmt'] = number_format($igst_amt, 2);
                    $item['CgstRt'] = number_format($cgst_percentage, 2);
                    $item['CgstAmt'] = number_format($cgst_amt, 2);
                    $item['SgstRt'] = number_format($sgst_percentage, 2);
                    $item['SgstAmt'] = number_format($sgst_amt, 2);
                    $item['CesRt'] = 0;
                    $item['CesAmt'] = 0;
                    $item['CesNonAdvlAmt'] = 0;
                    $item['StateCesRt'] = 0; //NEED TO CLARIFY IF KFC
                    $item['StateCesAmt'] = 0; //NEED TO CLARIFY IF KFC
                    $item['StateCesNonAdvlAmt'] = 0; //NEED TO CLARIFY IF KFC
                    $item['OthChrg'] = 0;
                    $item['TotItemVal'] = number_format(($total_amount), 2);

                    $item['OrdLineRef'] = "0";
                    $item['OrgCntry'] = "IN"; //Always value will be "IND"
                    $item['PrdSlNo'] = null;

                    //AttribDtls
                    $item['AttribDtls'][] = [
                        "Nm" => null,
                        "Val" => null,
                    ];

                    //EGST
                    //NO DATA GIVEN IN WORD DOC START
                    $item['EGST']['nilrated_amt'] = null;
                    $item['EGST']['exempted_amt'] = null;
                    $item['EGST']['non_gst_amt'] = null;
                    $item['EGST']['reason'] = null;
                    $item['EGST']['debit_gl_id'] = null;
                    $item['EGST']['debit_gl_name'] = null;
                    $item['EGST']['credit_gl_id'] = null;
                    $item['EGST']['credit_gl_name'] = null;
                    $item['EGST']['sublocation'] = null;
                    //NO DATA GIVEN IN WORD DOC END

                    $i++;
                    $items[] = $item;

                }
            }

            $part_amount = 0;
            if ($site_visit->onSiteOrderParts) {
                foreach ($site_visit->onSiteOrderParts as $key => $parts) {

                    $qty = $parts->qty;
                    //Issued Qty
                    $issued_qty = OnSiteOrderIssuedPart::where('on_site_order_part_id', $parts->id)->sum('issued_qty');
                    //Returned Qty
                    $returned_qty = OnSiteOrderReturnedPart::where('on_site_order_part_id', $parts->id)->sum('returned_qty');

                    $qty = $issued_qty - $returned_qty;
                    $qty = number_format($qty, 2);

                    if ($qty > 0) {

                        $total_amount = 0;
                        $tax_amount = 0;
                        $cgst_percentage = 0;
                        $sgst_percentage = 0;
                        $igst_percentage = 0;

                        $price = $parts->rate;
                        $tax_percent = 0;

                        if ($parts->part->taxCode) {
                            foreach ($parts->part->taxCode->taxes as $tax_key => $value) {
                                if ($value->type_id == $tax_type) {
                                    $tax_percent += $value->pivot->percentage;
                                }
                            }

                            $tax_percent = (100 + $tax_percent) / 100;

                            $price = $parts->rate / $tax_percent;
                            $price = number_format((float) $price, 2, '.', '');
                            $part_details[$key]['price'] = $price;
                        }

                        $total_price = $price * $qty;

                        $tax_amount = 0;
                        $tax_values = array();

                        if ($parts->part->taxCode) {
                            if (count($parts->part->taxCode->taxes) > 0) {
                                foreach ($parts->part->taxCode->taxes as $tax_key => $value) {
                                    $percentage_value = 0;
                                    if ($value->type_id == $tax_type) {
                                        $percentage_value = ($total_price * $value->pivot->percentage) / 100;
                                        $percentage_value = number_format((float) $percentage_value, 2, '.', '');

                                        //FOR CGST
                                        if ($value->name == 'CGST') {
                                            $cgst_amt = $percentage_value;
                                            $cgst_total += $cgst_amt;
                                            $cgst_percentage = $value->pivot->percentage;
                                        }
                                        //FOR SGST
                                        if ($value->name == 'SGST') {
                                            $sgst_amt = $percentage_value;
                                            $sgst_total += $sgst_amt;
                                            $sgst_percentage = $value->pivot->percentage;
                                        }
                                        //FOR CGST
                                        if ($value->name == 'IGST') {
                                            $igst_amt = $percentage_value;
                                            $igst_total += $igst_amt;
                                            $igst_percentage = $value->pivot->percentage;
                                        }
                                    }
                                }
                            } else {

                            }

                        } else {

                        }

                        $total_inv_amount += ($parts->rate * $qty);
                        $part_amount += ($parts->rate * $qty);

                        $item['SlNo'] = $i; //Statically assumed
                        $item['PrdDesc'] = $parts->part->name;
                        $item['IsServc'] = "Y"; //ALWAYS Y
                        $item['HsnCd'] = $parts->part->taxCode ? $parts->part->taxCode->code : null;

                        //BchDtls
                        $item['BchDtls']["Nm"] = null;
                        $item['BchDtls']["Expdt"] = null;
                        $item['BchDtls']["wrDt"] = null;

                        $item['Barcde'] = null;
                        $item['Qty'] = $qty;
                        $item['FreeQty'] = 0;
                        $item['Unit'] = $parts->part->uom ? $parts->part->uom->name : "NOS";
                        $item['Unit'] = "NOS";
                        $item['UnitPrice'] = number_format($price, 2);
                        $item['TotAmt'] = number_format($total_price, 2);
                        $item['Discount'] = 0; //Always value will be "0"
                        $item['PreTaxVal'] = number_format($total_price, 2);
                        $item['AssAmt'] = number_format($total_price, 2);
                        $item['IgstRt'] = number_format($igst_percentage, 2);
                        $item['IgstAmt'] = number_format($igst_amt, 2);
                        $item['CgstRt'] = number_format($cgst_percentage, 2);
                        $item['CgstAmt'] = number_format($cgst_amt, 2);
                        $item['SgstRt'] = number_format($sgst_percentage, 2);
                        $item['SgstAmt'] = number_format($sgst_amt, 2);
                        $item['CesRt'] = 0;
                        $item['CesAmt'] = 0;
                        $item['CesNonAdvlAmt'] = 0;
                        $item['StateCesRt'] = 0; //NEED TO CLARIFY IF KFC
                        $item['StateCesAmt'] = 0; //NEED TO CLARIFY IF KFC
                        $item['StateCesNonAdvlAmt'] = 0; //NEED TO CLARIFY IF KFC
                        $item['OthChrg'] = 0;
                        $item['TotItemVal'] = number_format(($parts->rate * $qty), 2);

                        $item['OrdLineRef'] = "0";
                        $item['OrgCntry'] = "IN"; //Always value will be "IND"
                        $item['PrdSlNo'] = null;

                        //AttribDtls
                        $item['AttribDtls'][] = [
                            "Nm" => null,
                            "Val" => null,
                        ];

                        //EGST
                        //NO DATA GIVEN IN WORD DOC START
                        $item['EGST']['nilrated_amt'] = null;
                        $item['EGST']['exempted_amt'] = null;
                        $item['EGST']['non_gst_amt'] = null;
                        $item['EGST']['reason'] = null;
                        $item['EGST']['debit_gl_id'] = null;
                        $item['EGST']['debit_gl_name'] = null;
                        $item['EGST']['credit_gl_id'] = null;
                        $item['EGST']['credit_gl_name'] = null;
                        $item['EGST']['sublocation'] = null;
                        //NO DATA GIVEN IN WORD DOC END

                        $i++;
                        $items[] = $item;

                    }
                }
            }

            // dd($items);

            $errors = [];
            //QR Code Generate
            if ($site_visit->e_invoice_registration == 1) {
                // RSA ENCRYPTION
                $rsa = new Crypt_RSA;

                $public_key = 'MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAxqHazGS4OkY/bDp0oklL+Ser7EpTpxyeMop8kfBlhzc8dzWryuAECwu8i/avzL4f5XG/DdSgMz7EdZCMrcxtmGJlMo2tUqjVlIsUslMG6Cmn46w0u+pSiM9McqIvJgnntKDHg90EIWg1BNnZkJy1NcDrB4O4ea66Y6WGNdb0DxciaYRlToohv8q72YLEII/z7W/7EyDYEaoSlgYs4BUP69LF7SANDZ8ZuTpQQKGF4TJKNhJ+ocmJ8ahb2HTwH3Ol0THF+0gJmaigs8wcpWFOE2K+KxWfyX6bPBpjTzC+wQChCnGQREhaKdzawE/aRVEVnvWc43dhm0janHp29mAAVv+ngYP9tKeFMjVqbr8YuoT2InHWFKhpPN8wsk30YxyDvWkN3mUgj3Q/IUhiDh6fU8GBZ+iIoxiUfrKvC/XzXVsCE2JlGVceuZR8OzwGrxk+dvMnVHyauN1YWnJuUTYTrCw3rgpNOyTWWmlw2z5dDMpoHlY0WmTVh0CrMeQdP33D3LGsa+7JYRyoRBhUTHepxLwk8UiLbu6bGO1sQwstLTTmk+Z9ZSk9EUK03Bkgv0hOmSPKC4MLD5rOM/oaP0LLzZ49jm9yXIrgbEcn7rv82hk8ghqTfChmQV/q+94qijf+rM2XJ7QX6XBES0UvnWnV6bVjSoLuBi9TF1ttLpiT3fkCAwEAAQ=='; //PROVIDE FROM BDO COMPANY

                $clientid = config('custom.CLIENT_ID');

                $rsa->loadKey($public_key);
                $rsa->setEncryptionMode(2);

                $client_secret_key = config('custom.CLIENT_SECRET_KEY');

                $ClientSecret = $rsa->encrypt($client_secret_key);
                $clientsecretencrypted = base64_encode($ClientSecret);

                $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                $app_secret_key = substr(str_shuffle($characters), 0, 32); // RANDOM KEY GENERATE

                $AppSecret = $rsa->encrypt($app_secret_key);
                $appsecretkey = base64_encode($AppSecret);

                $bdo_login_url = config('custom.BDO_LOGIN_URL');

                $ch = curl_init($bdo_login_url);
                // Setup request to send json via POST`
                $params = json_encode(array(
                    'clientid' => $clientid,
                    'clientsecretencrypted' => $clientsecretencrypted,
                    'appsecretkey' => $appsecretkey,
                ));

                // Attach encoded JSON string to the POST fields
                curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

                // Set the content type to application/json
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

                // Return response instead of outputting
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                // Execute the POST request
                $server_output = curl_exec($ch);

                // Get the POST request header status
                $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                // If header status is not Created or not OK, return error message
                if ($status != 200) {
                    return [
                        'success' => false,
                        'errors' => ["Conection Error in BDO Login!"],
                    ];
                    $errors[] = 'Conection Error in BDO Login!';
                }

                curl_close($ch);

                $bdo_login_check = json_decode($server_output);

                $api_params = [
                    'type_id' => 1062,
                    'entity_number' => $site_visit->number,
                    'entity_id' => $site_visit->id,
                    'url' => $bdo_login_url,
                    'src_data' => $params,
                    'response_data' => $server_output,
                    'user_id' => Auth::user()->id,
                    'status_id' => $bdo_login_check->status == 0 ? 11272 : 11271,
                    'errors' => !empty($errors) ? null : json_encode($errors),
                    'created_by_id' => Auth::user()->id,
                ];

                if ($bdo_login_check->status == 0) {
                    $api_params['message'] = 'Login Failed!';
                    $api_logs[0] = $api_params;
                    return [
                        'success' => false,
                        'errors' => [$bdo_login_check->ErrorMsg],
                        'api_logs' => $api_logs,
                    ];
                }
                $api_params['message'] = 'Login Success!';

                $api_logs[1] = $api_params;

                $expiry = $bdo_login_check->expiry;
                $bdo_authtoken = $bdo_login_check->bdo_authtoken;
                $status = $bdo_login_check->status;
                $bdo_sek = $bdo_login_check->bdo_sek;

                //DECRYPT WITH APP KEY AND BDO SEK KEY
                $decrypt_data_with_bdo_sek = self::decryptAesData($app_secret_key, $bdo_sek);
                if (!$decrypt_data_with_bdo_sek) {
                    $errors[] = 'Decryption Error!';
                    return response()->json(['success' => false, 'error' => 'Decryption Error!']);
                }

                //RefDtls BELLOW
                //PrecDocDtls
                $prodoc_detail = [];
                $prodoc_detail['InvNo'] = null;
                $prodoc_detail['InvDt'] = null;
                $prodoc_detail['OthRefNo'] = null; //no DATA ?
                //ContrDtls
                $control_detail = [];
                $control_detail['RecAdvRefr'] = null; //no DATA ?
                $control_detail['RecAdvDt'] = null; //no DATA ?
                $control_detail['Tendrefr'] = null; //no DATA ?
                $control_detail['Contrrefr'] = null; //no DATA ?
                $control_detail['Extrefr'] = null; //no DATA ?
                $control_detail['Projrefr'] = null;
                $control_detail['Porefr'] = null;
                $control_detail['PoRefDt'] = null;

                //AddlDocDtls
                $additionaldoc_detail = [];
                $additionaldoc_detail['Url'] = null;
                $additionaldoc_detail['Docs'] = null;
                $additionaldoc_detail['Info'] = null;

                // if ($sale_orders->customer_type_id == 800) {
                //     $type = 'CRN';
                // } elseif ($sale_orders->type_id == 801) {
                //     $type = 'DBN';
                // } else {
                //     $type = '';
                // }

                $json_encoded_data =
                    json_encode(
                    array(
                        'TranDtls' => array(
                            'TaxSch' => "GST",
                            'SupTyp' => "B2B", //ALWAYS B2B FOR REGISTER IRN
                            // 'RegRev' => $invoice->is_reverse_charge_applicable == 1 ? "Y" : "N",
                            'RegRev' => "N",
                            'EcmGstin' => null,
                            'IgstonIntra' => null, //NEED TO CLARIFY
                            'supplydir' => "O", //NULL ADDED 28-sep-2020 discussion "supplydir": "O"
                        ),
                        'DocDtls' => array(
                            "Typ" => 'INV',
                            "No" => $site_visit->number,
                            "Dt" => date('d-m-Y'),
                        ),
                        'SellerDtls' => array(
                            "Gstin" => $outlet ? ($outlet->gst_number ? $outlet->gst_number : 'N/A') : 'N/A',
                            "LglNm" => $outlet ? $outlet->name : 'N/A',
                            "TrdNm" => $outlet ? $outlet->name : 'N/A',
                            "Addr1" => $outlet->primaryAddress ? preg_replace('/\r|\n|:|"/', ",", $outlet->primaryAddress->address_line1) : 'N/A',
                            "Addr2" => $outlet->primaryAddress ? preg_replace('/\r|\n|:|"/', ",", $outlet->primaryAddress->address_line2) : null,
                            "Loc" => $outlet->primaryAddress ? ($outlet->primaryAddress->state ? $outlet->primaryAddress->state->name : 'N/A') : 'N/A',
                            "Pin" => $outlet->primaryAddress ? $outlet->primaryAddress->pincode : 'N/A',
                            "Stcd" => $outlet->primaryAddress ? ($outlet->primaryAddress->state ? $outlet->primaryAddress->state->e_invoice_state_code : 'N/A') : 'N/A',
                            "Ph" => '123456789', //need to clarify
                            "Em" => null, //need to clarify
                        ),
                        "BuyerDtls" => array(
                            "Gstin" => $address->gst_number ? $address->gst_number : 'N/A', //need to clarify if available ok otherwise ?
                            "LglNm" => $site_visit ? $site_visit->customer->name : 'N/A',
                            "TrdNm" => $site_visit ? $site_visit->customer->name : null,
                            "Pos" => $address ? ($address->state ? $address->state->e_invoice_state_code : 'N/A') : 'N/A',
                            "Loc" => $address ? ($address->state ? $address->state->name : 'N/A') : 'N/A',

                            "Addr1" => $address ? preg_replace('/\r|\n|:|"/', ",", $address->address_line1) : 'N/A',
                            "Addr2" => $address ? preg_replace('/\r|\n|:|"/', ",", $address->address_line2) : null,
                            "Stcd" => $address ? ($address->state ? $address->state->e_invoice_state_code : null) : null,
                            "Pin" => $address ? $address->pincode : null,
                            "Ph" => $site_visit->customer->mobile_no ? $site_visit->customer->mobile_no : null,
                            "Em" => $site_visit->customer->email ? $site_visit->customer->email : null,
                        ),
                        // 'BuyerDtls' => array(
                        'DispDtls' => array(
                            "Nm" => null,
                            "Addr1" => null,
                            "Addr2" => null,
                            "Loc" => null,
                            "Pin" => null,
                            "Stcd" => null,
                        ),
                        'ShipDtls' => array(
                            "Gstin" => null,
                            "LglNm" => null,
                            "TrdNm" => null,
                            "Addr1" => null,
                            "Addr2" => null,
                            "Loc" => null,
                            "Pin" => null,
                            "Stcd" => null,
                        ),
                        'ItemList' => array(
                            'Item' => $items,
                        ),
                        'ValDtls' => array(
                            "AssVal" => number_format(5000, 2),
                            "CgstVal" => number_format($cgst_total, 2),
                            "SgstVal" => number_format($sgst_total, 2),
                            "IgstVal" => number_format($igst_total, 2),
                            "CesVal" => 0,
                            "StCesVal" => 0,
                            "Discount" => 0,
                            "OthChrg" => number_format($tcs_total + $cess_on_gst_total, 2),
                            "RndOffAmt" => number_format(0, 2),
                            "TotInvVal" => number_format($total_inv_amount, 2),
                            // "TotInvVal" => number_format($part_amount, 2),
                            "TotInvValFc" => null,
                        ),
                        "PayDtls" => array(
                            "Nm" => null,
                            "Accdet" => null,
                            "Mode" => null,
                            "Fininsbr" => null,
                            "Payterm" => null, //NO DATA
                            "Payinstr" => null, //NO DATA
                            "Crtrn" => null, //NO DATA
                            "Dirdr" => null, //NO DATA
                            "Crday" => 0, //NO DATA
                            "Paidamt" => 0, //NO DATA
                            "Paymtdue" => 0, //NO DATA
                        ),
                        "RefDtls" => array(
                            "InvRm" => null,
                            "DocPerdDtls" => array(
                                "InvStDt" => null,
                                "InvEndDt" => null,
                            ),
                            "PrecDocDtls" => [
                                $prodoc_detail,
                            ],
                            "ContrDtls" => [
                                $control_detail,
                            ],
                        ),
                        "AddlDocDtls" => [
                            $additionaldoc_detail,
                        ],
                        "ExpDtls" => array(
                            "ShipBNo" => null,
                            "ShipBDt" => null,
                            "Port" => null,
                            "RefClm" => null,
                            "ForCur" => null,
                            "CntCode" => null, // ALWAYS IND //// ERROR : For Supply type other than EXPWP and EXPWOP, country code should be blank
                            "ExpDuty" => null,
                        ),
                        "EwbDtls" => array(
                            "Transid" => null,
                            "Transname" => null,
                            "Distance" => null,
                            "Transdocno" => null,
                            "TransdocDt" => null,
                            "Vehno" => null,
                            "Vehtype" => null,
                            "TransMode" => null,
                        ),
                    )
                );

                //dd($json_encoded_data);

                //AES ENCRYPT
                //ENCRYPT WITH Decrypted BDO SEK KEY TO PLAIN TEXT AND JSON DATA
                $encrypt_data = self::encryptAesData($decrypt_data_with_bdo_sek, $json_encoded_data);
                if (!$encrypt_data) {
                    $errors[] = 'IRN Encryption Error!';
                    return response()->json(['success' => false, 'error' => 'IRN Encryption Error!']);
                }

                //ENCRYPTED GIVEN DATA TO DBO
                $bdo_generate_irn_url = config('custom.BDO_IRN_REGISTRATION_URL');

                $ch = curl_init($bdo_generate_irn_url);
                // Setup request to send json via POST`
                $params = json_encode(array(
                    'Data' => $encrypt_data,
                ));

                // Attach encoded JSON string to the POST fields
                curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

                // Set the content type to application/json
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'client_id: ' . $clientid,
                    'bdo_authtoken: ' . $bdo_authtoken,
                    'action: GENIRN',
                ));

                // Return response instead of outputting
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                // Execute the POST request
                $generate_irn_output_data = curl_exec($ch);

                curl_close($ch);

                $generate_irn_output = json_decode($generate_irn_output_data, true);

                $api_params = [
                    'type_id' => 1062,
                    'entity_number' => $site_visit->number,
                    'entity_id' => $site_visit->id,
                    'url' => $bdo_generate_irn_url,
                    'src_data' => $params,
                    'response_data' => $generate_irn_output_data,
                    'user_id' => Auth::user()->id,
                    'status_id' => $bdo_login_check->status == 0 ? 11272 : 11271,
                    // 'errors' => !empty($errors) ? NULL : json_encode($errors),
                    'created_by_id' => Auth::user()->id,
                ];

                if (is_array($generate_irn_output['Error'])) {
                    $bdo_errors = [];
                    $rearrange_key = 0;
                    foreach ($generate_irn_output['Error'] as $key => $error) {
                        $bdo_errors[$rearrange_key] = $error;
                        $errors[$rearrange_key] = $error;
                        $rearrange_key++;
                    }

                    $api_params['errors'] = empty($errors) ? 'Somthin went worng!, Try again later!' : json_encode($errors);
                    $api_params['message'] = 'Error GENERATE IRN array!';

                    $api_logs[2] = $api_params;

                    return [
                        'success' => false,
                        'errors' => $bdo_errors,
                        'api_logs' => $api_logs,
                    ];
                    if ($generate_irn_output['status'] == 0) {
                        $api_params['errors'] = ['Somthing Went Wrong!. Try Again Later!'];
                        $api_params['message'] = 'Error Generating IRN!';
                        $api_logs[5] = $api_params;
                        return [
                            'success' => false,
                            'errors' => 'Somthing Went Wrong!. Try Again Later!',
                            'api_logs' => $api_logs,
                        ];
                    }
                } elseif (!is_array($generate_irn_output['Error'])) {
                    if ($generate_irn_output['Status'] != 1) {
                        $errors[] = $generate_irn_output['Error'];
                        $api_params['message'] = 'Error GENERATE IRN!';

                        $api_params['errors'] = empty($errors) ? 'Error GENERATE IRN, Try again later!' : json_encode($errors);
                        // DB::beginTransaction();

                        $api_logs[3] = $api_params;

                        return [
                            'success' => false,
                            'errors' => $generate_irn_output['Error'],
                            'api_logs' => $api_logs,
                        ];
                        // dd('Error: ' . $generate_irn_output['Error']);
                    }
                }

                $api_params['message'] = 'Success GENSERATE IRN!';

                $api_params['errors'] = null;
                $api_logs[4] = $api_params;

                //AES DECRYPTION AFTER GENERATE IRN
                $irn_decrypt_data = self::decryptAesData($decrypt_data_with_bdo_sek, $generate_irn_output['Data']);
                if (!$irn_decrypt_data) {
                    $errors[] = 'IRN Decryption Error!';
                    return response()->json(['success' => false, 'error' => 'IRN Decryption Error!']);
                }
                $final_json_decode = json_decode($irn_decrypt_data);

                if ($final_json_decode->irnStatus == 0) {
                    $api_params['message'] = $final_json_decode->irnStatus;
                    $api_params['errors'] = $final_json_decode->irnStatus;
                    $api_logs[6] = $api_params;
                    return [
                        'success' => false,
                        'errors' => $final_json_decode->ErrorMsg,
                        'api_logs' => $api_logs,
                    ];
                }

                $IRN_images_des = storage_path('app/public/on-site-visit/IRN_images');
                File::makeDirectory($IRN_images_des, $mode = 0777, true, true);

                $qr_images_des = storage_path('app/public/on-site-visit/qr_images');
                File::makeDirectory($qr_images_des, $mode = 0777, true, true);

                $url = QRCode::text($final_json_decode->SignedQRCode)->setSize(4)->setOutfile('storage/app/public/on-site-visit/IRN_images/' . $site_visit->number . '.png')->png();

                $qr_attachment_path = base_path("storage/app/public/on-site-visit/IRN_images/" . $site_visit->number . '.png');
                if (file_exists($qr_attachment_path)) {
                    $ext = pathinfo(base_path("storage/app/public/on-site-visit/IRN_images/" . $site_visit->number . '.png'), PATHINFO_EXTENSION);
                    if ($ext == 'png') {
                        $image = imagecreatefrompng($qr_attachment_path);
                        $bg = imagecreatetruecolor(imagesx($image), imagesy($image));
                        imagefill($bg, 0, 0, imagecolorallocate($bg, 255, 255, 255));
                        imagealphablending($bg, true);
                        imagecopy($bg, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
                        $quality = 70; // 0 = worst / smaller file, 100 = better / bigger file
                        imagejpeg($bg, 'storage/app/public/on-site-visit/qr_images/' . $site_visit->number . '.jpg', 100);

                        if (File::exists('storage/app/public/on-site-visit/qr_images/' . $site_visit->number . '.png')) {
                            File::delete('storage/app/public/on-site-visit/qr_images/' . $site_visit->number . '.png');
                        }

                        $qr_image = $site_visit->number . '.jpg';
                    }
                } else {
                    $qr_image = '';
                }

                $get_version = json_decode($final_json_decode->Invoice);
                $get_version = json_decode($get_version->data);

                $site_visit->irn_number = $final_json_decode->Irn;
                $site_visit->qr_image = $site_visit->number . '.jpg';
                $site_visit->ack_no = $final_json_decode->AckNo;
                $site_visit->ack_date = $final_json_decode->AckDt;
                $site_visit->version = $get_version->Version;
                $site_visit->irn_request = $json_encoded_data;
                $site_visit->irn_response = $irn_decrypt_data;

                $site_visit->errors = empty($errors) ? null : json_encode($errors);
            } else {
                $qrPaymentApp = QRPaymentApp::where([
                    'name' => 'On Site Visit',
                ])->first();
                if (!$qrPaymentApp) {
                    return [
                        'success' => false,
                        'errors' => 'QR Payment App not found : On Site Visit',
                    ];
                    $errors[] = 'QR Payment App not found : On Site Visit';
                }

                $base_url_with_invoice_details = url(
                    '/pay' .
                    '?invNo=' . $site_visit->number .
                    '&date=' . date('d-m-Y') .
                    '&invAmt=' . str_replace(',', '', $total_inv_amount) .
                    '&oc=' . $site_visit->outlet->code .
                    '&cc=' . $site_visit->customer->code .
                    '&cgst=' . $cgst_total .
                    '&sgst=' . $sgst_total .
                    '&igst=' . $igst_total .
                    '&cess=' . $cess_on_gst_total .
                    '&appCode=' . $qrPaymentApp->app_code
                );

                $B2C_images_des = storage_path('app/public/on-site-visit/qr_images');
                File::makeDirectory($B2C_images_des, $mode = 0777, true, true);

                $url = QRCode::URL($base_url_with_invoice_details)->setSize(4)->setOutfile('storage/app/public/on-site-visit/qr_images/' . $site_visit->number . '.png')->png();

                $qr_attachment_path = base_path("storage/app/public/on-site-visit/qr_images/" . $site_visit->number . '.png');

                if (file_exists($qr_attachment_path)) {
                    $ext = pathinfo(base_path("storage/app/public/on-site-visit/qr_images/" . $site_visit->number . '.png'), PATHINFO_EXTENSION);
                    if ($ext == 'png') {
                        $image = imagecreatefrompng($qr_attachment_path);
                        $bg = imagecreatetruecolor(imagesx($image), imagesy($image));
                        imagefill($bg, 0, 0, imagecolorallocate($bg, 255, 255, 255));
                        imagealphablending($bg, true);
                        imagecopy($bg, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));

                        imagejpeg($bg, 'storage/app/public/on-site-visit/qr_images/' . $site_visit->number . '.jpg', 100);

                        if (File::exists('storage/app/public/on-site-visit/qr_images/' . $site_visit->number . '.png')) {
                            File::delete('storage/app/public/on-site-visit/qr_images/' . $site_visit->number . '.png');
                        }
                    }
                }

                $site_visit->qr_image = $site_visit->number . '.jpg';
            }

            // $site_visit->status_id = 10;

            $otp_no = mt_rand(111111, 999999);
            $site_visit->otp_no = $otp_no;

            $url = url('/') . '/on-site-visit/view/bill-details/' . $site_visit->id . '/' . $otp_no;
            $short_url = ShortUrl::createShortLink($url, $maxlength = "7");

            $message = 'Dear Customer, Kindly click on this link to pay for the TVS job order ' . $short_url . ' Job Card Number : ' . $site_visit->number . ' - TVS';

            $msg = sendSMSNotification($customer_mobile, $message);

            $message = 'On Site Visit Completed Successfully!';

            $site_visit->updated_by_id = Auth::user()->id;
            $site_visit->updated_at = Carbon::now();
            $site_visit->save();

            //Generate Bill Details PDF
            $generate_on_site_estimate_pdf = OnSiteOrder::generateBillingPDF($site_visit->id);

            //Generate Labour PDF
            $generate_on_site_estimate_pdf = OnSiteOrder::generateLabourPDF($site_visit->id);

            //Generate Part PDF
            $generate_on_site_estimate_pdf = OnSiteOrder::generatePartPDF($site_visit->id);

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

    public function getTimeLog(Request $request)
    {
        // dd($request->all());
        try {
            $site_visit = OnSiteOrder::with([
                'onSiteOrderTravelLogs',
                'onSiteOrderWorkLogs',
            ])->find($request->id);

            if (!$site_visit) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation Error',
                    'errors' => [
                        'On Site Visit Not Found!',
                    ],
                ]);
            }

            // dd($site_visit);
            //Get Travel Time Log
            if (count($site_visit->onSiteOrderTravelLogs) > 0) {
                $duration = array();
                foreach ($site_visit->onSiteOrderTravelLogs as $on_site_travel_log) {
                    if ($on_site_travel_log['end_date_time']) {
                        $time1 = strtotime($on_site_travel_log['start_date_time']);
                        $time2 = strtotime($on_site_travel_log['end_date_time']);
                        if ($time2 < $time1) {
                            $time2 += 86400;
                        }

                        $total_duration = date("H:i:s", strtotime("00:00") + ($time2 - $time1));
                        $duration[] = $total_duration;

                        $format_change = explode(':', $total_duration);
                        $hour = $format_change[0];
                        $minutes = $format_change[1];

                        $total_duration_in_hrs = $hour . ' hrs ' . $minutes . ' min';

                        $on_site_travel_log->total_duration = $total_duration_in_hrs;
                    }
                }

                $total_duration = sum_mechanic_duration($duration);
                $format_change = explode(':', $total_duration);

                $hour = $format_change[0];
                $minutes = $format_change[1];

                $total_travel_hours = $hour . ' hrs ' . $minutes . ' min';
                unset($duration);

            } else {
                $total_travel_hours = '-';
            }

            //Get Work Time Log
            if (count($site_visit->onSiteOrderWorkLogs) > 0) {
                $work_duration = array();
                foreach ($site_visit->onSiteOrderWorkLogs as $on_site_work_log) {
                    if ($on_site_work_log['end_date_time']) {
                        $time1 = strtotime($on_site_work_log['start_date_time']);
                        $time2 = strtotime($on_site_work_log['end_date_time']);
                        if ($time2 < $time1) {
                            $time2 += 86400;
                        }

                        $total_duration = date("H:i:s", strtotime("00:00") + ($time2 - $time1));
                        $work_duration[] = $total_duration;

                        $format_change = explode(':', $total_duration);
                        $hour = $format_change[0];
                        $minutes = $format_change[1];

                        $total_duration_in_hrs = $hour . ' hrs ' . $minutes . ' min';

                        $on_site_work_log->total_duration = $total_duration_in_hrs;
                    }
                }
                // dd($work_duration);
                $total_duration = sum_mechanic_duration($work_duration);
                $format_change = explode(':', $total_duration);

                $hour = $format_change[0];
                $minutes = $format_change[1];

                $total_work_hours = $hour . ' hrs ' . $minutes . ' min';
                unset($work_duration);

            } else {
                $total_work_hours = '-';
            }

            $travel_start_button_status = 'true';
            $travel_end_button_status = 'false';

            $work_start_button_status = 'true';
            $work_end_button_status = 'false';

            //Travel Log Start Button Status
            $travel_log = OnSiteOrderTimeLog::where('on_site_order_id', $site_visit->id)->where('work_log_type_id', 1)->whereNull('end_date_time')->first();
            if ($travel_log) {
                $travel_start_button_status = 'false';
                $travel_end_button_status = 'true';

                // $work_start_button_status = 'true';
                // $work_end_button_status = 'false';
            }

            $travel_log = OnSiteOrderTimeLog::where('on_site_order_id', $site_visit->id)->where('work_log_type_id', 2)->whereNull('end_date_time')->first();
            if ($travel_log) {
                $travel_start_button_status = 'false';
                $travel_end_button_status = 'false';

                $work_start_button_status = 'false';
                $work_end_button_status = 'true';
            }

            return response()->json([
                'success' => true,
                'travel_logs' => $site_visit->onSiteOrderTravelLogs,
                'work_logs' => $site_visit->onSiteOrderWorkLogs,
                'total_travel_hours' => $total_travel_hours,
                'total_work_hours' => $total_work_hours,
                'travel_start_button_status' => $travel_start_button_status,
                'travel_end_button_status' => $travel_end_button_status,
                'work_start_button_status' => $work_start_button_status,
                'work_end_button_status' => $work_end_button_status,
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

    public function saveTimeLog(Request $request)
    {
        // dd($request->all());

        try {
            $site_visit = OnSiteOrder::find($request->on_site_order_id);

            if (!$site_visit) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation Error',
                    'errors' => [
                        'Site Visit Not Found!',
                    ],
                ]);
            }

            DB::beginTransaction();

            if ($request->work_log_type == 'travel_log') {
                if ($request->type_id == 1) {

                    //Check already save or not not means site visit status update
                    $travel_log = OnSiteOrderTimeLog::where('on_site_order_id', $site_visit->id)->where('work_log_type_id', 1)->first();
                    if (!$travel_log) {
                        $site_visit->actual_visit_date = date('Y-m-d');
                        $site_visit->status_id = 11;
                        $site_visit->updated_by_id = Auth::user()->id;
                        $site_visit->updated_at = Carbon::now();
                        $site_visit->save();
                    }

                    //Check Previous entry closed or not
                    $travel_log = OnSiteOrderTimeLog::where('on_site_order_id', $site_visit->id)->where('work_log_type_id', 1)->whereNull('end_date_time')->first();
                    if ($travel_log) {
                        return response()->json([
                            'success' => false,
                            'error' => 'Validation Error',
                            'errors' => [
                                'Previous Travel Log not closed!',
                            ],
                        ]);
                    }
                    $travel_log = new OnSiteOrderTimeLog;
                    $travel_log->on_site_order_id = $site_visit->id;
                    $travel_log->work_log_type_id = 1;
                    $travel_log->start_date_time = Carbon::now();
                    $travel_log->created_by_id = Auth::user()->id;
                    $travel_log->created_at = Carbon::now();
                    $travel_log->start_latitude = $request->latitude ? $request->latitude : $request->location_error;
                    $travel_log->start_longitude = $request->longitude;

                    $travel_log->save();
                    $message = 'Travel Log Added Successfully!';
                } else {
                    $travel_log = OnSiteOrderTimeLog::where('on_site_order_id', $site_visit->id)->where('work_log_type_id', 1)->whereNull('end_date_time')->first();
                    if (!$travel_log) {
                        return response()->json([
                            'success' => false,
                            'error' => 'Validation Error',
                            'errors' => [
                                'Previous Travel Log not found!',
                            ],
                        ]);
                    }
                    $travel_log->end_date_time = Carbon::now();
                    $travel_log->updated_by_id = Auth::user()->id;
                    $travel_log->updated_at = Carbon::now();
                    $travel_log->end_latitude = $request->latitude ? $request->latitude : $request->location_error;
                    $travel_log->end_longitude = $request->longitude;

                    $travel_log->save();
                    $message = 'Travel Log Updated Successfully!';
                }
            } else {
                if ($request->type_id == 1) {

                    //Check already save or not not means site visit status update
                    $travel_log = OnSiteOrderTimeLog::where('on_site_order_id', $site_visit->id)->where('work_log_type_id', 2)->first();

                    // if (!$travel_log) {
                    $site_visit->status_id = 14;
                    $site_visit->updated_by_id = Auth::user()->id;
                    $site_visit->updated_at = Carbon::now();
                    $site_visit->save();
                    // }

                    //Check Previous entry closed or not
                    $travel_log = OnSiteOrderTimeLog::where('on_site_order_id', $site_visit->id)->where('work_log_type_id', 2)->whereNull('end_date_time')->first();
                    if ($travel_log) {
                        return response()->json([
                            'success' => false,
                            'error' => 'Validation Error',
                            'errors' => [
                                'Previous Work Log not closed!',
                            ],
                        ]);
                    }
                    $travel_log = new OnSiteOrderTimeLog;
                    $travel_log->on_site_order_id = $site_visit->id;
                    $travel_log->work_log_type_id = 2;
                    $travel_log->start_date_time = Carbon::now();
                    $travel_log->created_by_id = Auth::user()->id;
                    $travel_log->created_at = Carbon::now();
                    $travel_log->start_latitude = $request->latitude ? $request->latitude : $request->location_error;
                    $travel_log->start_longitude = $request->longitude;

                    $travel_log->save();
                    $message = 'Work Started Successfully!';
                } else {
                    $travel_log = OnSiteOrderTimeLog::where('on_site_order_id', $site_visit->id)->where('work_log_type_id', 2)->whereNull('end_date_time')->first();
                    if (!$travel_log) {
                        return response()->json([
                            'success' => false,
                            'error' => 'Validation Error',
                            'errors' => [
                                'Previous Work Log not found!',
                            ],
                        ]);
                    }

                    $site_visit->status_id = 15;
                    $site_visit->updated_by_id = Auth::user()->id;
                    $site_visit->updated_at = Carbon::now();
                    $site_visit->save();

                    $travel_log->end_date_time = Carbon::now();
                    $travel_log->updated_by_id = Auth::user()->id;
                    $travel_log->updated_at = Carbon::now();
                    $travel_log->end_latitude = $request->latitude ? $request->latitude : $request->location_error;
                    $travel_log->end_longitude = $request->longitude;

                    $travel_log->save();
                    $message = 'Work Stopped Successfully!';
                }
            }

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

    public function get_client_ip()
    {
        $ipaddress = '';
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        } else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else if (isset($_SERVER['HTTP_X_FORWARDED'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        } else if (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        } else if (isset($_SERVER['HTTP_FORWARDED'])) {
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        } else if (isset($_SERVER['REMOTE_ADDR'])) {
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        } else {
            $ipaddress = 'UNKNOWN';
        }

        return $ipaddress;

    }

    public function deleteLabourParts(Request $request)
    {
        // dd($request->all());
        try {
            DB::beginTransaction();
            if ($request->payable_type == 'labour') {
                $validator = Validator::make($request->all(), [
                    'labour_parts_id' => [
                        'required',
                        'integer',
                        'exists:on_site_order_repair_orders,id',
                    ],
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Validation Error',
                        'errors' => $validator->errors()->all(),
                    ]);
                }

                if ($request->removal_reason_id == 10022) {
                    $on_site_order_repair_order = OnSiteOrderRepairOrder::find($request->labour_parts_id);
                    if ($on_site_order_repair_order) {
                        $on_site_order_repair_order->removal_reason_id = $request->removal_reason_id;
                        $on_site_order_repair_order->removal_reason = $request->removal_reason;
                        $on_site_order_repair_order->updated_by_id = Auth::user()->id;
                        $on_site_order_repair_order->updated_at = Carbon::now();
                        $on_site_order_repair_order->save();
                    }
                } else {
                    $on_site_order_repair_order = OnSiteOrderRepairOrder::where('id', $request->labour_parts_id)->forceDelete();
                }

            } else {
                $validator = Validator::make($request->all(), [
                    'labour_parts_id' => [
                        'required',
                        'integer',
                        'exists:on_site_order_parts,id',
                    ],
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Validation Error',
                        'errors' => $validator->errors()->all(),
                    ]);
                }

                if ($request->removal_reason_id == 10022) {
                    $on_site_order_parts = OnSiteOrderPart::find($request->labour_parts_id);
                    if ($on_site_order_parts) {
                        $on_site_order_parts->removal_reason_id = $request->removal_reason_id;
                        $on_site_order_parts->removal_reason = $request->removal_reason;
                        $on_site_order_parts->updated_by_id = Auth::user()->id;
                        $on_site_order_parts->updated_at = Carbon::now();
                        $on_site_order_parts->save();
                    }
                } else {
                    $on_site_order_parts = OnSiteOrderPart::where('id', $request->labour_parts_id)->forceDelete();
                }
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

    //Get Repair Orders
    public function getRepairOrderSearchList(Request $request)
    {
        return RepairOrder::searchRepairOrder($request);
    }

    public static function encryptAesData($encryption_key, $data)
    {
        $method = 'aes-256-ecb';

        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method));

        $encrypted = openssl_encrypt($data, $method, $encryption_key, 0, $iv);

        return $encrypted;
    }

    public static function decryptAesData($encryption_key, $data)
    {
        $method = 'aes-256-ecb';

        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method));

        $decrypted = openssl_decrypt(base64_decode($data), $method, $encryption_key, OPENSSL_RAW_DATA, $iv);
        return $decrypted;
    }
}

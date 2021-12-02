<?php

namespace Abs\GigoPkg\Api;

use Abs\BasicPkg\Traits\CrudTrait;
use Abs\GigoPkg\Bay;
use Abs\GigoPkg\GateLog;
use Abs\GigoPkg\GatePass;
use Abs\GigoPkg\GatePassDetail;
use Abs\GigoPkg\GatePassItem;
use Abs\GigoPkg\JobCard;
use Abs\GigoPkg\JobCardReturnableItem;
use Abs\GigoPkg\JobOrder;
use Abs\GigoPkg\JobOrderEInvoice;
use Abs\GigoPkg\JobOrderRepairOrder;
use Abs\GigoPkg\MechanicTimeLog;
use Abs\GigoPkg\RepairOrder;
use Abs\GigoPkg\RepairOrderMechanic;
use Abs\GigoPkg\RoadTestGatePass;
use Abs\GigoPkg\ShortUrl;
use Abs\PartPkg\Part;
use Abs\SerialNumberPkg\SerialNumberGroup;
use Abs\TaxPkg\Tax;
use App\Address;
use App\Attachment;
use App\Config;
use App\Customer;
use App\Employee;
use App\Entity;
use App\FinancialYear;
use App\FloatingGatePass;
use App\GigoInvoice;
use App\Http\Controllers\Controller;
use App\Http\Controllers\WpoSoapController;
use App\JobOrderEstimate;
use App\JobOrderIssuedPart;
use App\JobOrderPart;
use App\JobOrderReturnedPart;
use App\Jobs\Notification;
use App\OSLWorkOrder;
use App\Otp;
use App\Outlet;
use App\QRPaymentApp;
use App\QuoteType;
use App\ServiceOrderType;
use App\ServiceType;
use App\SplitOrderType;
use App\TradePlateNumber;
use App\User;
use App\VehicleInspectionItem;
use App\VehicleInspectionItemGroup;
use App\VehicleInventoryItem;
use App\Vendor;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use File;
use Illuminate\Http\Request;
use phpseclib\Crypt\RSA as Crypt_RSA;
use QRCode;
use Storage;
use Validator;

class JobCardController extends Controller
{
    use CrudTrait;
    public $model = JobCard::class;
    public $successStatus = 200;

    public function __construct(WpoSoapController $getSoap = null)
    {
        $this->getSoap = $getSoap;
    }

    public function getJobCardList(Request $request)
    {
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
                'job_cards.status_id',
                'bays.name as bay_name',
                'vehicles.registration_number',
                'vehicles.chassis_number',
                'vehicles.engine_number',
                'models.model_name as vehicle_model',
                'customers.name as customer_name',
                'status.name as status',
                'service_types.name as service_type',
                'quote_types.name as quote_type',
                'service_order_types.name as job_order_type',
                // 'gate_passes.id as gate_pass_id',

            ])
                ->join('job_orders', 'job_orders.id', 'job_cards.job_order_id')
                ->join('gate_logs', 'gate_logs.job_order_id', 'job_orders.id')
                ->leftJoin('bays', 'bays.id', 'job_cards.bay_id')
            // ->leftJoin('gate_passes', 'gate_passes.job_card_id', 'job_cards.id')
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
                            ->orWhere('vehicles.chassis_number', 'LIKE', '%' . $request->search_key . '%')
                            ->orWhere('vehicles.engine_number', 'LIKE', '%' . $request->search_key . '%')
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
                    $outlet_ids = Auth::user()->employee->outlets->pluck('id')->toArray();
                    // array_push($outlet_ids, Auth::user()->employee->outlet_id);
                    $job_card_list->whereIn('job_cards.outlet_id', $outlet_ids);
                } else if (Entrust::can('view-own-outlet-job-card')) {
                    // $job_card_list->where('job_cards.outlet_id', Auth::user()->employee->outlet_id)->whereRaw("IF (job_cards.`status_id` = '8220', job_cards.`floor_supervisor_id` IS  NULL, job_cards.`floor_supervisor_id` = '" . $request->floor_supervisor_id . "')");
                    $job_card_list->where('job_cards.outlet_id', Auth::user()->working_outlet_id);
                } else {
                    // $job_card_list->where('job_cards.floor_supervisor_id', Auth::user()->id);
                    $job_card_list->where('job_cards.outlet_id', Auth::user()->working_outlet_id);
                }
            }

            $job_card_list->whereNotNull('job_cards.status_id')->groupBy('job_cards.id')
                ->orderBy('job_cards.created_at', 'DESC');

            $total_records = $job_card_list->get()->count();

            if ($request->offset) {
                $job_card_list->offset($request->offset);
            }
            if ($request->limit) {
                $job_card_list->limit($request->limit);
            }

            $job_cards = $job_card_list->get();

            $params = [
                'config_type_id' => 42,
                'add_default' => true,
                'default_text' => "Select Status",
            ];

            $extras = [
                'job_order_type_list' => ServiceOrderType::getDropDownList(),
                'service_type_list' => ServiceType::getDropDownList(),
                'quote_type_list' => QuoteType::getDropDownList(),
                'status_list' => Config::getDropDownList($params),
            ];

            return response()->json([
                'success' => true,
                'job_card_list' => $job_cards,
                'extras' => $extras,
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

    //Update JC form data
    public function getUpdateJcFormData(Request $r)
    {
        try {
            $job_order = JobOrder::with([
                'status',
                'gateLog',
                'gateLog.status',
                'vehicle',
                'vehicle.model',
                'vehicle.currentOwner.customer',
                'vehicle.currentOwner.customer.primaryAddress',
                'jobCard',
                'jobCard.attachment',
                'jobOrderRepairOrders' => function ($q) {
                    $q->whereNull('removal_reason_id');
                },
                'jobOrderRepairOrders.repairOrder',
                'jobOrderRepairOrders.repairOrder.taxCode',
                'jobOrderRepairOrders.repairOrder.taxCode.taxes',
                'jobOrderParts' => function ($q) {
                    $q->whereNull('removal_reason_id');
                    // $q->whereIn('split_order_type_id', $customer_paid_type_id)->whereNull('removal_reason_id');
                },
                'jobOrderParts.part',
                'jobOrderParts.part.taxCode',
                'jobOrderParts.part.taxCode.taxes',
                'outlet',
                'type',
                'quoteType',
                'serviceType',
                'status',
                'gateLog',
                'customerVoices',
                'roadTestDoneBy',
                'roadTestPreferedBy',
                'roadTestPreferedBy.employee',
            ])
                ->find($r->id);

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
            if (!$job_order) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation Error',
                    'errors' => ['Job Order Not Found!'],
                ]);
            }

            if ($job_order->vehicle->currentOwner->customer->primaryAddress) {
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
            $taxes = Tax::whereIn('id', [1, 2, 3])->get();
            $customer_paid_type_id = SplitOrderType::where('paid_by_id', '10013')->pluck('id')->toArray();
            $parts_amount = 0;
            $labour_amount = 0;
            $total_amount = 0;

            $labour_details = array();
            if ($job_order->jobOrderRepairOrders) {
                foreach ($job_order->jobOrderRepairOrders as $key => $labour) {
                    if ($labour->is_free_service != 1 && (in_array($labour->split_order_type_id, $customer_paid_type_id) || !$labour->split_order_type_id)) {
                        $total_amount = 0;
                        $labour_details[$key]['name'] = $labour->repairOrder->name;
                        $labour_details[$key]['hsn_code'] = $labour->repairOrder->taxCode ? $labour->repairOrder->taxCode->code : '-';
                        $labour_details[$key]['qty'] = $labour->qty;
                        $labour_details[$key]['amount'] = $labour->amount;
                        $labour_details[$key]['is_free_service'] = $labour->is_free_service;
                        $labour_details[$key]['estimate_order_id'] = $labour->estimate_order_id;
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

                        $total_amount = $tax_amount + $labour->amount;
                        $total_amount = number_format((float) $total_amount, 2, '.', '');
                        $labour_amount += $total_amount;
                        $labour_details[$key]['tax_values'] = $tax_values;
                        $labour_details[$key]['total_amount'] = $total_amount;
                    }
                }
            }

            $part_details = array();
            if ($job_order->jobOrderParts) {
                foreach ($job_order->jobOrderParts as $key => $parts) {
                    if ($parts->is_free_service != 1 && (in_array($parts->split_order_type_id, $customer_paid_type_id) || !$parts->split_order_type_id)) {
                        $total_amount = 0;
                        $part_details[$key]['name'] = $parts->part->name;
                        $part_details[$key]['hsn_code'] = $parts->part->taxCode ? $parts->part->taxCode->code : '-';
                        $part_details[$key]['qty'] = $parts->qty;
                        $part_details[$key]['rate'] = $parts->rate;
                        $part_details[$key]['amount'] = $parts->amount;
                        $part_details[$key]['is_free_service'] = $parts->is_free_service;
                        $part_details[$key]['estimate_order_id'] = $parts->estimate_order_id;
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

                        $total_amount = $tax_amount + $parts->amount;
                        $total_amount = number_format((float) $total_amount, 2, '.', '');
                        $parts_amount += $total_amount;
                        $part_details[$key]['tax_values'] = $tax_values;
                        $part_details[$key]['total_amount'] = $total_amount;
                    }
                }
            }

            $total_amount = $parts_amount + $labour_amount;
            $total_amount = round($total_amount);

            $job_order->attachment_path = 'storage/app/public/gigo/job_card/attachments';

            $job_order->labour_details = $labour_details;
            $job_order->part_details = $part_details;
            $job_order->parts_total_amount = number_format($parts_amount, 2);
            $job_order->labour_total_amount = number_format($labour_amount, 2);
            $job_order->total_amount = number_format($total_amount, 2);

            return response()->json([
                'success' => true,
                'job_order' => $job_order,
                'taxes' => $taxes,
                'extras' => $extras,
                'tax_count' => count($taxes),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Server Network Down!',
                'errors' => ['Exception Error' => $e->getMessage()],
            ]);
        }
    }

    //Save Jobcard data
    public function saveJobCard(Request $request)
    {
        // dd($request->all());
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
                // 'floor_supervisor_id' => [
                //     'required',
                //     'exists:users,id',
                //     'integer',
                // ],
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
            $job_card = JobCard::where('job_order_id', $request->job_order_id)->first();

            if ($job_card) {
                if ($job_card->status_id == 8221) {
                    $job_card->updated_by = Auth::user()->id;
                } else {
                    $job_card->job_card_number = $request->job_card_number;
                    $job_card->date = date('Y-m-d', strtotime($request->job_card_date));
                    $job_card->outlet_id = $job_order->outlet_id;
                    $job_card->floor_supervisor_id = $request->floor_supervisor_id;
                    $job_card->status_id = 8220; //Floor Supervisor not Assigned
                    $job_card->company_id = Auth::user()->company_id;
                    $job_card->created_by = Auth::user()->id;
                }
                $job_card->save();
            }

            //Update Job Order status
            JobOrder::where('id', $request->job_order_id)->update(['job_card_number' => $request->job_card_number,'status_id' => 12220, 'floor_supervisor_id' => $request->floor_supervisor_id, 'updated_by_id' => Auth::user()->id, 'updated_at' => Carbon::now()]);

            //Update Gatelog
            GateLog::where('job_order_id', $request->job_order_id)->update(['floor_supervisor_id' => $request->floor_supervisor_id,'updated_at' => Carbon::now()]);

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

            $estimate_file_name = $request->job_order_id . '_estimate.pdf';
            $directoryPath = storage_path('app/public/gigo/pdf/' . $estimate_file_name);
            if (!file_exists($directoryPath)) {
                $generate_estimate_pdf = JobOrder::generateEstimatePDF($request->job_order_id);

                if (!$generate_estimate_pdf) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Validation Error',
                        'errors' => ['Something went on Server.Please Try again later!!'],
                    ]);
                }
            }

            DB::commit();

            //PUSH NOTIFCATION
            $title = 'JobCard List';
            $message = 'Vehicle Inward Completed! Waiting for Bay Allocation';

            $notifications['notification_type'] = 'PUSH';
            $notifications['data'] = ['title' => $title, 'message' => $message, 'redirection_id' => 2, 'vehicle_data' => null, 'outlet_id' => $job_card->outlet_id];

            Notification::dispatch($notifications);

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
    public function getBayFormData(Request $r)
    {
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
                ->orderBy('display_order')
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

    //Bay Save
    public function saveBay(Request $request)
    {
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
                    $bay->job_order_id = null;
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

            //UPDATE GATE LOG FLOOR Supervisor
            $job_order->gateLog()->update(['floor_supervisor_id' => $request->floor_supervisor_id]);

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
    public function getBayViewData(Request $r)
    {
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

    //PDF View
    public function getPdf(Request $r)
    {
        // dd($r->all());
        try {
            $job_card = JobCard::with([
                'gatePasses',
                'gatePasses.gatePassDetail',
                'gatePasses.gatePassDetail.vendor',
                'gatePasses.gatePassDetail.vendorType',
                'jobOrder',
                'jobOrder.vehicle',
                'jobOrder.vehicle.model',
                'status',
            ])->find($r->id);

            if (!$job_card) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation Error',
                    'errors' => ['Job Card Not Found!'],
                ]);
            }

            //Check Estimate PDF Available or not
            $directoryPath = storage_path('app/public/gigo/pdf/' . $job_card->jobOrder->id . '_estimate.pdf');
            if (file_exists($directoryPath)) {
                $job_card->estimate_pdf = url('storage/app/public/gigo/pdf/' . $job_card->jobOrder->id . '_estimate.pdf');
            } else {
                $job_card->estimate_pdf = '';
            }

            //Check RevisedEstimate PDF Available or not
            $directoryPath = storage_path('app/public/gigo/pdf/' . $job_card->jobOrder->id . '_revised_estimate.pdf');
            if (file_exists($directoryPath)) {
                $job_card->revised_estimate_pdf = url('storage/app/public/gigo/pdf/' . $job_card->jobOrder->id . '_revised_estimate.pdf');
            } else {
                $job_card->revised_estimate_pdf = '';
            }

            //Check Labour PDF Available or not
            $directoryPath = storage_path('app/public/gigo/pdf/' . $job_card->id . '_labour_invoice.pdf');
            if (file_exists($directoryPath)) {
                $job_card->labour_pdf = url('storage/app/public/gigo/pdf/' . $job_card->id . '_labour_invoice.pdf');
            } else {
                $job_card->labour_pdf = '';
            }

            //Check Parts PDF Available or not
            $directoryPath = storage_path('app/public/gigo/pdf/' . $job_card->id . '_part_invoice.pdf');
            if (file_exists($directoryPath)) {
                $job_card->parts_pdf = url('storage/app/public/gigo/pdf/' . $job_card->id . '_part_invoice.pdf');
            } else {
                $job_card->parts_pdf = '';
            }

            //Check Covering Letter PDF Available or not
            $directoryPath = storage_path('app/public/gigo/pdf/' . $job_card->jobOrder->id . '_covering_letter.pdf');
            if (file_exists($directoryPath)) {
                $job_card->covering_letter_pdf = url('storage/app/public/gigo/pdf/' . $job_card->jobOrder->id . '_covering_letter.pdf');
            } else {
                $job_card->covering_letter_pdf = '';
            }

            //Check GatePass PDF Available or not
            $directoryPath = storage_path('app/public/gigo/pdf/' . $job_card->jobOrder->id . '_gatepass.pdf');
            if (file_exists($directoryPath)) {
                $job_card->gate_pass_pdf = url('storage/app/public/gigo/pdf/' . $job_card->jobOrder->id . '_gatepass.pdf');
            } else {
                $job_card->gate_pass_pdf = '';
            }

            //Check Inspection PDF Available or not
            $directoryPath = storage_path('app/public/gigo/pdf/' . $job_card->jobOrder->id . '_inward_inspection.pdf');
            if (file_exists($directoryPath)) {
                $job_card->inspection_pdf = url('storage/app/public/gigo/pdf/' . $job_card->jobOrder->id . '_inward_inspection.pdf');
            } else {
                $job_card->inspection_pdf = '';
            }

            //Check Invoice PDF Available or not
            $directoryPath = storage_path('app/public/gigo/pdf/' . $job_card->jobOrder->id . '_invoice.pdf');
            if (file_exists($directoryPath)) {
                $job_card->invoice_pdf = url('storage/app/public/gigo/pdf/' . $job_card->jobOrder->id . '_invoice.pdf');
            } else {
                $job_card->invoice_pdf = '';
            }

             //Check Inventory PDF Available or not
            $directoryPath = storage_path('app/public/gigo/pdf/' . $job_card->jobOrder->id . '_inward_inventory.pdf');
            if (file_exists($directoryPath)) {
                $job_card->inventory_pdf = url('storage/app/public/gigo/pdf/' . $job_card->jobOrder->id . '_inward_inventory.pdf');
            } else {
                $job_card->inventory_pdf = '';
            }

             //Check Manual JO PDF Available or not
            $directoryPath = storage_path('app/public/gigo/pdf/' . $job_card->jobOrder->id . '_manual_job_order.pdf');
            if (file_exists($directoryPath)) {
                $job_card->manual_job_order_pdf = url('storage/app/public/gigo/pdf/' . $job_card->jobOrder->id . '_manual_job_order.pdf');
            } else {
                $job_card->manual_job_order_pdf = '';
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

    public function getOrderViewData(Request $r)
    {
        //dd($r->all());
        try {

            $customer_paid_type_id = SplitOrderType::where('paid_by_id', '10013')->pluck('id')->toArray();

            $job_card = JobCard::with([
                'status',
                'outlet',
                'jobOrder',
                'jobOrder.vehicle',
                'jobOrder.vehicle.currentOwner.customer',
                'jobOrder.vehicle.currentOwner.customer.primaryAddress',
                'jobOrder.vehicle.model',
                'jobOrder.jobOrderRepairOrders' => function ($q) use ($customer_paid_type_id) {
                    $q->whereNull('removal_reason_id');
                },
                'jobOrder.jobOrderRepairOrders.repairOrder',
                'jobOrder.jobOrderRepairOrders.repairOrder.taxCode',
                'jobOrder.jobOrderRepairOrders.repairOrder.taxCode.taxes',
                'jobOrder.jobOrderParts' => function ($q) use ($customer_paid_type_id) {
                    $q->whereNull('removal_reason_id');
                    // $q->whereIn('split_order_type_id', $customer_paid_type_id)->whereNull('removal_reason_id');
                },
                'jobOrder.jobOrderParts.part',
                'jobOrder.jobOrderParts.part.taxCode',
                'jobOrder.jobOrderParts.part.taxCode.taxes',
                'jobOrder.type',
                'jobOrder.quoteType',
                'jobOrder.serviceType',
                'jobOrder.status',
                'jobOrder.gateLog',
                'jobOrder.customerVoices',
                'jobOrder.roadTestDoneBy',
                'jobOrder.roadTestPreferedBy',
                'jobOrder.roadTestPreferedBy.employee',
                'outlet',
                'bay',
            ])
                ->find($r->id);

            if (!$job_card) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation Error',
                    'errors' => ['Job Card Not Found!'],
                ]);
            }

            $parts_amount = 0;
            $labour_amount = 0;
            $total_amount = 0;

            if ($job_card->jobOrder->vehicle->currentOwner->customer->primaryAddress) {
                //Check which tax applicable for customer
                if ($job_card->outlet->state_id == $job_card->jobOrder->vehicle->currentOwner->customer->primaryAddress->state_id) {
                    $tax_type = 1160; //Within State
                } else {
                    $tax_type = 1161; //Inter State
                }
            } else {
                $tax_type = 1160; //Within State
            }

            //Count Tax Type
            $taxes = Tax::whereIn('id', [1, 2, 3])->get();

            $labour_details = array();
            if ($job_card->jobOrder->jobOrderRepairOrders) {
                foreach ($job_card->jobOrder->jobOrderRepairOrders as $key => $labour) {
                    if ($labour->is_free_service != 1 && (in_array($labour->split_order_type_id, $customer_paid_type_id) || !$labour->split_order_type_id)) {
                        $total_amount = 0;
                        $labour_details[$key]['name'] = $labour->repairOrder->name;
                        $labour_details[$key]['hsn_code'] = $labour->repairOrder->taxCode ? $labour->repairOrder->taxCode->code : '-';
                        $labour_details[$key]['qty'] = $labour->qty;
                        $labour_details[$key]['amount'] = $labour->amount;
                        $labour_details[$key]['is_free_service'] = $labour->is_free_service;
                        $labour_details[$key]['estimate_order_id'] = $labour->estimate_order_id;
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

                        $total_amount = $tax_amount + $labour->amount;
                        $total_amount = number_format((float) $total_amount, 2, '.', '');
                        $labour_amount += $total_amount;
                        $labour_details[$key]['tax_values'] = $tax_values;
                        $labour_details[$key]['total_amount'] = $total_amount;
                    }
                }
            }

            $part_details = array();
            if ($job_card->jobOrder->jobOrderParts) {
                foreach ($job_card->jobOrder->jobOrderParts as $key => $parts) {
                    if ($parts->is_free_service != 1 && (in_array($parts->split_order_type_id, $customer_paid_type_id) || !$parts->split_order_type_id)) {
                        $total_amount = 0;
                        $part_details[$key]['name'] = $parts->part->name;
                        $part_details[$key]['hsn_code'] = $parts->part->taxCode ? $parts->part->taxCode->code : '-';
                        $part_details[$key]['qty'] = $parts->qty;
                        $part_details[$key]['rate'] = $parts->rate;
                        $part_details[$key]['amount'] = $parts->amount;
                        $part_details[$key]['is_free_service'] = $parts->is_free_service;
                        $part_details[$key]['estimate_order_id'] = $parts->estimate_order_id;
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

                        $total_amount = $tax_amount + $parts->amount;
                        $total_amount = number_format((float) $total_amount, 2, '.', '');
                        $parts_amount += $total_amount;
                        $part_details[$key]['tax_values'] = $tax_values;
                        $part_details[$key]['total_amount'] = $total_amount;
                    }
                }
            }

            $total_amount = $parts_amount + $labour_amount;
            $total_amount = round($total_amount);

            $extras = [
                'taxes' => $taxes,
                'part_details' => $part_details,
                'labour_details' => $labour_details,
                'tax_count' => count($taxes),
                'parts_total_amount' => number_format($parts_amount, 2),
                'labour_total_amount' => number_format($labour_amount, 2),
                'total_amount' => number_format($total_amount, 2),
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

    //FLOATING WORK
    public function floatingWorkFormData(Request $r)
    {
        // dd($r->all());
        try {
            //JOB CARD
            $job_card = JobCard::with([
                'status',
                'jobOrder',
                'bay',
                'jobOrder.vehicle',
                'jobOrder.vehicle.model',
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

            $job_card->floating_parts = $floating_parts = FloatingGatePass::join('floating_stocks', 'floating_stocks.id', 'floating_stock_logs.floating_stock_id')
                ->join('parts', 'parts.id', 'floating_stocks.part_id')
                ->join('users', 'floating_stock_logs.issued_to_id', 'users.id')
                ->join('configs', 'configs.id', 'floating_stock_logs.status_id')
                ->where('floating_stock_logs.job_card_id', $r->id)
                ->select('parts.code', 'parts.name', 'users.name as mechanic', 'floating_stock_logs.qty as qty', 'floating_stock_logs.id', 'floating_stock_logs.status_id', 'floating_stock_logs.number', DB::raw('DATE_FORMAT(floating_stock_logs.created_at,"%d/%m/%Y") as date'), 'floating_stock_logs.inward_date', 'floating_stock_logs.status_id', 'floating_stock_logs.outward_date', 'configs.name as status_name')
                ->get();

            $job_card->floating_part_confirmation_status = FloatingGatePass::where('floating_stock_logs.job_card_id', $r->id)->where('status_id', 11160)->count();

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

    public function updateFloatingGatePassStatus(Request $request)
    {
        // dd($request->all());
        try {
            if ($request->type == 2) {
                $validator = Validator::make($request->all(), [
                    'id' => [
                        'required',
                        'integer',
                        'exists:floating_stock_logs,id',
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

                $floating_gatepass = FloatingGatePass::where('id', $request->id)->update(['status_id' => 11163, 'updated_by_id' => Auth::user()->id, 'updated_at' => Carbon::now()]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Floating Part Updated Successfully!!',
                ]);
            } else {
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

                $floating_gatepass = FloatingGatePass::where('job_card_id', $request->id)->where('status_id', 11160)
                    ->update(['status_id' => 11161, 'updated_by_id' => Auth::user()->id, 'updated_at' => Carbon::now()]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Floating GatePass Updated Successfully!!',
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

    //SCHEDULE
    public function LabourAssignmentFormData(Request $r)
    {
        // dd($r->all());
        try {

            //$osl_work_ids = JobOrderRepairOrder::join('repair_orders', 'repair_orders.id', 'job_order_repair_orders.repair_order_id')->join('job_cards', 'job_cards.job_order_id', 'job_order_repair_orders.job_order_id')->where('repair_orders.is_editable', 1)->where('job_cards.id', $r->id)->pluck('job_order_repair_orders.id')->toArray();

            //if (!$osl_work_ids) {
            $osl_work_ids = [];
            //}

            //JOB CARD
            $job_card = JobCard::with([
                'status',
                'bay',
                'jobOrder',
                'jobOrder.vehicle',
                'jobOrder.vehicle.model',
                'jobOrder.jobOrderRepairOrders' => function ($q) use ($osl_work_ids) {
                    $q->whereNull('removal_reason_id')->whereNotIn('id', $osl_work_ids);
                },
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

    public function getMechanicTimeLog(Request $request)
    {
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

    public function getMechanic(Request $request)
    {
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
                DB::RAW('CONCAT(employees.code, " / ",users.name) as user_name'),
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
                ->where('employees.outlet_id', $job_card->outlet_id)
                ->orWhere('employees.deputed_outlet_id', $job_card->outlet_id)
                ->orderBy('users.name', 'asc');

            if ($repair_order->skill_level_id) {
                $employee_details = $employee_details->where('employees.skill_level_id', $repair_order->skill_level_id);
            }

            $employee_details = $employee_details->get();

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

    public function saveMechanic(Request $request)
    {
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
                // 'selected_mechanic_ids' => [
                //     'required',
                //     'string',
                // ],
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

            if ($request->selected_mechanic_ids) {
                $mechanic_ids = explode(',', $request->selected_mechanic_ids);
            } else {
                $mechanic_ids = [];
            }
            // dd($mechanic_ids);

            $repair_order = JobOrderRepairOrder::join('job_cards', 'job_cards.job_order_id', 'job_order_repair_orders.job_order_id')->where('job_order_repair_orders.repair_order_id', $request->repair_order_id)->where('job_cards.id', $request->job_card_id)->select('job_order_repair_orders.id')->first();

            $job_order_repair_order = JobOrderRepairOrder::find($repair_order->id);

            if (!$job_order_repair_order) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation Error',
                    'errors' => [
                        'Job Order Repair Order Not Found!',
                    ],
                ]);
            }

            DB::beginTransaction();

            if (count($mechanic_ids) > 0) {
                $repair_order_mechanic_remove = RepairOrderMechanic::where('job_order_repair_order_id', $job_order_repair_order->id)->whereNotIn('mechanic_id', $mechanic_ids)->forceDelete();

                foreach ($mechanic_ids as $mechanic_id) {
                    $repair_order_mechanic = RepairOrderMechanic::firstOrNew([
                        'job_order_repair_order_id' => $job_order_repair_order->id,
                        'mechanic_id' => $mechanic_id,
                    ]);
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
                }

                $job_order_repair_order->status_id = 8182; //WORK PENDING
                $job_order_repair_order->updated_by_id = Auth::user()->id;
                $job_order_repair_order->updated_at = Carbon::now();
                $job_order_repair_order->save();

                $message = 'Mechanic assigned successfully!!';
            } else {
                //Check any mechanics start work
                $mechanics = RepairOrderMechanic::where('job_order_repair_order_id', $job_order_repair_order->id)->where('status_id', '!=', '8260')->count();

                if ($mechanics > 0) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Validation Error',
                        'errors' => [
                            'Some Mechanics start their work.Kindly unselect others!',
                        ],
                    ]);
                }

                //Remove Mechanics
                $repair_order_mechanic_remove = RepairOrderMechanic::where('job_order_repair_order_id', $job_order_repair_order->id)->forceDelete();

                $job_order_repair_order->status_id = 8181; //Mechanic Not Assigned
                $job_order_repair_order->updated_by_id = Auth::user()->id;
                $job_order_repair_order->updated_at = Carbon::now();
                $job_order_repair_order->save();

                $message = 'Mechanic unassigned successfully!!';
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

    public function LabourAssignmentFormSave(Request $request)
    {
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

    //Labour Review form data
    public function getLabourReviewData(Request $request)
    {
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
                'labourReviewAttachment',
                'repairOrder',
                'repairOrderMechanics',
                'fault',
                'splitOrderType',
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

    public function LabourReviewSave(Request $request)
    {
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

            if ($request->complaint_id) {
                $validator = Validator::make($request->all(), [
                    'complaint_id' => [
                        'required',
                        'integer',
                        'exists:complaints,id',
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
            }

            if ($request->fault_id) {
                $validator = Validator::make($request->all(), [
                    'fault_id' => [
                        'required',
                        'integer',
                        'exists:faults,id',
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
            }

            DB::beginTransaction();

            //UPDATE JOB CARD STATUS
            // $job_card = JobCard::where('id', $job_card->id)
            //     ->update([
            //         'status_id' => 8223, //Ready for Billing
            //         'updated_by' => Auth::user()->id,
            //         'updated_at' => Carbon::now(),
            //     ]);

            $job_order_repair_order = JobOrderRepairOrder::find($request->job_order_repair_order_id);
            $job_order_repair_order->fill($request->all());
            $job_order_repair_order->updated_at = Carbon::now();
            $job_order_repair_order->updated_by_id = Auth::user()->id;
            if ($request->complaint_id) {
                $job_order_repair_order->complaint_id = $request->complaint_id;
            } else {
                $job_order_repair_order->complaint_id = null;
            }

            if ($request->fault_id) {
                $job_order_repair_order->fault_id = $request->fault_id;
            } else {
                $job_order_repair_order->fault_id = null;
            }

            
            //Change Mechnanic status completed into rework
            if ($request->status_id == 8186) {
                // $mechnic = RepairOrderMechanic::where('job_order_repair_order_id', $request->job_order_repair_order_id)
                //     ->update([
                    //         'status_id' => 8264, //Rework
                    //         'updated_by_id' => Auth::user()->id,
                    //         'updated_at' => Carbon::now(),
                    //     ]);
                $job_order_repair_order->status_id = 8186;
            }
            $job_order_repair_order->save();
            
            if ($request->status_id == 8187) {
                
                $job_order_repair_order->status_id = 8187;
                $job_order_repair_order->save();

                //Check OSL Work
                $osl_work_id = JobOrderRepairOrder::join('repair_orders', 'repair_orders.id', 'job_order_repair_orders.repair_order_id')->where('repair_orders.is_editable', 1)->where('job_order_repair_orders.job_order_id', $job_order_repair_order->job_order_id)->pluck('job_order_repair_orders.id')->toArray();

                if (count($osl_work_id) > 0) {
                    $total_count = JobOrderRepairOrder::where('job_order_id', $job_order_repair_order->job_order_id)->whereNull('removal_reason_id')->where('status_id', '!=', 8187)->whereNotIn('id', $osl_work_id)->count();
                } else {
                    $total_count = JobOrderRepairOrder::where('job_order_id', $job_order_repair_order->job_order_id)->whereNull('removal_reason_id')->where('status_id', '!=', 8187)->count();
                }

                if ($total_count == 0) {
                    $job_card = JobCard::where('id', $request->job_card_id)
                        ->update([
                            'status_id' => 8223, //Review Completed
                            'updated_by' => Auth::user()->id,
                            'updated_at' => Carbon::now(),
                        ]);
                }

                //MULTIPLE ATTACHMENT REMOVAL
                $attachment_removal_ids = json_decode($request->attachment_removal_ids);
                if (!empty($attachment_removal_ids)) {
                    Attachment::whereIn('id', $attachment_removal_ids)->forceDelete();
                }

                //Save Labour Review Attachment
                if (!empty($request->review_attachments)) {
                    foreach ($request->review_attachments as $key => $review_attachment) {
                        $value = rand(1, 100);
                        $image = $review_attachment;

                        $file_name_with_extension = $image->getClientOriginalName();
                        $file_name = pathinfo($file_name_with_extension, PATHINFO_FILENAME);
                        $extension = $image->getClientOriginalExtension();
                        $time_stamp = date('Y_m_d_h_i_s');
                        $name = $job_order_repair_order->id . '_' . $time_stamp . '_' . rand(10, 1000) . '_labour_review_image.' . $extension;

                        $review_attachment->move(storage_path('app/public/gigo/job_order/attachments/'), $name);
                        $attachement = new Attachment;
                        $attachement->attachment_of_id = 227; //Job order
                        $attachement->attachment_type_id = 10096; //Labour Review Attachment
                        $attachement->entity_id = $job_order_repair_order->id;
                        $attachement->name = $name;
                        $attachement->save();
                    }
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

    public function mechanicReschedule(Request $request){
        // dd($request->all());
        try {
            $validator = Validator::make($request->all(), [
                'job_order_id' => [
                    'required',
                    'integer',
                    'exists:job_orders,id',
                ],
                'job_card_id' => [
                    'required',
                    'integer',
                    'exists:job_cards,id',
                ],
                'repair_order_mechanic_id' => [
                    'required',
                    'integer',
                    'exists:repair_order_mechanics,id',
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

            //Update Mechanic Status
            $mechnic = RepairOrderMechanic::where('id', $request->repair_order_mechanic_id)->first();
            $mechanic->status_id = 8264;
            $mechanic->updated_at = Carbon::now();
            $mechanic->updated_by_id = Auth::user()->id;
            $mechanic->save();

            //Update Repair Order Status
            $job_order_repair_order = JobOrderRepairOrder::find($mechnic->job_order_repair_order_id);
            $job_order_repair_order->status_id = 8186;
            $job_order_repair_order->updated_at = Carbon::now();
            $job_order_repair_order->updated_by_id = Auth::user()->id;
            $job_order_repair_order->save();
            
            $job_card = JobCard::where('id', $request->job_card_id)
                ->update([
                    'status_id' => 8221, //Work In Progress
                    'updated_by' => Auth::user()->id,
                    'updated_at' => Carbon::now(),
                ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Mechanic Rescheduled Successfully!!',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Server Network Down!',
                'errors' => ['Exception Error' => $e->getMessage()],
            ]);
        }
    }
    public function updateJobCardStatus(Request $request)
    {
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

            $job_card = JobCard::find($request->id);

            if (!$job_card) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation Error',
                    'errors' => ['Job Card Not Found!'],
                ]);
            }

            DB::beginTransaction();

            if ($request->type == 2) {
                //Split Order Confirmation
                $job_card->status_id = 8224; //Ready for Billing

                //Check Customer Paid Labour/Parts availbale
                $customer_paid_type_id = SplitOrderType::where('paid_by_id', '10013')->pluck('id')->toArray();

                // dump($customer_paid_type_id);
                $labour_count = JobOrderRepairOrder::where('job_order_id', $job_card->job_order_id)->whereNull('removal_reason_id')->whereIn('split_order_type_id', $customer_paid_type_id)->count();

                $parts_count = JobOrderPart::where('job_order_id', $job_card->job_order_id)->whereNull('removal_reason_id')->whereIn('split_order_type_id', $customer_paid_type_id)->count();

                if ($labour_count == 0 && $parts_count == 0) {

                    $job_card->status_id = 8226; //Job Card Completed

                    //Generate GatePass
                    $gate_log = GateLog::where('job_order_id', $job_card->job_order_id)->first();

                    $gate_pass = GatePass::firstOrNew(['job_order_id' => $job_card->job_order_id, 'job_card_id' => $job_card->id, 'type_id' => 8280]); //VEHICLE GATE PASS

                    $gate_pass->gate_pass_of_id = 11281;
                    $gate_pass->entity_id = $job_card->id;

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

                                if (!$gate_pass->exists) {
                                    $gate_pass->updated_at = Carbon::now();
                                } else {
                                    $gate_pass->created_at = Carbon::now();
                                }

                                $gate_pass->company_id = $gate_log->company_id;
                                $gate_pass->number = $generateNumber['number'];
                                $gate_pass->status_id = 8340; //GATE OUT PENDING
                                $gate_pass->save();

                                $gate_log->gate_pass_id = $gate_pass->id;
                                $gate_log->status_id = 8123; //GATE OUT PENDING
                                $gate_log->save();
                            }

                            $generate_estimate_pdf = JobCard::generateGatePassPDF($job_card->id, $type = 'GateIn');
                            $generate_covering_pdf = JobCard::generateCoveringLetterPDF($job_card->id, $type = 'GateIn');
                        }
                    }

                }
            } else {
                //Work Completed Confirmation
                //Check All material items returned or not
                $material = GatePass::where('job_card_id', $request->id)->whereIn('status_id', [8300, 8301, 8302, 8303])->count();
                if ($material > 0) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Validation Error',
                        'errors' => ['Some OSL works are not completed!'],
                    ]);
                }

                //Check Floating Gatepass
                $floating_gate_pass = FloatingGatePass::where('job_card_id', $request->id)->whereIn('status_id', [11160, 11161, 11162])->count();
                if ($floating_gate_pass > 0) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Validation Error',
                        'errors' => ['Floating Parts are not returned!'],
                    ]);
                }

                // //Check Parts Requsted or not
                // $job_order_parts = JobOrderPart::where('job_order_id', $job_card->job_order_id)->whereNull('removal_reason_id')->count();
                // if ($job_order_parts > 0) {
                $job_card->status_id = 8227; //Waiting for Parts Confirmation
                // } else {
                //     $job_card->status_id = 8231; //Ready for Split Order
                // }

                $job_card->work_completed_at = Carbon::now();

                //Generate Inspection PDF
                $generate_estimate_inspection_pdf = JobOrder::generateInspectionPDF($job_card->job_order_id);
            }

            $job_card->updated_by = Auth::user()->id;
            $job_card->updated_at = Carbon::now();
            $job_card->save();

            //Bay Free
            Bay::where('job_order_id', $job_card->job_order_id)
                ->update([
                    'status_id' => 8240, //Free
                    'job_order_id' => null, //Free
                    'updated_by_id' => Auth::user()->id,
                    'updated_at' => Carbon::now(),
                ]);

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

    public function sendCustomerApproval(Request $request)
    {
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
                        'Financial Year Not Found',
                    ],
                ]);
            }

            $customer_mobile = $job_card->jobOrder->contact_number;
            $vehicle_no = $job_card->jobOrder->vehicle->registration_number;

            if (!$customer_mobile) {
                return response()->json([
                    'success' => false,
                    'error' => 'Customer Mobile Number Not Found',
                ]);
            }

            $customer_paid_type_id = SplitOrderType::where('paid_by_id', '10013')->pluck('id')->toArray();

            $job_order = JobOrder::with([
                'customer',
                'customerAddress',
                'jobOrderRepairOrders' => function ($q) use ($customer_paid_type_id) {
                    $q->whereIn('split_order_type_id', $customer_paid_type_id)->where('is_free_service', '!=', 1)->whereNull('removal_reason_id');
                },
                // 'jobOrderRepairOrders.repairOrder',
                // 'jobOrderRepairOrders.repairOrder.taxCode',
                // 'jobOrderRepairOrders.repairOrder.taxCode.taxes',
                'jobOrderParts' => function ($q) use ($customer_paid_type_id) {
                    $q->whereIn('split_order_type_id', $customer_paid_type_id)->where('is_free_service', '!=', 1)->whereNull('removal_reason_id');
                },
                // 'jobOrderParts.part',
                // 'jobOrderParts.part.taxCode',
                // 'jobOrderParts.part.taxCode.taxes',
            ])
                ->find($job_card->job_order_id);

            if (!$job_order) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation Error',
                    'errors' => ['Job Order Not Found!'],
                ]);
            }

            $address = Address::find($job_order->address_id);
            if (!$address) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation Error',
                    'errors' => [
                        'Address Not Found!',
                    ],
                ]);
            }

            //GET BRANCH/OUTLET
            $outlet = $branch = Outlet::where('id', $job_card->outlet_id)->first();
            if (!$outlet) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation Error',
                    'errors' => [
                        'Outlet Not Found!',
                    ],
                ]);
            }

            DB::beginTransaction();

            $total_inv_amount = 0;

            if ($address) {
                //Check which tax applicable for customer
                if ($job_order->outlet->state_id == $address->state_id) {
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
            if ($job_order->jobOrderRepairOrders) {
                $i = 1;
                foreach ($job_order->jobOrderRepairOrders as $key => $labour) {
                    $total_amount = 0;
                    $tax_amount = 0;
                    $cgst_percentage = 0;
                    $sgst_percentage = 0;
                    $igst_percentage = 0;

                    // dd($labour);
                    // dd($labour->repairOrder->taxCode,$labour->repairOrder->taxCode->taxes);
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
            if ($job_order->jobOrderParts) {
                foreach ($job_order->jobOrderParts as $key => $parts) {

                    $qty = $parts->qty;
                    //Issued Qty
                    $issued_qty = JobOrderIssuedPart::where('job_order_part_id', $parts->id)->sum('issued_qty');
                    //Returned Qty
                    $returned_qty = JobOrderReturnedPart::where('job_order_part_id', $parts->id)->sum('returned_qty');

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
                        // $item['Unit'] = $parts->part->uom ? $parts->part->uom->name : "NOS";
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

            $errors = [];
            //QR Code Generate
            if ($job_order->e_invoice_registration == 1) {
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
                    'entity_number' => $job_order->number,
                    'entity_id' => $job_order->id,
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
                            "No" => $job_order->number,
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
                            "LglNm" => $job_order ? $job_order->customer->name : 'N/A',
                            "TrdNm" => $job_order ? $job_order->customer->name : null,
                            "Pos" => $address ? ($address->state ? $address->state->e_invoice_state_code : 'N/A') : 'N/A',
                            "Loc" => $address ? ($address->state ? $address->state->name : 'N/A') : 'N/A',

                            "Addr1" => $address ? preg_replace('/\r|\n|:|"/', ",", $address->address_line1) : 'N/A',
                            "Addr2" => $address ? preg_replace('/\r|\n|:|"/', ",", $address->address_line2) : null,
                            "Stcd" => $address ? ($address->state ? $address->state->e_invoice_state_code : null) : null,
                            "Pin" => $address ? $address->pincode : null,
                            "Ph" => $job_order->customer->mobile_no ? $job_order->customer->mobile_no : null,
                            "Em" => $job_order->customer->email ? $job_order->customer->email : null,
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

                // dd($json_encoded_data);

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
                    'entity_number' => $job_order->number,
                    'entity_id' => $job_order->id,
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

                $IRN_images_des = storage_path('app/public/gigo/job_order/IRN_images');
                File::makeDirectory($IRN_images_des, $mode = 0777, true, true);

                $qr_images_des = storage_path('app/public/gigo/job_order/qr_images');
                File::makeDirectory($qr_images_des, $mode = 0777, true, true);

                $url = QRCode::text($final_json_decode->SignedQRCode)->setSize(4)->setOutfile('storage/app/public/gigo/job_order/IRN_images/' . $job_order->number . '.png')->png();

                $qr_attachment_path = base_path("storage/app/public/gigo/job_order/IRN_images/" . $job_order->number . '.png');
                if (file_exists($qr_attachment_path)) {
                    $ext = pathinfo(base_path("storage/app/public/gigo/job_order/IRN_images/" . $job_order->number . '.png'), PATHINFO_EXTENSION);
                    if ($ext == 'png') {
                        $image = imagecreatefrompng($qr_attachment_path);
                        $bg = imagecreatetruecolor(imagesx($image), imagesy($image));
                        imagefill($bg, 0, 0, imagecolorallocate($bg, 255, 255, 255));
                        imagealphablending($bg, true);
                        imagecopy($bg, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
                        $quality = 70; // 0 = worst / smaller file, 100 = better / bigger file
                        imagejpeg($bg, 'storage/app/public/gigo/job_order/qr_images/' . $job_order->number . '.jpg', 100);

                        if (File::exists('storage/app/public/gigo/job_order/qr_images/' . $job_order->number . '.png')) {
                            File::delete('storage/app/public/gigo/job_order/qr_images/' . $job_order->number . '.png');
                        }

                        $qr_image = $job_order->number . '.jpg';
                    }
                } else {
                    $qr_image = '';
                }

                $get_version = json_decode($final_json_decode->Invoice);
                $get_version = json_decode($get_version->data);

                $job_order_e_invoice = JobOrderEInvoice::firstOrNew(['job_order_id' => $job_order->id]);
                if ($job_order_e_invoice->exist) {
                    $job_order_e_invoice->updated_by_id = Auth::user()->id;
                    $job_order_e_invoice->updated_at = Carbon::now();
                } else {
                    $job_order_e_invoice->created_by_id = Auth::user()->id;
                    $job_order_e_invoice->created_at = Carbon::now();
                    $job_order_e_invoice->updated_at = null;
                }
                $job_order_e_invoice->e_invoice_registration = 1;
                $job_order_e_invoice->irn_number = $final_json_decode->Irn;
                $job_order_e_invoice->qr_image = $job_order->number . '.jpg';
                $job_order_e_invoice->ack_no = $final_json_decode->AckNo;
                $job_order_e_invoice->ack_date = $final_json_decode->AckDt;
                $job_order_e_invoice->version = $get_version->Version;
                $job_order_e_invoice->irn_request = $json_encoded_data;
                $job_order_e_invoice->irn_response = $irn_decrypt_data;
                $job_order_e_invoice->errors = empty($errors) ? null : json_encode($errors);
                $job_order_e_invoice->save();
            } else {
                $qrPaymentApp = QRPaymentApp::where([
                    'name' => 'GIGO',
                ])->first();
                if (!$qrPaymentApp) {
                    return [
                        'success' => false,
                        'errors' => 'QR Payment App not found : GIGO',
                    ];
                    $errors[] = 'QR Payment App not found : GIGO';
                }

                $base_url_with_invoice_details = url(
                    '/pay' .
                    '?invNo=' . $job_order->number .
                    '&date=' . date('d-m-Y') .
                    '&invAmt=' . str_replace(',', '', $total_inv_amount) .
                    '&oc=' . $job_order->outlet->code .
                    '&cc=' . $job_order->customer->code .
                    '&cgst=' . $cgst_total .
                    '&sgst=' . $sgst_total .
                    '&igst=' . $igst_total .
                    '&cess=' . $cess_on_gst_total .
                    '&appCode=' . $qrPaymentApp->app_code
                );

                $B2C_images_des = storage_path('app/public/gigo/job_order/qr_images');
                File::makeDirectory($B2C_images_des, $mode = 0777, true, true);

                $url = QRCode::URL($base_url_with_invoice_details)->setSize(4)->setOutfile('storage/app/public/gigo/job_order/qr_images/' . $job_order->number . '.png')->png();

                $qr_attachment_path = base_path("storage/app/public/gigo/job_order/qr_images/" . $job_order->number . '.png');

                if (file_exists($qr_attachment_path)) {
                    $ext = pathinfo(base_path("storage/app/public/gigo/job_order/qr_images/" . $job_order->number . '.png'), PATHINFO_EXTENSION);
                    if ($ext == 'png') {
                        $image = imagecreatefrompng($qr_attachment_path);
                        $bg = imagecreatetruecolor(imagesx($image), imagesy($image));
                        imagefill($bg, 0, 0, imagecolorallocate($bg, 255, 255, 255));
                        imagealphablending($bg, true);
                        imagecopy($bg, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));

                        imagejpeg($bg, 'storage/app/public/gigo/job_order/qr_images/' . $job_order->number . '.jpg', 100);

                        if (File::exists('storage/app/public/gigo/job_order/qr_images/' . $job_order->number . '.png')) {
                            File::delete('storage/app/public/gigo/job_order/qr_images/' . $job_order->number . '.png');
                        }
                    }
                }

                $job_order->qr_image = $job_order->number . '.jpg';
            }

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

                $this->saveGigoInvoice($params);
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

                $this->saveGigoInvoice($params);
            }

            //Overall invoice
            $invoice_pdf = JobCard::generateInvoicePDF($job_card->id);

            $job_card->status_id = 8225; //Waiting for Customer Payment
            $job_card->save();

            $job_order->otp_no = mt_rand(111111, 999999);
            $job_order->updated_by_id = Auth::user()->id;
            $job_order->updated_at = Carbon::now();
            $job_order->save();

            $url = url('/') . '/jobcard/bill-details/view/' . $job_order->id . '/' . $job_order->otp_no;

            $short_url = ShortUrl::createShortLink($url, $maxlength = "7");

            $message = 'Dear Customer, Kindly click on this link to pay for the TVS job order ' . $short_url . ' Vehicle Reg Number : ' . $vehicle_no . ' - TVS';

            $msg = sendOTPSMSNotification($customer_mobile, $message);

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

    public function saveGigoInvoice($params)
    {

        DB::beginTransaction();

        $invoice = GigoInvoice::firstOrNew([
            'invoice_of_id' => $params['invoice_of_id'],
            'entity_id' => $params['job_card_id'],
        ]);
        // dump($params);
        // dd(1);
        if ($invoice->exists) {
            //FIRST
            $invoice->invoice_amount = $params['invoice_amount'];
            $invoice->balance_amount = $params['invoice_amount'];
            $invoice->updated_by_id = Auth::user()->id;
            $invoice->updated_at = Carbon::now();
        } else {
            //NEW
            $invoice->company_id = Auth::user()->company_id;
            $invoice->invoice_number = $params['invoice_number'];
            $invoice->invoice_date = date('Y-m-d');
            $invoice->customer_id = $params['customer_id'];
            $invoice->invoice_of_id = $params['invoice_of_id']; // JOB CARD
            $invoice->entity_id = $params['job_card_id'];
            $invoice->outlet_id = $params['outlet_id'];
            $invoice->sbu_id = 54; //SERVICE ALSERV
            $invoice->invoice_amount = $params['invoice_amount'];
            $invoice->balance_amount = $params['invoice_amount'];
            $invoice->status_id = 10031; //PENDING
            $invoice->created_by_id = Auth::user()->id;
            $invoice->created_at = Carbon::now();
        }
        $invoice->save();

        if ($params['invoice_of_id'] == 7425) {
            //Generate JobCard Labour PDF
            $generate_estimate_pdf = JobCard::generateJobcardLabourPDF($params['job_card_id']);

            if (!$generate_estimate_pdf) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation Error',
                    'errors' => ['Something went on Server.Please Try again later!!'],
                ]);
            }
        }

        if ($params['invoice_of_id'] == 7426) {
            //Generate JobCard Part PDF
            $generate_estimate_pdf = JobCard::generateJobcardPartPDF($params['job_card_id']);

            if (!$generate_estimate_pdf) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation Error',
                    'errors' => ['Something went on Server.Please Try again later!!'],
                ]);
            }
        }

        DB::commit();

        return true;
    }

    public function VendorList(Request $request)
    {
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
            // dd($request->all());
            if ($request->type_id && $request->type_id == 121) {
                $type_id = [121];
            } elseif ($request->type_id && $request->type_id == 122) {
                $type_id = [122];
            } else {
                $type_id = [121, 122];
            }

            // dd($type_id);

            $VendorList = Vendor::where('code', 'LIKE', '%' . $request->vendor_code . '%')->whereIn('type_id', [$type_id])
            // ->where(function ($query) use ($type_id) {
            //     $query->whereIn('type_id', [$type_id]);
            //     // $query->where('type_id', 121)
            //     //     ->orWhere('type_id', 122);
            // })
                ->get();

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

    public function VendorDetails($vendor_id)
    {
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

    public function getRoadTestObservation(Request $request)
    {
        // dd($request->all());
        $job_card = JobCard::with(['status', 'jobOrder',
            'jobOrder.type',
            'jobOrder.vehicle',
            'jobOrder.vehicle.model',
            'jobOrder.vehicle.status',
            'jobOrder.status',
            'jobOrder.roadTestDoneBy',
            'jobOrder.roadTestPreferedBy',
            'jobOrder.gateLog',
            'jobOrder.tradePlateNumber',
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

        $trade_plate_number_list = collect(TradePlateNumber::where('status_id', 8240)->where('company_id', Auth::user()->company_id)->where('outlet_id', $job_card->outlet_id)->whereDate('insurance_validity_to', '>=', date('Y-m-d'))->select('id', 'trade_plate_number')->get())->prepend(['id' => '', 'trade_plate_number' => 'Select Trade Plate']);

        if ($request->road_test_id) {
            $road_test_gate_passes = RoadTestGatePass::with([
                'status',
                'jobOrder',
                'roadTestDoneBy',
                'roadTestPreferedBy',
                'tradePlateNumber',
            ])
                ->find($request->road_test_id);

            if ($road_test_gate_passes->trade_plate_number_id) {
                $trade_plate_number_list->push(['id' => $road_test_gate_passes->tradePlateNumber->id, 'trade_plate_number' => $road_test_gate_passes->tradePlateNumber->trade_plate_number]);
            }

        } else {
            $road_test_gate_passes = RoadTestGatePass::with([
                'status',
                'jobOrder',
                'roadTestDoneBy',
                'roadTestPreferedBy',
                'tradePlateNumber',
            ])
                ->where('job_order_id', $job_card->jobOrder->id)
                ->get();
        }

        $extras = [
            'road_test_by' => Config::getDropDownList(['config_type_id' => 36, 'add_default' => false]), //ROAD TEST DONE BY
            'user_list' => User::getUserEmployeeList(['road_test' => true]),
            'trade_plate_number_list' => $trade_plate_number_list,
        ];

        return response()->json([
            'success' => true,
            'job_card' => $job_card,
            'road_test_gate_pass' => $road_test_gate_passes,
            'extras' => $extras,
        ]);

    }

    //ROAD TEST OBSERVATION SAVE
    public function saveRoadTestObservation(Request $request)
    {
        // dd($request->all());
        DB::beginTransaction();
        try {

            if ($request->type == 1) {
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
                        'required_if:road_test_done_by_id,8101',
                        'integer',
                        'exists:users,id',
                    ],
                    'road_test_trade_plate_number_id' => [
                        'required_if:road_test_done_by_id,8101',
                        'integer',
                        'exists:trade_plate_numbers,id',
                    ],
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

                $road_test = RoadTestGatePass::where('job_order_id', $job_order->id)->where('status_id', 11141)->first();
                if ($road_test) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Validation Error',
                        'errors' => [
                            'Vehicle Road Test is Inprogress!.',
                        ],
                    ]);
                }

                if ($request->is_road_test_required == 1) {
                    if ($request->road_test_id) {
                        $road_test = RoadTestGatePass::where('id', $request->road_test_id)->first();

                        $road_test->updated_by_id = Auth::user()->id;
                        $road_test->updated_at = Carbon::now();

                        if ($road_test->trade_plate_number_id != $request->road_test_trade_plate_number_id) {
                            //Update Current Trade Plate Number Status
                            $plate_number_update = TradePlateNumber::where('id', $request->road_test_trade_plate_number_id)
                                ->update([
                                    'status_id' => 8241, //ASSIGNED
                                    'updated_by_id' => Auth::user()->id,
                                    'updated_at' => Carbon::now(),
                                ]);

                            //Update Previous Trade Plate Number Status
                            $plate_number_update = TradePlateNumber::where('id', $road_test->trade_plate_number_id)
                                ->update([
                                    'status_id' => 8240, //FREE
                                    'updated_by_id' => Auth::user()->id,
                                    'updated_at' => Carbon::now(),
                                ]);
                        }

                    } else {

                        $road_test = RoadTestGatePass::where('job_order_id', $job_order->id)->where('status_id', 11140)->first();
                        if ($road_test) {
                            return response()->json([
                                'success' => false,
                                'error' => 'Validation Error',
                                'errors' => [
                                    'Vehicle Road Test is Inprogress!.',
                                ],
                            ]);
                        }

                        //Update Current Trade Plate Number Status
                        $plate_number_update = TradePlateNumber::where('id', $request->road_test_trade_plate_number_id)
                            ->update([
                                'status_id' => 8241, //ASSIGNED
                                'updated_by_id' => Auth::user()->id,
                                'updated_at' => Carbon::now(),
                            ]);

                        $road_test = new RoadTestGatePass;

                        //Generate Serial Number
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
                        $generateNumber = SerialNumberGroup::generateNumber(105, $financial_year->id, $branch->state_id, $branch->id);
                        if (!$generateNumber['success']) {
                            return response()->json([
                                'success' => false,
                                'error' => 'Validation Error',
                                'errors' => [
                                    'No Road Test Gate Pass number found for FY : ' . $financial_year->year . ', State : ' . $branch->state->code . ', Outlet : ' . $branch->code,
                                ],
                            ]);
                        }

                        $road_test->company_id = Auth::user()->company_id;
                        $road_test->job_order_id = $job_order->id;
                        $road_test->status_id = 11140;
                        $road_test->number = $generateNumber['number'];
                        $road_test->created_by_id = Auth::user()->id;
                        $road_test->created_at = Carbon::now();
                    }

                    $road_test->trade_plate_number_id = $request->road_test_trade_plate_number_id;
                    $road_test->road_test_done_by_id = $request->road_test_done_by_id;

                    if ($request->road_test_done_by_id == 8101) {
                        // EMPLOYEE
                        $road_test->road_test_performed_by_id = $request->road_test_performed_by_id;
                    } else {
                        $road_test->road_test_performed_by_id = null;
                    }

                    $road_test->remarks = $request->road_test_report;
                    $road_test->save();
                } else {
                    if ($request->road_test_id) {
                        $road_test = RoadTestGatePass::where('id', $request->road_test_id)->first();

                        $plate_number_update = TradePlateNumber::where('id', $road_test->trade_plate_number_id)
                            ->update([
                                'status_id' => 8240, //FREE
                                'updated_by_id' => Auth::user()->id,
                                'updated_at' => Carbon::now(),
                            ]);

                        $delete_road_test = RoadTestGatePass::where('id', $request->road_test_id)->forceDelete();
                    }
                }
            } else {
                $validator = Validator::make($request->all(), [
                    'road_test_id' => [
                        'required',
                        'integer',
                        'exists:road_test_gate_pass,id',
                    ],
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Validation Error',
                        'errors' => $validator->errors()->all(),
                    ]);
                }

                $road_test = RoadTestGatePass::where('id', $request->road_test_id)->first();

                $road_test->updated_by_id = Auth::user()->id;
                $road_test->updated_at = Carbon::now();
                $road_test->remarks = $request->road_test_report;
                $road_test->status_id = 11143;
                $road_test->save();
            }

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

    public function getExpertDiagnosis(Request $request)
    {
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

        $extras = [
            'user_list' => User::getUserEmployeeList(['road_test' => false]),
        ];

        return response()->json([
            'success' => true,
            'job_card' => $job_card,
            'extras' => $extras,
        ]);
    }

    public function getDmsCheckList(Request $request)
    {
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
            'jobOrder.amcMember',
            'jobOrder.amcMember.amcPolicy',
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

    public function compaigns($namespaceModel, $job_order, $type)
    {
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

    public function getGateInDetail(Request $request)
    {
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

    public function getVehicleDetail(Request $request)
    {
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

    public function getCustomerDetail(Request $request)
    {
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

        //CUSTMER PENDING AMOUNT CALAULATE
        $total_invoice_amount = 0;
        $total_received_amount = 0;
        if ($job_card->jobOrder->vehicle) {
            if ($job_card->jobOrder->vehicle->currentOwner) {
                $customer_code = $job_card->jobOrder->vehicle->currentOwner->customer->code;
                $params2 = ['CustomerCode' => $customer_code];
                $cust_invoices = $this->getSoap->getCustomerInvoiceDetails($params2);
                if ($cust_invoices) {
                    foreach ($cust_invoices as $cust_invoice) {
                        $total_invoice_amount += $cust_invoice['invoice_amount'];
                        $total_received_amount += $cust_invoice['received_amount'];
                    }
                }
            }
        }
        $job_card->jobOrder['total_due_amount'] = $total_invoice_amount - $total_received_amount;

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

    public function getOrderDetail(Request $request)
    {
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
            'jobOrder.rcBookAttachment',
            'jobOrder.CREUser'])
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

        $extras = [
            'job_order_type_list' => ServiceOrderType::getDropDownList(),
            'service_type_list' => ServiceType::getDropDownList(),
            'quote_type_list' => QuoteType::getDropDownList(),
        ];

        return response()->json([
            'success' => true,
            'job_card' => $job_card,
            'extras' => $extras,
        ]);

    }

    public function getInventory(Request $request)
    {
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

    public function getCaptureVoc(Request $request)
    {
        $job_card = JobCard::with(['jobOrder',
            'jobOrder.type',
            'jobOrder.vehicle',
            'jobOrder.vehicle.model',
            'status',
            'jobOrder.vehicle.status',
            'jobOrder.customerVoices',
            'jobOrder.gateLog',
            'jobOrder.VOCAttachment',
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

    public function deleteOutwardItem(Request $request)
    {
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

    public function getEstimateStatus(Request $request)
    {
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

    //Estimate form data
    public function getEstimate(Request $request)
    {

        $customer_paid_type_id = SplitOrderType::where('paid_by_id', '10013')->pluck('id')->toArray();

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
            'jobOrderRepairOrders' => function ($q) {
                $q->whereNull('removal_reason_id');
            },
            'jobOrderParts' => function ($q) {
                $q->whereNull('removal_reason_id');
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
            ->find($job_card->jobOrder->id);

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

        if ($job_order->vehicle->currentOwner->customer->primaryAddress) {
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
        $taxes = Tax::whereIn('id', [1, 2, 3])->get();

        $oem_recomentaion_labour_amount = 0;
        $additional_rot_and_parts_labour_amount = 0;

        $oem_recomentaion_labour_amount_include_tax = 0;
        $additional_rot_and_parts_labour_amount_include_tax = 0;
        $total_labour_hours = JobOrderRepairOrder::where('job_order_id', $job_card->jobOrder->id)->sum('qty');

        $total_schedule_labour_tax = 0;
        $total_schedule_labour_amount = 0;
        $total_schedule_labour_without_tax_amount = 0;
        $total_payable_labour_tax = 0;
        $total_payable_labour_amount = 0;
        $total_payable_labour_without_tax_amount = 0;

        //Repair Orders
        if ($job_order->jobOrderRepairOrders) {
            foreach ($job_order->jobOrderRepairOrders as $key => $labour) {
                if (in_array($labour->split_order_type_id, $customer_paid_type_id) || !$labour->split_order_type_id) {
                    //SCHEDULE MAINTANENCE
                    if ($labour->is_recommended_by_oem == 1 && $labour->is_free_service != 1) {
                        $tax_amount = 0;
                        if ($labour->repairOrder->taxCode) {
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
                            // $total_amount = $labour->amount;
                            $total_schedule_labour_amount += $total_amount;
                        } else {
                            $total_schedule_labour_amount += $labour->amount;
                        }
                        // $total_schedule_labour_without_tax_amount += ($labour->amount - $tax_amount);
                        $total_schedule_labour_without_tax_amount += $labour->amount;
                    }
                    //PAYABLE
                    if ($labour->is_recommended_by_oem == 0 && $labour->is_free_service != 1) {
                        $tax_amount = 0;
                        if ($labour->repairOrder->taxCode) {
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
                            // $total_amount = $labour->amount;
                            $total_payable_labour_amount += $total_amount;
                        } else {
                            $total_payable_labour_amount += $labour->amount;
                        }
                        // $total_payable_labour_without_tax_amount += ($labour->amount - $tax_amount);
                        $total_payable_labour_without_tax_amount += $labour->amount;
                    }
                }
            }
        }

        $total_schedule_part_amount = 0;
        $total_schedule_part_without_tax_amount = 0;
        $total_schedule_part_tax = 0;
        $total_payable_part_tax = 0;
        $total_payable_part_amount = 0;
        $total_payable_part_without_tax_amount = 0;

        //Parts
        if ($job_order->jobOrderParts) {
            foreach ($job_order->jobOrderParts as $key => $parts) {
                if (in_array($parts->split_order_type_id, $customer_paid_type_id) || !$parts->split_order_type_id) {
                    //SCHEDULE MAINTANENCE
                    if ($parts->is_oem_recommended == 1 && $parts->is_free_service != 1) {
                        $tax_amount = 0;
                        if ($parts->part->taxCode) {
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
                            // $total_amount = $tax_amount + $parts->amount;
                            $total_amount = $parts->amount;
                            $total_schedule_part_amount += $total_amount;
                        } else {
                            $total_schedule_part_amount += $parts->amount;
                        }
                        $total_schedule_part_without_tax_amount += ($parts->amount - $tax_amount);
                    }
                    if ($parts->is_oem_recommended == 0 && $parts->is_free_service != 1) {
                        $tax_amount = 0;
                        if ($parts->part->taxCode) {
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
                            // $total_amount = $tax_amount + $parts->amount;
                            $total_amount = $parts->amount;
                            $total_payable_part_amount += $total_amount;
                        } else {
                            $total_payable_part_amount += $parts->amount;
                        }
                        $total_payable_part_without_tax_amount += ($parts->amount - $tax_amount);
                    }
                }
            }
        }

        $schedule_tax_total = $total_schedule_labour_tax + $total_schedule_part_tax;

        $payable_tax_total = $total_payable_labour_tax + $total_payable_part_tax;

        $total_amount = $total_schedule_labour_amount + $total_schedule_part_amount + $total_payable_labour_amount + $total_payable_part_amount;
        $total_tax_amount = $schedule_tax_total + $payable_tax_total;

        //OEM RECOMENTATION LABOUR AND PARTS AND SUB TOTAL
        $job_order->oem_recomentation_labour_amount = $total_schedule_labour_without_tax_amount;
        $job_order->oem_recomentation_part_amount = $total_schedule_part_without_tax_amount;
        $job_order->oem_recomentation_tax_total = $schedule_tax_total;
        $job_order->oem_recomentation_sub_total = $total_schedule_labour_amount + $total_schedule_part_amount;

        //ADDITIONAL ROT & PARTS LABOUR AND PARTS AND SUB TOTAL
        $job_order->additional_rot_parts_labour_amount = $total_payable_labour_without_tax_amount;
        $job_order->additional_rot_parts_part_amount = $total_payable_part_without_tax_amount;
        $job_order->additional_rot_parts_tax_total = $payable_tax_total;
        $job_order->additional_rot_parts_sub_total = $total_payable_labour_amount + $total_payable_part_amount;

        //TOTAL ESTIMATE
        $job_order->total_estimate_labour_amount = $total_schedule_labour_without_tax_amount + $total_payable_labour_without_tax_amount;

        $job_order->total_estimate_parts_amount = $total_schedule_part_without_tax_amount + $total_payable_part_without_tax_amount;

        $job_order->total_tax_amount = $total_tax_amount;
        $job_order->total_estimate_amount = round($total_amount);

        $job_order->total_labour_hours = round($total_labour_hours);

        $estimation_date = date("Y-m-d H:i:s", strtotime('+' . $job_order->total_labour_hours . ' hours', strtotime($job_order->created_at)));
        // dd($job_order->created_at, $estimation_date);
        $job_order->est_date = date("d-m-Y", strtotime($estimation_date));
        $job_order->est_time = date("h:i a", strtotime($estimation_date));

        //Check Custoemr Approval Need or not
        $total_invoice_amount = $this->getApprovedLabourPartsAmount($job_order->id);

        $send_approval_status = 0;
        if ($total_invoice_amount) {
            $send_approval_status = 1;
        }

        return response()->json([
            'success' => true,
            'job_order' => $job_order,
            'job_card' => $job_card,
            'revised_estimate_amount' => $total_invoice_amount,
            'send_approval_status' => $send_approval_status,
        ]);

    }

    public function getPartsIndent(Request $request)
    {
        $job_card = JobCard::with([
            'jobOrder',
            'jobOrder.type',
            'jobOrder.vehicle',
            'jobOrder.vehicle.model',
            'jobOrder.vehicle.status',
            'status',
            'bay',
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

        $issued_parts = JobOrderIssuedPart::select(
            'job_order_issued_parts.id as issued_id',
            'parts.code',
            'parts.name',
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
            'job_card' => $job_card,
        ]);

    }

    public function getScheduleMaintenance(Request $request)
    {

        $job_card = JobCard::with(['jobOrder',
            'jobOrder.type',
            'jobOrder.serviceType',
            'jobOrder.vehicle',
            'jobOrder.vehicle.model',
            'status',
            'bay',
        ])->find($request->id);
        if (!$job_card) {
            return response()->json([
                'success' => false,
                'error' => 'Validation Error',
                'errors' => ['Job Card Not Found!'],
            ]);
        }

        $params['job_order_id'] = $job_card->job_order_id;
        $params['type_id'] = 1;

        $result = $this->getLabourPartsData($params);

        return response()->json([
            'success' => true,
            'job_order' => $result['job_order'],
            'part_details' => $result['part_details'],
            'labour_details' => $result['labour_details'],
            'total_amount' => $result['total_amount'],
            'labour_total_amount' => $result['labour_amount'],
            'parts_total_amount' => $result['part_amount'],
            'job_card' => $job_card,
        ]);

        // return response()->json([
        //     'success' => true,
        //     'job_order' => $job_order,
        //     'schedule_maintenance' => $schedule_maintenance,
        //     'job_card' => $job_card,
        // ]);

    }

    public function getPayableLabourPart(Request $request)
    {

        $job_card = JobCard::with(['jobOrder',
            'jobOrder.type',
            'jobOrder.vehicle',
            'jobOrder.vehicle.model',
            'status',
            'bay',
            'jobOrder.jobOrderRepairOrders' => function ($query) {
                $query->where('is_recommended_by_oem', 0);
            },
            'jobOrder.jobOrderRepairOrders.repairOrder',
        ])
            ->find($request->id);

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

        $customer_paid_type_id = SplitOrderType::where('paid_by_id', '10013')->pluck('id')->toArray();

        return response()->json([
            'success' => true,
            'job_order' => $result['job_order'],
            'part_details' => $result['part_details'],
            'labour_details' => $result['labour_details'],
            'total_amount' => $result['total_amount'],
            'labour_total_amount' => $result['labour_amount'],
            'parts_total_amount' => $result['part_amount'],
            'job_card' => $job_card,
            'labours' => $result['labours'],
            'customer_voices' => $result['customer_voices'],
        ]);

        // return response()->json([
        //     'success' => true,
        //     'job_order' => $job_order,
        //     'total_amount' => number_format($total_amount, 2),
        //     'parts_total_amount' => number_format($parts_total_amount, 2),
        //     'labour_total_amount' => number_format($labour_total_amount, 2),
        //     'job_card' => $job_card,
        //     'send_approval_status' => $send_approval_status,
        // ]);

    }

    public function getApprovedLabourPartsAmount($job_order_id)
    {
        // dd($job_order_id);

        $customer_paid_type = SplitOrderType::where('paid_by_id', '10013')->pluck('id')->toArray();

        $job_order = JobOrder::with([
            'outlet',
            'vehicle',
            'vehicle.currentOwner.customer',
            'vehicle.currentOwner.customer.primaryAddress',
            'jobOrderRepairOrders' => function ($q) {
                $q->whereNull('removal_reason_id');
            },
            'jobOrderRepairOrders.repairOrder',
            'jobOrderRepairOrders.repairOrder.taxCode',
            'jobOrderRepairOrders.repairOrder.taxCode.taxes',
            'jobOrderParts' => function ($q) {
                $q->whereNull('removal_reason_id');
            },
            'jobOrderParts.part',
            'jobOrderParts.part.taxCode',
            'jobOrderParts.part.taxCode.taxes',
        ])
            ->find($job_order_id);

        if ($job_order->vehicle->currentOwner->customer->primaryAddress) {
            //Check which tax applicable for customer
            if ($job_order->outlet->state_id == $job_order->vehicle->currentOwner->customer->primaryAddress->state_id) {
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

        if ($job_order->jobOrderRepairOrders) {
            foreach ($job_order->jobOrderRepairOrders as $key => $labour) {
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
                    // $total_amount = $labour->amount;
                    $total_amount = number_format((float) $total_amount, 2, '.', '');
                    $labour_amount += $total_amount;
                }
            }
        }

        if ($job_order->jobOrderParts) {
            foreach ($job_order->jobOrderParts as $key => $parts) {
                if ($parts->is_free_service != 1 && (in_array($parts->split_order_type_id, $customer_paid_type) || !$parts->split_order_type_id)) {
                    $total_amount = 0;

                    // $tax_amount = 0;
                    // if ($parts->part->taxCode) {
                    //     if (count($parts->part->taxCode->taxes) > 0) {
                    //         foreach ($parts->part->taxCode->taxes as $tax_key => $value) {
                    //             $percentage_value = 0;
                    //             if ($value->type_id == $tax_type) {
                    //                 $percentage_value = ($parts->amount * $value->pivot->percentage) / 100;
                    //                 $percentage_value = number_format((float) $percentage_value, 2, '.', '');
                    //             }
                    //             $tax_amount += $percentage_value;
                    //         }
                    //     }
                    // }

                    // $total_amount = $tax_amount + $parts->amount;
                    $total_amount = $parts->amount;
                    $total_amount = number_format((float) $total_amount, 2, '.', '');
                    $parts_amount += $total_amount;
                }
            }
        }

        $total_billing_amount = $parts_amount + $labour_amount;

        $total_billing_amount = round($total_billing_amount);

        if ($total_billing_amount > $job_order->estimated_amount) {
            return $total_billing_amount;
        } else {
            return '0';
        }
    }

    public function getLabourPartsData($params)
    {

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
        $labours = array();
        if ($job_order->jobOrderRepairOrders) {
            foreach ($job_order->jobOrderRepairOrders as $key => $value) {
                $labour_details[$key]['id'] = $value->id;
                $labour_details[$key]['labour_id'] = $value->repair_order_id;
                $labour_details[$key]['code'] = $value->repairOrder->code;
                $labour_details[$key]['name'] = $value->repairOrder->name;
                $labour_details[$key]['type'] = $value->repairOrder->repairOrderType ? $value->repairOrder->repairOrderType->short_name : '-';
                $labour_details[$key]['qty'] = $value->qty;
                if ($value->repairOrder->is_editable == 1) {
                    $labour_details[$key]['rate'] = $value->amount;
                } else {
                    $labour_details[$key]['rate'] = $value->repairOrder->amount;
                }
                $labour_details[$key]['amount'] = $value->amount;
                $labour_details[$key]['is_free_service'] = $value->is_free_service;
                $labour_details[$key]['split_order_type'] = $value->splitOrderType ? $value->splitOrderType->code . "|" . $value->splitOrderType->name : '-';
                $labour_details[$key]['removal_reason_id'] = $value->removal_reason_id;
                $labour_details[$key]['split_order_type_id'] = $value->split_order_type_id;
                $labour_details[$key]['status_id'] = $value->status_id;
                $labour_details[$key]['repair_order'] = $value->repairOrder;
                $labour_details[$key]['is_fixed_schedule'] = $value->is_fixed_schedule;
                $labour_details[$key]['customer_voice'] = $value->customerVoice;
                $labour_details[$key]['customer_voice_id'] = $value->customer_voice_id;
                if (in_array($value->split_order_type_id, $customer_paid_type) || !$value->split_order_type_id) {
                    if ($value->is_free_service != 1 && $value->removal_reason_id == null) {
                        $labour_amount += $value->amount;
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
        if ($job_order->jobOrderParts) {
            foreach ($job_order->jobOrderParts as $key => $value) {
                $part_details[$key]['id'] = $value->id;
                $part_details[$key]['part_id'] = $value->part_id;
                $part_details[$key]['code'] = $value->part->code;
                $part_details[$key]['name'] = $value->part->name;
                $part_details[$key]['type'] = $value->part->partType ? $value->part->partType->name : '-';
                $part_details[$key]['rate'] = $value->rate;
                $part_details[$key]['qty'] = $value->qty;
                $part_details[$key]['amount'] = $value->amount;
                $part_details[$key]['is_free_service'] = $value->is_free_service;
                $part_details[$key]['split_order_type'] = $value->splitOrderType ? $value->splitOrderType->code . "|" . $value->splitOrderType->name : '-';
                $part_details[$key]['removal_reason_id'] = $value->removal_reason_id;
                $part_details[$key]['split_order_type_id'] = $value->split_order_type_id;
                $part_details[$key]['status_id'] = $value->status_id;
                $part_details[$key]['repair_order'] = $value->part->repair_order_parts;
                $part_details[$key]['is_fixed_schedule'] = $value->is_fixed_schedule;
                $part_details[$key]['customer_voice'] = $value->customerVoice;
                $part_details[$key]['customer_voice_id'] = $value->customer_voice_id;
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

        $customer_voices = array();
        $customer_voices[0]['id'] = '';
        $customer_voices[0]['name'] = 'Select Customer Voice';
        foreach ($job_order->customerVoices as $key => $customerVoices) {
            $customer_voices[$key + 1]['id'] = $customerVoices->id;
            $customer_voices[$key + 1]['name'] = $customerVoices->code . ' / ' . $customerVoices->name;
        }

        $total_amount = $part_amount + $labour_amount;

        $result['job_order'] = $job_order;
        $result['labour_details'] = $labour_details;
        $result['part_details'] = $part_details;
        $result['labour_amount'] = $labour_amount;
        $result['part_amount'] = $part_amount;
        $result['total_amount'] = $total_amount;
        $result['labours'] = $labours;
        $result['customer_voices'] = $customer_voices;

        return $result;
    }

    public function deletePayable(Request $request)
    {
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

                if ($request->removal_reason_id == 10022) {
                    $job_order_repair_order = JobOrderRepairOrder::find($request->labour_parts_id);
                    if ($request->removal_reason_id == 10022) {
                        $job_order_repair_order->removal_reason_id = $request->removal_reason_id;
                        $job_order_repair_order->removal_reason = $request->removal_reason;
                    } else {
                        $job_order_repair_order->removal_reason_id = $request->removal_reason_id;
                        $job_order_repair_order->removal_reason = null;
                    }
                    $job_order_repair_order->updated_by_id = Auth::user()->id;
                    $job_order_repair_order->updated_at = Carbon::now();
                    $job_order_repair_order->save();
                } else {
                    $job_order_repair_order = JobOrderRepairOrder::where('id', $request->labour_parts_id)->forceDelete();
                }

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

                if ($request->removal_reason_id == 10022) {
                    $job_order_parts = JobOrderPart::find($request->labour_parts_id);
                    if ($request->removal_reason_id == 10022) {
                        $job_order_parts->removal_reason_id = $request->removal_reason_id;
                        $job_order_parts->removal_reason = $request->removal_reason;
                    } else {
                        $job_order_parts->removal_reason_id = $request->removal_reason_id;
                        $job_order_parts->removal_reason = null;
                    }
                    $job_order_parts->updated_by_id = Auth::user()->id;
                    $job_order_parts->updated_at = Carbon::now();
                    $job_order_parts->save();
                } else {
                    $job_order_parts = JobOrderPart::where('id', $request->labour_parts_id)->forceDelete();
                }
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

    public function sendConfirmation(Request $request)
    {
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

            if (!$job_order) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation Error',
                    'errors' => ['Job Order Not Found!'],
                ]);
            }

            $customer_mobile = $job_order->contact_number;

            if (!$customer_mobile) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation Error',
                    'errors' => ['Customer Mobile Number Not Found!'],
                ]);
            }

            $otp_no = mt_rand(111111, 999999);

            DB::beginTransaction();

            $job_order->otp_no = $otp_no;
            // $job_order->status_id = 8469; //Waiting for Customer Approval
            $job_order->updated_by_id = Auth::user()->id;
            $job_order->updated_at = Carbon::now();
            $job_order->save();

            //Update JobCard Status
            $job_card = JobCard::where('job_order_id', $job_order->id)->first();
            if (!$job_card) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation Error',
                    'errors' => ['Job Card Not Found!'],
                ]);
            }
            $job_card->status_id = 8229; //Waiting for Customer Approval
            $job_card->updated_by = Auth::user()->id;
            $job_card->updated_at = Carbon::now();
            $job_card->save();

            // $estimate_file_name = $job_card->id . '_revised_estimate.pdf';
            // $directoryPath = storage_path('app/public/gigo/pdf/' . $estimate_file_name);
            // if (!file_exists($directoryPath)) {

            //Generate Revised Estimate PDF
            $generate_estimate_pdf = JobCard::generateRevisedEstimatePDF($job_card->id);

            if (!$generate_estimate_pdf) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation Error',
                    'errors' => ['Something went on Server.Please Try again later!!'],
                ]);
            }
            // }

            if ($request->type == 2) {

                // $vehicle_no = $job_order->vehicle->registration_number;
                if ($job_order->vehicle->registration_number) {
                    $vehicle_no = $job_order->vehicle->registration_number;
                    $number = ' Vehicle Reg Number';
                } elseif ($job_order->vehicle->chassis_number) {
                    $vehicle_no = $job_order->vehicle->chassis_number;
                    $number = ' Vehicle Chassis Number';
                } else {
                    $vehicle_no = $job_order->vehicle->engine_number;
                    $number = ' Vehicle Engine Number';
                }

                $url = url('/') . '/vehicle-inward/estimate/customer/view/' . $request->job_order_id . '/' . $job_order->otp_no;

                $short_url = ShortUrl::createShortLink($url, $maxlength = "7");

                $message = 'Dear Customer, Kindly click on this link to approve for Revised TVS job order ' . $short_url . $number . ' : ' . $vehicle_no . ' - TVS';

                $msg = sendOTPSMSNotification($customer_mobile, $message);

                //Update JobOrder Estimate
                $job_order_estimate = JobOrderEstimate::where('job_order_id', $job_order->id)->orderBy('id', 'DESC')->first();
                $job_order_estimate->status_id = 10071;
                $job_order_estimate->updated_by_id = Auth::user()->id;
                $job_order_estimate->updated_at = Carbon::now();
                $job_order_estimate->save();

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => $message,
                ]);

            } else {

                $current_time = date("Y-m-d H:m:s");

                $expired_time = Entity::where('entity_type_id', 32)->select('name')->first();
                if ($expired_time) {
                    $expired_time = date("Y-m-d H:i:s", strtotime('+' . $expired_time->name . ' hours', strtotime($current_time)));
                } else {
                    $expired_time = date("Y-m-d H:i:s", strtotime('+1 hours', strtotime($current_time)));
                }

                //Otp Save
                $otp = new Otp;
                $otp->entity_type_id = 10112;
                $otp->entity_id = $job_order->id;
                $otp->otp_no = $otp_no;
                $otp->created_by_id = Auth::user()->id;
                $otp->created_at = $current_time;
                $otp->expired_at = $expired_time;
                $otp->outlet_id = Auth::user()->employee->outlet_id;
                $otp->save();

                DB::commit();

                $message = 'OTP is ' . $otp_no . ' for Revised Job Order Estimation. Please show this SMS to Our Floor Supervisor to verify your Revised Job Order Estimate - TVS';

                $msg = sendOTPSMSNotification($customer_mobile, $message);

                return response()->json([
                    'success' => true,
                    'mobile_number' => $customer_mobile,
                    'message' => 'OTP Sent successfully!!',
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

    public function verifyOtp(Request $request)
    {
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

            $job_order = JobOrder::find($request->job_order_id);

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
                    'error' => 'Validation Error',
                    'errors' => ['Job Order Approve Behalf of Customer OTP is wrong. Please try again.'],
                ]);
            }

            $current_time = date("Y-m-d H:m:s");

            //Validate OTP -> Expired or Not
            $otp_validate = OTP::where('entity_type_id', 10112)->where('entity_id', $request->job_order_id)->where('otp_no', '=', $request->otp_no)->where('expired_at', '>=', $current_time)
                ->first();

            // dump($current_time);
            if (!$otp_validate) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation Error',
                    'errors' => ['OTP Expired!'],
                ]);
            }

            //UPDATE JOB ORDER STATUS
            $job_order_status_update = JobOrder::find($request->job_order_id);
            $job_order_status_update->is_customer_approved = 1;
            if ($request->revised_estimate_amount) {
                $job_order_status_update->estimated_amount = $request->revised_estimate_amount;
            }
            $job_order_status_update->updated_at = Carbon::now();
            $job_order_status_update->save();

            //Update JobCard Status
            $job_card = JobCard::where('job_order_id', $request->job_order_id)->first();
            if (!$job_card) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation Error',
                    'errors' => ['Job Card Not Found!'],
                ]);
            }
            $job_card->status_id = 8221; //Work Inprogress
            $job_card->updated_by = Auth::user()->id;
            $job_card->updated_at = Carbon::now();
            $job_card->save();

            //UPDATE JOB ORDER REPAIR ORDER STATUS
            JobOrderRepairOrder::where('job_order_id', $job_order->id)->where('is_customer_approved', 0)->whereNull('removal_reason_id')->update(['is_customer_approved' => 1, 'status_id' => 8181, 'updated_at' => Carbon::now()]);

            //UPDATE JOB ORDER PARTS STATUS
            JobOrderPart::where('job_order_id', $job_order->id)->where('is_customer_approved', 0)->whereNull('removal_reason_id')->update(['is_customer_approved' => 1, 'status_id' => 8201, 'updated_at' => Carbon::now()]);

            JobOrderEstimate::where('job_order_id', $job_order->id)->where('status_id', 10071)->update(['status_id' => 10072, 'updated_at' => Carbon::now()]);

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

    //VEHICLE INSPECTION GET FORM DATA
    public function getVehicleInspection(Request $request)
    {
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

    public function getReturnableItems(Request $request)
    {
        $job_card = $job_card = JobCard::with([
            'status',
            'jobOrder',
            'jobOrder.vehicle',
            'bay',
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

        $returnable_parts_items = JobCardReturnableItem::with([
            'attachment',
        ])
            ->where('job_card_id', $job_card->id)
            ->whereNotNull('part_id')
            ->get();

        $returnable_other_items = JobCardReturnableItem::with([
            'attachment',
        ])
            ->where('job_card_id', $job_card->id)
            ->whereNull('part_id')
            ->get();

        return response()->json([
            'success' => true,
            'job_card' => $job_card,
            'returnable_parts_items' => $returnable_parts_items,
            'returnable_other_items' => $returnable_other_items,
            'attachement_path' => url('storage/app/public/gigo/job_card/returnable_items/'),
        ]);

    }

    public function getReturnableItemFormdata(Request $request)
    {
        // dd($request->all());
        $job_card = JobCard::with([
            'jobOrder',
            'bay',
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

    public function returnableItemSave(Request $request)
    {
        // dd($request->all());
        try {
            $validator = Validator::make($request->all(), [
                'job_card_id' => [
                    'required',
                    'integer',
                    'exists:job_cards,id',
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

    public function getReturnablePartsFormdata(Request $request)
    {
        $job_card = JobCard::with([
            'jobOrder',
            'bay',
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

    public function returnablePartSave(Request $request)
    {
        // dd($request->all());
        try {
            DB::beginTransaction();

            if ($request->returned_parts) {
                $delete_parts = JobCardReturnableItem::where('job_card_id', $request->job_card_id)->whereNotNull('part_id')->forceDelete();

                foreach ($request->returned_parts as $key => $parts) {
                    if (isset($parts['qty'])) {
                        $returnable_part = new JobCardReturnableItem;
                        $returnable_part->job_card_id = $request->job_card_id;
                        $returnable_part->part_id = $parts['part_id'];
                        $returnable_part->item_name = $parts['part_code'];
                        $returnable_part->item_description = $parts['part_name'];
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

    public function viewJobCard($job_card_id)
    {
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
                'jobOrder.jobOrderRepairOrder' => function ($q) {
                    $q->whereNull('removal_reason_id');
                },
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
    public function getJobCardTimeLog(Request $request)
    {
        // dd($request->all());
        try {
            $job_card_time_log = JobCard::with([
                'status',
                'bay',
                'jobOrder',
                'jobOrder.vehicle',
                'jobOrder.vehicle.model',
                // 'jobOrder.gateLog',
                // 'jobOrder.gateLog.vehicleDetail',
                // 'jobOrder.gateLog.vehicleDetail.vehicleModel',
                'jobOrder.jobOrderRepairOrders' => function ($q) {
                    $q->whereNull('removal_reason_id')->where('is_customer_approved', 1);
                },
                'jobOrder.jobOrderRepairOrders.status',
                'jobOrder.jobOrderRepairOrders.repairOrder',
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
    //OSL or Material gate pass
    public function viewMeterialGatePass(Request $request)
    {
        // dd($request->all());
        try {
            $view_metrial_gate_pass = JobCard::with([
                'status',
                'bay',
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

            //Repair Orders
            if ($view_metrial_gate_pass->gatePasses) {
                foreach ($view_metrial_gate_pass->gatePasses as $key => $value) {
                    $repair_orders = JobOrderRepairOrder::join('repair_orders', 'repair_orders.id', 'job_order_repair_orders.repair_order_id')->where('job_order_repair_orders.osl_work_order_id', $value->entity_id)->select('repair_orders.code', 'repair_orders.name', 'repair_orders.is_editable', 'job_order_repair_orders.id', 'job_order_repair_orders.amount')->get()->toArray();
                    $value->repair_orders = $repair_orders;
                }
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
    public function getMeterialGatePassData(Request $request)
    {
        // dd($request->all());
        try {
            $job_card = JobCard::with([
                'status',
                'bay',
                'jobOrder',
                'jobOrder.type',
                'jobOrder.vehicle',
                'jobOrder.vehicle.model',
                'jobOrder.jobOrderRepairOrders' => function ($q) {
                    $q->whereNull('removal_reason_id');
                },
                'jobOrder.jobOrderRepairOrders.repairOrder',
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
                    'gatePassItems',
                    'gatePassItems.attachment',
                ])
                    ->select([
                        'gate_passes.*',
                        'osl_work_orders.id as osl_work_order_id',
                    ])

                    ->leftJoin('osl_work_orders', function ($join) {
                        $join->on('osl_work_orders.id', 'gate_passes.entity_id')
                            ->where('gate_passes.gate_pass_of_id', 11282);
                    })
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

                //Repair Orders
                if ($gate_pass && $gate_pass->osl_work_order_id) {
                    $selected_job_order_repair_order_ids = JobOrderRepairOrder::where('osl_work_order_id', $gate_pass->osl_work_order_id)->pluck('id')->toArray();
                } else {
                    $selected_job_order_repair_order_ids = [];
                }

            } else {
                $gate_pass = new GatePass();
                $gate_pass->gate_pass_detail = new GatePassDetail();
                $gate_pass->gate_pass_detail->vendor = new Vendor();
                $gate_pass->gate_pass_items = new GatePassItem();
                $selected_job_order_repair_order_ids = [];
            }

            $labour_details = array();
            if ($job_card->jobOrder->jobOrderRepairOrders) {
                foreach ($job_card->jobOrder->jobOrderRepairOrders as $key => $value) {
                    if ((in_array($value->id, $selected_job_order_repair_order_ids)) || ($value->osl_work_order_id == null)) {
                        $labours = array();
                        $labours['id'] = $value->id;
                        $labours['name'] = $value->repairOrder->code . ' - ' . $value->repairOrder->name;
                        $labour_details[] = $labours;
                    }
                }
            }

            return response()->json([
                'success' => true,
                'gate_pass' => $gate_pass,
                'job_card' => $job_card,
                'job_order_repair_order_ids' => $selected_job_order_repair_order_ids,
                'labour_details' => $labour_details,
                'attachement_path' => url('storage/app/public/gigo/material_gate_pass/attachments/'),
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
    public function saveMaterialGatePass(Request $request)
    {
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
                    'unique:gate_pass_details,work_order_no,' . $request->gate_pass_id . ',gate_pass_id',
                    'unique:osl_work_orders,number,' . $request->osl_work_order_id . ',id',
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
                'job_order_repair_order_id' => [
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

            if (!$request->item_details) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation Error',
                    'errors' => ['Please add atleast one item!'],
                ]);
            }

            if ($request->job_order_repair_order_id) {
                $job_order_repair_order_ids = json_decode($request->job_order_repair_order_id);
                if (count($job_order_repair_order_ids) == 0) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Validation Error',
                        'errors' => ['Please Select Repair Order!'],
                    ]);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation Error',
                    'errors' => ['Please Select Repair Order!'],
                ]);
            }

            DB::beginTransaction();

            $job_card = JobCard::with(['jobOrder'])->find($request->job_card_id);

            if (!$job_card) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation Error',
                    'errors' => ['Job Card not found!'],
                ]);
            }

            $gate_pass = GatePass::firstOrNew([
                'id' => $request->gate_pass_id,
            ]);

            $gate_pass->type_id = 8281; //Material Gate Pass
            $gate_pass->status_id = 8300; //Gate Out Pending
            $gate_pass->company_id = Auth::user()->company_id;
            $gate_pass->job_order_id = $job_card->job_order_id;
            $gate_pass->fill($request->all());
            $gate_pass->created_by_id = Auth::user()->id;
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
                        'unique:gate_passes,number,' . $gate_pass->id . ',id,company_id,' . Auth::user()->company_id . ',type_id,8281',
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

            //SAVE OSL WORK
            $osl_work_order = OSLWorkOrder::firstOrNew([
                'id' => $request->osl_work_order_id,
                'company_id' => Auth::user()->company_id,
            ]);
            $osl_work_order->vendor_id = $request->vendor_id;
            $osl_work_order->job_card_id = $job_card->id;
            $osl_work_order->number = $request->work_order_no;
            $osl_work_order->vendor_contact_no = $request->vendor_contact_no;
            $osl_work_order->work_order_description = $request->work_order_description;
            $osl_work_order->created_by_id = Auth::user()->id;
            $osl_work_order->created_at = Carbon::now();
            $osl_work_order->save();

            $gate_pass->gate_pass_of_id = 11282;
            $gate_pass->entity_id = $osl_work_order->id;
            $gate_pass->save();

            //Remove Old Updated Repair Orders
            $job_order_repair_order = JobOrderRepairOrder::where('osl_work_order_id', $request->osl_work_order_id)->update(['is_work_order' => 0, 'osl_work_order_id' => null]);

            //Update Repair Orders
            foreach ($job_order_repair_order_ids as $key => $job_order_repair_order_id) {
                $job_order_repair_order = JobOrderRepairOrder::find($job_order_repair_order_id);
                if ($job_order_repair_order) {
                    $job_order_repair_order->is_work_order = 1;
                    $job_order_repair_order->osl_work_order_id = $osl_work_order->id;
                    $job_order_repair_order->save();
                }

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
            // $gate_pass_detail->job_order_repair_order_id = $request->job_order_repair_order_id;
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
                    $gate_pass_item->status_id = 11121; //PENDING
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

    public function saveMaterialGatePassBill(Request $request)
    {
        // dd($request->all());
        try {
            $validator = Validator::make($request->all(), [
                'job_card_id' => [
                    'required',
                    'integer',
                    'exists:job_cards,id',
                ],
                'invoice_number' => [
                    'required',
                ],
                'invoice_date' => [
                    'required',
                ],
                'invoice_amount' => [
                    'required',
                ],
                'gate_pass_id' => [
                    'required',
                    'integer',
                    'exists:gate_passes,id',
                ],
                'work_order_id' => [
                    'required',
                    'integer',
                    'exists:osl_work_orders,id',
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

            $job_card = JobCard::with(['jobOrder'])->find($request->job_card_id);

            $gate_pass = GatePass::find($request->gate_pass_id);

            if (!$gate_pass) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation Error',
                    'errors' => [
                        'OSL Work Order Not Found',
                    ],
                ]);
            }

            $gate_pass->status_id = 8304; //OSL Work Completed
            $gate_pass->updated_by_id = Auth::user()->id;
            $gate_pass->updated_at = Carbon::now();
            $gate_pass->save();

            $osl_work_order = OSLWorkOrder::with(['vendor'])->find($request->work_order_id);

            if (!$osl_work_order) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation Error',
                    'errors' => [
                        'OSL Work Order Not Found',
                    ],
                ]);
            }

            $osl_work_order->invoice_number = $request->invoice_number;
            $osl_work_order->invoice_date = date('Y-m-d', strtotime($request->invoice_date));
            $osl_work_order->invoice_amount = $request->invoice_amount;
            $osl_work_order->updated_by_id = Auth::user()->id;
            $osl_work_order->updated_at = Carbon::now();
            $osl_work_order->save();

            $total_invoice_amount = 0;
            if ($request->gate_pass_repair_order) {
                foreach ($request->gate_pass_repair_order as $key => $gate_pass_repair_order) {
                    if (isset($gate_pass_repair_order['osl_amount'])) {
                        //Internal Vendor
                        if ($osl_work_order->vendor && $osl_work_order->vendor->type_id == 121) {
                            $internal_amount = ($gate_pass_repair_order['osl_amount'] * 50) / 100;
                            $total_amount = $gate_pass_repair_order['osl_amount'] + $internal_amount;
                        } else {
                            //External Vendor
                            $external_amount = ($gate_pass_repair_order['osl_amount'] * 25) / 100;
                            $total_amount = $gate_pass_repair_order['osl_amount'] + $external_amount;
                        }

                        //Update Job Order Repair Order amount
                        $job_order_repair_order = JobOrderRepairOrder::find($gate_pass_repair_order['job_order_repair_id']);
                        if ($job_order_repair_order) {
                            $job_order_repair_order->amount = $total_amount;
                            $job_order_repair_order->status_id = 8187; //Work Completed
                            $job_order_repair_order->save();
                        }

                        $total_invoice_amount += $gate_pass_repair_order['osl_amount'];
                    }
                }
            }

            if ($total_invoice_amount > $request->invoice_amount) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation Error',
                    'errors' => [
                        'OSL Invoice amount should be equal to OSL Repair Order Bill amount',
                    ],
                ]);
            }

            //Save Attcahments
            $attachment_removal_ids = json_decode($request->attachment_removal_ids);
            if (!empty($attachment_removal_ids)) {
                Attachment::whereIn('id', $attachment_removal_ids)->forceDelete();
            }

            if (!empty($request->osl_work_order_attachments)) {
                foreach ($request->osl_work_order_attachments as $key => $osl_work_order_attachment) {
                    $value = rand(1, 100);
                    $image = $osl_work_order_attachment;

                    $file_name_with_extension = $image->getClientOriginalName();
                    $file_name = pathinfo($file_name_with_extension, PATHINFO_FILENAME);
                    $extension = $image->getClientOriginalExtension();
                    $time_stamp = date('Y_m_d_h_i_s');
                    $name = $gate_pass->id . '_' . $time_stamp . '_' . rand(10, 1000) . '_osl_bill.' . $extension;

                    $osl_work_order_attachment->move(storage_path('app/public/gigo/material_gate_pass/attachments/'), $name);
                    $attachement = new Attachment;
                    $attachement->attachment_of_id = 231; //Material Gate Pass
                    $attachement->attachment_type_id = 10097; //OSL Bill
                    $attachement->entity_id = $gate_pass->id;
                    $attachement->name = $name;
                    $attachement->save();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'OSL Work Order Bill Detail Saved Successfully!!',
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

    //Split Order
    public function viewSplitOrderDetails(Request $request)
    {
        // dd($request->all());
        try {
            $job_card = JobCard::with([
                'jobOrder',
                'bay',
                'outlet',
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
            $taxes = Tax::whereIn('id', [1, 2, 3])->get();

            $parts_amount = 0;
            $labour_amount = 0;
            $total_amount = 0;

            //dd($job_card->jobOrder->vehicle->currentOwner);

            if ($job_card->jobOrder->vehicle->currentOwner->customer->primaryAddress) {
                //Check which tax applicable for customer
                if ($job_card->outlet->state_id == $job_card->jobOrder->vehicle->currentOwner->customer->primaryAddress->state_id) {
                    $tax_type = 1160; //Within State
                } else {
                    $tax_type = 1161; //Inter State
                }
            } else {
                $tax_type = 1160; //Within State
            }
            //Count Tax Type
            $taxes = Tax::whereIn('id', [1, 2, 3])->get();

            $unassigned_labour_count = 0;
            $unassigned_part_count = 0;
            $unassigned_labour_amount = 0;

            $labour_details = array();
            if ($job_card->jobOrder->jobOrderRepairOrders) {
                foreach ($job_card->jobOrder->jobOrderRepairOrders as $key => $labour) {
                    $total_amount = 0;
                    $labour_details[$key]['id'] = $labour->id;
                    $labour_details[$key]['repair_order_id'] = $labour->repairOrder->id;
                    $labour_details[$key]['name'] = $labour->repairOrder->code . ' | ' . $labour->repairOrder->name;
                    $labour_details[$key]['hsn_code'] = $labour->repairOrder->taxCode ? $labour->repairOrder->taxCode->code : '-';
                    // $labour_details[$key]['qty'] = $labour->qty;
                    $labour_details[$key]['qty'] = 1;
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
                    $labour_details[$key]['amount'] = $total_amount;
                    $labour_details[$key]['total_amount'] = $total_amount;

                    if ($labour->split_order_type_id == null) {
                        $unassigned_labour_count++;
                        $unassigned_labour_amount += $total_amount;
                    }
                }
            }

            $unassigned_part_amount = 0;
            $part_details = array();
            if ($job_card->jobOrder->jobOrderParts) {
                foreach ($job_card->jobOrder->jobOrderParts as $key => $parts) {
                    //Check Parts Issued or Not
                    $issued_qty = JobOrderIssuedPart::where('job_order_part_id', $parts->id)->sum('issued_qty');

                    //Check Parts Retunred or Not
                    $returned_qty = JobOrderReturnedPart::where('job_order_part_id', $parts->id)->sum('returned_qty');

                    $total_qty = $issued_qty - $returned_qty;

                    if ($total_qty > 0) {
                        $total_amount = 0;
                        $billing_parts_amount = 0;
                        $billing_parts_amount = $total_qty * $parts->rate;
                        $part_details[$key]['id'] = $parts->id;
                        $part_details[$key]['part_id'] = $parts->part->id;
                        $part_details[$key]['name'] = $parts->part->code . ' | ' . $parts->part->name;
                        $part_details[$key]['hsn_code'] = $parts->part->taxCode ? $parts->part->taxCode->code : '-';
                        // $part_details[$key]['qty'] = $parts->qty;
                        $part_details[$key]['qty'] = $total_qty;
                        $part_details[$key]['rate'] = $parts->rate;
                        $part_details[$key]['mrp'] = $parts->rate;
                        $part_details[$key]['price'] = $parts->rate;
                        $part_details[$key]['amount'] = number_format((float) $billing_parts_amount, 2, '.', '');
                        $part_details[$key]['is_free_service'] = $parts->is_free_service;
                        $part_details[$key]['split_order_type_id'] = $parts->split_order_type_id;

                        $tax_percent = 0;
                        $price = $parts->rate;

                        if ($parts->part->taxCode) {
                            foreach ($parts->part->taxCode->taxes as $tax_key => $value) {
                                if ($value->type_id == $tax_type) {
                                    $tax_percent += $value->pivot->percentage;
                                }
                            }

                            $tax_percent = (100 + $tax_percent) / 100;

                            $price = $parts->rate / $tax_percent;
                            $price = number_format((float) $price, 2, '.', '');
                        }
                        $part_details[$key]['price'] = $price;

                        $total_price = $price * $parts->qty;
                        $part_details[$key]['taxable_amount'] = $total_price;

                        $tax_amount = 0;
                        $tax_values = array();
                        if ($parts->part->taxCode) {
                            foreach ($parts->part->taxCode->taxes as $tax_key => $value) {
                                $percentage_value = 0;
                                if ($value->type_id == $tax_type) {
                                    $percentage_value = ($total_price * $value->pivot->percentage) / 100;
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

                        // $total_amount = $tax_amount + $billing_parts_amount;
                        $total_amount = $billing_parts_amount;
                        $total_amount = number_format((float) $total_amount, 2, '.', '');

                        $part_details[$key]['total_amount'] = $total_amount;
                        $part_details[$key]['tax_amount'] = number_format((float) $tax_amount, 2, '.', '');

                        if ($parts->split_order_type_id == null) {
                            $unassigned_part_count++;
                            $unassigned_part_amount += $total_amount;
                        }
                    }
                }
            }

            // dd($part_details);
            $total_amount = $parts_amount + $labour_amount;

            // foreach ($part_details as $key => $part) {
            //     if (!$part['split_order_type_id']) {
            //         // $unassigned_part_count += 1;
            //         $unassigned_part_amount += $part['total_amount'];
            //     }
            // }
            // $unassigned_labour_amount = 0;
            // foreach ($labour_details as $key => $labour) {
            //     if (!$labour['split_order_type_id']) {
            //         // $unassigned_labour_count += 1;
            //         $unassigned_labour_amount += $labour['total_amount'];
            //     }
            // }
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

    //Update Split Order
    public function splitOrderUpdate(Request $request)
    {
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

            if ($request->complaint_id) {
                $validator = Validator::make($request->all(), [
                    'complaint_id' => [
                        'required',
                        'integer',
                        'exists:complaints,id',
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
            }

            if ($request->fault_id) {
                $validator = Validator::make($request->all(), [
                    'fault_id' => [
                        'required',
                        'integer',
                        'exists:faults,id',
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
                $job_order_part->split_order_type_id = $request->split_order_type_id == '-1' ? null : $request->split_order_type_id;
                $job_order_part->updated_at = Carbon::now();
                $job_order_part->updated_by_id = Auth::user()->id;
                if ($request->complaint_id) {
                    $job_order_part->complaint_id = $request->complaint_id;
                } else {
                    $job_order_part->complaint_id = null;
                }

                if ($request->fault_id) {
                    $job_order_part->fault_id = $request->fault_id;
                } else {
                    $job_order_part->fault_id = null;
                }
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
                $job_order_repair_order->split_order_type_id = $request->split_order_type_id == '-1' ? null : $request->split_order_type_id;
                $job_order_repair_order->updated_at = Carbon::now();
                $job_order_repair_order->updated_by_id = Auth::user()->id;
                if ($request->complaint_id) {
                    $job_order_repair_order->complaint_id = $request->complaint_id;
                } else {
                    $job_order_repair_order->complaint_id = null;
                }

                if ($request->fault_id) {
                    $job_order_repair_order->fault_id = $request->fault_id;
                } else {
                    $job_order_repair_order->fault_id = null;
                }
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

    //Bill Details - Split Order wise view
    public function viewBillDetails(Request $request)
    {
        // dd($request->all());
        try {
            $job_card = JobCard::with([
                'jobOrder',
                'bay',
                'outlet',
                'jobOrder.serviceType',
                'jobOrder.type',
                'jobOrder.vehicle',
                'jobOrder.vehicle.model',
                'jobOrder.jobOrderRepairOrders' => function ($q) {
                    $q->whereNull('removal_reason_id')->where('is_customer_approved', 1)->whereNotNull('split_order_type_id');
                },
                'jobOrder.jobOrderRepairOrders.repairOrder',
                'jobOrder.jobOrderRepairOrders.repairOrder.repairOrderType',
                'jobOrder.jobOrderRepairOrders.repairOrder.taxCode',
                'jobOrder.jobOrderRepairOrders.repairOrder.taxCode.taxes',
                'jobOrder.jobOrderParts' => function ($q) {
                    $q->whereNull('removal_reason_id')->where('is_customer_approved', 1)->whereNotNull('split_order_type_id');
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

            if ($job_card->jobOrder->vehicle->currentOwner->customer->primaryAddress) {
                //Check which tax applicable for customer
                if ($job_card->outlet->state_id == $job_card->jobOrder->vehicle->currentOwner->customer->primaryAddress->state_id) {
                    $tax_type = 1160; //Within State
                } else {
                    $tax_type = 1161; //Inter State
                }
            } else {
                $tax_type = 1160; //Within State
            }

            //Count Tax Type
            $taxes = Tax::whereIn('id', [1, 2, 3])->get();

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
                        // $labour_details[$key]['qty'] = $labour->qty;
                        $labour_details[$key]['qty'] = 1;
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
                    //Check Parts Issued or Not
                    $issued_qty = JobOrderIssuedPart::where('job_order_part_id', $parts->id)->sum('issued_qty');

                    //Check Parts Returned or Not
                    $returned_qty = JobOrderReturnedPart::where('job_order_part_id', $parts->id)->sum('returned_qty');

                    $total_qty = $issued_qty - $returned_qty;

                    if ($total_qty > 0) {
                        $billing_parts_amount = 0;
                        $billing_parts_amount = $total_qty * $parts->rate;

                        $part_sub_total = 0;
                        $total_amount = 0;
                        $part_details[$key]['id'] = $parts->id;
                        $part_details[$key]['part_id'] = $parts->part->id;
                        $part_details[$key]['name'] = $parts->part->code . ' | ' . $parts->part->name;
                        $part_details[$key]['hsn_code'] = $parts->part->taxCode ? $parts->part->taxCode->code : '-';
                        // $part_details[$key]['qty'] = $parts->qty;
                        $part_details[$key]['qty'] = $total_qty;
                        $part_details[$key]['mrp'] = $parts->rate;
                        $part_details[$key]['price'] = $parts->rate;

                        // $part_details[$key]['rate'] = $parts->rate;
                        // $part_details[$key]['amount'] = $parts->amount;
                        $part_details[$key]['amount'] = number_format((float) $billing_parts_amount, 2, '.', '');
                        $part_details[$key]['is_free_service'] = $parts->is_free_service;
                        $part_details[$key]['split_order_type_id'] = $parts->split_order_type_id;
                        $tax_amount = 0;
                        $part_details[$key]['tax_code'] = $parts->part->taxCode;

                        $tax_percent = 0;
                        $price = $parts->rate;

                        if ($parts->part->taxCode) {
                            foreach ($parts->part->taxCode->taxes as $tax_key => $value) {
                                if ($value->type_id == $tax_type) {
                                    $tax_percent += $value->pivot->percentage;
                                }
                            }

                            $tax_percent = (100 + $tax_percent) / 100;

                            $price = $parts->rate / $tax_percent;
                            $price = number_format((float) $price, 2, '.', '');
                        }
                        $part_details[$key]['price'] = $price;
                        $total_price = $price * $parts->qty;
                        $total_price = number_format((float) $total_price, 2, '.', '');
                        $part_details[$key]['taxable_amount'] = $total_price;

                        $tax_values = array();

                        if (in_array($parts->split_order_type_id, $customer_paid_type_id)) {
                            if ($parts->is_free_service != 1) {
                                if ($parts->part->taxCode) {
                                    foreach ($parts->part->taxCode->taxes as $tax_key => $value) {
                                        $percentage_value = 0;
                                        if ($value->type_id == $tax_type) {
                                            $percentage_value = ($total_price * $value->pivot->percentage) / 100;
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
                                // $total_amount = $tax_amount + $billing_parts_amount;
                                $total_amount = $billing_parts_amount;
                                $total_amount = number_format((float) $total_amount, 2, '.', '');

                                $part_details[$key]['tax_values'] = $tax_values;

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
                            $parts_total_amount += $total_amount;

                            $part_details[$key]['tax_values'] = $tax_values;

                        } else {
                            if ($parts->part->taxCode) {
                                foreach ($parts->part->taxCode->taxes as $tax_key => $value) {
                                    $percentage_value = 0;
                                    if ($value->type_id == $tax_type) {
                                        $percentage_value = ($total_price * $value->pivot->percentage) / 100;
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

                            $total_amount = $tax_amount + $billing_parts_amount;
                            $total_amount = number_format((float) $total_amount, 2, '.', '');

                            $part_details[$key]['total_amount'] = $total_amount;
                            $part_details[$key]['tax_amount'] = number_format((float) $tax_amount, 2, '.', '');

                            $parts_total_amount += $total_amount;
                        }
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

    public function getBillDetailFormData(Request $request)
    {
        // dd($request->all());
        try {
            $job_card = JobCard::with([
                'status',
                'bay',
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

    public function updateBillDetails(Request $request)
    {
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

    //Newly Added for floor supervisor
    public function floorSupervisorGetDetails(Request $request){
        // dd($request->all());
            $job_card = JobCard::with([
                'jobOrder',
                'jobOrder.type',
                'jobOrder.serviceType',
                'jobOrder.vehicle',
                'jobOrder.vehicle.model',
                'status',    
                'floorSupervisor',
            ])
            ->select([
                'job_cards.*',
                DB::raw('DATE_FORMAT(job_cards.created_at,"%d/%m/%Y") as date'),
                DB::raw('DATE_FORMAT(job_cards.created_at,"%h:%i %p") as time'),
            ])
            ->find($request->id);

        $extras = [
            'floor_supervisor_list' => collect(User::select([
                'users.id',
                DB::RAW('CONCAT(users.ecode," / ",users.name) as name'),
            ])
                    ->join('role_user','role_user.user_id','users.id')
                    ->join('permission_role','permission_role.role_id','role_user.role_id')
                    ->where('permission_role.permission_id', 5608) 
                    ->where('users.user_type_id', 1) //EMPLOYEE
                    ->where('users.company_id', $job_card->company_id)
                    ->where('users.working_outlet_id', $job_card->outlet_id)
                    ->groupBy('users.id')
                    ->orderBy('users.name','asc')
                    ->get())->prepend(['id' => '', 'name' => 'Select Floor Supervisor']),
        ];

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
            'extras' => $extras,
        ]);
    }
}

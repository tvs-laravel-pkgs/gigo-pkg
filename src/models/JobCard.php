<?php

namespace Abs\GigoPkg;

use Abs\GigoPkg\JobOrder;
use Abs\HelperPkg\Traits\SeederTrait;
use Abs\ImportCronJobPkg\ImportCronJob;
use Abs\TaxPkg\Tax;
use App\BaseModel;
use App\Company;
use App\Config;
use App\JobCardBilledDetail;
use App\JobCardDetail;
use App\JobOrderIssuedPart;
use App\JobOrderReturnedPart;
use App\SplitOrderType;
use Auth;
use Carbon\Carbon;
use DB;
use File;
use Illuminate\Database\Eloquent\Model;
use PDF;
use PHPExcel_Style_NumberFormat;
use Storage;

// use Illuminate\Database\Eloquent\SoftDeletes;

class JobCard extends BaseModel
{
    use SeederTrait;
    // use SoftDeletes;
    protected $table = 'job_cards';
    public $timestamps = true;
    protected $fillable = [
        "company_id",
        "job_card_number",
        "dms_job_card_number",
        "date",
        "created_by",
        "job_order_id",
        "number",
        "order_number",
        "floor_supervisor_id",
        "status_id",
    ];

    //APPEND - INBETWEEN REGISTRATION NUMBER
    public function getRegistrationNumberAttribute($value)
    {
        $registration_number = '';

        if ($value) {
            $value = str_replace('-', '', $value);
            $reg_number = str_split($value);

            $last_four_numbers = substr($value, -4);

            $registration_number .= $reg_number[0] . $reg_number[1] . '-' . $reg_number[2] . $reg_number[3] . '-';

            if (is_numeric($reg_number[4])) {
                $registration_number .= $last_four_numbers;
            } else {
                $registration_number .= $reg_number[4];
                if (is_numeric($reg_number[5])) {
                    $registration_number .= '-' . $last_four_numbers;
                } else {
                    $registration_number .= $reg_number[5] . '-' . $last_four_numbers;
                }
            }
        }
        return $this->attributes['registration_number'] = $registration_number;
    }

    public function getDateAttribute($value)
    {
        return empty($value) ? '' : date('d-m-Y', strtotime($value));
    }

    public function getCreatedAtAttribute($value)
    {
        return empty($value) ? '' : date('d-m-Y h:i A', strtotime($value));
    }

    public function getWorkCompletedAtAttribute($value)
    {
        return empty($value) ? '' : date('d-m-Y h:i A', strtotime($value));
    }

    public function setDateOfJoinAttribute($date)
    {
        return $this->attributes['date_of_join'] = empty($date) ? null : date('Y-m-d', strtotime($date));
    }

    public function jobOrder()
    {
        return $this->belongsTo('Abs\GigoPkg\JobOrder', 'job_order_id');
    }

    public function outlet()
    {
        return $this->belongsTo('App\Outlet', 'outlet_id')->where('company_id', Auth::user()->company_id);
    }

    public function company()
    {
        return $this->belongsTo('App\Company', 'company_id');
    }

    public function workOrders()
    {
        return $this->hasMany('App\WorkOrder');
    }

    public function business()
    {
        return $this->belongsTo('App\Business', 'business_id')->where('company_id', Auth::user()->company_id);
    }

    public function sacCode()
    {
        return $this->belongsTo('App\Entity', 'sac_code_id')->where('company_id', Auth::user()->company_id);
    }

    public function model()
    {
        return $this->belongsTo('App\Entity', 'model_id')->where('company_id', Auth::user()->company_id);
    }

    public function segment()
    {
        return $this->belongsTo('App\Entity', 'segment_id')->where('company_id', Auth::user()->company_id);
    }

    public function bay()
    {
        return $this->belongsTo('App\Bay', 'bay_id');
    }

    public function status()
    {
        return $this->belongsTo('App\Config', 'status_id');
    }

    public function gatePasses()
    {
        return $this->hasMany('App\GatePass', 'job_card_id', 'id');
    }

    public function jobCardReturnableItems()
    {
        return $this->hasMany('Abs\GigoPkg\JobCardReturnableItem');
    }

    public function gigoInvoices()
    {
        return $this->hasMany('Abs\GigoPkg\GigoInvoice', 'entity_id', 'id');
    }

    public function attachment()
    {
        return $this->hasOne('App\Attachment', 'entity_id', 'id')->where('attachment_of_id', 228)->where('attachment_type_id', 255);
    }

    public function floatLogs()
    {
        return $this->hasMany('App\FloatingGatePass');
    }

    public function floorSupervisor()
    {
        return $this->belongsTo('App\User', 'floor_supervisor_id');
    }

    // Query Scopes --------------------------------------------------------------

    public function scopeFilterSearch($query, $term)
    {
        if (strlen($term)) {
            $query->where(function (Builder $query) use ($term) {
                $query->orWhereRaw("TRIM(CONCAT(full_name, ' ', surname)) LIKE ?", [
                    "%{$term}%",
                ]);
                $query->orWhere('additional_name', 'LIKE', '%' . $term . '%');
                $query->orWhere('alias', 'LIKE', '%' . $term . '%');
                $query->orWhere('end_date', 'LIKE', '%' . $term . '%');
                $query->orWhere('address_1', 'LIKE', '%' . $term . '%');
                $query->orWhere('city', 'LIKE', '%' . $term . '%');
                $query->orWhere('county', 'LIKE', '%' . $term . '%');
                $query->orWhereRaw("REPLACE(postcode, ' ', '') LIKE ?", ['%' . str_replace(' ', '', $term) . '%']);
                $query->orWhere('tel_h', 'LIKE', '%' . $term . '%');
                $query->orWhere('tel_m', 'LIKE', '%' . $term . '%');
                $query->orWhere('email', 'LIKE', '%' . $term . '%');
            });
        }
    }

    // Operations --------------------------------------------------------------

    public static function createFromObject($record_data)
    {

        $errors = [];
        $company = Company::where('code', $record_data->company)->first();
        if (!$company) {
            dump('Invalid Company : ' . $record_data->company);
            return;
        }

        $admin = $company->admin();
        if (!$admin) {
            dump('Default Admin user not found');
            return;
        }

        $type = Config::where('name', $record_data->type)->where('config_type_id', 89)->first();
        if (!$type) {
            $errors[] = 'Invalid Tax Type : ' . $record_data->type;
        }

        if (count($errors) > 0) {
            dump($errors);
            return;
        }

        $record = self::firstOrNew([
            'company_id' => $company->id,
            'name' => $record_data->tax_name,
        ]);
        $record->type_id = $type->id;
        $record->created_by_id = $admin->id;
        $record->save();
        return $record;
    }

    public static function getList($params = [], $add_default = true, $default_text = 'Select Job Card')
    {
        $list = Collect(Self::select([
            'id',
            'name',
        ])
                ->orderBy('name')
                ->get());
        if ($add_default) {
            $list->prepend(['id' => '', 'name' => $default_text]);
        }
        return $list;
    }

    public static function generateRevisedEstimatePDF($job_card_id)
    {

        $data['revised_estimate'] = $job_card = JobCard::with([
            'gatePasses',
            'jobOrder',
            'outlet',
            'jobOrder.type',
            'jobOrder.vehicle',
            'jobOrder.vehicle.model',
            'jobOrder.vehicle.status',
            'jobOrder.outlet',
            'jobOrder.gateLog',
            'jobOrder.customer',
            'jobOrder.customerAddress',
            'jobOrder.customerAddress.country',
            'jobOrder.customerAddress.state',
            'jobOrder.customerAddress.city',
            'jobOrder.serviceType',
            'jobOrder.jobOrderRepairOrders' => function ($q) {
                $q->whereNull('removal_reason_id');
            },
            'jobOrder.jobOrderRepairOrders.repairOrder',
            'jobOrder.jobOrderRepairOrders.repairOrder.repairOrderType',
            'jobOrder.floorAdviser',
            'jobOrder.serviceAdviser',
            'jobOrder.roadTestPreferedBy.employee',
            'jobOrder.jobOrderParts' => function ($q) {
                $q->whereNull('removal_reason_id');
            },
            'jobOrder.jobOrderParts.part',
            'jobOrder.jobOrderParts.part.taxCode',
            'jobOrder.jobOrderParts.part.taxCode.taxes'])
            ->select([
                'job_cards.*',
                DB::raw('DATE_FORMAT(job_cards.created_at,"%d-%m-%Y") as jobdate'),
                DB::raw('DATE_FORMAT(job_cards.created_at,"%h:%i %p") as time'),
            ])
            ->find($job_card_id);

        $parts_amount = 0;
        $labour_amount = 0;
        $total_amount = 0;

        if ($job_card->jobOrder->customerAddress) {
            //Check which tax applicable for customer
            if ($job_card->outlet->state_id == $job_card->jobOrder->customerAddress->state_id) {
                $tax_type = 1160; //Within State
            } else {
                $tax_type = 1161; //Inter State
            }
        } else {
            $tax_type = 1160; //Within State
        }

        $customer_paid_type_id = SplitOrderType::where('paid_by_id', '10013')->pluck('id')->toArray();

        //Count Tax Type
        $taxes = Tax::whereIn('id', [1, 2, 3])->get();

        $tax_percentage_wise_amount = [];

        $labour_details = array();
        if ($job_card->jobOrder->jobOrderRepairOrders) {
            $i = 1;
            $total_labour_qty = 0;
            $total_labour_mrp = 0;
            $total_labour_price = 0;
            $total_labour_tax = 0;
            $total_labour_taxable_amount = 0;

            foreach ($job_card->jobOrder->jobOrderRepairOrders as $key => $labour) {
                $total_amount = 0;
                $labour_details[$key]['sno'] = $i;
                $labour_details[$key]['code'] = $labour->repairOrder->code;
                $labour_details[$key]['name'] = $labour->repairOrder->name;
                $labour_details[$key]['hsn_code'] = $labour->repairOrder->taxCode ? $labour->repairOrder->taxCode->code : '-';
                $labour_details[$key]['qty'] = '1.00';
                $labour_details[$key]['price'] = $labour->amount;
                $labour_details[$key]['mrp'] = $labour->amount;
                $labour_details[$key]['amount'] = $labour->amount;
                $labour_details[$key]['taxable_amount'] = $labour->amount;
                $labour_details[$key]['is_free_service'] = $labour->is_free_service;
                $tax_values = array();

                if ((in_array($labour->split_order_type_id, $customer_paid_type_id) || !$labour->split_order_type_id) && $labour->is_free_service != 1) {
                    $tax_amount = 0;

                    if ($labour->repairOrder->taxCode) {
                        $count = 1;
                        foreach ($labour->repairOrder->taxCode->taxes as $tax_key => $value) {
                            $percentage_value = 0;
                            if ($value->type_id == $tax_type) {
                                $percentage_value = ($labour->amount * $value->pivot->percentage) / 100;
                                $percentage_value = number_format((float) $percentage_value, 2, '.', '');

                                if (isset($tax_percentage_wise_amount[$value->pivot->percentage])) {
                                    if ($count == 1) {
                                        if (isset($tax_percentage_wise_amount[$value->pivot->percentage]['taxable_amount'])) {
                                            $tax_percentage_wise_amount[$value->pivot->percentage]['taxable_amount'] = $tax_percentage_wise_amount[$value->pivot->percentage]['taxable_amount'] + $labour->amount;
                                        } else {
                                            $tax_percentage_wise_amount[$value->pivot->percentage]['taxable_amount'] = $labour->amount;
                                        }
                                    }

                                    if (isset($tax_percentage_wise_amount[$value->pivot->percentage]['tax'][$value->name])) {
                                        $tax_percentage_wise_amount[$value->pivot->percentage]['tax'][$value->name] = $tax_percentage_wise_amount[$value->pivot->percentage]['tax'][$value->name] + $percentage_value;
                                    } else {
                                        $tax_percentage_wise_amount[$value->pivot->percentage]['tax'][$value->name] = $percentage_value;
                                    }
                                } else {
                                    $tax_percentage_wise_amount[$value->pivot->percentage]['tax_percentage'] = $value->pivot->percentage;
                                    $tax_percentage_wise_amount[$value->pivot->percentage]['taxable_amount'] = $labour->amount;
                                    $tax_percentage_wise_amount[$value->pivot->percentage]['tax'][$value->name] = $percentage_value;
                                }
                                $count++;
                            }
                            $tax_values[$tax_key] = $percentage_value;
                            $tax_amount += $percentage_value;

                        }
                    } else {
                        for ($i = 0; $i < count($taxes); $i++) {
                            $tax_values[$i] = 0.00;
                        }
                    }

                    $total_amount = $tax_amount + $labour->amount;
                    $total_amount = number_format((float) $total_amount, 2, '.', '');

                    $total_labour_qty += 1;
                    $total_labour_mrp += $total_amount;
                    $total_labour_price += $labour->repairOrder->amount;
                    $total_labour_tax += $tax_amount;
                    $total_labour_taxable_amount += $labour->amount;

                    $labour_details[$key]['tax_values'] = $tax_values;
                    $labour_details[$key]['tax_amount'] = $tax_amount;

                    $labour_details[$key]['total_amount'] = $total_amount;
                    $labour_details[$key]['mrp'] = $total_amount;

                    // if ($labour->is_free_service != 1) {
                    $labour_amount += $total_amount;
                    // }
                } else {
                    for ($i = 0; $i < count($taxes); $i++) {
                        $tax_values[$i] = 0.00;
                    }

                    $labour_details[$key]['tax_values'] = $tax_values;
                    $labour_details[$key]['total_amount'] = '0.00';
                }
                $i++;
            }
        }

        $part_details = array();
        if ($job_card->jobOrder->jobOrderParts) {
            $j = 1;
            $total_parts_qty = 0;
            $total_parts_mrp = 0;
            $total_parts_price = 0;
            $total_parts_tax = 0;
            $total_parts_taxable_amount = 0;

            foreach ($job_card->jobOrder->jobOrderParts as $key => $parts) {
                $total_amount = 0;
                $part_details[$key]['sno'] = $j;
                $part_details[$key]['code'] = $parts->part->code;
                $part_details[$key]['name'] = $parts->part->name;
                $part_details[$key]['hsn_code'] = $parts->part->taxCode ? $parts->part->taxCode->code : '-';
                $part_details[$key]['qty'] = $parts->qty;
                $part_details[$key]['mrp'] = $parts->rate;
                $part_details[$key]['price'] = $parts->rate;
                // $part_details[$key]['amount'] = $parts->amount;
                $part_details[$key]['is_free_service'] = $parts->is_free_service;
                $tax_amount = 0;
                $tax_percentage = 0;

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

                $total_price = $price * $parts->qty;
                $part_details[$key]['taxable_amount'] = $total_price;

                $tax_values = array();
                if ((in_array($parts->split_order_type_id, $customer_paid_type_id) || !$parts->split_order_type_id) && $parts->is_free_service != 1) {
                    if ($parts->part->taxCode) {
                        $count = 1;
                        foreach ($parts->part->taxCode->taxes as $tax_key => $value) {
                            $percentage_value = 0;
                            if ($value->type_id == $tax_type) {

                                $percentage_value = ($total_price * $value->pivot->percentage) / 100;
                                $percentage_value = number_format((float) $percentage_value, 2, '.', '');

                                if (isset($tax_percentage_wise_amount[$value->pivot->percentage])) {
                                    if ($count == 1) {
                                        if (isset($tax_percentage_wise_amount[$value->pivot->percentage]['taxable_amount'])) {
                                            $tax_percentage_wise_amount[$value->pivot->percentage]['taxable_amount'] = $tax_percentage_wise_amount[$value->pivot->percentage]['taxable_amount'] + $total_price;
                                        } else {
                                            $tax_percentage_wise_amount[$value->pivot->percentage]['taxable_amount'] = $total_price;
                                        }
                                    }

                                    if (isset($tax_percentage_wise_amount[$value->pivot->percentage]['tax'][$value->name])) {
                                        $tax_percentage_wise_amount[$value->pivot->percentage]['tax'][$value->name] = $tax_percentage_wise_amount[$value->pivot->percentage]['tax'][$value->name] + $percentage_value;
                                    } else {
                                        $tax_percentage_wise_amount[$value->pivot->percentage]['tax'][$value->name] = $percentage_value;
                                    }
                                } else {
                                    $tax_percentage_wise_amount[$value->pivot->percentage]['tax_percentage'] = $value->pivot->percentage;
                                    $tax_percentage_wise_amount[$value->pivot->percentage]['taxable_amount'] = $total_price;
                                    $tax_percentage_wise_amount[$value->pivot->percentage]['tax'][$value->name] = $percentage_value;
                                }

                                $count++;
                            }
                            $tax_values[$tax_key] = $percentage_value;
                            $tax_amount += $percentage_value;

                        }
                    } else {
                        for ($i = 0; $i < count($taxes); $i++) {
                            $tax_values[$i] = 0.00;
                        }
                    }

                    $total_parts_qty += $parts->qty;
                    $total_parts_mrp += $parts->rate;
                    $total_parts_price += $price;
                    $total_parts_tax += $tax_amount;
                    $total_parts_taxable_amount += $total_price;

                    $part_details[$key]['tax_values'] = $tax_values;
                    // $total_amount = $tax_amount + $parts->amount;
                    $total_amount = $parts->amount;
                    $total_amount = number_format((float) $total_amount, 2, '.', '');
                    if ($parts->is_free_service != 1) {
                        $parts_amount += $total_amount;
                    }
                    $part_details[$key]['total_amount'] = $total_amount;
                } else {
                    for ($i = 0; $i < count($taxes); $i++) {
                        $tax_values[$i] = 0.00;
                    }

                    $part_details[$key]['tax_values'] = $tax_values;
                    $part_details[$key]['total_amount'] = '0.00';
                }
                $j++;
            }
        }

        $data['tax_percentage_wise_amount'] = $tax_percentage_wise_amount;

        $total_amount = $parts_amount + $labour_amount;
        $data['taxes'] = $taxes;
        $data['date'] = date('d-m-Y');
        $data['part_details'] = $part_details;
        $data['labour_details'] = $labour_details;
        $data['total_labour_qty'] = number_format((float) $total_labour_qty, 2, '.', '');
        $data['total_labour_mrp'] = number_format((float) $total_labour_mrp, 2, '.', '');
        $data['total_labour_price'] = number_format((float) $total_labour_price, 2, '.', '');
        $data['total_labour_tax'] = number_format((float) $total_labour_tax, 2, '.', '');
        $data['total_labour_taxable_amount'] = number_format((float) $total_labour_taxable_amount, 2, '.', '');

        $data['total_parts_qty'] = number_format((float) $total_parts_qty, 2, '.', '');
        $data['total_parts_mrp'] = number_format((float) $total_parts_mrp, 2, '.', '');
        $data['total_parts_price'] = number_format((float) $total_parts_price, 2, '.', '');
        $data['total_parts_taxable_amount'] = number_format((float) $total_parts_taxable_amount, 2, '.', '');
        $data['parts_total_amount'] = number_format($parts_amount, 2);
        $data['labour_total_amount'] = number_format($labour_amount, 2);

        //FOR ROUND OFF
        if ($total_amount <= round($total_amount)) {
            $round_off = round($total_amount) - $total_amount;
        } else {
            $round_off = round($total_amount) - $total_amount;
        }

        $data['round_total_amount'] = number_format($round_off, 2);
        $data['total_amount'] = number_format(round($total_amount), 2);

        $total_amount_wordings = convert_number_to_words(round($total_amount));
        $data['total_amount_wordings'] = strtoupper($total_amount_wordings) . ' Rupees ONLY';

        $data['title'] = 'Revised Estimate';

        $save_path = storage_path('app/public/gigo/pdf');
        Storage::makeDirectory($save_path, 0777);

        if (!Storage::disk('public')->has('gigo/pdf/')) {
            Storage::disk('public')->makeDirectory('gigo/pdf/');
        }

        $name = $job_card->jobOrder->id . '_revised_estimate.pdf';

        $pdf = PDF::loadView('pdf-gigo/revised-estimate-pdf', $data)->setPaper('a4', 'portrait');

        $img_path = $save_path . '/' . $name;
        if (File::exists($img_path)) {
            File::delete($img_path);
        }

        $pdf->save(storage_path('app/public/gigo/pdf/' . $name));

        return true;
    }

    public static function generateInvoicePDF($job_card_id)
    {

        $data['invoice'] = $job_card = JobCard::with([
            'gatePasses',
            'jobOrder',
            'outlet',
            'jobOrder.type',
            'jobOrder.vehicle',
            'jobOrder.vehicle.model',
            'jobOrder.vehicle.status',
            'jobOrder.outlet',
            'jobOrder.gateLog',
            'jobOrder.customer',
            'jobOrder.customerAddress',
            'jobOrder.customerAddress.country',
            'jobOrder.customerAddress.state',
            'jobOrder.customerAddress.city',
            'jobOrder.serviceType',
            'jobOrder.jobOrderRepairOrders' => function ($q) {
                $q->whereNull('removal_reason_id');
            },
            'jobOrder.jobOrderRepairOrders.repairOrder',
            'jobOrder.jobOrderRepairOrders.repairOrder.repairOrderType',
            'jobOrder.floorAdviser',
            'jobOrder.serviceAdviser',
            'jobOrder.roadTestPreferedBy.employee',
            'jobOrder.jobOrderParts' => function ($q) {
                $q->whereNull('removal_reason_id');
            },
            'jobOrder.jobOrderParts.part',
            'jobOrder.jobOrderParts.part.taxCode',
            'jobOrder.jobOrderParts.part.taxCode.taxes'])
            ->select([
                'job_cards.*',
                DB::raw('DATE_FORMAT(job_cards.created_at,"%d-%m-%Y") as jobdate'),
                DB::raw('DATE_FORMAT(job_cards.created_at,"%h:%i %p") as time'),
            ])
            ->find($job_card_id);

        $parts_amount = 0;
        $labour_amount = 0;
        $total_amount = 0;

        if ($job_card->jobOrder->customerAddress) {
            //Check which tax applicable for customer
            if ($job_card->outlet->state_id == $job_card->jobOrder->customerAddress->state_id) {
                $tax_type = 1160; //Within State
            } else {
                $tax_type = 1161; //Inter State
            }
        } else {
            $tax_type = 1160; //Within State
        }

        $customer_paid_type_id = SplitOrderType::where('paid_by_id', '10013')->pluck('id')->toArray();

        //Count Tax Type
        $taxes = Tax::whereIn('id', [1, 2, 3])->get();

        $tax_percentage_wise_amount = [];

        $labour_details = array();
        if ($job_card->jobOrder->jobOrderRepairOrders) {
            $i = 1;
            $total_labour_qty = 0;
            $total_labour_mrp = 0;
            $total_labour_price = 0;
            $total_labour_tax = 0;
            $total_labour_taxable_amount = 0;

            foreach ($job_card->jobOrder->jobOrderRepairOrders as $key => $labour) {
                $total_amount = 0;
                $labour_details[$key]['sno'] = $i;
                $labour_details[$key]['code'] = $labour->repairOrder->code;
                $labour_details[$key]['name'] = $labour->repairOrder->name;
                $labour_details[$key]['hsn_code'] = $labour->repairOrder->taxCode ? $labour->repairOrder->taxCode->code : '-';
                $labour_details[$key]['qty'] = '1.00';
                $labour_details[$key]['price'] = $labour->amount;
                $labour_details[$key]['mrp'] = $labour->amount;
                $labour_details[$key]['amount'] = $labour->amount;
                $labour_details[$key]['taxable_amount'] = $labour->amount;
                $labour_details[$key]['is_free_service'] = $labour->is_free_service;
                $tax_values = array();

                if ((in_array($labour->split_order_type_id, $customer_paid_type_id) || !$labour->split_order_type_id) && $labour->is_free_service != 1) {
                    $tax_amount = 0;

                    if ($labour->repairOrder->taxCode) {
                        $count = 1;
                        foreach ($labour->repairOrder->taxCode->taxes as $tax_key => $value) {
                            $percentage_value = 0;
                            if ($value->type_id == $tax_type) {
                                $percentage_value = ($labour->amount * $value->pivot->percentage) / 100;
                                $percentage_value = number_format((float) $percentage_value, 2, '.', '');

                                if (isset($tax_percentage_wise_amount[$value->pivot->percentage])) {
                                    if ($count == 1) {
                                        if (isset($tax_percentage_wise_amount[$value->pivot->percentage]['taxable_amount'])) {
                                            $tax_percentage_wise_amount[$value->pivot->percentage]['taxable_amount'] = $tax_percentage_wise_amount[$value->pivot->percentage]['taxable_amount'] + $labour->amount;
                                        } else {
                                            $tax_percentage_wise_amount[$value->pivot->percentage]['taxable_amount'] = $labour->amount;
                                        }
                                    }

                                    if (isset($tax_percentage_wise_amount[$value->pivot->percentage]['tax'][$value->name])) {
                                        $tax_percentage_wise_amount[$value->pivot->percentage]['tax'][$value->name] = $tax_percentage_wise_amount[$value->pivot->percentage]['tax'][$value->name] + $percentage_value;
                                    } else {
                                        $tax_percentage_wise_amount[$value->pivot->percentage]['tax'][$value->name] = $percentage_value;
                                    }
                                } else {
                                    $tax_percentage_wise_amount[$value->pivot->percentage]['tax_percentage'] = $value->pivot->percentage;
                                    $tax_percentage_wise_amount[$value->pivot->percentage]['taxable_amount'] = $labour->amount;
                                    $tax_percentage_wise_amount[$value->pivot->percentage]['tax'][$value->name] = $percentage_value;
                                }
                                $count++;
                            }
                            $tax_values[$tax_key] = $percentage_value;
                            $tax_amount += $percentage_value;

                        }
                    } else {
                        for ($i = 0; $i < count($taxes); $i++) {
                            $tax_values[$i] = 0.00;
                        }
                    }

                    $total_amount = $tax_amount + $labour->amount;
                    $total_amount = number_format((float) $total_amount, 2, '.', '');

                    $total_labour_qty += 1;
                    $total_labour_mrp += $total_amount;
                    $total_labour_price += $labour->repairOrder->amount;
                    $total_labour_tax += $tax_amount;
                    $total_labour_taxable_amount += $labour->amount;

                    $labour_details[$key]['tax_values'] = $tax_values;
                    $labour_details[$key]['tax_amount'] = $tax_amount;

                    $labour_details[$key]['total_amount'] = $total_amount;
                    $labour_details[$key]['mrp'] = $total_amount;

                    // if ($labour->is_free_service != 1) {
                    $labour_amount += $total_amount;
                    // }
                } else {
                    for ($i = 0; $i < count($taxes); $i++) {
                        $tax_values[$i] = 0.00;
                    }

                    $labour_details[$key]['tax_values'] = $tax_values;
                    $labour_details[$key]['total_amount'] = '0.00';
                }
                $i++;
            }
        }

        $part_details = array();
        if ($job_card->jobOrder->jobOrderParts) {
            $j = 1;
            $total_parts_qty = 0;
            $total_parts_mrp = 0;
            $total_parts_price = 0;
            $total_parts_tax = 0;
            $total_parts_taxable_amount = 0;

            foreach ($job_card->jobOrder->jobOrderParts as $key => $parts) {

                $qty = $parts->qty;
                //Issued Qty
                $issued_qty = JobOrderIssuedPart::where('job_order_part_id', $parts->id)->sum('issued_qty');
                //Returned Qty
                $returned_qty = JobOrderReturnedPart::where('job_order_part_id', $parts->id)->sum('returned_qty');

                $qty = $issued_qty - $returned_qty;

                if ($qty > 0) {
                    $total_amount = 0;
                    $part_details[$key]['sno'] = $j;
                    $part_details[$key]['code'] = $parts->part->code;
                    $part_details[$key]['name'] = $parts->part->name;
                    $part_details[$key]['hsn_code'] = $parts->part->taxCode ? $parts->part->taxCode->code : '-';
                    $part_details[$key]['qty'] = $parts->qty;
                    $part_details[$key]['mrp'] = $parts->rate;
                    $part_details[$key]['price'] = $parts->rate;
                    // $part_details[$key]['amount'] = $parts->amount;
                    $part_details[$key]['is_free_service'] = $parts->is_free_service;
                    $tax_amount = 0;
                    $tax_percentage = 0;

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
                    $part_details[$key]['taxable_amount'] = $total_price;

                    $tax_values = array();
                    if ((in_array($parts->split_order_type_id, $customer_paid_type_id) || !$parts->split_order_type_id) && $parts->is_free_service != 1) {
                        if ($parts->part->taxCode) {
                            $count = 1;
                            foreach ($parts->part->taxCode->taxes as $tax_key => $value) {
                                $percentage_value = 0;
                                if ($value->type_id == $tax_type) {

                                    $percentage_value = ($total_price * $value->pivot->percentage) / 100;
                                    $percentage_value = number_format((float) $percentage_value, 2, '.', '');

                                    if (isset($tax_percentage_wise_amount[$value->pivot->percentage])) {
                                        if ($count == 1) {
                                            if (isset($tax_percentage_wise_amount[$value->pivot->percentage]['taxable_amount'])) {
                                                $tax_percentage_wise_amount[$value->pivot->percentage]['taxable_amount'] = $tax_percentage_wise_amount[$value->pivot->percentage]['taxable_amount'] + $total_price;
                                            } else {
                                                $tax_percentage_wise_amount[$value->pivot->percentage]['taxable_amount'] = $total_price;
                                            }
                                        }

                                        if (isset($tax_percentage_wise_amount[$value->pivot->percentage]['tax'][$value->name])) {
                                            $tax_percentage_wise_amount[$value->pivot->percentage]['tax'][$value->name] = $tax_percentage_wise_amount[$value->pivot->percentage]['tax'][$value->name] + $percentage_value;
                                        } else {
                                            $tax_percentage_wise_amount[$value->pivot->percentage]['tax'][$value->name] = $percentage_value;
                                        }
                                    } else {
                                        $tax_percentage_wise_amount[$value->pivot->percentage]['tax_percentage'] = $value->pivot->percentage;
                                        $tax_percentage_wise_amount[$value->pivot->percentage]['taxable_amount'] = $total_price;
                                        $tax_percentage_wise_amount[$value->pivot->percentage]['tax'][$value->name] = $percentage_value;
                                    }

                                    $count++;
                                }
                                $tax_values[$tax_key] = $percentage_value;
                                $tax_amount += $percentage_value;
                            }
                        } else {
                            for ($i = 0; $i < count($taxes); $i++) {
                                $tax_values[$i] = 0.00;
                            }
                        }

                        $part_details[$key]['tax_values'] = $tax_values;

                        $total_amount = $parts->rate * $qty;

                        $total_amount = number_format((float) $total_amount, 2, '.', '');
                        if ($parts->is_free_service != 1) {
                            $parts_amount += $total_amount;
                        }
                        $part_details[$key]['total_amount'] = $total_amount;

                        $total_parts_qty += $qty;
                        $total_parts_mrp += $total_amount;
                        $total_parts_price += $price;
                        $total_parts_tax += $tax_amount;
                        $total_parts_taxable_amount += $total_price;

                    } else {
                        for ($i = 0; $i < count($taxes); $i++) {
                            $tax_values[$i] = 0.00;
                        }

                        $part_details[$key]['tax_values'] = $tax_values;
                        $part_details[$key]['total_amount'] = '0.00';
                    }
                    $j++;
                }
            }
        }

        $data['tax_percentage_wise_amount'] = $tax_percentage_wise_amount;

        $total_amount = $parts_amount + $labour_amount;
        $data['taxes'] = $taxes;
        $data['date'] = date('d-m-Y');
        $data['part_details'] = $part_details;
        $data['labour_details'] = $labour_details;
        $data['total_labour_qty'] = number_format((float) $total_labour_qty, 2, '.', '');
        $data['total_labour_mrp'] = number_format((float) $total_labour_mrp, 2, '.', '');
        $data['total_labour_price'] = number_format((float) $total_labour_price, 2, '.', '');
        $data['total_labour_tax'] = number_format((float) $total_labour_tax, 2, '.', '');
        $data['total_labour_taxable_amount'] = number_format((float) $total_labour_taxable_amount, 2, '.', '');

        $data['total_parts_qty'] = number_format((float) $total_parts_qty, 2, '.', '');
        $data['total_parts_mrp'] = number_format((float) $total_parts_mrp, 2, '.', '');
        $data['total_parts_price'] = number_format((float) $total_parts_price, 2, '.', '');
        $data['total_parts_taxable_amount'] = number_format((float) $total_parts_taxable_amount, 2, '.', '');
        $data['parts_total_amount'] = number_format($parts_amount, 2);
        $data['labour_total_amount'] = number_format($labour_amount, 2);

        //FOR ROUND OFF
        if ($total_amount <= round($total_amount)) {
            $round_off = round($total_amount) - $total_amount;
        } else {
            $round_off = round($total_amount) - $total_amount;
        }

        $data['round_total_amount'] = number_format($round_off, 2);
        $data['total_amount'] = number_format(round($total_amount), 2);

        $total_amount_wordings = convert_number_to_words(round($total_amount));
        $data['total_amount_wordings'] = strtoupper($total_amount_wordings) . ' Rupees ONLY';

        $data['title'] = 'Invoice';

        $save_path = storage_path('app/public/gigo/pdf');
        Storage::makeDirectory($save_path, 0777);

        if (!Storage::disk('public')->has('gigo/pdf/')) {
            Storage::disk('public')->makeDirectory('gigo/pdf/');
        }

        $name = $job_card->jobOrder->id . '_invoice.pdf';

        $pdf = PDF::loadView('pdf-gigo/invoice-pdf', $data)->setPaper('a4', 'portrait');

        $img_path = $save_path . '/' . $name;
        if (File::exists($img_path)) {
            File::delete($img_path);
        }

        $pdf->save(storage_path('app/public/gigo/pdf/' . $name));

        return true;
    }

    public static function generateJobcardLabourPDF($job_card_id)
    {

        $split_order_type_ids = SplitOrderType::where('paid_by_id', '10013')->pluck('id')->toArray();

        $data['job_card'] = $job_card = JobCard::with([
            'outlet',
            'jobOrder',
            'jobOrder.outlet',
            'jobOrder.customerAddress',
            'jobOrder.serviceType',
            'jobOrder.type',
            'jobOrder.vehicle',
            'jobOrder.vehicle.model',
            'jobOrder.jobOrderRepairOrders' => function ($query) use ($split_order_type_ids) {
                $query->whereIn('job_order_repair_orders.split_order_type_id', $split_order_type_ids)->whereNull('removal_reason_id');
            },
            'jobOrder.jobOrderRepairOrders.repairOrder',
            'jobOrder.jobOrderRepairOrders.repairOrder.repairOrderType',
            'jobOrder.jobOrderRepairOrders.repairOrder.taxCode',
            'jobOrder.jobOrderRepairOrders.repairOrder.taxCode.taxes',
            'status',
        ])
            ->find($job_card_id);

        if (!$job_card) {
            return response()->json([
                'success' => false,
                'error' => 'Job Card Not found!',
            ]);
        }

        $job_card['creation_date'] = date('d-m-Y', strtotime($job_card->created_at));
        $data['date'] = date('d-m-Y');

        $labour_amount = 0;
        $total_amount = 0;

        if ($job_card->jobOrder->customerAddress) {
            //Check which tax applicable for customer
            if ($job_card->outlet->state_id == $job_card->jobOrder->customerAddress->state_id) {
                $tax_type = 1160; //Within State
            } else {
                $tax_type = 1161; //Inter State
            }
        } else {
            $tax_type = 1160; //Within State
        }

        //Count Tax Type
        $taxes = Tax::whereIn('id', [1, 2, 3])->get();

        $tax_percentage_wise_amount = [];

        $labour_details = array();
        if ($job_card->jobOrder->jobOrderRepairOrders) {
            $i = 1;
            $total_labour_qty = 0;
            $total_labour_mrp = 0;
            $total_labour_price = 0;
            $total_labour_tax = 0;
            $total_labour_taxable_amount = 0;

            foreach ($job_card->jobOrder->jobOrderRepairOrders as $key => $labour) {
                $total_amount = 0;
                $labour_details[$key]['sno'] = $i;
                $labour_details[$key]['code'] = $labour->repairOrder->code;
                $labour_details[$key]['name'] = $labour->repairOrder->name;
                $labour_details[$key]['hsn_code'] = $labour->repairOrder->taxCode ? $labour->repairOrder->taxCode->code : '-';
                $labour_details[$key]['qty'] = '1.00';
                $labour_details[$key]['price'] = $labour->amount;
                $labour_details[$key]['mrp'] = $labour->amount;
                $labour_details[$key]['amount'] = $labour->amount;
                $labour_details[$key]['taxable_amount'] = $labour->amount;
                $labour_details[$key]['is_free_service'] = $labour->is_free_service;
                $tax_values = array();

                if ($labour->is_free_service != 1) {
                    $tax_amount = 0;

                    if ($labour->repairOrder->taxCode) {
                        $count = 1;
                        foreach ($labour->repairOrder->taxCode->taxes as $tax_key => $value) {
                            $percentage_value = 0;
                            if ($value->type_id == $tax_type) {
                                $percentage_value = ($labour->amount * $value->pivot->percentage) / 100;
                                $percentage_value = number_format((float) $percentage_value, 2, '.', '');

                                if (isset($tax_percentage_wise_amount[$value->pivot->percentage])) {
                                    if ($count == 1) {
                                        if (isset($tax_percentage_wise_amount[$value->pivot->percentage]['taxable_amount'])) {
                                            $tax_percentage_wise_amount[$value->pivot->percentage]['taxable_amount'] = $tax_percentage_wise_amount[$value->pivot->percentage]['taxable_amount'] + $labour->amount;
                                        } else {
                                            $tax_percentage_wise_amount[$value->pivot->percentage]['taxable_amount'] = $labour->amount;
                                        }
                                    }

                                    if (isset($tax_percentage_wise_amount[$value->pivot->percentage]['tax'][$value->name])) {
                                        $tax_percentage_wise_amount[$value->pivot->percentage]['tax'][$value->name] = $tax_percentage_wise_amount[$value->pivot->percentage]['tax'][$value->name] + $percentage_value;
                                    } else {
                                        $tax_percentage_wise_amount[$value->pivot->percentage]['tax'][$value->name] = $percentage_value;
                                    }
                                } else {
                                    $tax_percentage_wise_amount[$value->pivot->percentage]['tax_percentage'] = $value->pivot->percentage;
                                    $tax_percentage_wise_amount[$value->pivot->percentage]['taxable_amount'] = $labour->amount;
                                    $tax_percentage_wise_amount[$value->pivot->percentage]['tax'][$value->name] = $percentage_value;
                                }
                                $count++;
                            }
                            $tax_values[$tax_key] = $percentage_value;
                            $tax_amount += $percentage_value;

                        }
                    } else {
                        for ($i = 0; $i < count($taxes); $i++) {
                            $tax_values[$i] = 0.00;
                        }
                    }

                    $total_amount = $tax_amount + $labour->amount;
                    $total_amount = number_format((float) $total_amount, 2, '.', '');

                    $total_labour_qty += 1;
                    $total_labour_mrp += $total_amount;
                    $total_labour_price += $labour->repairOrder->amount;
                    $total_labour_tax += $tax_amount;
                    $total_labour_taxable_amount += $labour->amount;

                    $labour_details[$key]['tax_values'] = $tax_values;
                    $labour_details[$key]['tax_amount'] = $tax_amount;

                    $labour_details[$key]['total_amount'] = $total_amount;
                    $labour_details[$key]['mrp'] = $total_amount;

                    // if ($labour->is_free_service != 1) {
                    $labour_amount += $total_amount;
                    // }
                } else {
                    for ($i = 0; $i < count($taxes); $i++) {
                        $tax_values[$i] = 0.00;
                    }

                    $labour_details[$key]['tax_values'] = $tax_values;
                    $labour_details[$key]['total_amount'] = '0.00';
                }
                $i++;
            }
        }

        $data['tax_percentage_wise_amount'] = $tax_percentage_wise_amount;

        $total_amount = $labour_amount;
        $data['taxes'] = $taxes;
        $data['date'] = date('d-m-Y');
        $data['labour_details'] = $labour_details;
        $data['total_labour_qty'] = number_format((float) $total_labour_qty, 2, '.', '');
        $data['total_labour_mrp'] = number_format((float) $total_labour_mrp, 2, '.', '');
        $data['total_labour_price'] = number_format((float) $total_labour_price, 2, '.', '');
        $data['total_labour_tax'] = number_format((float) $total_labour_tax, 2, '.', '');
        $data['total_labour_taxable_amount'] = number_format((float) $total_labour_taxable_amount, 2, '.', '');

        $data['labour_total_amount'] = number_format($labour_amount, 2);

        //FOR ROUND OFF
        if ($total_amount <= round($total_amount)) {
            $round_off = round($total_amount) - $total_amount;
        } else {
            $round_off = round($total_amount) - $total_amount;
        }

        $data['round_total_amount'] = number_format($round_off, 2);
        $data['total_amount'] = number_format(round($total_amount), 2);

        $total_amount_wordings = convert_number_to_words(round($total_amount));
        $data['total_amount_wordings'] = strtoupper($total_amount_wordings) . ' Rupees ONLY';

        $save_path = storage_path('app/public/gigo/pdf');
        Storage::makeDirectory($save_path, 0777);

        $data['date'] = date('d-m-Y');

        $name = $job_card->id . '_labour_invoice.pdf';

        $pdf = PDF::loadView('pdf-gigo/bill-detail-labour-pdf', $data)->setPaper('a4', 'portrait');

        $img_path = $save_path . '/' . $name;
        if (File::exists($img_path)) {
            File::delete($img_path);
        }

        $pdf->save(storage_path('app/public/gigo/pdf/' . $name));

        return true;
    }

    public static function generateJobcardPartPDF($job_card_id)
    {

        $split_order_type_ids = SplitOrderType::where('paid_by_id', '10013')->pluck('id')->toArray();

        $data['job_card'] = $job_card = JobCard::with([
            'outlet',
            'jobOrder',
            'jobOrder.customerAddress',
            'jobOrder.outlet',
            'jobOrder.serviceType',
            'jobOrder.type',
            'jobOrder.vehicle',
            'jobOrder.vehicle.model',
            'jobOrder.jobOrderParts' => function ($query) use ($split_order_type_ids) {
                $query->whereIn('job_order_parts.split_order_type_id', $split_order_type_ids)->whereNull('removal_reason_id');
            },
            'jobOrder.jobOrderParts.part',
            'jobOrder.jobOrderParts.part.taxCode',
            'jobOrder.jobOrderParts.part.taxCode.taxes',
            'status',
        ])
            ->find($job_card_id);

        if (!$job_card) {
            return response()->json([
                'success' => false,
                'error' => 'Job Card Not found!',
            ]);
        }

        $job_card['creation_date'] = date('d-m-Y', strtotime($job_card->created_at));

        $parts_amount = 0;
        $total_amount = 0;

        if ($job_card->jobOrder->customerAddress) {
            //Check which tax applicable for customer
            if ($job_card->outlet->state_id == $job_card->jobOrder->customerAddress->state_id) {
                $tax_type = 1160; //Within State
            } else {
                $tax_type = 1161; //Inter State
            }
        } else {
            $tax_type = 1160; //Within State
        }

        //Count Tax Type
        $taxes = Tax::whereIn('id', [1, 2, 3])->get();

        $tax_percentage_wise_amount = [];

        $part_details = array();
        if ($job_card->jobOrder->jobOrderParts) {
            $j = 1;
            $total_parts_qty = 0;
            $total_parts_mrp = 0;
            $total_parts_price = 0;
            $total_parts_tax = 0;
            $total_parts_taxable_amount = 0;

            foreach ($job_card->jobOrder->jobOrderParts as $key => $parts) {
                $qty = $parts->qty;
                //Issued Qty
                $issued_qty = JobOrderIssuedPart::where('job_order_part_id', $parts->id)->sum('issued_qty');
                //Returned Qty
                $returned_qty = JobOrderReturnedPart::where('job_order_part_id', $parts->id)->sum('returned_qty');

                $qty = $issued_qty - $returned_qty;
                $qty = number_format($qty, 2);

                if ($qty > 0) {
                    $total_amount = 0;
                    $part_details[$key]['sno'] = $j;
                    $part_details[$key]['code'] = $parts->part->code;
                    $part_details[$key]['name'] = $parts->part->name;
                    $part_details[$key]['hsn_code'] = $parts->part->taxCode ? $parts->part->taxCode->code : '-';
                    $part_details[$key]['qty'] = $qty;
                    $part_details[$key]['mrp'] = $parts->rate;
                    $part_details[$key]['price'] = $parts->rate;
                    // $part_details[$key]['amount'] = $parts->amount;
                    $part_details[$key]['is_free_service'] = $parts->is_free_service;
                    $tax_amount = 0;
                    $tax_percentage = 0;

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
                    $part_details[$key]['taxable_amount'] = $total_price;

                    $tax_values = array();
                    if ($parts->is_free_service != 1) {
                        if ($parts->part->taxCode) {
                            $count = 1;
                            foreach ($parts->part->taxCode->taxes as $tax_key => $value) {
                                $percentage_value = 0;
                                if ($value->type_id == $tax_type) {

                                    $percentage_value = ($total_price * $value->pivot->percentage) / 100;
                                    $percentage_value = number_format((float) $percentage_value, 2, '.', '');

                                    if (isset($tax_percentage_wise_amount[$value->pivot->percentage])) {
                                        if ($count == 1) {
                                            if (isset($tax_percentage_wise_amount[$value->pivot->percentage]['taxable_amount'])) {
                                                $tax_percentage_wise_amount[$value->pivot->percentage]['taxable_amount'] = $tax_percentage_wise_amount[$value->pivot->percentage]['taxable_amount'] + $total_price;
                                            } else {
                                                $tax_percentage_wise_amount[$value->pivot->percentage]['taxable_amount'] = $total_price;
                                            }
                                        }

                                        if (isset($tax_percentage_wise_amount[$value->pivot->percentage]['tax'][$value->name])) {
                                            $tax_percentage_wise_amount[$value->pivot->percentage]['tax'][$value->name] = $tax_percentage_wise_amount[$value->pivot->percentage]['tax'][$value->name] + $percentage_value;
                                        } else {
                                            $tax_percentage_wise_amount[$value->pivot->percentage]['tax'][$value->name] = $percentage_value;
                                        }
                                    } else {
                                        $tax_percentage_wise_amount[$value->pivot->percentage]['tax_percentage'] = $value->pivot->percentage;
                                        $tax_percentage_wise_amount[$value->pivot->percentage]['taxable_amount'] = $total_price;
                                        $tax_percentage_wise_amount[$value->pivot->percentage]['tax'][$value->name] = $percentage_value;
                                    }

                                    $count++;
                                }
                                $tax_values[$tax_key] = $percentage_value;
                                $tax_amount += $percentage_value;

                            }
                        } else {
                            for ($i = 0; $i < count($taxes); $i++) {
                                $tax_values[$i] = 0.00;
                            }
                        }

                        $total_parts_qty += $qty;
                        $total_parts_mrp += $parts->rate;
                        $total_parts_price += $price;
                        $total_parts_tax += $tax_amount;
                        $total_parts_taxable_amount += $total_price;

                        $part_details[$key]['tax_values'] = $tax_values;
                        // $total_amount = $tax_amount + $parts->amount;
                        $total_amount = $parts->rate * $qty;
                        $total_amount = number_format((float) $total_amount, 2, '.', '');
                        if ($parts->is_free_service != 1) {
                            $parts_amount += $total_amount;
                        }
                        $part_details[$key]['total_amount'] = $total_amount;
                    } else {
                        for ($i = 0; $i < count($taxes); $i++) {
                            $tax_values[$i] = 0.00;
                        }

                        $part_details[$key]['tax_values'] = $tax_values;
                        $part_details[$key]['total_amount'] = '0.00';
                    }
                    $j++;
                }
            }
        }

        $data['tax_percentage_wise_amount'] = $tax_percentage_wise_amount;

        $total_amount = $parts_amount;
        $data['taxes'] = $taxes;
        $data['date'] = date('d-m-Y');
        $data['part_details'] = $part_details;

        $data['total_parts_qty'] = number_format((float) $total_parts_qty, 2, '.', '');
        $data['total_parts_mrp'] = number_format((float) $total_parts_mrp, 2, '.', '');
        $data['total_parts_price'] = number_format((float) $total_parts_price, 2, '.', '');
        $data['total_parts_taxable_amount'] = number_format((float) $total_parts_taxable_amount, 2, '.', '');
        $data['parts_total_amount'] = number_format($parts_amount, 2);

        //FOR ROUND OFF
        if ($total_amount <= round($total_amount)) {
            $round_off = round($total_amount) - $total_amount;
        } else {
            $round_off = round($total_amount) - $total_amount;
        }

        $data['round_total_amount'] = number_format($round_off, 2);
        $data['total_amount'] = number_format(round($total_amount), 2);

        $total_amount_wordings = convert_number_to_words(round($total_amount));
        $data['total_amount_wordings'] = strtoupper($total_amount_wordings) . ' Rupees ONLY';

        $save_path = storage_path('app/public/gigo/pdf');
        Storage::makeDirectory($save_path, 0777);

        $data['date'] = date('d-m-Y');

        $name = $job_card->id . '_part_invoice.pdf';

        $pdf = PDF::loadView('pdf-gigo/bill-detail-part-pdf', $data)->setPaper('a4', 'portrait');

        $img_path = $save_path . '/' . $name;
        if (File::exists($img_path)) {
            File::delete($img_path);
        }

        $pdf->save(storage_path('app/public/gigo/pdf/' . $name));

        return true;
    }

    public static function generateGatePassPDF($job_card_id, $type)
    {
        $data['gate_pass'] = $job_card = JobCard::with([
            'jobOrder',
            'jobOrder.type',
            'jobOrder.quoteType',
            'jobOrder.serviceType',
            'jobOrder.vehicle',
            'jobOrder.vehicle.model',
            'jobOrder.vehicle.status',
            'jobOrder.outlet',
            'jobOrder.gateLog',
            'jobOrder.gatePass',
            'jobOrder.vehicle.currentOwner.customer',
            'jobOrder.vehicle.currentOwner.customer.primaryAddress',
            'jobOrder.vehicle.currentOwner.customer.primaryAddress.country',
            'jobOrder.vehicle.currentOwner.customer.primaryAddress.state',
            'jobOrder.vehicle.currentOwner.customer.primaryAddress.city',
            'jobOrder.jobOrderRepairOrders.repairOrder',
            'jobOrder.jobOrderRepairOrders.repairOrder.repairOrderType',
            'jobOrder.floorAdviser',
            'jobOrder.serviceAdviser',
        ])
            ->select([
                'job_cards.*',
                DB::raw('DATE_FORMAT(job_cards.created_at,"%d-%m-%Y") as jobdate'),
                DB::raw('DATE_FORMAT(job_cards.created_at,"%h:%i %p") as time'),
            ])
            ->find($job_card_id);

        $params['field_type_id'] = [11, 12];
        $company_id = $job_card->company_id;
        // $data['extras'] = [
        //     'inventory_type_list' => VehicleInventoryItem::getInventoryList($job_card->jobOrder->id, $params, '', '', $company_id),
        // ];

        $vehicle_inventories = [];

        $inventory_list = VehicleInventoryItem::where('company_id', $company_id)->whereIn('field_type_id', [11, 12])->orderBy('id')->get();

        if ($inventory_list) {
            foreach ($inventory_list as $key => $inventory) {
                $vehicle_inventories[$key]['id'] = $inventory['id'];
                $vehicle_inventories[$key]['name'] = $inventory['name'];

                //Check GateIn
                $gate_in_inventory = DB::table('job_order_vehicle_inventory_item')->where('job_order_id', $job_card->job_order_id)->where('gate_log_id', $job_card->jobOrder->gateLog->id)->where('vehicle_inventory_item_id', $inventory['id'])->where('entry_type_id', 11300)->first();
                if ($gate_in_inventory) {
                    $vehicle_inventories[$key]['gate_in_checked'] = true;
                    $vehicle_inventories[$key]['gate_in_remarks'] = $gate_in_inventory->remarks;
                } else {
                    $vehicle_inventories[$key]['gate_in_checked'] = false;
                    $vehicle_inventories[$key]['gate_in_remarks'] = '';
                }

                //Check GateOut
                $gate_out_inventory = DB::table('job_order_vehicle_inventory_item')->where('job_order_id', $job_card->job_order_id)->where('gate_log_id', $job_card->jobOrder->gateLog->id)->where('vehicle_inventory_item_id', $inventory['id'])->where('entry_type_id', 11301)->first();
                if ($gate_out_inventory) {
                    $vehicle_inventories[$key]['gate_out_checked'] = true;
                    $vehicle_inventories[$key]['gate_out_remarks'] = $gate_out_inventory->remarks;
                } else {
                    $vehicle_inventories[$key]['gate_out_checked'] = false;
                    $vehicle_inventories[$key]['gate_out_remarks'] = '';
                }
            }
        }

        $data['type'] = $type;
        $data['vehicle_inventories'] = $vehicle_inventories;

        $save_path = storage_path('app/public/gigo/pdf');
        Storage::makeDirectory($save_path, 0777);

        if (!Storage::disk('public')->has('gigo/pdf/')) {
            Storage::disk('public')->makeDirectory('gigo/pdf/');
        }

        $data['date'] = date('d-m-Y');

        $name = $job_card->jobOrder->id . '_gatepass.pdf';

        $pdf = PDF::loadView('pdf-gigo/job-card-gate-pass-pdf', $data)->setPaper('a4', 'portrait');

        $img_path = $save_path . '/' . $name;
        if (File::exists($img_path)) {
            File::delete($img_path);
        }

        $pdf->save(storage_path('app/public/gigo/pdf/' . $name));

        return true;
    }

    public static function generateCoveringLetterPDF($job_card_id)
    {

        $data['covering_letter'] = $covering_letter = JobCard::with([
            'gatePasses',
            'gigoInvoices',
            'company',
            'jobOrder',
            'jobOrder.type',
            'jobOrder.vehicle',
            'jobOrder.vehicle.model',
            'jobOrder.vehicle.status',
            'jobOrder.outlet',
            'jobOrder.gateLog',
            'jobOrder.vehicle.currentOwner.customer',
            'jobOrder.vehicle.currentOwner.customer.address',
            'jobOrder.vehicle.currentOwner.customer.address.country',
            'jobOrder.vehicle.currentOwner.customer.address.state',
            'jobOrder.vehicle.currentOwner.customer.address.city',
            'jobOrder.serviceType',
            'jobOrder.jobOrderRepairOrders.repairOrder',
            'jobOrder.jobOrderRepairOrders.repairOrder.repairOrderType',
            'jobOrder.floorAdviser',
            'jobOrder.serviceAdviser'])
            ->select([
                'job_cards.*',
                DB::raw('DATE_FORMAT(job_cards.created_at,"%d-%m-%Y") as jobdate'),
                DB::raw('DATE_FORMAT(job_cards.created_at,"%h:%i %p") as time'),
            ])
            ->find($job_card_id);

        $gigo_invoice = [];
        if (isset($covering_letter->gigoInvoices)) {
            foreach ($covering_letter->gigoInvoices as $key => $gigoInvoice) {
                $gigo_invoice[$key]['bill_no'] = $gigoInvoice->invoice_number;
                $gigo_invoice[$key]['bill_date'] = date('d-m-Y', strtotime($gigoInvoice->invoice_date));
                $gigo_invoice[$key]['invoice_amount'] = $gigoInvoice->invoice_amount;

                //FOR ROUND OFF
                if ($gigoInvoice->invoice_amount <= round($gigoInvoice->invoice_amount)) {
                    $round_off = round($gigoInvoice->invoice_amount) - $gigoInvoice->invoice_amount;
                } else {
                    $round_off = round($gigoInvoice->invoice_amount) - $gigoInvoice->invoice_amount;
                }
                $gigo_invoice[$key]['round_off'] = number_format($round_off, 2);
                $gigo_invoice[$key]['total_amount'] = number_format(round($gigoInvoice->invoice_amount), 2);
            }
        }

        if ($gigo_invoice) {
            $invoice_date = date('d-m-Y', strtotime($gigo_invoice[0]['bill_date']));
        } else {
            $invoice_date = date('d-m-Y');
        }

        $data['gigo_invoices'] = $gigo_invoice;
        $data['invoice_date'] = $invoice_date;

        $save_path = storage_path('app/public/gigo/pdf');
        Storage::makeDirectory($save_path, 0777);

        if (!Storage::disk('public')->has('gigo/pdf/')) {
            Storage::disk('public')->makeDirectory('gigo/pdf/');
        }

        $name = $covering_letter->jobOrder->id . '_covering_letter.pdf';

        $pdf = PDF::loadView('pdf-gigo/covering-letter-pdf', $data)->setPaper('a4', 'portrait');

        $img_path = $save_path . '/' . $name;
        if (File::exists($img_path)) {
            File::delete($img_path);
        }

        $pdf->save(storage_path('app/public/gigo/pdf/' . $name));

        return true;
    }

    public static function searchJobCard($r)
    {
        $key = $r->key;
        $list = self::where('company_id', Auth::user()->company_id)
            ->select(
                'id',
                'job_card_number',
                'date'
            )
            ->where(function ($q) use ($key) {
                $q->where('job_card_number', 'like', $key . '%')
                ;
            })
            ->where('outlet_id', Auth::user()->working_outlet_id)
            ->get();
        return response()->json($list);
    }

    public static function importFromExcelJCDetail($job)
    {
        try {
            $response = ImportCronJob::getRecordsFromExcel($job, 'BR');
            $rows = $response['rows'];
            $header = $response['header'];
            $all_error_records = [];
            $i = 0;
            foreach ($rows as $k => $row) {
                $record = [];
                foreach ($header as $key => $column) {
                    if (!$column) {
                        continue;
                    } else {
                        $record[$column] = trim($row[$key]);
                    }
                }

                $original_record = $record;
                $status = [];
                $status['errors'] = [];

                $job_detail_report = new JobCardDetail;
                if (empty($record['Company Code'])) {
                    $status['errors'][] = 'Company Code is empty';
                } else {
                    $job_detail_report->company_code = $record['Company Code'];
                }
                if (empty($record['Company Name'])) {
                    $status['errors'][] = 'Company Name is empty';
                } else {
                    $job_detail_report->company_name = $record['Company Name'];
                }
                if (empty($record['Plant Code'])) {
                    $status['errors'][] = 'Company Name is empty';
                } else {
                    $job_detail_report->plant_code = $record['Plant Code'];
                }
                if (empty($record['Plant Name'])) {
                    $status['errors'][] = 'Company Name is empty';
                } else {
                    $job_detail_report->plant_name = $record['Plant Name'];
                }
                if (empty($record['SAC Code'])) {
                    $status['errors'][] = 'SAC Code is empty';
                } else {
                    $job_detail_report->sac_code = $record['SAC Code'];
                }
                if (empty($record['Company GSTN'])) {
                    $status['errors'][] = 'Company GSTN is empty';
                } else {
                    $job_detail_report->company_gstin = $record['Company GSTN'];
                }
                if (empty($record['Status'])) {
                    $status['errors'][] = 'Status is empty';
                } else {
                    $job_detail_report->status = $record['Status'];
                }
                if (empty($record['Job Card No'])) {
                    $status['errors'][] = 'Job Card No is empty';
                } else {
                    $job_detail_report->job_card_number = $record['Job Card No'];
                }
                if (empty($record['Job Card Date'])) {
                    $status['errors'][] = 'Job Card Date is empty';
                } else {
                    $job_card_date = PHPExcel_Style_NumberFormat::toFormattedString($record['Job Card Date'], PHPExcel_Style_NumberFormat::FORMAT_DATE_YYYYMMDD2);
                    $job_detail_report->job_card_date = $job_card_date;
                }
                if (empty($record['Job card Billed date'])) {
                    // $status['errors'][] = 'Job card Billed date is empty';
                } else {
                    $job_card_bill_date = PHPExcel_Style_NumberFormat::toFormattedString($record['Job card Billed date'], PHPExcel_Style_NumberFormat::FORMAT_DATE_YYYYMMDD2);
                    $job_detail_report->job_card_billed_date = $job_card_bill_date;
                }
                if (empty($record['Model Code'])) {
                    // $status['errors'][] = 'Model Code is empty';
                } else {
                    $job_detail_report->model_code = $record['Model Code'];
                }
                if (empty($record['Model Name'])) {
                    // $status['errors'][] = 'Model Name is empty';
                } else {
                    $job_detail_report->model_name = $record['Model Name'];
                }
                if (empty($record['Dealer Specific Order No'])) {
                    $status['errors'][] = 'Dealer Specific Order No is empty';
                } else {
                    $job_detail_report->dealer_specific_order_number = $record['Dealer Specific Order No'];
                }
                if (empty($record['Customer Code'])) {
                    $status['errors'][] = 'Customer Code is empty';
                } else {
                    $job_detail_report->customer_code = $record['Customer Code'];
                }
                if (empty($record['Customer Name'])) {
                    // $status['errors'][] = 'Customer Name is empty';
                } else {
                    $job_detail_report->customer_name = $record['Customer Name'];
                }
                if (empty($record['Registration No'])) {
                    // $status['errors'][] = 'Registration No is empty';
                } else {
                    $job_detail_report->registration_number = $record['Registration No'];
                }
                if (empty($record['Chassis No'])) {
                    // $status['errors'][] = 'Chassis No is empty';
                } else {
                    $job_detail_report->chassis_number = $record['Chassis No'];
                }
                if (empty($record['Engine No'])) {
                    // $status['errors'][] = 'Engine No is empty';
                } else {
                    $job_detail_report->engine_number = $record['Engine No'];
                }
                if (empty($record['Aggr Serial No'])) {
                    // $status['errors'][] = 'Aggr Serial No is empty';
                } else {
                    $job_detail_report->aggregate_serial_number = $record['Aggr Serial No'];
                }
                if (empty($record['KM Reading/HM Reading'])) {
                    // $status['errors'][] = 'KM Reading/HM Reading is empty';
                } else {
                    $job_detail_report->km_hr_reading = $record['KM Reading/HM Reading'];
                }
                if (empty($record['Customer voice'])) {
                    // $status['errors'][] = 'Customer voice is empty';
                } else {
                    $job_detail_report->customer_voice = $record['Customer voice'];
                }
                if (empty($record['Supervised By'])) {
                    $status['errors'][] = 'Supervised By is empty';
                } else {
                    $job_detail_report->supervised_by = $record['Supervised By'];
                }
                if (empty($record['Service Advisor'])) {
                    // $status['errors'][] = 'Service Advisor is empty';
                } else {
                    $job_detail_report->service_advisor = $record['Service Advisor'];
                }
                if (empty($record['Repair order/Work order'])) {
                    $status['errors'][] = 'Repair order/Work order is empty';
                } else {
                    $job_detail_report->repair_order_work_order = $record['Repair order/Work order'];
                }
                if (empty($record['Header Order Type'])) {
                    $status['errors'][] = 'Header Order Type is empty';
                } else {
                    $job_detail_report->header_order_type = $record['Header Order Type'];
                }
                if (empty($record['Split Order Type'])) {
                    $status['errors'][] = 'Split Order Type is empty';
                } else {
                    $job_detail_report->split_order_type = $record['Split Order Type'];
                }
                if (empty($record['Service Type'])) {
                    // $status['errors'][] = 'Service Type is empty';
                } else {
                    $job_detail_report->service_type = $record['Service Type'];
                }
                if (empty($record['Quotation Type'])) {
                    // $status['errors'][] = 'Quotation Type is empty';
                } else {
                    $job_detail_report->quotation_type = $record['Quotation Type'];
                }
                if (empty($record['Supplier Invoice No'])) {
                    // $status['errors'][] = 'Supplier Invoice No is empty';
                } else {
                    $job_detail_report->supplier_invoice_number = $record['Supplier Invoice No'];
                }
                if (empty($record['Supplier Invoice Date'])) {
                    // $status['errors'][] = 'Supplier Invoice Date is empty';
                } else {
                    $supplier_inv_date = PHPExcel_Style_NumberFormat::toFormattedString($record['Supplier Invoice Date'], PHPExcel_Style_NumberFormat::FORMAT_DATE_YYYYMMDD2);
                    $job_detail_report->supplier_invoice_date = $supplier_inv_date;
                }
                if (empty($record['ROT/Work Order Code'])) {
                    $status['errors'][] = 'ROT/Work Order Code is empty';
                } else {
                    $job_detail_report->rot_work_order_code = $record['ROT/Work Order Code'];
                }
                if (empty($record['ROT/Work Order Description'])) {
                    $status['errors'][] = 'ROT/Work Order Description is empty';
                } else {
                    $job_detail_report->rot_work_order_description = $record['ROT/Work Order Description'];
                }
                if (empty($record['Quantity'])) {
                    $status['errors'][] = 'Quantity is empty';
                } else {
                    $job_detail_report->quantity = $record['Quantity'];
                }
                if (empty($record['UOM'])) {
                    $status['errors'][] = 'UOM is empty';
                } else {
                    $job_detail_report->uom = $record['UOM'];
                }
                if (empty($record['Cost'])) {
                    $status['errors'][] = 'Cost is empty';
                } else {
                    $job_detail_report->cost = $record['Cost'];
                }
                if (empty($record['Criticality'])) {
                    // $status['errors'][] = 'Criticality is empty';
                } else {
                    $job_detail_report->criticality = $record['Criticality'];
                }
                if (empty($record['Total Amount'])) {
                    $status['errors'][] = 'Total Amount is empty';
                } else {
                    $job_detail_report->total_amount = $record['Total Amount'];
                }
                if (empty($record['Complaint'])) {
                    // $status['errors'][] = 'Complaint is empty';
                } else {
                    $job_detail_report->complaint = $record['Complaint'];
                }
                if (empty($record['Fault'])) {
                    // $status['errors'][] = 'Fault is empty';
                } else {
                    $job_detail_report->fault = $record['Fault'];
                }
                if (empty($record['LOB'])) {
                    $status['errors'][] = 'LOB is empty';
                } else {
                    $job_detail_report->lob = $record['LOB'];
                }
                if (empty($record['Failure Date'])) {
                    $status['errors'][] = 'Failure Date is empty';
                } else {
                    $failure_date = PHPExcel_Style_NumberFormat::toFormattedString($record['Failure Date'], PHPExcel_Style_NumberFormat::FORMAT_DATE_YYYYMMDD2);
                    $job_detail_report->failure_date = $failure_date;
                }
                if (empty($record['Claim Type'])) {
                    // $status['errors'][] = 'Claim Type is empty';
                } else {
                    $job_detail_report->claim_type = $record['Claim Type'];
                }
                if (empty($record['Selling Dealer Code'])) {
                    // $status['errors'][] = 'Selling Dealer Code is empty';
                } else {
                    $job_detail_report->selling_dealer_code = $record['Selling Dealer Code'];
                }
                if (empty($record['Servicing Dealer Code'])) {
                    $status['errors'][] = 'Servicing Dealer Code is empty';
                } else {
                    $job_detail_report->service_dealer_code = $record['Servicing Dealer Code'];
                }
                if (empty($record['Is BD Job Card'])) {
                    // $status['errors'][] = 'Is BD Job Card is empty';
                } else {
                    $job_detail_report->is_bd_job_card = $record['Is BD Job Card'];
                }
                if (empty($record['LD Number'])) {
                    // $status['errors'][] = 'LD Number is empty';
                } else {
                    $job_detail_report->ld_number = $record['LD Number'];
                }
                if (empty($record['KCC'])) {
                    // $status['errors'][] = 'KCC is empty';
                } else {
                    $job_detail_report->kcc = $record['KCC'];
                }
                if (empty($record['KCC %'])) {
                    // $status['errors'][] = 'KCC % is empty';
                } else {
                    $job_detail_report->kcc_percentage = $record['KCC %'];
                }
                if (empty($record['CGST'])) {
                    // $status['errors'][] = 'CGST is empty';
                } else {
                    $job_detail_report->cgst = $record['CGST'];
                }
                if (empty($record['CGST %'])) {
                    // $status['errors'][] = 'CGST % is empty';
                } else {
                    $job_detail_report->cgst_percentage = $record['CGST %'];
                }
                if (empty($record['SGST'])) {
                    // $status['errors'][] = 'SGST is empty';
                } else {
                    $job_detail_report->sgst = $record['SGST'];
                }
                if (empty($record['SGST %'])) {
                    // $status['errors'][] = 'SGST % is empty';
                } else {
                    $job_detail_report->sgst_percentage = $record['SGST %'];
                }
                if (empty($record['UGST'])) {
                    // $status['errors'][] = 'UGST is empty';
                } else {
                    $job_detail_report->ugst = $record['UGST'];
                }
                if (empty($record['UGST %'])) {
                    // $status['errors'][] = 'UGST % is empty';
                } else {
                    $job_detail_report->ugst_percentage = $record['UGST %'];
                }
                if (empty($record['IGST'])) {
                    // $status['errors'][] = 'IGST is empty';
                } else {
                    $job_detail_report->igst = $record['IGST'];
                }
                if (empty($record['IGST %'])) {
                    // $status['errors'][] = 'IGST % is empty';
                } else {
                    $job_detail_report->igst_percentage = $record['IGST %'];
                }
                if (empty($record['TCS %'])) {
                    // $status['errors'][] = 'TCS % is empty';
                } else {
                    $job_detail_report->tcs = $record['TCS %'];
                }
                if (empty($record['TCS'])) {
                    // $status['errors'][] = 'TCS is empty';
                } else {
                    $job_detail_report->tcs_percentage = $record['TCS'];
                }
                if (empty($record['Customer PAN'])) {
                    // $status['errors'][] = 'Customer PAN is empty';
                } else {
                    $job_detail_report->customer_pan_number = $record['Customer PAN'];
                }
                if (empty($record['Taxable Amount'])) {
                    $status['errors'][] = 'Taxable Amount is empty';
                } else {
                    $job_detail_report->taxable_amount = $record['Taxable Amount'];
                }
                if (empty($record['Bill Number'])) {
                    // $status['errors'][] = 'Bill Number is empty';
                } else {
                    $job_detail_report->bill_number = $record['Bill Number'];
                }
                if (empty($record['Bill Type'])) {
                    // $status['errors'][] = 'Bill Type is empty';
                } else {
                    $job_detail_report->bill_type = $record['Bill Type'];
                }
                if (empty($record['Vechicle HSN'])) {
                    // $status['errors'][] = 'Vechicle HSN is empty';
                } else {
                    $job_detail_report->vehicle_hsn = $record['Vechicle HSN'];
                }
                if (empty($record['Gate Pass Date'])) {
                    // $status['errors'][] = 'Gate Pass Date is empty';
                } else {
                    $gate_pass_date = PHPExcel_Style_NumberFormat::toFormattedString($record['Gate Pass Date'], PHPExcel_Style_NumberFormat::FORMAT_DATE_YYYYMMDD2);
                    $job_detail_report->gate_pass_date = $gate_pass_date;
                }
                if (empty($record['HSN /SAC Code'])) {
                    $status['errors'][] = 'HSN /SAC Code is empty';
                } else {
                    $job_detail_report->hsn_sac_code = $record['HSN /SAC Code'];
                }
                if (empty($record['Customer GSTN'])) {
                    // $status['errors'][] = 'Customer GSTN is empty';
                } else {
                    $job_detail_report->customer_gstin = $record['Customer GSTN'];
                }
                if (empty($record['State of Customer'])) {
                    $status['errors'][] = 'State of Customer is empty';
                } else {
                    $job_detail_report->state_of_customer = $record['State of Customer'];
                }
                if (empty($record['Customer Address'])) {
                    $status['errors'][] = 'Customer Address is empty';
                } else {
                    $job_detail_report->customer_address = $record['Customer Address'];
                }
                if (empty($record['Dealer Specific Bill No'])) {
                    // $status['errors'][] = 'Dealer Specific Bill No is empty';
                } else {
                    $job_detail_report->dealer_specific_bill_number = $record['Dealer Specific Bill No'];
                }
                $job_detail_report->created_by_id = $job->created_by_id;
                $job_detail_report->updated_at = null;

                //UPDATING PROGRESS FOR EVERY FIFTY RECORDS
                if (($k + 1) % 50 == 0) {
                    $job->processed_count = $k;
                    $job->save();
                }

                if (count($status['errors']) > 0) {
                    dump($status['errors']);
                    $original_record['Record No'] = $k + 1;
                    $original_record['Error Details'] = implode(',', $status['errors']);
                    $all_error_records[] = $original_record;
                    $job->incrementError();
                    continue;
                }

                try {
                    DB::beginTransaction();
                    $job_detail_report->save();
                    DB::commit();
                    $job->incrementNew();
                } catch (\Exception $e) {
                    $status['errors'][] = $e->getMessage();
                    if (count($status['errors']) > 0) {
                        dump($status['errors']);
                        $original_record['Record No'] = $k + 1;
                        $original_record['Error Details'] = implode(',', $status['errors']);
                        $all_error_records[] = $original_record;
                        $job->incrementError();
                        continue;
                    }
                }

            }

            $job->remaining_count = 0;
            $job->processed_count = $job->total_record_count;
            //COMPLETED or completed with errors
            $job->status_id = $job->error_count == 0 ? 7202 : 7205;
            $job->save();

            // dd($job);
            ImportCronJob::generateImportReport([
                'job' => $job,
                'all_error_records' => $all_error_records,
            ]);

        } catch (\Throwable $e) {
            dump($job->error_details);
            $job->status_id = 7203; //Error
            $job->error_details = 'Error:' . $e->getMessage() . '. Line:' . $e->getLine() . '. File:' . $e->getFile(); //Error
            $job->save();
        }
    }
    public static function importFromExcelJCBilled($job)
    {
        try {
            $response = ImportCronJob::getRecordsFromExcel($job, 'CT');
            $rows = $response['rows'];
            $header = $response['header'];
            $all_error_records = [];
            $i = 0;
            foreach ($rows as $k => $row) {
                $record = [];
                foreach ($header as $key => $column) {
                    if (!$column) {
                        continue;
                    } else {
                        $record[$column] = trim($row[$key]);
                    }
                }

                $original_record = $record;
                $status = [];
                $status['errors'] = [];

                $job_card_billed_details = new JobCardBilledDetail;

                if (empty($record['Plant'])) {
                    $status['errors'][] = 'Plant is empty';
                } else {
                    $job_card_billed_details->plant = $record['Plant'];
                }
                if (empty($record['Plant Name'])) {
                    $status['errors'][] = 'Plant Name is empty';
                } else {
                    $job_card_billed_details->plant_name = $record['Plant Name'];
                }
                if (empty($record['Company Code'])) {
                    $status['errors'][] = 'Company Code is empty';
                } else {
                    $job_card_billed_details->company_code = $record['Company Code'];
                }
                if (empty($record['Company Name'])) {
                    $status['errors'][] = 'Company Name is empty';
                } else {
                    $job_card_billed_details->company_name = $record['Company Name'];
                }
                if (empty($record['SAC Code'])) {
                    $status['errors'][] = 'SAC Code is empty';
                } else {
                    $job_card_billed_details->sac_code = $record['SAC Code'];
                }
                if (empty($record['Company GST'])) {
                    $status['errors'][] = 'Company GST is empty';
                } else {
                    $job_card_billed_details->company_gst = $record['Company GST'];
                }

                if (empty($record['Job Card No.'])) {
                    $status['errors'][] = 'Job Card  Number is empty';
                } else {
                    $job_card_billed_details->job_card_number = $record['Job Card No.'];
                }

                if (empty($record['Jobcard Date'])) {
                    $status['errors'][] = 'Job Card Date is empty';
                } else {
                    $job_card_date = PHPExcel_Style_NumberFormat::toFormattedString($record['Jobcard Date'], PHPExcel_Style_NumberFormat::FORMAT_DATE_YYYYMMDD2);
                    $job_card_billed_details->job_card_date = $job_card_date;
                }

                if (empty($record['Jobcard Time'])) {
                    $status['errors'][] = 'Job Card Time is empty';
                } else {
                    $job_card_in_time = PHPExcel_Style_NumberFormat::toFormattedString($record['Jobcard Time'], PHPExcel_Style_NumberFormat::FORMAT_DATE_TIME2);
                    $job_card_billed_details->job_card_time = $job_card_in_time;
                }

                if (empty($record['Dealer Order No.'])) {
                    $status['errors'][] = 'Document No is empty';
                } else {
                    $job_card_billed_details->dealer_order_number = $record['Dealer Order No.'];
                }

                if (empty($record['Dealer Invoice No'])) {
                    // $status['errors'][] = 'Plant Code is empty';
                } else {
                    $job_card_billed_details->dealer_invoice_number = $record['Dealer Invoice No'];
                }

                if (empty($record['Invoice Date'])) {
                    $status['errors'][] = 'Invoice Date is empty';
                } else {
                    $inward_date = PHPExcel_Style_NumberFormat::toFormattedString($record['Invoice Date'], PHPExcel_Style_NumberFormat::FORMAT_DATE_YYYYMMDD2);
                    $job_card_billed_details->invoice_date = $inward_date;
                }

                if (empty($record['Sale Date'])) {
                    // $status['errors'][] = 'Sale Date is empty';
                } else {
                    $sale_date = PHPExcel_Style_NumberFormat::toFormattedString($record['Sale Date'], PHPExcel_Style_NumberFormat::FORMAT_DATE_YYYYMMDD2);
                    $job_card_billed_details->sale_date = $sale_date;
                }

                if (empty($record['Customer Code'])) {
                    $status['errors'][] = 'Customer Code is empty';
                } else {
                    $job_card_billed_details->customer_code = $record['Customer Code'];
                }

                if (empty($record['Customer'])) {
                    // $status['errors'][] = 'Customer is empty';
                } else {
                    $job_card_billed_details->customer_name = $record['Customer'];
                }

                if (empty($record['Header order Type'])) {
                    $status['errors'][] = 'Header order Type is empty';
                } else {
                    $job_card_billed_details->header_order_type = $record['Header order Type'];
                }

                if (empty($record['Invoice Type'])) {
                    $status['errors'][] = 'Invoice Type is empty';
                } else {
                    $job_card_billed_details->invoice_type = $record['Invoice Type'];
                }

                if (empty($record['Chassis No.'])) {
                    // $status['errors'][] = 'Chassis No is empty';
                } else {
                    $job_card_billed_details->chassis_number = $record['Chassis No.'];
                }

                if (empty($record['Reg No.'])) {
                    // $status['errors'][] = 'Reg No is empty';
                } else {
                    $job_card_billed_details->registration_number = $record['Reg No.'];
                }
                if (empty($record['Engine No.'])) {
                    // $status['errors'][] = 'Engine No. is empty';
                } else {
                    $job_card_billed_details->engine_number = $record['Engine No.'];
                }
                if (empty($record['Model Name'])) {
                    // $status['errors'][] = 'Model Name is empty';
                } else {
                    $job_card_billed_details->model_name = $record['Model Name'];
                }

                if (empty($record['Service Type'])) {
                    // $status['errors'][] = 'Service Type is empty';
                } else {
                    $job_card_billed_details->service_type = $record['Service Type'];
                }

                if (empty($record['KM Reading / HR Reading'])) {
                    // $status['errors'][] = 'KM Reading / HR Reading is empty';
                } else {
                    $job_card_billed_details->current_reading = $record['KM Reading / HR Reading'];
                }
                if (empty($record['km/hr'])) {
                    $status['errors'][] = 'km/hr is empty';
                } else {
                    $job_card_billed_details->km_reading_type = $record['km/hr'];
                }

                if (empty($record['Status'])) {
                    $status['errors'][] = 'Status Type is empty';
                } else {
                    $job_card_billed_details->status = $record['Status'];
                }

                if (empty($record['Supervisor Name'])) {
                    $status['errors'][] = 'Supervisor Name is empty';
                } else {
                    $job_card_billed_details->supervisor_name = $record['Supervisor Name'];
                }

                if (empty($record['LOB'])) {
                    $status['errors'][] = 'LOB is empty';
                } else {
                    $job_card_billed_details->lob = $record['LOB'];
                }
                if (empty($record['Customer voice'])) {
                    // $status['errors'][] = 'Customer voice is empty';
                } else {
                    $job_card_billed_details->customer_voice = $record['Customer voice'];
                }
                if (empty($record['Customer Mobile'])) {
                    // $status['errors'][] = 'Customer Mobile is empty';
                } else {
                    $job_card_billed_details->customer_mobile = $record['Customer Mobile'];
                }
                if (empty($record['Driver Mobile'])) {
                    // $status['errors'][] = 'Driver Mobile is empty';
                } else {
                    $job_card_billed_details->driver_mobile = $record['Driver Mobile'];
                }

                if (empty($record['Customer Service Contact'])) {
                    // $status['errors'][] = 'Customer Service Contact is empty';
                } else {
                    $job_card_billed_details->service_contact_number = $record['Customer Service Contact'];
                }
                if (empty($record['Selling Company'])) {
                    // $status['errors'][] = 'Selling Company is empty';
                } else {
                    $job_card_billed_details->selling_company = $record['Selling Company'];
                }
                if (empty($record['Servicing Company'])) {
                    $status['errors'][] = 'Servicing Company is empty';
                } else {
                    $job_card_billed_details->servicing_company = $record['Servicing Company'];
                }
                if (empty($record['Selling Service Code'])) {
                    // $status['errors'][] = 'Selling Service Code is empty';
                } else {
                    $job_card_billed_details->selling_service_code = $record['Selling Service Code'];
                }
                if (empty($record['Service Code'])) {
                    $status['errors'][] = 'Service Code is empty';
                } else {
                    $job_card_billed_details->service_code = $record['Service Code'];
                }
                if (empty($record['Request Type'])) {
                    // $status['errors'][] = 'Request Type is empty';
                } else {
                    $job_card_billed_details->request_type = $record['Request Type'];
                }
                if (empty($record['Source'])) {
                    $status['errors'][] = 'Source is empty';
                } else {
                    $job_card_billed_details->source = $record['Source'];
                }

                if (empty($record['Presale-Labour'])) {
                    // $status['errors'][] = 'Presale-Labour is empty';
                } else {
                    $job_card_billed_details->presale_labour = $record['Presale-Labour'];
                }

                if (empty($record['Presale-Spares'])) {
                    // $status['errors'][] = 'Presale-Spares is empty';
                } else {
                    $job_card_billed_details->presale_spares = $record['Presale-Spares'];
                }
                if (empty($record['Presale-Lube'])) {
                    // $status['errors'][] = 'Presale-Lube is empty';
                } else {
                    $job_card_billed_details->presale_lube = $record['Presale-Lube'];
                }
                if (empty($record['PDI-Labour'])) {
                    // $status['errors'][] = 'PDI-Labour is empty';
                } else {
                    $job_card_billed_details->pdi_labour = $record['PDI-Labour'];
                }
                if (empty($record['PDI-Spares'])) {
                    // $status['errors'][] = 'PDI-Spares is empty';
                } else {
                    $job_card_billed_details->pdi_spares = $record['PDI-Spares'];
                }
                if (empty($record['PDI-Lube'])) {
                    // $status['errors'][] = 'PDI-Lube is empty';
                } else {
                    $job_card_billed_details->pdi_lube = $record['PDI-Lube'];
                }
                if (empty($record['Free and Extended service-Labour'])) {
                    // $status['errors'][] = 'Free and Extended service-Labour is empty';
                } else {
                    $job_card_billed_details->free_extended_service_labour = $record['Free and Extended service-Labour'];
                }
                if (empty($record['Free and Extended service-Spares'])) {
                    // $status['errors'][] = 'Free and Extended service-Spares is empty';
                } else {
                    $job_card_billed_details->free_extended_service_spares = $record['Free and Extended service-Spares'];
                }
                if (empty($record['Free and Extended service-Lube'])) {
                    // $status['errors'][] = 'Free and Extended service-Lube is empty';
                } else {
                    $job_card_billed_details->free_extended_service_lube = $record['Free and Extended service-Lube'];
                }
                if (empty($record['Paid-Labour'])) {
                    // $status['errors'][] = 'Paid-Labour is empty';
                } else {
                    $job_card_billed_details->paid_labour = $record['Paid-Labour'];
                }
                if (empty($record['Paid-Spares'])) {
                    // $status['errors'][] = 'Paid-Spares is empty';
                } else {
                    $job_card_billed_details->paid_spares = $record['Paid-Spares'];
                }
                if (empty($record['Paid-Lube'])) {
                    // $status['errors'][] = 'Paid-Lube is empty';
                } else {
                    $job_card_billed_details->paid_lube = $record['Paid-Lube'];
                }

                if (empty($record['Total Paid'])) {
                    // $status['errors'][] = 'Total Paid is empty';
                } else {
                    $job_card_billed_details->total_paid = $record['Total Paid'];
                }

                if (empty($record['Warranty-Labour'])) {
                    // $status['errors'][] = 'Warranty-Labour is empty';
                } else {
                    $job_card_billed_details->warranty_labour = $record['Warranty-Labour'];
                }

                if (empty($record['Warranty-Spares'])) {
                    // $status['errors'][] = 'Warranty-Spares is empty';
                } else {
                    $job_card_billed_details->warranty_spares = $record['Warranty-Spares'];
                }

                // if (empty($record['LAL Warranty-Labour'])) {
                //     $status['errors'][] = 'LAL Warranty-Labour is empty';
                // } else {
                //     $job_card_billed_details->lal_warranty_labours = $record['LAL Warranty-Labour'];
                // }
                // if (empty($record['LAL Warranty-Spares'])) {
                //     $status['errors'][] = 'LAL Warranty-Spares is empty';
                // } else {
                //     $job_card_billed_details->lal_warranty_spares = $record['LAL Warranty-Spares'];
                // }
                if (empty($record['Warranty-Lube'])) {
                    // $status['errors'][] = 'Warranty-Lube is empty';
                } else {
                    $job_card_billed_details->warranty_lube = $record['Warranty-Lube'];
                }
                // if (empty($record['LAL Warranty-Lube'])) {
                //     $status['errors'][] = 'LAL Warranty-Lube is empty';
                // } else {
                //     $job_card_billed_details->lal_warranty_lube = $record['LAL Warranty-Lube'];
                // }
                if (empty($record['Warranty ESP-Labour'])) {
                    // $status['errors'][] = 'Warranty ESP-Labour is empty';
                } else {
                    $job_card_billed_details->warranty_esp_labours = $record['Warranty ESP-Labour'];
                }
                if (empty($record['Warranty ESP-Spares'])) {
                    // $status['errors'][] = 'Warranty ESP-Spares is empty';
                } else {
                    $job_card_billed_details->warranty_esp_spares = $record['Warranty ESP-Spares'];
                }
                if (empty($record['Warranty ESP-Lube'])) {
                    // $status['errors'][] = 'Warranty ESP-Lube is empty';
                } else {
                    $job_card_billed_details->warranty_esp_lube = $record['Warranty ESP-Lube'];
                }

                if (empty($record['Extended Warranty-Labour'])) {
                    // $status['errors'][] = 'Extended Warranty-Labour is empty';
                } else {
                    $job_card_billed_details->extended_warranty_labour = $record['Extended Warranty-Labour'];
                }
                if (empty($record['Extended Warranty-Spares'])) {
                    // $status['errors'][] = 'Extended Warranty-Spares is empty';
                } else {
                    $job_card_billed_details->extended_warranty_spares = $record['Extended Warranty-Spares'];
                }
                if (empty($record['Extended Warranty-Lube'])) {
                    // $status['errors'][] = 'Extended Warranty-Lube is empty';
                } else {
                    $job_card_billed_details->extended_warranty_lube = $record['Extended Warranty-Lube'];
                }
                if (empty($record['Warranty Recon-Labour'])) {
                    // $status['errors'][] = 'Warranty Recon-Labour is empty';
                } else {
                    $job_card_billed_details->warranty_recon_labour = $record['Warranty Recon-Labour'];
                }

                if (empty($record['Warranty Recon-Spares'])) {
                    // $status['errors'][] = 'Warranty Recon-Spares is empty';
                } else {
                    $job_card_billed_details->warranty_recon_spares = $record['Warranty Recon-Spares'];
                }
                if (empty($record['Warranty Recon-Lube'])) {
                    // $status['errors'][] = 'Warranty Recon-Lube is empty';
                } else {
                    $job_card_billed_details->warranty_recon_lube = $record['Warranty Recon-Lube'];
                }
                if (empty($record['AMC-Labour'])) {
                    // $status['errors'][] = 'AMC-Labour is empty';
                } else {
                    $job_card_billed_details->amc_labour = $record['AMC-Labour'];
                }
                if (empty($record['AMC-Spares'])) {
                    // $status['errors'][] = 'AMC-Spares is empty';
                } else {
                    $job_card_billed_details->amc_spares = $record['AMC-Spares'];
                }
                if (empty($record['AMC-Lube'])) {
                    // $status['errors'][] = 'AMC-Lube is empty';
                } else {
                    $job_card_billed_details->amc_lube = $record['AMC-Lube'];
                }
                if (empty($record['Aggregate-Labour'])) {
                    // $status['errors'][] = 'Aggregate-Labour is empty';
                } else {
                    $job_card_billed_details->aggregate_labour = $record['Aggregate-Labour'];
                }
                if (empty($record['Aggregate-Spares'])) {
                    // $status['errors'][] = 'Aggregate-Spares is empty';
                } else {
                    $job_card_billed_details->aggregate_spares = $record['Aggregate-Spares'];
                }
                if (empty($record['Aggregate-Lube'])) {
                    // $status['errors'][] = 'Aggregate-Lube is empty';
                } else {
                    $job_card_billed_details->aggregate_lube = $record['Aggregate-Lube'];
                }
                if (empty($record['Goodwill Commercial-Labour'])) {
                    // $status['errors'][] = 'Goodwill Commercial-Labour is empty';
                } else {
                    $job_card_billed_details->goodwill_commercial_labour = $record['Goodwill Commercial-Labour'];
                }
                if (empty($record['Goodwill Commercial-Spares'])) {
                    // $status['errors'][] = 'Goodwill Commercial-Spares is empty';
                } else {
                    $job_card_billed_details->goodwill_commercial_spares = $record['Goodwill Commercial-Spares'];
                }
                if (empty($record['Goodwill Commercial-Lube'])) {
                    // $status['errors'][] = 'Goodwill Commercial-Lube is empty';
                } else {
                    $job_card_billed_details->goodwill_commercial_lube = $record['Goodwill Commercial-Lube'];
                }

                if (empty($record['Goodwill Technical-Labour'])) {
                    // $status['errors'][] = 'Goodwill Technical-Labour is empty';
                } else {
                    $job_card_billed_details->goodwill_technical_labour = $record['Goodwill Technical-Labour'];
                }
                if (empty($record['Goodwill Technical-Spares'])) {
                    // $status['errors'][] = 'Goodwill Technical-Spares is empty';
                } else {
                    $job_card_billed_details->goodwill_technical_spares = $record['Goodwill Technical-Spares'];
                }
                if (empty($record['Goodwill Technical-Lube'])) {
                    // $status['errors'][] = 'Goodwill Technical-Lube is empty';
                } else {
                    $job_card_billed_details->goodwill_technical_lube = $record['Goodwill Technical-Lube'];
                }
                if (empty($record['FOC-Labour'])) {
                    // $status['errors'][] = 'FOC-Labour is empty';
                } else {
                    $job_card_billed_details->foc_labour = $record['FOC-Labour'];
                }

                if (empty($record['FOC-Spares'])) {
                    // $status['errors'][] = 'FOC-Spares is empty';
                } else {
                    $job_card_billed_details->foc_spares = $record['FOC-Spares'];
                }

                if (empty($record['FOC-Lube'])) {
                    // $status['errors'][] = 'FOC-Lube is empty';
                } else {
                    $job_card_billed_details->foc_lube = $record['FOC-Lube'];
                }

                if (empty($record['Ancillary-Labour'])) {
                    // $status['errors'][] = 'Ancillary-Labour is empty';
                } else {
                    $job_card_billed_details->ancillary_labour = $record['Ancillary-Labour'];
                }
                if (empty($record['Ancillary-Spares'])) {
                    // $status['errors'][] = 'Ancillary-Spares is empty';
                } else {
                    $job_card_billed_details->ancillary_spares = $record['Ancillary-Spares'];
                }
                if (empty($record['Ancillary-Lube'])) {
                    // $status['errors'][] = 'Ancillary-Lube is empty';
                } else {
                    $job_card_billed_details->ancillary_lube = $record['Ancillary-Lube'];
                }
                if (empty($record['Accident Insurance-Labour'])) {
                    // $status['errors'][] = 'Accident Insurance-Labour is empty';
                } else {
                    $job_card_billed_details->accident_insurance_labour = $record['Accident Insurance-Labour'];
                }
                if (empty($record['Accident Insurance-Spares'])) {
                    // $status['errors'][] = 'Accident Insurance-Spares is empty';
                } else {
                    $job_card_billed_details->accident_insurance_spares = $record['Accident Insurance-Spares'];
                }
                if (empty($record['Accident Insurance-Lube'])) {
                    // $status['errors'][] = 'Accident Insurance-Lube is empty';
                } else {
                    $job_card_billed_details->accident_insurance_lube = $record['Accident Insurance-Lube'];
                }
                if (empty($record['Accident Paid-Labour'])) {
                    // $status['errors'][] = 'Accident Paid-Labour is empty';
                } else {
                    $job_card_billed_details->accident_paid_labour = $record['Accident Paid-Labour'];
                }
                if (empty($record['Accident Paid-Lube'])) {
                    // $status['errors'][] = 'Accident Paid-Lube is empty';
                } else {
                    $job_card_billed_details->accident_paid_lube = $record['Accident Paid-Lube'];
                }

                if (empty($record['Parts Warranty-Spares'])) {
                    // $status['errors'][] = 'Parts Warranty-Spares is empty';
                } else {
                    $job_card_billed_details->parts_warranty_spares = $record['Parts Warranty-Spares'];
                }
                if (empty($record['Parts Warranty-Labour'])) {
                    // $status['errors'][] = 'Parts Warranty-Labour is empty';
                } else {
                    $job_card_billed_details->parts_warranty_labour = $record['Parts Warranty-Labour'];
                }
                if (empty($record['Total Labour'])) {
                    // $status['errors'][] = 'Total Labour is empty';
                } else {
                    $job_card_billed_details->total_labour = $record['Total Labour'];
                }
                if (empty($record['Total Spares'])) {
                    // $status['errors'][] = 'Total Spares is empty';
                } else {
                    $job_card_billed_details->total_spares = $record['Total Spares'];
                }
                if (empty($record['Total Lube'])) {
                    // $status['errors'][] = 'Total Lube is empty';
                } else {
                    $job_card_billed_details->total_lube = $record['Total Lube'];
                }
                if (empty($record['Total Amount'])) {
                    // $status['errors'][] = 'Total Amount is empty';
                } else {
                    $job_card_billed_details->total_amount = $record['Total Amount'];
                }
                if (empty($record['DSP-Labour'])) {
                    // $status['errors'][] = 'DSP-Labour is empty';
                } else {
                    $job_card_billed_details->dsp_labour = $record['DSP-Labour'];
                }
                if (empty($record['DSP-Spares'])) {
                    // $status['errors'][] = 'DSP-Spares is empty';
                } else {
                    $job_card_billed_details->dsp_spares = $record['DSP-Spares'];
                }

                if (empty($record['DSP-Lube'])) {
                    // $status['errors'][] = 'DSP-Lube is empty';
                } else {
                    $job_card_billed_details->dsp_lube = $record['DSP-Lube'];
                }
                if (empty($record['Campaign-Labour'])) {
                    // $status['errors'][] = 'Campaign-Labour is empty';
                } else {
                    $job_card_billed_details->campaign_labour = $record['Campaign-Labour'];
                }
                if (empty($record['Campaign-Spares'])) {
                    // $status['errors'][] = 'Campaign-Spares is empty';
                } else {
                    $job_card_billed_details->campaign_spares = $record['Campaign-Spares'];
                }
                if (empty($record['Campaign-Lubes'])) {
                    // $status['errors'][] = 'Campaign-Lubes is empty';
                } else {
                    $job_card_billed_details->campaign_lube = $record['Campaign-Lubes'];
                }

                $job_card_billed_details->created_by_id = $job->created_by_id;
                $job_card_billed_details->created_at = Carbon::now();
                $job_card_billed_details->updated_at = null;

                //UPDATING PROGRESS FOR EVERY FIFTY RECORDS
                if (($k + 1) % 50 == 0) {
                    $job->processed_count = $k;
                    $job->save();
                }

                if (count($status['errors']) > 0) {
                    dump($status['errors']);
                    $original_record['Record No'] = $k + 1;
                    $original_record['Error Details'] = implode(',', $status['errors']);
                    $all_error_records[] = $original_record;
                    $job->incrementError();
                    continue;
                }

                try {
                    DB::beginTransaction();
                    $job_card_billed_details->save();
                    DB::commit();
                    $job->incrementNew();
                } catch (\Exception $e) {
                    $status['errors'][] = $e->getMessage();
                    if (count($status['errors']) > 0) {
                        dump($status['errors']);
                        $original_record['Record No'] = $k + 1;
                        $original_record['Error Details'] = implode(',', $status['errors']);
                        $all_error_records[] = $original_record;
                        $job->incrementError();
                        continue;
                    }
                }
            }

            $job->remaining_count = 0;
            $job->processed_count = $job->total_record_count;

            //COMPLETED or completed with errors
            $job->status_id = $job->error_count == 0 ? 7202 : 7205;
            $job->save();

            // dd($job);
            ImportCronJob::generateImportReport([
                'job' => $job,
                'all_error_records' => $all_error_records,
            ]);

        } catch (\Throwable $e) {
            dump($job->error_details);
            $job->status_id = 7203; //
            $job->error_details = 'Error:' . $e->getMessage() . '. Line:' . $e->getLine() . '. File:' . $e->getFile(); //Error
            $job->save();
        }
    }
}

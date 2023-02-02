<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use Abs\TaxPkg\Tax;
use App\BaseModel;
use File;
use PDF;
use Storage;

class OnSiteOrder extends BaseModel
{
    use SeederTrait;
    protected $table = 'on_site_orders';
    protected $fillable = [
        "company_id",
        "outlet_id",
        "on_site_visit_user_id",
        "number",
        "customer_id",
        "job_card_number",
        "service_type_id",
        "planned_visit_date",
        "actual_visit_date",
        "customer_remarks",
        "se_remarks	",
    ];

    public function getCreatedAtAttribute($value)
    {
        return empty($value) ? '' : date('d-m-Y h:i A', strtotime($value));
    }

    public function getPlannedVisitDateAttribute($value)
    {
        return empty($value) ? '' : date('d-m-Y', strtotime($value));
    }

    public function getActualVisitDateAttribute($value)
    {
        return empty($value) ? '' : date('d-m-Y', strtotime($value));
    }

    public function company()
    {
        return $this->belongsTo('App\Company', 'company_id');
    }

    public function outlet()
    {
        return $this->belongsTo('App\Outlet', 'outlet_id');
    }

    public function sbu()
    {
        return $this->belongsTo('App\Sbu', 'sbu_id');
    }

    public function onSiteVisitUser()
    {
        return $this->belongsTo('App\User', 'on_site_visit_user_id');
    }

    public function customer()
    {
        return $this->belongsTo('App\Customer', 'customer_id');
    }

    public function status()
    {
        return $this->belongsTo('App\OnSiteOrderStatus', 'status_id');
    }

    public function address()
    {
        return $this->belongsTo('App\Address', 'address_id');
    }

    public function onSiteOrderRepairOrders()
    {
        return $this->hasMany('App\OnSiteOrderRepairOrder', 'on_site_order_id');
    }
    public function RotIemDetail()
    {
        return $this->hasMany('App\OnSiteOrderRepairOrder', 'rot_iem_id');
    }
    public function onSiteOrderParts()
    {
        return $this->hasMany('App\OnSiteOrderPart', 'on_site_order_id');
    }

    public function onSiteOrderTravelLogs()
    {
        return $this->hasMany('App\OnSiteOrderTimeLog', 'on_site_order_id')->where('work_log_type_id', 1);
    }

    public function onSiteOrderWorkLogs()
    {
        return $this->hasMany('App\OnSiteOrderTimeLog', 'on_site_order_id')->where('work_log_type_id', 2);
    }

    public function photos()
    {
        return $this->hasMany('App\Attachment', 'entity_id')->where('attachment_of_id', 9124);
    }

    public static function generateBillingPDF($on_site_order_id)
    {
        $customer_paid_type_id = SplitOrderType::where('paid_by_id', '10013')->pluck('id')->toArray();

        $data['estimate'] = $site_visit = OnSiteOrder::with([
            'company',
            'outlet',
            'onSiteVisitUser',
            'customer',
            'address',
            'address.country',
            'address.state',
            'address.city',
            'status',
            'onSiteOrderRepairOrders' => function ($q) use ($customer_paid_type_id) {
                $q->where('status_id', 8187)->whereNull('removal_reason_id');
            },
            'onSiteOrderParts' => function ($q) use ($customer_paid_type_id) {
                $q->where('status_id', 8202)->whereNull('removal_reason_id');
            },
        ])->find($on_site_order_id);

        // dd($site_visit);
        if (!$site_visit) {
            return false;
        }

        $parts_amount = 0;
        $labour_amount = 0;
        $total_amount = 0;

        if ($site_visit->address) {
            //Check which tax applicable for customer
            if ($site_visit->outlet->state_id == $site_visit->address->state_id) {
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
        $cgst_total = 0;
        $sgst_total = 0;
        $igst_total = 0;
        $cgst_amt = 0;
        $sgst_amt = 0;
        $igst_amt = 0;
        $tcs_total = 0;
        $cess_on_gst_total = 0;

        $labour_details = array();
        $total_labour_qty = 0;
        $total_labour_mrp = 0;
        $total_labour_price = 0;
        $total_labour_tax = 0;
        $tax_percentage = 0;
        $total_labour_taxable_amount = 0;

        if ($site_visit->onSiteOrderRepairOrders) {
            $i = 1;
            foreach ($site_visit->onSiteOrderRepairOrders as $key => $labour) {
                $total_amount = 0;
                $labour_details[$key]['sno'] = $i;
                $labour_details[$key]['code'] = $labour->repairOrder->code;
                $labour_details[$key]['name'] = $labour->repairOrder->name;
                $labour_details[$key]['hsn_code'] = $labour->repairOrder->taxCode ? $labour->repairOrder->taxCode->code : '-';
                $labour_details[$key]['qty'] = '1.00'; //$labour->qty;
                $labour_details[$key]['price'] = $labour->amount;
                $labour_details[$key]['mrp'] = $labour->amount;
                $labour_details[$key]['amount'] = $labour->amount;
                $labour_details[$key]['taxable_amount'] = $labour->amount;
                $labour_details[$key]['is_free_service'] = $labour->is_free_service;
                $labour_details[$key]['estimate_order_id'] = $labour->estimate_order_id;

                $tax_amount = 0;
                $tax_values = array();

                if (in_array($labour->split_order_type_id, $customer_paid_type_id)) {
                    if ($labour->repairOrder->taxCode) {
                        $count = 1;
                        foreach ($labour->repairOrder->taxCode->taxes as $tax_key => $value) {
                            $percentage_value = 0;
                            // dump($count);
                            if ($value->type_id == $tax_type) {
                                $tax_percentage += $value->pivot->percentage;
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

                                //FOR CGST
                                if ($value->name == 'CGST') {
                                    $cgst_amt = $percentage_value;
                                    $cgst_total += $cgst_amt;
                                }
                                //FOR CGST
                                if ($value->name == 'SGST') {
                                    $sgst_amt = $percentage_value;
                                    $sgst_total += $sgst_amt;
                                }
                                //FOR CGST
                                if ($value->name == 'IGST') {
                                    $igst_amt = $percentage_value;
                                    $igst_total += $igst_amt;
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
                    $labour_amount += $total_amount;
                    $labour_details[$key]['tax_values'] = $tax_values;
                    $labour_details[$key]['tax_amount'] = number_format($tax_amount, 2);
                    $labour_details[$key]['total_amount'] = $total_amount;

                    $total_labour_qty += $labour->qty;
                    $total_labour_mrp += $total_amount;
                    $total_labour_price += $labour->amount;
                    $total_labour_tax += $tax_amount;
                    $total_labour_taxable_amount += $labour->amount;

                } else {
                    for ($i = 0; $i < count($taxes); $i++) {
                        $tax_values[$i] = 0.00;
                    }

                    $total_labour_qty += $labour->qty;
                    $labour_details[$key]['tax_values'] = $tax_values;
                    $labour_details[$key]['tax_amount'] = number_format($tax_amount, 2);
                    $labour_details[$key]['total_amount'] = $total_amount;
                }
                $i++;
            }
        }

        $part_details = array();
        $total_parts_qty = 0;
        $total_parts_mrp = 0;
        $total_parts_price = 0;
        $total_parts_tax = 0;
        $total_parts_taxable_amount = 0;

        if ($site_visit->onSiteOrderParts) {
            $j = 1;
            foreach ($site_visit->onSiteOrderParts as $key => $parts) {
                $total_amount = 0;

                $qty = $parts->qty;
                //Issued Qty
                $issued_qty = OnSiteOrderIssuedPart::where('on_site_order_part_id', $parts->id)->sum('issued_qty');
                //Returned Qty
                $returned_qty = OnSiteOrderReturnedPart::where('on_site_order_part_id', $parts->id)->sum('returned_qty');

                $qty = $issued_qty - $returned_qty;

                if ($qty > 0) {
                    $part_details[$key]['sno'] = $j;
                    $part_details[$key]['code'] = $parts->part->code;
                    $part_details[$key]['name'] = $parts->part->name;
                    $part_details[$key]['hsn_code'] = $parts->part->taxCode ? $parts->part->taxCode->code : '-';
                    $part_details[$key]['qty'] = number_format($qty, 2);
                    $part_details[$key]['mrp'] = $parts->rate;
                    $part_details[$key]['price'] = $parts->rate;
                    $part_details[$key]['is_free_service'] = $parts->is_free_service;
                    $part_details[$key]['estimate_order_id'] = $parts->estimate_order_id;

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

                    $tax_amount = 0;
                    $tax_values = array();

                    if (in_array($parts->split_order_type_id, $customer_paid_type_id)) {
                        if ($parts->part->taxCode) {
                            $count = 1;
                            if (count($parts->part->taxCode->taxes) > 0) {
                                foreach ($parts->part->taxCode->taxes as $tax_key => $value) {
                                    $percentage_value = 0;
                                    if ($value->type_id == $tax_type) {
                                        $tax_percentage += $value->pivot->percentage;
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

                                        //FOR CGST
                                        if ($value->name == 'CGST') {
                                            $cgst_amt = $percentage_value;
                                            $cgst_total += $cgst_amt;
                                        }
                                        //FOR CGST
                                        if ($value->name == 'SGST') {
                                            $sgst_amt = $percentage_value;
                                            $sgst_total += $sgst_amt;
                                        }
                                        //FOR CGST
                                        if ($value->name == 'IGST') {
                                            $igst_amt = $percentage_value;
                                            $igst_total += $igst_amt;
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

                        } else {
                            for ($i = 0; $i < count($taxes); $i++) {
                                $tax_values[$i] = 0.00;
                            }
                        }

                        $total_amount = $tax_amount + $total_price;
                        // $total_amount = $parts->amount;
                        // $total_amount = $parts->rate * $qty;
                        $total_amount = number_format((float) $total_amount, 2, '.', '');
                        $parts_amount += $total_amount;
                        $total_parts_qty += $qty;
                        $total_parts_mrp += $parts->rate;
                        $total_parts_price += $price;
                        $total_parts_tax += $tax_amount;
                        $total_parts_taxable_amount += $total_price;
                    } else {
                        for ($i = 0; $i < count($taxes); $i++) {
                            $tax_values[$i] = 0.00;
                        }
                    }

                    $part_details[$key]['tax_values'] = $tax_values;
                    $part_details[$key]['tax_amount'] = number_format($tax_amount, 2);
                    $part_details[$key]['total_amount'] = $total_amount;

                    $j++;
                }
            }
        }

        $data['tax_percentage_wise_amount'] = $tax_percentage_wise_amount;

        $total_amount = $parts_amount + $labour_amount;
        $data['taxes'] = $taxes;
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

        $data['date'] = date('d-m-Y');

        $data['title'] = 'Bill Details';
        $name = $site_visit->number . '_bill_details.pdf';

        $save_path = storage_path('app/public/on-site-visit/pdf');
        Storage::makeDirectory($save_path, 0777);

        if (!Storage::disk('public')->has('on-site-visit/pdf/')) {
            Storage::disk('public')->makeDirectory('on-site-visit/pdf/');
        }

        $pdf = PDF::loadView('pdf-gigo/on-site-billing-pdf', $data)->setPaper('a4', 'portrait');

        $img_path = $save_path . '/' . $name;
        if (File::exists($img_path)) {
            File::delete($img_path);
        }

        $pdf->save(storage_path('app/public/on-site-visit/pdf/' . $name));
    }

    public static function generateEstimatePDF($on_site_order_id, $type)
    {
        $customer_paid_type_id = SplitOrderType::where('paid_by_id', '10013')->pluck('id')->toArray();

        $data['estimate'] = $site_visit = OnSiteOrder::with([
            'company',
            'outlet',
            'onSiteVisitUser',
            'customer',
            'address',
            'address.country',
            'address.state',
            'address.city',
            'status',
            'onSiteOrderRepairOrders' => function ($q) use ($customer_paid_type_id) {
                $q->whereNull('removal_reason_id');
            },
            'onSiteOrderParts' => function ($q) use ($customer_paid_type_id) {
                $q->whereNull('removal_reason_id');
            },
        ])->find($on_site_order_id);

        // dd($site_visit);
        if (!$site_visit) {
            return false;
        }

        $parts_amount = 0;
        $labour_amount = 0;
        $total_amount = 0;

        if ($site_visit->address) {
            //Check which tax applicable for customer
            if ($site_visit->outlet->state_id == $site_visit->address->state_id) {
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
        $total_labour_qty = 0;
        $total_labour_mrp = 0;
        $total_labour_price = 0;
        $total_labour_tax = 0;
        $tax_percentage = 0;
        $total_labour_taxable_amount = 0;

        if ($site_visit->onSiteOrderRepairOrders) {
            $i = 1;
            foreach ($site_visit->onSiteOrderRepairOrders as $key => $labour) {
                $total_amount = 0;
                $labour_details[$key]['sno'] = $i;
                $labour_details[$key]['code'] = $labour->iemrepairOrder->rot_code;
                $labour_details[$key]['name'] = $labour->iemrepairOrder->name;
                $labour_details[$key]['hsn_code'] = $labour->iemrepairOrder->tax_code ? $labour->iemrepairOrder->taxCode->code : '-';
                $labour_details[$key]['qty'] = '1.00'; //$labour->qty;
                $labour_details[$key]['price'] = $labour->amount;
                $labour_details[$key]['mrp'] = $labour->amount;
                $labour_details[$key]['amount'] = $labour->amount;
                $labour_details[$key]['taxable_amount'] = $labour->amount;
                $labour_details[$key]['is_free_service'] = $labour->is_free_service;
                $labour_details[$key]['estimate_order_id'] = $labour->estimate_order_id;

                $tax_amount = 0;
                $tax_values = array();

                if (in_array($labour->split_order_type_id, $customer_paid_type_id)) {
                    if ($labour->repairOrder->taxCode) {
                        $count = 1;
                        foreach ($labour->repairOrder->taxCode->taxes as $tax_key => $value) {
                            $percentage_value = 0;
                            if ($value->type_id == $tax_type) {
                                $tax_percentage += $value->pivot->percentage;
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
                    $labour_amount += $total_amount;
                    $labour_details[$key]['mrp'] = $total_amount;
                    $labour_details[$key]['tax_values'] = $tax_values;
                    $labour_details[$key]['tax_amount'] = number_format($tax_amount, 2);
                    $labour_details[$key]['total_amount'] = $total_amount;

                    $total_labour_qty += $labour->qty;
                    $total_labour_mrp += $total_amount;
                    $total_labour_price += $labour->amount;
                    $total_labour_tax += $tax_amount;
                    $total_labour_taxable_amount += $labour->amount;

                } else {
                    for ($i = 0; $i < count($taxes); $i++) {
                        $tax_values[$i] = 0.00;
                    }

                    $total_labour_qty += $labour->qty;
                    $labour_details[$key]['tax_values'] = $tax_values;
                    $labour_details[$key]['tax_amount'] = number_format($tax_amount, 2);
                    $labour_details[$key]['total_amount'] = $total_amount;
                }
                $i++;
            }
        }

        $part_details = array();
        $total_parts_qty = 0;
        $total_parts_mrp = 0;
        $total_parts_price = 0;
        $total_parts_tax = 0;
        $total_parts_taxable_amount = 0;

        if ($site_visit->onSiteOrderParts) {
            $j = 1;
            foreach ($site_visit->onSiteOrderParts as $key => $parts) {
                $total_amount = 0;

                $qty = $parts->qty;
                $part_details[$key]['sno'] = $j;
                $part_details[$key]['code'] = $parts->part->code;
                $part_details[$key]['name'] = $parts->part->name;
                $part_details[$key]['hsn_code'] = $parts->part->taxCode ? $parts->part->taxCode->code : '-';
                $part_details[$key]['qty'] = number_format($qty, 2);
                $part_details[$key]['mrp'] = $parts->rate;
                $part_details[$key]['price'] = $parts->rate;
                $part_details[$key]['is_free_service'] = $parts->is_free_service;
                $part_details[$key]['estimate_order_id'] = $parts->estimate_order_id;

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

                $tax_amount = 0;
                $tax_values = array();

                if (in_array($parts->split_order_type_id, $customer_paid_type_id)) {
                    if ($parts->part->taxCode) {
                        $count = 1;
                        if (count($parts->part->taxCode->taxes) > 0) {
                            foreach ($parts->part->taxCode->taxes as $tax_key => $value) {
                                $percentage_value = 0;
                                if ($value->type_id == $tax_type) {
                                    $tax_percentage += $value->pivot->percentage;
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

                    } else {
                        for ($i = 0; $i < count($taxes); $i++) {
                            $tax_values[$i] = 0.00;
                        }
                    }

                    // $total_amount = $tax_amount + $parts->amount;
                    // $total_amount = $parts->amount;
                    $total_amount = $tax_amount + $total_price;
                    $total_amount = number_format((float) $total_amount, 2, '.', '');
                    $parts_amount += $total_amount;
                    $total_parts_qty += $qty;
                    $total_parts_mrp += $parts->rate;
                    $total_parts_price += $price;
                    $total_parts_tax += $tax_amount;
                    $total_parts_taxable_amount += $total_price;
                } else {
                    for ($i = 0; $i < count($taxes); $i++) {
                        $tax_values[$i] = 0.00;
                    }
                }

                $part_details[$key]['tax_values'] = $tax_values;
                $part_details[$key]['tax_amount'] = number_format($tax_amount, 2);
                $part_details[$key]['total_amount'] = $total_amount;

                $j++;
            }
        }

        $data['tax_percentage_wise_amount'] = $tax_percentage_wise_amount;

        $total_amount = $parts_amount + $labour_amount;
        $data['taxes'] = $taxes;
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
        $data['total_parts_tax'] = number_format((float) $total_parts_taxable_amount, 2, '.', '');
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

        $data['date'] = date('d-m-Y');

        if ($type == 1) {
            $data['title'] = 'Estimate';
            $name = $site_visit->number . '_estimate.pdf';
        } elseif ($type == 2) {
            $data['title'] = 'Revised Estimate';
            $name = $site_visit->number . '_revised_estimate.pdf';
        }

        $data['type'] = $type;

        $save_path = storage_path('app/public/on-site-visit/pdf');
        Storage::makeDirectory($save_path, 0777);

        if (!Storage::disk('public')->has('on-site-visit/pdf/')) {
            Storage::disk('public')->makeDirectory('on-site-visit/pdf/');
        }

        $pdf = PDF::loadView('pdf-gigo/on-site-estimate-pdf', $data)->setPaper('a4', 'portrait');

        $img_path = $save_path . '/' . $name;
        if (File::exists($img_path)) {
            File::delete($img_path);
        }

        $pdf->save(storage_path('app/public/on-site-visit/pdf/' . $name));

    }

    public static function generateLabourPDF($on_site_order_id)
    {

        $customer_paid_type_id = SplitOrderType::where('paid_by_id', '10013')->pluck('id')->toArray();

        $data['site_visit'] = $site_visit = OnSiteOrder::with([
            'company',
            'outlet',
            'onSiteVisitUser',
            'customer',
            'address',
            'address.country',
            'address.state',
            'address.city',
            'status',
            'onSiteOrderRepairOrders' => function ($q) use ($customer_paid_type_id) {
                $q->where('status_id', 8187)->whereNull('removal_reason_id');
            },
        ])->find($on_site_order_id);

        if (!$site_visit) {
            return false;
        }

        $labour_amount = 0;
        $total_amount = 0;

        if ($site_visit->address) {
            //Check which tax applicable for customer
            if ($site_visit->outlet->state_id == $site_visit->address->state_id) {
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
        $total_labour_qty = 0;
        $total_labour_mrp = 0;
        $total_labour_price = 0;
        $total_labour_tax = 0;
        $tax_percentage = 0;
        $total_labour_taxable_amount = 0;

        if ($site_visit->onSiteOrderRepairOrders) {
            $i = 1;
            foreach ($site_visit->onSiteOrderRepairOrders as $key => $labour) {
                $total_amount = 0;
                $labour_details[$key]['sno'] = $i;
                $labour_details[$key]['code'] = $labour->iemrepairOrder->code;
                $labour_details[$key]['name'] = $labour->iemrepairOrder->name;
                $labour_details[$key]['hsn_code'] = $labour->iemrepairOrder->taxCode ? $labour->iemrepairOrder->taxCode->code : '-';
                $labour_details[$key]['qty'] = '1.00'; //$labour->qty;
                $labour_details[$key]['price'] = $labour->amount;
                $labour_details[$key]['mrp'] = $labour->amount;
                $labour_details[$key]['amount'] = $labour->amount;
                $labour_details[$key]['taxable_amount'] = $labour->amount;
                $labour_details[$key]['is_free_service'] = $labour->is_free_service;
                $labour_details[$key]['estimate_order_id'] = $labour->estimate_order_id;

                $tax_amount = 0;
                $tax_values = array();

                if (in_array($labour->split_order_type_id, $customer_paid_type_id)) {
                    if ($labour->iemrepairOrder->taxCode) {
                        $count = 1;

                        foreach ($labour->repairOrder->taxCode->taxes as $tax_key => $value) {
                            $percentage_value = 0;
                            if ($value->type_id == $tax_type) {
                                $tax_percentage += $value->pivot->percentage;
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
                    $labour_amount += $total_amount;
                    $labour_details[$key]['mrp'] = $total_amount;

                    $total_labour_qty += $labour->qty;
                    $total_labour_mrp += $total_amount;
                    $total_labour_price += $labour->amount;
                    $total_labour_tax += $tax_amount;
                    $total_labour_taxable_amount += $labour->amount;

                } else {
                    for ($i = 0; $i < count($taxes); $i++) {
                        $tax_values[$i] = 0.00;
                    }

                    $total_labour_qty += $labour->qty;
                }

                $labour_details[$key]['tax_values'] = $tax_values;
                $labour_details[$key]['tax_amount'] = number_format($tax_amount, 2);
                $labour_details[$key]['total_amount'] = $total_amount;

                $i++;
            }
        }
        $data['tax_percentage_wise_amount'] = $tax_percentage_wise_amount;
        // $data['tax_percentage'] = $tax_percentage;
        $data['type'] = $type;
        $total_amount = $labour_amount;
        $data['taxes'] = $taxes;
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

        $data['date'] = date('d-m-Y');

        $data['title'] = 'Labour Invoice';
        $name = $site_visit->number . '_labour_invoice.pdf';

        $save_path = storage_path('app/public/gigo/pdf');
        Storage::makeDirectory($save_path, 0777);

        $data['date'] = date('d-m-Y');

        $save_path = storage_path('app/public/on-site-visit/pdf');
        Storage::makeDirectory($save_path, 0777);

        if (!Storage::disk('public')->has('on-site-visit/pdf/')) {
            Storage::disk('public')->makeDirectory('on-site-visit/pdf/');
        }

        $pdf = PDF::loadView('pdf-gigo/on-site-visit-labour-pdf', $data)->setPaper('a4', 'portrait');

        $img_path = $save_path . '/' . $name;
        if (File::exists($img_path)) {
            File::delete($img_path);
        }

        $pdf->save(storage_path('app/public/on-site-visit/pdf/' . $name));

        return true;
    }

    public static function generatePartPDF($on_site_order_id)
    {

        $customer_paid_type_id = SplitOrderType::where('paid_by_id', '10013')->pluck('id')->toArray();

        $data['site_visit'] = $site_visit = OnSiteOrder::with([
            'company',
            'outlet',
            'onSiteVisitUser',
            'customer',
            'address',
            'address.country',
            'address.state',
            'address.city',
            'status',
            'onSiteOrderParts' => function ($q) use ($customer_paid_type_id) {
                $q->where('status_id', 8202)->whereNull('removal_reason_id');
            },
        ])->find($on_site_order_id);

        if (!$site_visit) {
            return false;
        }

        $parts_amount = 0;
        $labour_amount = 0;
        $total_amount = 0;

        if ($site_visit->address) {
            //Check which tax applicable for customer
            if ($site_visit->outlet->state_id == $site_visit->address->state_id) {
                $tax_type = 1160; //Within State
            } else {
                $tax_type = 1161; //Inter State
            }
        } else {
            $tax_type = 1160; //Within State
        }

        //Count Tax Type
        $taxes = Tax::whereIn('id', [1, 2, 3])->get();

        $tax_percentage = 0;
        $tax_percentage_wise_amount = [];

        $part_details = array();
        $total_parts_qty = 0;
        $total_parts_mrp = 0;
        $total_parts_price = 0;
        $total_parts_tax = 0;
        $total_parts_taxable_amount = 0;

        if ($site_visit->onSiteOrderParts) {
            $i = 1;
            foreach ($site_visit->onSiteOrderParts as $key => $parts) {
                $total_amount = 0;
                //Calculate issue,returned parts
                //Issued Qty
                $issued_qty = OnSiteOrderIssuedPart::where('on_site_order_part_id', $parts->id)->sum('issued_qty');
                //Returned Qty
                $returned_qty = OnSiteOrderReturnedPart::where('on_site_order_part_id', $parts->id)->sum('returned_qty');
                $qty = $issued_qty - $returned_qty;

                if ($qty > 0) {
                    $part_details[$key]['sno'] = $i;
                    $part_details[$key]['code'] = $parts->part->code;
                    $part_details[$key]['name'] = $parts->part->name;
                    $part_details[$key]['hsn_code'] = $parts->part->taxCode ? $parts->part->taxCode->code : '-';
                    $part_details[$key]['qty'] = number_format($qty, 2);
                    $part_details[$key]['mrp'] = $parts->rate;
                    $part_details[$key]['price'] = $parts->rate;
                    $part_details[$key]['is_free_service'] = $parts->is_free_service;
                    $part_details[$key]['estimate_order_id'] = $parts->estimate_order_id;

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

                    $tax_amount = 0;
                    $tax_values = array();

                    if (in_array($parts->split_order_type_id, $customer_paid_type_id)) {
                        if ($parts->part->taxCode) {
                            if (count($parts->part->taxCode->taxes) > 0) {
                                $count = 1;
                                foreach ($parts->part->taxCode->taxes as $tax_key => $value) {
                                    $percentage_value = 0;
                                    if ($value->type_id == $tax_type) {
                                        $tax_percentage += $value->pivot->percentage;
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

                        } else {
                            for ($i = 0; $i < count($taxes); $i++) {
                                $tax_values[$i] = 0.00;
                            }
                        }

                        // $total_amount = $parts->amount;
                        $total_amount = $tax_amount + $total_price;
                        $total_amount = number_format((float) $total_amount, 2, '.', '');
                        $parts_amount += $total_amount;
                        $total_parts_qty += $qty;
                        $total_parts_mrp += $parts->rate;
                        $total_parts_price += $price;
                        $total_parts_tax += $tax_amount;
                        $total_parts_taxable_amount += $total_price;

                    } else {
                        for ($i = 0; $i < count($taxes); $i++) {
                            $tax_values[$i] = 0.00;
                        }
                    }

                    $part_details[$key]['tax_values'] = $tax_values;
                    $part_details[$key]['tax_amount'] = number_format($tax_amount, 2);
                    $part_details[$key]['total_amount'] = $total_amount;

                    $i++;
                }
            }
        }

        $data['tax_percentage_wise_amount'] = $tax_percentage_wise_amount;

        $total_amount = $parts_amount;
        $data['taxes'] = $taxes;
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

        $data['date'] = date('d-m-Y');

        $data['title'] = 'Parts Invoice';

        $name = $site_visit->number . '_parts_invoice.pdf';

        $save_path = storage_path('app/public/on-site-visit/pdf');
        Storage::makeDirectory($save_path, 0777);

        if (!Storage::disk('public')->has('on-site-visit/pdf/')) {
            Storage::disk('public')->makeDirectory('on-site-visit/pdf/');
        }

        $pdf = PDF::loadView('pdf-gigo/on-site-visit-part-pdf', $data)->setPaper('a4', 'portrait');

        $img_path = $save_path . '/' . $name;
        if (File::exists($img_path)) {
            File::delete($img_path);
        }

        $pdf->save(storage_path('app/public/on-site-visit/pdf/' . $name));

        return true;
    }

}

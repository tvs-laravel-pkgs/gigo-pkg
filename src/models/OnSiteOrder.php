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

    public function onSiteOrderRepairOrders()
    {
        return $this->hasMany('App\OnSiteOrderRepairOrder', 'on_site_order_id');
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

    public static function generateEstimatePDF($on_site_order_id, $type)
    {
        $customer_paid_type_id = SplitOrderType::where('paid_by_id', '10013')->pluck('id')->toArray();

        if ($type == 3) {
            $data['estimate'] = $site_visit = OnSiteOrder::with([
                'company',
                'outlet',
                'onSiteVisitUser',
                'customer',
                'customer.primaryAddress',
                'customer.primaryAddress.country',
                'customer.primaryAddress.state',
                'customer.primaryAddress.city',
                'status',
                'onSiteOrderRepairOrders' => function ($q) use ($customer_paid_type_id) {
                    $q->where('status_id', 8187)->whereNull('removal_reason_id');
                },
                'onSiteOrderParts' => function ($q) use ($customer_paid_type_id) {
                    $q->where('status_id', 8202)->whereNull('removal_reason_id');
                },
            ])->find($on_site_order_id);
        } else {
            $data['estimate'] = $site_visit = OnSiteOrder::with([
                'company',
                'outlet',
                'onSiteVisitUser',
                'customer',
                'customer.primaryAddress',
                'customer.primaryAddress.country',
                'customer.primaryAddress.state',
                'customer.primaryAddress.city',
                'status',
                'onSiteOrderRepairOrders' => function ($q) use ($customer_paid_type_id) {
                    $q->whereNull('removal_reason_id');
                },
                'onSiteOrderParts' => function ($q) use ($customer_paid_type_id) {
                    $q->whereNull('removal_reason_id');
                },
            ])->find($on_site_order_id);

        }

        // dd($site_visit);
        if (!$site_visit) {
            return false;
        }

        $parts_amount = 0;
        $labour_amount = 0;
        $total_amount = 0;

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

        //Count Tax Type
        $taxes = Tax::whereIn('id', [1, 2, 3])->get();

        //GET SEPERATE TAXEX
        $seperate_tax = array();
        for ($i = 0; $i < count($taxes); $i++) {
            $seperate_tax[$i] = 0.00;
        }

        $labour_details = array();
        $total_labour_qty = 0;
        $total_labour_mrp = 0;
        $total_labour_price = 0;
        $total_labour_tax = 0;
        $tax_percentage = 0;

        if ($site_visit->onSiteOrderRepairOrders) {
            $i = 1;
            foreach ($site_visit->onSiteOrderRepairOrders as $key => $labour) {
                $total_amount = 0;
                $labour_details[$key]['sno'] = $i;
                $labour_details[$key]['code'] = $labour->repairOrder->code;
                $labour_details[$key]['name'] = $labour->repairOrder->name;
                $labour_details[$key]['hsn_code'] = $labour->repairOrder->taxCode ? $labour->repairOrder->taxCode->code : '-';
                $labour_details[$key]['rate'] = $labour->repairOrder->amount;
                $labour_details[$key]['qty'] = $labour->qty;
                $labour_details[$key]['amount'] = $labour->amount;
                $labour_details[$key]['is_free_service'] = $labour->is_free_service;
                $labour_details[$key]['estimate_order_id'] = $labour->estimate_order_id;

                if (in_array($labour->split_order_type_id, $customer_paid_type_id)) {
                    $tax_amount = 0;
                    $tax_values = array();
                    if ($labour->repairOrder->taxCode) {
                        foreach ($labour->repairOrder->taxCode->taxes as $tax_key => $value) {
                            $percentage_value = 0;
                            if ($value->type_id == $tax_type) {
                                $tax_percentage += $value->pivot->percentage;
                                $percentage_value = ($labour->amount * $value->pivot->percentage) / 100;
                                $percentage_value = number_format((float) $percentage_value, 2, '.', '');
                            }
                            $tax_values[$tax_key] = $percentage_value;
                            $tax_amount += $percentage_value;

                            if (count($seperate_tax) > 0) {
                                $seperate_tax_value = $seperate_tax[$tax_key];
                            } else {
                                $seperate_tax_value = 0;
                            }
                            $seperate_tax[$tax_key] = $seperate_tax_value + $percentage_value;
                        }
                    } else {
                        for ($i = 0; $i < count($taxes); $i++) {
                            $tax_values[$i] = 0.00;
                        }
                    }

                    $total_labour_qty += $labour->qty;
                    $total_labour_mrp += $labour->amount;
                    $total_labour_price += $labour->repairOrder->amount;
                    $total_labour_tax += $tax_amount;

                    $total_amount = $tax_amount + $labour->amount;
                    $total_amount = number_format((float) $total_amount, 2, '.', '');
                    $labour_amount += $total_amount;
                    $labour_details[$key]['tax_values'] = $tax_values;
                    $labour_details[$key]['tax_amount'] = number_format($tax_amount, 2);
                    $labour_details[$key]['total_amount'] = $total_amount;
                } else {
                    $tax_amount = 0;
                    $tax_values = array();

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

        if ($site_visit->onSiteOrderParts) {
            $i = 1;
            foreach ($site_visit->onSiteOrderParts as $key => $parts) {
                $total_amount = 0;
                $part_details[$key]['sno'] = $i;
                $part_details[$key]['code'] = $parts->part->code;
                $part_details[$key]['name'] = $parts->part->name;
                $part_details[$key]['hsn_code'] = $parts->part->taxCode ? $parts->part->taxCode->code : '-';
                $part_details[$key]['qty'] = $parts->qty;
                $part_details[$key]['rate'] = $parts->rate;
                $part_details[$key]['amount'] = $parts->amount;
                $part_details[$key]['is_free_service'] = $parts->is_free_service;
                $part_details[$key]['estimate_order_id'] = $parts->estimate_order_id;

                if (in_array($parts->split_order_type_id, $customer_paid_type_id)) {
                    $tax_amount = 0;
                    $tax_values = array();
                    if ($parts->part->taxCode) {
                        if (count($parts->part->taxCode->taxes) > 0) {
                            foreach ($parts->part->taxCode->taxes as $tax_key => $value) {
                                $percentage_value = 0;
                                if ($value->type_id == $tax_type) {
                                    $tax_percentage += $value->pivot->percentage;
                                    $percentage_value = ($parts->amount * $value->pivot->percentage) / 100;
                                    $percentage_value = number_format((float) $percentage_value, 2, '.', '');
                                }
                                $tax_values[$tax_key] = $percentage_value;
                                $tax_amount += $percentage_value;

                                if (count($seperate_tax) > 0) {
                                    $seperate_tax_value = $seperate_tax[$tax_key];
                                } else {
                                    $seperate_tax_value = 0;
                                }
                                $seperate_tax[$tax_key] = $seperate_tax_value + $percentage_value;
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

                    $total_amount = $tax_amount + $parts->amount;
                    $total_amount = number_format((float) $total_amount, 2, '.', '');
                    $parts_amount += $total_amount;
                    $total_parts_qty += $parts->qty;
                    $total_parts_mrp += $parts->rate;
                    $total_parts_price += $parts->amount;
                    $total_parts_tax += $tax_amount;

                    $part_details[$key]['tax_values'] = $tax_values;
                    $part_details[$key]['tax_amount'] = number_format($tax_amount, 2);
                    $part_details[$key]['total_amount'] = $total_amount;
                } else {
                    $tax_amount = 0;
                    $tax_values = array();
                    if ($parts->part->taxCode) {
                        if (count($parts->part->taxCode->taxes) > 0) {
                            foreach ($parts->part->taxCode->taxes as $tax_key => $value) {
                                $percentage_value = 0;
                                if ($value->type_id == $tax_type) {
                                    $percentage_value = ($parts->amount * $value->pivot->percentage) / 100;
                                    $percentage_value = number_format((float) $percentage_value, 2, '.', '');
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

                    $part_details[$key]['tax_values'] = $tax_values;
                    $part_details[$key]['tax_amount'] = number_format($tax_amount, 2);
                    $part_details[$key]['total_amount'] = $total_amount;
                }
                $i++;
            }
        }

        $total_amount = $parts_amount + $labour_amount;

        foreach ($seperate_tax as $key => $s_tax) {
            $seperate_tax[$key] = convert_number_to_words($s_tax);
        }
        $data['seperate_taxes'] = $seperate_tax;
        $total_taxable_amount = $total_labour_tax + $total_parts_tax;
        $data['tax_percentage'] = convert_number_to_words($tax_percentage);
        $data['total_taxable_amount'] = convert_number_to_words($total_taxable_amount);

        $data['taxes'] = $taxes;
        $data['part_details'] = $part_details;
        $data['labour_details'] = $labour_details;
        $data['total_labour_qty'] = $total_labour_qty;
        $data['total_labour_mrp'] = number_format($total_labour_mrp, 2);
        $data['total_labour_price'] = number_format($total_labour_price, 2);
        $data['total_labour_tax'] = number_format($total_labour_tax, 2);

        $data['total_parts_qty'] = $total_parts_qty;
        $data['total_parts_mrp'] = number_format($total_parts_mrp, 2);
        $data['total_parts_price'] = number_format($total_parts_price, 2);
        $data['total_parts_tax'] = number_format($total_parts_tax, 2);

        $data['tax_count'] = count($taxes);
        $data['parts_total_amount'] = number_format($parts_amount, 2);
        $data['labour_total_amount'] = number_format($labour_amount, 2);

        //FOR ROUND OFF
        if ($total_amount <= round($total_amount)) {
            $round_off = round($total_amount) - $total_amount;
        } else {
            $round_off = round($total_amount) - $total_amount;
        }

        $data['round_total_amount'] = number_format($round_off, 2);

        $total_amount = round($total_amount);

        $data['total_amount'] = number_format($total_amount, 2);
        $data['date'] = date('d-m-Y');

        if ($type == 1) {
            $data['title'] = 'Estimate';
            $name = $site_visit->number . '_estimate.pdf';
        } elseif ($type == 2) {
            $data['title'] = 'Revised Estimate';
            $name = $site_visit->number . '_revised_estimate.pdf';
        } else {
            $data['title'] = 'Bill Details';
            $name = $site_visit->number . '_bill_details.pdf';
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
            'customer.primaryAddress',
            'customer.primaryAddress.country',
            'customer.primaryAddress.state',
            'customer.primaryAddress.city',
            'status',
            'onSiteOrderRepairOrders' => function ($q) use ($customer_paid_type_id) {
                $q->where('status_id', 8187)->whereNull('removal_reason_id');
            },
        ])->find($on_site_order_id);

        // dd($site_visit->onSiteOrderRepairOrders);

        if (!$site_visit) {
            return false;
        }

        $parts_amount = 0;
        $labour_amount = 0;
        $total_amount = 0;

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

        //Count Tax Type
        $taxes = Tax::whereIn('id', [1, 2, 3])->get();

        //GET SEPERATE TAXEX
        $seperate_tax = array();
        for ($i = 0; $i < count($taxes); $i++) {
            $seperate_tax[$i] = 0.00;
        }

        $labour_details = array();
        $total_labour_qty = 0;
        $total_labour_mrp = 0;
        $total_labour_price = 0;
        $total_labour_tax = 0;
        $tax_percentage = 0;

        if ($site_visit->onSiteOrderRepairOrders) {
            $i = 1;
            foreach ($site_visit->onSiteOrderRepairOrders as $key => $labour) {
                $total_amount = 0;
                $labour_details[$key]['sno'] = $i;
                $labour_details[$key]['code'] = $labour->repairOrder->code;
                $labour_details[$key]['name'] = $labour->repairOrder->name;
                $labour_details[$key]['hsn_code'] = $labour->repairOrder->taxCode ? $labour->repairOrder->taxCode->code : '-';
                $labour_details[$key]['rate'] = $labour->repairOrder->amount;
                $labour_details[$key]['qty'] = $labour->qty;
                $labour_details[$key]['amount'] = $labour->amount;
                $labour_details[$key]['is_free_service'] = $labour->is_free_service;
                $labour_details[$key]['estimate_order_id'] = $labour->estimate_order_id;

                if (in_array($labour->split_order_type_id, $customer_paid_type_id)) {
                    $tax_amount = 0;
                    $tax_values = array();
                    if ($labour->repairOrder->taxCode) {
                        foreach ($labour->repairOrder->taxCode->taxes as $tax_key => $value) {
                            $percentage_value = 0;
                            if ($value->type_id == $tax_type) {
                                $tax_percentage += $value->pivot->percentage;
                                $percentage_value = ($labour->amount * $value->pivot->percentage) / 100;
                                $percentage_value = number_format((float) $percentage_value, 2, '.', '');
                            }
                            $tax_values[$tax_key] = $percentage_value;
                            $tax_amount += $percentage_value;

                            if (count($seperate_tax) > 0) {
                                $seperate_tax_value = $seperate_tax[$tax_key];
                            } else {
                                $seperate_tax_value = 0;
                            }
                            $seperate_tax[$tax_key] = $seperate_tax_value + $percentage_value;
                        }
                    } else {
                        for ($i = 0; $i < count($taxes); $i++) {
                            $tax_values[$i] = 0.00;
                        }
                    }

                    $total_labour_qty += $labour->qty;
                    $total_labour_mrp += $labour->amount;
                    $total_labour_price += $labour->repairOrder->amount;
                    $total_labour_tax += $tax_amount;

                    $total_amount = $tax_amount + $labour->amount;
                    $total_amount = number_format((float) $total_amount, 2, '.', '');
                    $labour_amount += $total_amount;
                    $labour_details[$key]['tax_values'] = $tax_values;
                    $labour_details[$key]['tax_amount'] = number_format($tax_amount, 2);
                    $labour_details[$key]['total_amount'] = $total_amount;
                } else {
                    $tax_amount = 0;
                    $tax_values = array();

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

        // dd($labour_details);

        $total_amount = $labour_amount;

        foreach ($seperate_tax as $key => $s_tax) {
            $seperate_tax[$key] = convert_number_to_words($s_tax);
        }
        $data['seperate_taxes'] = $seperate_tax;
        $total_taxable_amount = $total_labour_tax;
        $data['tax_percentage'] = convert_number_to_words($tax_percentage);
        $data['total_taxable_amount'] = convert_number_to_words($total_taxable_amount);

        $data['taxes'] = $taxes;
        $data['labour_details'] = $labour_details;
        $data['total_labour_qty'] = $total_labour_qty;
        $data['total_labour_mrp'] = number_format($total_labour_mrp, 2);
        $data['total_labour_price'] = number_format($total_labour_price, 2);
        $data['total_labour_tax'] = number_format($total_labour_tax, 2);

        $data['tax_count'] = count($taxes);
        $data['labour_total_amount'] = number_format($labour_amount, 2);

        //FOR ROUND OFF
        if ($total_amount <= round($total_amount)) {
            $round_off = round($total_amount) - $total_amount;
        } else {
            $round_off = round($total_amount) - $total_amount;
        }

        $data['round_total_amount'] = number_format($round_off, 2);

        $total_amount = round($total_amount);

        $data['total_amount'] = number_format($total_amount, 2);
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
            'customer.primaryAddress',
            'customer.primaryAddress.country',
            'customer.primaryAddress.state',
            'customer.primaryAddress.city',
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

        //Count Tax Type
        $taxes = Tax::whereIn('id', [1, 2, 3])->get();

        //GET SEPERATE TAXEX
        $seperate_tax = array();
        for ($i = 0; $i < count($taxes); $i++) {
            $seperate_tax[$i] = 0.00;
        }

        $tax_percentage = 0;

        $part_details = array();
        $total_parts_qty = 0;
        $total_parts_mrp = 0;
        $total_parts_price = 0;
        $total_parts_tax = 0;

        if ($site_visit->onSiteOrderParts) {
            $i = 1;
            foreach ($site_visit->onSiteOrderParts as $key => $parts) {
                $total_amount = 0;
                $part_details[$key]['sno'] = $i;
                $part_details[$key]['code'] = $parts->part->code;
                $part_details[$key]['name'] = $parts->part->name;
                $part_details[$key]['hsn_code'] = $parts->part->taxCode ? $parts->part->taxCode->code : '-';
                $part_details[$key]['qty'] = $parts->qty;
                $part_details[$key]['rate'] = $parts->rate;
                $part_details[$key]['amount'] = $parts->amount;
                $part_details[$key]['is_free_service'] = $parts->is_free_service;
                $part_details[$key]['estimate_order_id'] = $parts->estimate_order_id;

                if (in_array($parts->split_order_type_id, $customer_paid_type_id)) {
                    $tax_amount = 0;
                    $tax_values = array();
                    if ($parts->part->taxCode) {
                        if (count($parts->part->taxCode->taxes) > 0) {
                            foreach ($parts->part->taxCode->taxes as $tax_key => $value) {
                                $percentage_value = 0;
                                if ($value->type_id == $tax_type) {
                                    $tax_percentage += $value->pivot->percentage;
                                    $percentage_value = ($parts->amount * $value->pivot->percentage) / 100;
                                    $percentage_value = number_format((float) $percentage_value, 2, '.', '');
                                }
                                $tax_values[$tax_key] = $percentage_value;
                                $tax_amount += $percentage_value;

                                if (count($seperate_tax) > 0) {
                                    $seperate_tax_value = $seperate_tax[$tax_key];
                                } else {
                                    $seperate_tax_value = 0;
                                }
                                $seperate_tax[$tax_key] = $seperate_tax_value + $percentage_value;
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

                    $total_amount = $tax_amount + $parts->amount;
                    $total_amount = number_format((float) $total_amount, 2, '.', '');
                    $parts_amount += $total_amount;
                    $total_parts_qty += $parts->qty;
                    $total_parts_mrp += $parts->rate;
                    $total_parts_price += $parts->amount;
                    $total_parts_tax += $tax_amount;

                    $part_details[$key]['tax_values'] = $tax_values;
                    $part_details[$key]['tax_amount'] = number_format($tax_amount, 2);
                    $part_details[$key]['total_amount'] = $total_amount;
                } else {
                    $tax_amount = 0;
                    $tax_values = array();
                    if ($parts->part->taxCode) {
                        if (count($parts->part->taxCode->taxes) > 0) {
                            foreach ($parts->part->taxCode->taxes as $tax_key => $value) {
                                $percentage_value = 0;
                                if ($value->type_id == $tax_type) {
                                    $percentage_value = ($parts->amount * $value->pivot->percentage) / 100;
                                    $percentage_value = number_format((float) $percentage_value, 2, '.', '');
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

                    $part_details[$key]['tax_values'] = $tax_values;
                    $part_details[$key]['tax_amount'] = number_format($tax_amount, 2);
                    $part_details[$key]['total_amount'] = $total_amount;
                }
                $i++;
            }
        }

        $total_amount = $parts_amount;

        foreach ($seperate_tax as $key => $s_tax) {
            $seperate_tax[$key] = convert_number_to_words($s_tax);
        }
        $data['seperate_taxes'] = $seperate_tax;
        $total_taxable_amount = $total_parts_tax;
        $data['tax_percentage'] = convert_number_to_words($tax_percentage);
        $data['total_taxable_amount'] = convert_number_to_words($total_taxable_amount);

        $data['taxes'] = $taxes;
        $data['part_details'] = $part_details;

        $data['total_parts_qty'] = $total_parts_qty;
        $data['total_parts_mrp'] = number_format($total_parts_mrp, 2);
        $data['total_parts_price'] = number_format($total_parts_price, 2);
        $data['total_parts_tax'] = number_format($total_parts_tax, 2);

        $data['tax_count'] = count($taxes);
        $data['parts_total_amount'] = number_format($parts_amount, 2);

        //FOR ROUND OFF
        if ($total_amount <= round($total_amount)) {
            $round_off = round($total_amount) - $total_amount;
        } else {
            $round_off = round($total_amount) - $total_amount;
        }

        $data['round_total_amount'] = number_format($round_off, 2);

        $total_amount = round($total_amount);

        $data['total_amount'] = number_format($total_amount, 2);
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

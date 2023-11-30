<?php

namespace Abs\GigoPkg;

use App\Config;
use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Oracle\ArInvoiceExport;
use App\Oracle\ApInvoiceExport;
use App\Oracle\OtherTypeDetail;
use Abs\GigoPkg\GigoInvoiceItem;
use Auth;
use App\BatteryLoadTestResult;

class GigoInvoice extends BaseModel
{
    use SeederTrait;
    use SoftDeletes;
    protected $table = 'gigo_invoices';
    public $timestamps = true;
    protected $fillable = [
        'company_id',
        'invoice_number',
        'invoice_date',
        'customer_id',
        'invoice_of_id',
        'entity_id',
        'outlet_id',
        'sbu_id',
        'invoice_amount',
        'received_amount',
        'balance_amount',
        'created_by_id',
        'created_at',
    ];

    public function getCreatedAtAttribute($date)
    {
        return empty($date) ? '' : date('d-m-Y h:i A ', strtotime($date));
    }

    public function invoiceItems()
    {
        return $this->hasMany('Abs\GigoPkg\GigoInvoiceItem', 'invoice_id');
    }

    public function customer()
    {
        return $this->belongsTo('App\Customer', 'customer_id')->withTrashed();
    }

    public function outlet()
    {
        return $this->belongsTo('App\Outlet', 'outlet_id')->withTrashed();
    }

    public function sbu()
    {
        return $this->belongsTo('App\Sbu', 'sbu_id')->withTrashed();
    }

    public function saleOrder()
    {
        return $this->belongsTo('App\SaleOrder', 'entity_id');
    }

    public function company()
    {
        return $this->belongsTo('App\Company', 'company_id');
    }

    public function invoiceExportToOracle() {
        $res = [];
        $res['success'] = false;
        $res['errors'] = [];

        $gigoInvoice = $this;
        if (empty($gigoInvoice->invoice_amount) || floatval($gigoInvoice->invoice_amount) == 0.00) {
            $res['errors'] = ['Invoice value should be greater than 0'];
            return $res;
        }

        $companyName = null;
        $companyCode = null;
        if(!empty($gigoInvoice->company->oem_business_unit)){
            $companyName = $gigoInvoice->company->oem_business_unit->name;
            $companyCode = $gigoInvoice->company->oem_business_unit->code;
        }
        $businessUnitName = $companyName;

        //DOUBT
        $transactionClass = '';
        $transactionBatchName = '';
        $transactionTypeName = '';
        $transactionDetail = $gigoInvoice->company ? $gigoInvoice->company->vehicleBatteryPurchaseInvoiceTransaction() : null;
        if (!empty($transactionDetail)) {
            $transactionClass = $transactionDetail->class ? $transactionDetail->class : $transactionClass;
            $transactionBatchName = $transactionDetail->batch ? $transactionDetail->batch : $transactionBatchName;
            $transactionTypeName = $transactionDetail->type ? $transactionDetail->type : $transactionTypeName;
        }

        $arInvoiceExport = ArInvoiceExport::where([
            'transaction_number' => $gigoInvoice->invoice_number,
            'business_unit' => $companyName,
            'transaction_type_name' => $transactionTypeName,
        ])->first();
        if (!empty($arInvoiceExport)) {
            $res['errors'] = ['Invoice already exported to oracle table'];
            return $res;
        }

        $transactionNumber = $gigoInvoice->invoice_number;
        $invoiceDate = $gigoInvoice->invoice_date ? date("Y-m-d", strtotime($gigoInvoice->invoice_date)) : null;

        $gigoInvoiceItemDetails = GigoInvoiceItem::where('invoice_id', $gigoInvoice->id)->get();
        if (count($gigoInvoiceItemDetails) == 0) {
            $res['errors'] = ['Invoice item details not found'];
            return $res;
        }

        $customerCode = $gigoInvoice->customer ? $gigoInvoice->customer->code : null;
        $outletCode = $gigoInvoice->outlet ? $gigoInvoice->outlet->oracle_code_l2 : null;
        $customerSiteNumber = $outletCode;
        $accountingClass = 'REV';
        $sbu = $gigoInvoice->sbu;
        $lob = $costCentre = null;
        if ($sbu) {
            $lob = $sbu->oracle_code;
            $costCentre = $sbu->oracle_cost_centre;
        }
        $location = $outletCode;
        //DOUBT
        // $labourItemCode = Config::where('id', 133923)->first()->name;
        // $labourNaturalAccount = $labourItemCode;

        // $partItemCode = Config::where('id', 133924)->first()->name;
        // $partNaturalAccount = $partItemCode;
        $export_record = [];
        $export_record['company_id'] = $gigoInvoice->company_id;
        $export_record['business_unit'] = $businessUnitName;
        $export_record['transaction_class'] = $transactionClass;
        $export_record['transaction_batch_source_name'] = $transactionBatchName;
        $export_record['transaction_type_name'] = $transactionTypeName;
        $export_record['transaction_number'] = $transactionNumber;
        $export_record['transaction_date'] = $invoiceDate;
        $export_record['customer_account_number'] = $customerCode;
        $export_record['credit_outlet'] = $outletCode;
        $export_record['quantity'] = 1;
        $export_record['accounting_class'] = $accountingClass;
        $export_record['company'] = $companyCode;
        $export_record['lob'] = $lob;
        $export_record['location'] = $location;
        $export_record['cost_centre'] = $costCentre;
        $export_record['created_by_id'] = !empty(Auth::user()->id) ? Auth::user()->id : $gigoInvoice->updated_by_id;

        $itemRecords = [];
        foreach ($gigoInvoiceItemDetails as $key => $itemDetail) {
            $taxCodeId = !empty($itemDetail->grnItemData->itemData->tax_code_id) ? $itemDetail->grnItemData->itemData->tax_code_id : 0;
            if(empty($taxCodeId)){
                $taxCodeId = 'ws'.$key;
            }

            $batteryDetails = BatteryLoadTestResult::select([
                'battery_makes.name as battery_name',
                'battery_load_test_results.battery_serial_number as battery_serial_number',
            ])
                ->join('part_return_items','part_return_items.item_id', 'battery_load_test_results.id')
                ->join('vehicle_batteries', 'vehicle_batteries.id', 'battery_load_test_results.vehicle_battery_id')
                ->join('battery_makes', 'battery_makes.id', 'battery_load_test_results.battery_make_id')
                ->where('part_return_items.id', $itemDetail->entity_id)
                ->first();
            $batterySerialNumber = $batteryName = null;
            if ($batteryDetails){
                $batterySerialNumber = $batteryDetails->battery_serial_number;
                $batteryName = $batteryDetails->battery_name;
            }

            if (!isset($itemRecords[$taxCodeId]) || !$itemRecords[$taxCodeId]) {
                $itemRecords[$taxCodeId] = [];
                $itemRecords[$taxCodeId]['unit_price'] = 0;
                $itemRecords[$taxCodeId]['cgst_amount'] = 0;
                $itemRecords[$taxCodeId]['cgst_percentage'] = 0;
                $itemRecords[$taxCodeId]['sgst_amount'] = 0;
                $itemRecords[$taxCodeId]['sgst_percentage'] = 0;
                $itemRecords[$taxCodeId]['igst_amount'] = 0;
                $itemRecords[$taxCodeId]['igst_percentage'] = 0;
                $itemRecords[$taxCodeId]['ugst_amount'] = 0;
                $itemRecords[$taxCodeId]['ugst_percentage'] = 0;
                $itemRecords[$taxCodeId]['tcs_amount'] = 0;
                $itemRecords[$taxCodeId]['tcs_percentage'] = 0;
                $itemRecords[$taxCodeId]['cess_amount'] = 0;
                $itemRecords[$taxCodeId]['cess_percentage'] = 0;
                $itemRecords[$taxCodeId]['hsn_code'] = isset($itemDetail->grnItemData->itemData->taxCode) ? $itemDetail->grnItemData->itemData->taxCode->code : '';
                $itemRecords[$taxCodeId]['description'] = 'Bp.Inv:'.$transactionNumber.',Dt:'.$invoiceDate;
            }

            //TAXES
            $cgstDetail = $itemDetail->taxes()->where('tax_id', 1)->select('amount', 'percentage')->first();
            $sgstDetail = $itemDetail->taxes()->where('tax_id', 2)->select('amount', 'percentage')->first();
            $igstDetail = $itemDetail->taxes()->where('tax_id', 3)->select('amount', 'percentage')->first();
            $ugstDetail = $itemDetail->taxes()->where('tax_id', 7)->select('amount', 'percentage')->first();
            $tcsDetail = $itemDetail->taxes()->where('tax_id', 5)->select('amount', 'percentage')->first();
            $cessDetail = $itemDetail->taxes()->where('tax_id', 6)->select('amount', 'percentage')->first();

            $unitPrice = $itemDetail->amount;
            if ($unitPrice && $unitPrice > 0) {
                $itemRecords[$taxCodeId]['unit_price'] += floatval($unitPrice);
            }

            if (isset($cgstDetail->amount) && $cgstDetail->amount > 0) {
                $itemRecords[$taxCodeId]['cgst_amount'] += floatval($cgstDetail->amount);
                $itemRecords[$taxCodeId]['cgst_percentage'] = floatval($cgstDetail->percentage);
            }

            if (isset($sgstDetail->amount) && $sgstDetail->amount > 0) {
                $itemRecords[$taxCodeId]['sgst_amount'] += floatval($sgstDetail->amount);
                $itemRecords[$taxCodeId]['sgst_percentage'] = floatval($sgstDetail->percentage);
            }

            if (isset($igstDetail->amount) && $igstDetail->amount > 0) {
                $itemRecords[$taxCodeId]['igst_amount'] += floatval($igstDetail->amount);
                $itemRecords[$taxCodeId]['igst_percentage'] = floatval($igstDetail->percentage);
            }

            if (isset($ugstDetail->amount) && $ugstDetail->amount > 0) {
                $itemRecords[$taxCodeId]['ugst_amount'] += floatval($ugstDetail->amount);
                $itemRecords[$taxCodeId]['ugst_percentage'] = floatval($ugstDetail->percentage);
            }

            if (isset($tcsDetail->amount) && $tcsDetail->amount > 0) {
                $itemRecords[$taxCodeId]['tcs_amount'] += floatval($tcsDetail->amount);
                $itemRecords[$taxCodeId]['tcs_percentage'] = floatval($tcsDetail->percentage);
            }

            if (isset($cessDetail->amount) && $cessDetail->amount > 0) {
                $itemRecords[$taxCodeId]['cess_amount'] += floatval($cessDetail->amount);
                $itemRecords[$taxCodeId]['cess_percentage'] = floatval($cessDetail->percentage);
            }

            $description = '';
            if ($batterySerialNumber) {
                $description .= $batterySerialNumber;   
            }
            if ($batteryName) {
                $description .= ($description ? '-'.($batteryName) : ($batteryName));   
            }
            $itemRecords[$taxCodeId]['description'] .= ',' . ($description);
        }

        $showInvoiceAmount = true;
        if (count($itemRecords) > 0) {
            $unitPriceAmt = array_sum(array_column($itemRecords, 'unit_price'));
            $cgstAmt = array_sum(array_column($itemRecords, 'cgst_amount'));
            $sgstAmt = array_sum(array_column($itemRecords, 'sgst_amount'));
            $igstAmt = array_sum(array_column($itemRecords, 'igst_amount'));
            $ugstAmt = array_sum(array_column($itemRecords, 'ugst_amount'));
            $tcsAmt = array_sum(array_column($itemRecords, 'tcs_amount'));
            $cessAmt = array_sum(array_column($itemRecords, 'cess_amount'));
            $invoiceTotal = floatval($unitPriceAmt + $cgstAmt + $sgstAmt + $igstAmt + $ugstAmt + $tcsAmt + $cessAmt);
            if (round($gigoInvoice->invoice_amount) != round($invoiceTotal)) {
                $res['errors'] = ['Invoice item amount and tax amount not matched with invoice amount.'];
                return $res;
            }

            foreach ($itemRecords as $itemRecord) {
                $export_record['unit_price'] = $itemRecord['unit_price'];
                $export_record['amount'] = $itemRecord['unit_price'];
                $export_record['hsn_code'] = $itemRecord['hsn_code'];
                $export_record['cgst'] = $itemRecord['cgst_amount'];
                $export_record['sgst'] = $itemRecord['sgst_amount'];
                $export_record['igst'] = $itemRecord['igst_amount'];
                $export_record['ugst'] = $itemRecord['ugst_amount'];
                $export_record['tcs'] = $itemRecord['tcs_amount'];
                $export_record['cess'] = $itemRecord['cess_amount'];
                $export_record['description'] = $itemRecord['description'];

                //TAX CLASSIFICATIONS
                $taxNames = '';
                $taxPercentages = '';
                if (floatval($export_record['cgst']) > 0 && floatval($export_record['sgst']) > 0) {
                    $taxNames = 'CGST+SGST';
                    $taxPercentages = round(floatval($itemRecord['cgst_percentage']) + floatval($itemRecord['sgst_percentage']));
                }

                if (floatval($export_record['igst']) > 0) {
                    if (!empty($taxNames)) {
                        $taxNames .= '+IGST';
                    } else {
                        $taxNames .= 'IGST';
                    }

                    if (!empty($taxPercentages)) {
                        $taxPercentages .= '+' . (round(floatval($itemRecord['igst_percentage'])));
                    } else {
                        $taxPercentages .= round(floatval($itemRecord['igst_percentage']));
                    }
                }

                if (floatval($export_record['ugst']) > 0) {
                    if (!empty($taxNames)) {
                        $taxNames .= '+UGST';
                    } else {
                        $taxNames .= 'UGST';
                    }

                    if (!empty($taxPercentages)) {
                        $taxPercentages .= '+' . (round(floatval($itemRecord['ugst_percentage'])));
                    } else {
                        $taxPercentages .= round(floatval($itemRecord['ugst_percentage']));
                    }
                }

                if (floatval($export_record['tcs']) > 0) {
                    if (!empty($taxNames)) {
                        $taxNames .= '+TCS';
                    } else {
                        $taxNames .= 'TCS';
                    }

                    if (!empty($taxPercentages)) {
                        $taxPercentages .= '+' . (round(floatval($itemRecord['tcs_percentage'])));
                    } else {
                        $taxPercentages .= round(floatval($itemRecord['tcs_percentage']));
                    }
                }

                if (floatval($export_record['cess']) > 0) {
                    if (!empty($taxNames)) {
                        $taxNames .= '+CESS';
                    } else {
                        $taxNames .= 'CESS';
                    }

                    if (!empty($taxPercentages)) {
                        $taxPercentages .= '+' . (round(floatval($itemRecord['cess_percentage'])));
                    } else {
                        $taxPercentages .= round(floatval($itemRecord['cess_percentage']));
                    }
                }

                $taxClassifications = '';
                if(!empty($taxNames) || !empty($taxPercentages)){
                    $taxClassifications = $taxNames . ' REC ' . $taxPercentages;
                }
                $export_record['tax_classification'] = $taxClassifications;
                $export_record['invoice_amount'] = null;
                if ($showInvoiceAmount == true) {
                    $export_record['invoice_amount'] = round($invoiceTotal);
                }
                $storeInOracleTable = ArInvoiceExport::store($export_record);
                $showInvoiceAmount = false;
            }

            //ROUND OFF ENTRY
            $amountDiff = 0;
            if (!empty($invoiceTotal)) {
                $amountDiff = number_format((round($invoiceTotal) - $invoiceTotal), 2, '.', '');
            }
            if ($amountDiff && $amountDiff != '0.00') {
                $roundOffTransaction = OtherTypeDetail::arRoundOffTransaction();
                $export_record['invoice_amount'] = $export_record['cgst'] = $export_record['sgst'] = $export_record['igst'] = $export_record['ugst'] = $export_record['tcs'] = $export_record['cess'] = $export_record['tax_classification'] = $export_record['hsn_code'] = null;
                $export_record['description'] = $roundOffTransaction ? $roundOffTransaction->name : null;
                $export_record['unit_price'] = $amountDiff;
                $export_record['amount'] = $amountDiff;
                $export_record['accounting_class'] = $roundOffTransaction ? $roundOffTransaction->accounting_class : null;
                $export_record['natural_account'] = $roundOffTransaction ? $roundOffTransaction->natural_account : null;
                $storeInOracleTable = ArInvoiceExport::store($export_record);
            }
        }
        $res['success'] = true;
        return $res;
    }

    public function vehicleBatteryPurchaseApInvoiceExportToOracle() {
        $res = [];
        $res['success'] = false;
        $res['errors'] = [];

        $gigoInvoice = $this;
        if (empty($gigoInvoice->invoice_amount) || floatval($gigoInvoice->invoice_amount) == 0.00) {
            $res['errors'] = ['Invoice value should be greater than 0'];
            return $res;
        }

        $gigoInvoiceItemDetails = GigoInvoiceItem::where('invoice_id', $gigoInvoice->id)->get();
        if (count($gigoInvoiceItemDetails) == 0) {
            $res['errors'] = ['Invoice item details not found'];
            return $res;
        }

        $businessUnit = null;
        $companyCode = null;
        if(!empty($gigoInvoice->company->oem_business_unit)){
            $businessUnit = $gigoInvoice->company->oem_business_unit->name;
            $companyCode = $gigoInvoice->company->oem_business_unit->code;
        }

        $invoiceSource = null;
        $documentType = null;
        $transactionDetail = $gigoInvoice->company ? $gigoInvoice->company->vehicleBatteryPurchaseInvoiceTransaction() : null;
        if (!empty($transactionDetail)) {
            $invoiceSource = $transactionDetail->batch ? $transactionDetail->batch : $invoiceSource;
            $documentType = $transactionDetail->type ? $transactionDetail->type : $documentType;
        }

        $apInvoiceExport = ApInvoiceExport::where([
            'invoice_number' => $gigoInvoice->invoice_number,
            'business_unit' => $businessUnit,
            'document_type' => $documentType,
        ])->first();
        if ($apInvoiceExport) {
            $res['errors'] = ['Invoice already exported to oracle table'];
            return $res;
        }

        $invoiceNumber = $gigoInvoice->invoice_number;
        $invoiceDate = $gigoInvoice->invoice_date ? date("Y-m-d", strtotime($gigoInvoice->invoice_date)) : null;
        $outletCode = $gigoInvoice->outlet ? $gigoInvoice->outlet->oracle_code_l2 : null;
        $lob = $department = null;
        $sbu = $gigoInvoice->sbu;
        if ($sbu) {
            $lob = $sbu->oracle_code;
            $department = $sbu->oracle_cost_centre;
        }
        $naturalAccount = Config::getConfigName(134400);

        $export_record = [
            'company_id' => $gigoInvoice->company_id,
            'business_unit' => $businessUnit,
            'invoice_source' => $invoiceSource,
            'invoice_number' => $invoiceNumber,
            'invoice_date' => $invoiceDate,
            'supplier_number' => $gigoInvoice->customer ? $gigoInvoice->customer->code : null,
            'supplier_site_name' => $outletCode,
            'invoice_type' => 'STANDARD',
            'accounting_date' => $invoiceDate,
            'outlet' => $outletCode,
            'document_type' => $documentType,
            'line_type' => 'Item',
            'accounting_class' => 'Purchase/Expense',
            'company' => $companyCode,
            'lob' => $lob,
            'location' => $outletCode,
            'department' => $department,
            'natural_account' => $naturalAccount,
            'created_by_id' => (isset(Auth::user()->id) && Auth::user()->id) ? Auth::user()->id : $this->updated_by_id,
        ];

        // Item based
        $itemRecords = [];
        foreach ($gigoInvoiceItemDetails as $key => $itemDetail) {
            $taxCodeId = !empty($itemDetail->grnItemData->itemData->tax_code_id) ? $itemDetail->grnItemData->itemData->tax_code_id : 0;
            if(empty($taxCodeId)){
                $taxCodeId = 'ws'.$key;
            }

            $batteryDetails = BatteryLoadTestResult::select([
                'battery_makes.name as battery_name',
                'battery_load_test_results.battery_serial_number as battery_serial_number',
            ])
                ->join('part_return_items','part_return_items.item_id', 'battery_load_test_results.id')
                ->join('battery_makes', 'battery_makes.id', 'battery_load_test_results.battery_make_id')
                ->where('part_return_items.id', $itemDetail->entity_id)
                ->first();
            $batterySerialNumber = $batteryName = null;
            if ($batteryDetails){
                $batterySerialNumber = $batteryDetails->battery_serial_number;
                $batteryName = $batteryDetails->battery_name;
            }

            if (!isset($itemRecords[$taxCodeId]) || !$itemRecords[$taxCodeId]) {
                $itemRecords[$taxCodeId] = [];
                $itemRecords[$taxCodeId]['amount'] = 0;
                $itemRecords[$taxCodeId]['cgst_amount'] = 0;
                $itemRecords[$taxCodeId]['cgst_percentage'] = 0;
                $itemRecords[$taxCodeId]['sgst_amount'] = 0;
                $itemRecords[$taxCodeId]['sgst_percentage'] = 0;
                $itemRecords[$taxCodeId]['igst_amount'] = 0;
                $itemRecords[$taxCodeId]['igst_percentage'] = 0;
                $itemRecords[$taxCodeId]['ugst_amount'] = 0;
                $itemRecords[$taxCodeId]['ugst_percentage'] = 0;
                $itemRecords[$taxCodeId]['tcs_amount'] = 0;
                $itemRecords[$taxCodeId]['tcs_percentage'] = 0;
                $itemRecords[$taxCodeId]['cess_amount'] = 0;
                $itemRecords[$taxCodeId]['cess_percentage'] = 0;
                $itemRecords[$taxCodeId]['hsn_code'] = isset($itemDetail->grnItemData->itemData->taxCode) ? $itemDetail->grnItemData->itemData->taxCode->code : '';
                // $itemRecords[$taxCodeId]['invoice_description'] = 'Bp.Inv:'.$invoiceNumber.',Dt:'.$invoiceDate;
                $itemRecords[$taxCodeId]['invoice_description'] = null;
                $itemRecords[$taxCodeId]['item_count'] = 0;
            }

            //TAXES
            $cgstDetail = $itemDetail->taxes()->where('tax_id', 1)->select('amount', 'percentage')->first();
            $sgstDetail = $itemDetail->taxes()->where('tax_id', 2)->select('amount', 'percentage')->first();
            $igstDetail = $itemDetail->taxes()->where('tax_id', 3)->select('amount', 'percentage')->first();
            $ugstDetail = $itemDetail->taxes()->where('tax_id', 7)->select('amount', 'percentage')->first();
            $tcsDetail = $itemDetail->taxes()->where('tax_id', 5)->select('amount', 'percentage')->first();
            $cessDetail = $itemDetail->taxes()->where('tax_id', 6)->select('amount', 'percentage')->first();

            $unitPrice = floatval($itemDetail->amount);
            if ($unitPrice && $unitPrice > 0) {
                $itemRecords[$taxCodeId]['amount'] += $unitPrice;
            }

            if (isset($cgstDetail->amount) && $cgstDetail->amount > 0) {
                $itemRecords[$taxCodeId]['cgst_amount'] += floatval($cgstDetail->amount);
                $itemRecords[$taxCodeId]['cgst_percentage'] = floatval($cgstDetail->percentage);
            }

            if (isset($sgstDetail->amount) && $sgstDetail->amount > 0) {
                $itemRecords[$taxCodeId]['sgst_amount'] += floatval($sgstDetail->amount);
                $itemRecords[$taxCodeId]['sgst_percentage'] = floatval($sgstDetail->percentage);
            }

            if (isset($igstDetail->amount) && $igstDetail->amount > 0) {
                $itemRecords[$taxCodeId]['igst_amount'] += floatval($igstDetail->amount);
                $itemRecords[$taxCodeId]['igst_percentage'] = floatval($igstDetail->percentage);
            }

            if (isset($ugstDetail->amount) && $ugstDetail->amount > 0) {
                $itemRecords[$taxCodeId]['ugst_amount'] += floatval($ugstDetail->amount);
                $itemRecords[$taxCodeId]['ugst_percentage'] = floatval($ugstDetail->percentage);
            }

            if (isset($tcsDetail->amount) && $tcsDetail->amount > 0) {
                $itemRecords[$taxCodeId]['tcs_amount'] += floatval($tcsDetail->amount);
                $itemRecords[$taxCodeId]['tcs_percentage'] = floatval($tcsDetail->percentage);
            }

            if (isset($cessDetail->amount) && $cessDetail->amount > 0) {
                $itemRecords[$taxCodeId]['cess_amount'] += floatval($cessDetail->amount);
                $itemRecords[$taxCodeId]['cess_percentage'] = floatval($cessDetail->percentage);
            }

            $description = '';
            if ($batterySerialNumber) {
                $description .= $batterySerialNumber;   
            }
            if ($batteryName) {
                $description .= ($description ? '-'.($batteryName) : ($batteryName));   
            }
            $itemRecords[$taxCodeId]['invoice_description'] .= ',' . ($description);
            $itemRecords[$taxCodeId]['item_count']++;
        }
        $showInvoiceAmount = true;
        if (count($itemRecords) > 0) {
            $unitPriceAmt = array_sum(array_column($itemRecords, 'amount'));
            $cgstAmt = array_sum(array_column($itemRecords, 'cgst_amount'));
            $sgstAmt = array_sum(array_column($itemRecords, 'sgst_amount'));
            $igstAmt = array_sum(array_column($itemRecords, 'igst_amount'));
            $ugstAmt = array_sum(array_column($itemRecords, 'ugst_amount'));
            $tcsAmt = array_sum(array_column($itemRecords, 'tcs_amount'));
            $cessAmt = array_sum(array_column($itemRecords, 'cess_amount'));
            $invoiceTotal = floatval($unitPriceAmt + $cgstAmt + $sgstAmt + $igstAmt + $ugstAmt + $tcsAmt + $cessAmt);

            foreach ($itemRecords as $itemRecord) {
                $export_record['amount'] = $itemRecord['amount'];
                $export_record['hsn_code'] = $itemRecord['hsn_code'];
                $export_record['cgst'] = $itemRecord['cgst_amount'];
                $export_record['sgst'] = $itemRecord['sgst_amount'];
                $export_record['igst'] = $itemRecord['igst_amount'];
                $export_record['ugst'] = $itemRecord['ugst_amount'];
                $export_record['tcs'] = $itemRecord['tcs_amount'];
                $export_record['cess'] = $itemRecord['cess_amount'];
                // $export_record['invoice_description'] = $itemRecord['invoice_description'];
                $export_record['invoice_description'] = 'Purchase of '. $itemRecord['item_count']. ' item'. $itemRecord['invoice_description'];

                //TAX CLASSIFICATIONS
                $taxNames = '';
                $taxPercentages = '';
                if (floatval($export_record['cgst']) > 0 && floatval($export_record['sgst']) > 0) {
                    $taxNames = 'CGST+SGST';
                    $taxPercentages = round(floatval($itemRecord['cgst_percentage']) + floatval($itemRecord['sgst_percentage']));
                }

                if (floatval($export_record['igst']) > 0) {
                    if (!empty($taxNames)) {
                        $taxNames .= '+IGST';
                    } else {
                        $taxNames .= 'IGST';
                    }

                    if (!empty($taxPercentages)) {
                        $taxPercentages .= '+' . (round(floatval($itemRecord['igst_percentage'])));
                    } else {
                        $taxPercentages .= round(floatval($itemRecord['igst_percentage']));
                    }
                }

                if (floatval($export_record['ugst']) > 0) {
                    if (!empty($taxNames)) {
                        $taxNames .= '+UGST';
                    } else {
                        $taxNames .= 'UGST';
                    }

                    if (!empty($taxPercentages)) {
                        $taxPercentages .= '+' . (round(floatval($itemRecord['ugst_percentage'])));
                    } else {
                        $taxPercentages .= round(floatval($itemRecord['ugst_percentage']));
                    }
                }

                if (floatval($export_record['tcs']) > 0) {
                    if (!empty($taxNames)) {
                        $taxNames .= '+TCS';
                    } else {
                        $taxNames .= 'TCS';
                    }

                    if (!empty($taxPercentages)) {
                        $taxPercentages .= '+' . (round(floatval($itemRecord['tcs_percentage'])));
                    } else {
                        $taxPercentages .= round(floatval($itemRecord['tcs_percentage']));
                    }
                }

                if (floatval($export_record['cess']) > 0) {
                    if (!empty($taxNames)) {
                        $taxNames .= '+CESS';
                    } else {
                        $taxNames .= 'CESS';
                    }

                    if (!empty($taxPercentages)) {
                        $taxPercentages .= '+' . (round(floatval($itemRecord['cess_percentage'])));
                    } else {
                        $taxPercentages .= round(floatval($itemRecord['cess_percentage']));
                    }
                }

                $taxClassifications = '';
                if(!empty($taxNames) || !empty($taxPercentages)){
                    $taxClassifications = $taxNames . ' REC ' . $taxPercentages;
                }
                $export_record['tax_classification'] = $taxClassifications;
                $taxAmount = $itemRecord['cgst_amount'] + $itemRecord['sgst_amount'] + $itemRecord['igst_amount'] + $itemRecord['ugst_amount'] + $itemRecord['tcs_amount'] + $itemRecord['cess_amount'];
                $export_record['tax_amount'] = $taxAmount;

                $export_record['invoice_amount'] = null;
                if ($showInvoiceAmount == true) {
                    $export_record['invoice_amount'] = round($invoiceTotal);
                }
                $storeInOracleTable = ApInvoiceExport::store($export_record);
                $showInvoiceAmount = false;
            }

            //ROUND OFF ENTRY
            $amountDiff = 0;
            if (!empty($invoiceTotal)) {
                $amountDiff = number_format((round($invoiceTotal) - $invoiceTotal), 2, '.', '');
            }
            if ($amountDiff && floatval($amountDiff) != 0.00) {
                $roundOffTransaction = OtherTypeDetail::apRoundOffTransaction();
                $export_record['invoice_amount'] = $export_record['hsn_code'] = $export_record['cgst'] = $export_record['sgst'] = $export_record['igst'] = $export_record['ugst'] = $export_record['tcs'] = $export_record['cess'] = $export_record['tax_amount'] = $export_record['tax_classification'] = null;
                
                $export_record['amount'] = $amountDiff;
                $export_record['accounting_class'] = $roundOffTransaction ? $roundOffTransaction->accounting_class : null;
                $export_record['natural_account'] = $roundOffTransaction ? $roundOffTransaction->natural_account : null;
                $export_record['invoice_description'] = $roundOffTransaction ? $roundOffTransaction->name : null;
                $storeInOracleTable = ApInvoiceExport::store($export_record);
            }
        }
        $res['success'] = true;
        return $res;
    }
}

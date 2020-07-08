<?php

namespace Abs\GigoPkg;
use Abs\GigoPkg\JobCard;
use Abs\GigoPkg\JobOrderRepairOrder;
use Abs\GigoPkg\JobOrder;
use Abs\TaxPkg\Tax;
use DB;
use PDF;
use App\Http\Controllers\Controller;

class PDFController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	public function gatePass($id) {

		$this->data['gate_pass'] = JobCard::with([
			'gatePasses',
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
				DB::raw('DATE_FORMAT(job_cards.created_at,"%d/%m/%Y") as jobdate'),
				DB::raw('DATE_FORMAT(job_cards.created_at,"%h:%i %p") as time'),
			])
			->find($id);

			//dd($this->data['gate_pass']);

	    $pdf = PDF::loadView('pdf-gigo/gate-pass-pdf',$this->data);

		return $pdf->stream('gate-pass-pdf');
	}

	public function coveringletter($id) {

		$this->data['covering_letter'] = JobCard::with([
			'gatePasses',
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
				DB::raw('DATE_FORMAT(job_cards.created_at,"%d/%m/%Y") as jobdate'),
				DB::raw('DATE_FORMAT(job_cards.created_at,"%h:%i %p") as time'),
			])
			->find($id);

	    $pdf = PDF::loadView('pdf-gigo/covering-letter-pdf',$this->data);

		return $pdf->stream('covering-letter-pdf');
	}

	public function estimate($id) {

		$this->data['estimate'] = $job_order = JobCard::with([
			'gatePasses',
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
		    'jobOrder.serviceAdviser',
		    'jobOrder.roadTestPreferedBy.employee',
		    'jobOrder.jobOrderParts.part',
			'jobOrder.jobOrderParts.part.taxCode',
			'jobOrder.jobOrderParts.part.taxCode.taxes',])
			->select([
				'job_cards.*',
				DB::raw('DATE_FORMAT(job_cards.created_at,"%d/%m/%Y") as jobdate'),
				DB::raw('DATE_FORMAT(job_cards.created_at,"%h:%i %p") as time'),
			])
			->find($id);

		    $parts_amount = 0;
			$labour_amount = 0;
			$total_amount = 0;

			//Check which tax applicable for customer
			if ($job_order->jobOrder->outlet->state_id == $job_order->jobOrder->vehicle->currentOwner->customer->primaryAddress->state_id) {
				$tax_type = 1160; //Within State
			} else {
				$tax_type = 1161; //Inter State
			}

			//Count Tax Type
			$taxes = Tax::get();

			$labour_details = array();
			if ($job_order->jobOrder->jobOrderRepairOrders) {
				$i = 1;
				$total_labour_qty = 0;
				$total_labour_mrp = 0;
				$total_labour_price = 0;
				$total_labour_tax = 0;
				foreach ($job_order->jobOrder->jobOrderRepairOrders as $key => $labour) {
					$total_amount = 0;
					$labour_details[$key]['sno'] = $i;
					$labour_details[$key]['code'] = $labour->repairOrder->code;
					$labour_details[$key]['hsn_code'] = $labour->repairOrder->taxCode ? $labour->repairOrder->taxCode->code : '-';
					$labour_details[$key]['qty'] = $labour->qty;
					$labour_details[$key]['amount'] = $labour->amount;
					$labour_details[$key]['rate'] = $labour->repairOrder->amount;
					$labour_details[$key]['is_free_service'] = $labour->is_free_service;
					$tax_amount = 0;
					$tax_values = array();
					if ($labour->repairOrder->taxCode) {
						foreach ($labour->repairOrder->taxCode->taxes as $tax_key => $value) {
							$percentage_value = 0;
							if ($value->type_id == $tax_type) {
								$percentage_value = ($labour->amount * $value->pivot->percentage) / 100;
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

					$total_labour_qty += $labour->qty;
					$total_labour_mrp += $labour->amount;
					$total_labour_price += $labour->repairOrder->amount;
					$total_labour_tax += $tax_amount;

					$labour_details[$key]['tax_values'] = $tax_values;
					$labour_details[$key]['tax_amount'] = $tax_amount;
					$total_amount = $tax_amount + $labour->amount;
					$total_amount = number_format((float) $total_amount, 2, '.', '');
					if ($labour->is_free_service != 1) {
						$labour_amount += $total_amount;
					}
					$labour_details[$key]['total_amount'] = $total_amount;
					$i++;
				}
			}

			$part_details = array();
			if ($job_order->jobOrder->jobOrderParts) {
				$i = 1;
				$total_parts_qty = 0;
				$total_parts_mrp = 0;
				$total_parts_price = 0;
				$total_parts_tax = 0;
				foreach ($job_order->jobOrder->jobOrderParts as $key => $parts) {
					$total_amount = 0;
					$part_details[$key]['sno'] = $i;
					$part_details[$key]['code'] = $parts->part->code;
					$part_details[$key]['hsn_code'] = $parts->part->taxCode ? $parts->part->taxCode->code : '-';
					$part_details[$key]['qty'] = $parts->qty;
					$part_details[$key]['rate'] = $parts->rate;
					$part_details[$key]['amount'] = $parts->amount;
					$part_details[$key]['is_free_service'] = $parts->is_free_service;
					$tax_amount = 0;
					$tax_values = array();
					if ($parts->part->taxCode) {
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

					$total_parts_qty += $parts->qty;
					$total_parts_mrp += $parts->rate;
					$total_parts_price += $parts->amount;
					$total_parts_tax += $tax_amount;

					$part_details[$key]['tax_values'] = $tax_values;
					$part_details[$key]['tax_amount'] = $tax_amount;
					$total_amount = $tax_amount + $parts->amount;
					$total_amount = number_format((float) $total_amount, 2, '.', '');
					if ($parts->is_free_service != 1) {
						$parts_amount += $total_amount;
					}
					$part_details[$key]['total_amount'] = $total_amount;
					$i++;
				}
			}

			$total_amount = $parts_amount + $labour_amount;
			$this->data['taxes'] = $taxes;
			$this->data['part_details'] = $part_details;
			$this->data['labour_details'] = $labour_details;
			$this->data['total_labour_qty'] = $total_labour_qty;
			$this->data['total_labour_mrp'] = $total_labour_mrp;
			$this->data['total_labour_price'] = $total_labour_price;
			$this->data['total_labour_tax'] = $total_labour_tax;

			$this->data['total_parts_qty'] = $total_parts_qty;
			$this->data['total_parts_mrp'] = $total_parts_mrp;
			$this->data['total_parts_price'] = $total_parts_price;
			$this->data['total_parts_tax'] = $total_parts_tax;
			$this->data['parts_total_amount'] = number_format($parts_amount, 2);
			$this->data['labour_total_amount'] = number_format($labour_amount, 2);
			$this->data['round_total_amount'] = round($total_amount);
			$this->data['total_amount'] = number_format($total_amount, 2);

			
	    $pdf = PDF::loadView('pdf-gigo/estimate-pdf',$this->data);

		return $pdf->stream('estimate-pdf');
	}

	public function InsuranceEstimate($id) {

		$this->data['insurance_estimate'] = $job_order = JobCard::with([
			'gatePasses',
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
		    'jobOrder.serviceAdviser',
		    'jobOrder.roadTestPreferedBy.employee',
		    'jobOrder.jobOrderParts.part',
			'jobOrder.jobOrderParts.part.taxCode',
			'jobOrder.jobOrderParts.part.taxCode.taxes',])
			->select([
				'job_cards.*',
				DB::raw('DATE_FORMAT(job_cards.created_at,"%d/%m/%Y") as jobdate'),
				DB::raw('DATE_FORMAT(job_cards.created_at,"%h:%i %p") as time'),
			])
			->find($id);

			//dd($this->data['gate_pass']);

		    $parts_amount = 0;
			$labour_amount = 0;
			$total_amount = 0;

			//Check which tax applicable for customer
			if ($job_order->jobOrder->outlet->state_id == $job_order->jobOrder->vehicle->currentOwner->customer->primaryAddress->state_id) {
				$tax_type = 1160; //Within State
			} else {
				$tax_type = 1161; //Inter State
			}

			//Count Tax Type
			$taxes = Tax::get();

			$labour_details = array();
			if ($job_order->jobOrder->jobOrderRepairOrders) {
				$i = 1;
				$total_labour_qty = 0;
				$total_labour_mrp = 0;
				$total_labour_price = 0;
				$total_labour_tax = 0;
				foreach ($job_order->jobOrder->jobOrderRepairOrders as $key => $labour) {
					$total_amount = 0;
					$labour_details[$key]['sno'] = $i;
					$labour_details[$key]['code'] = $labour->repairOrder->code;
					$labour_details[$key]['hsn_code'] = $labour->repairOrder->taxCode ? $labour->repairOrder->taxCode->code : '-';
					$labour_details[$key]['qty'] = $labour->qty;
					$labour_details[$key]['amount'] = $labour->amount;
					$labour_details[$key]['rate'] = $labour->repairOrder->amount;
					$labour_details[$key]['is_free_service'] = $labour->is_free_service;
					$tax_amount = 0;
					$tax_values = array();
					if ($labour->repairOrder->taxCode) {
						foreach ($labour->repairOrder->taxCode->taxes as $tax_key => $value) {
							$percentage_value = 0;
							if ($value->type_id == $tax_type) {
								$percentage_value = ($labour->amount * $value->pivot->percentage) / 100;
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

					$total_labour_qty += $labour->qty;
					$total_labour_mrp += $labour->amount;
					$total_labour_price += $labour->repairOrder->amount;
					$total_labour_tax += $tax_amount;

					$labour_details[$key]['tax_values'] = $tax_values;
					$labour_details[$key]['tax_amount'] = $tax_amount;
					$total_amount = $tax_amount + $labour->amount;
					$total_amount = number_format((float) $total_amount, 2, '.', '');
					if ($labour->is_free_service != 1) {
						$labour_amount += $total_amount;
					}
					$labour_details[$key]['total_amount'] = $total_amount;
					$i++;
				}
			}

			$part_details = array();
			if ($job_order->jobOrder->jobOrderParts) {
				$i = 1;
				$total_parts_qty = 0;
				$total_parts_mrp = 0;
				$total_parts_price = 0;
				$total_parts_tax = 0;
				foreach ($job_order->jobOrder->jobOrderParts as $key => $parts) {
					$total_amount = 0;
					$part_details[$key]['sno'] = $i;
					$part_details[$key]['code'] = $parts->part->code;
					$part_details[$key]['hsn_code'] = $parts->part->taxCode ? $parts->part->taxCode->code : '-';
					$part_details[$key]['qty'] = $parts->qty;
					$part_details[$key]['rate'] = $parts->rate;
					$part_details[$key]['amount'] = $parts->amount;
					$part_details[$key]['is_free_service'] = $parts->is_free_service;
					$tax_amount = 0;
					$tax_values = array();
					if ($parts->part->taxCode) {
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

					$total_parts_qty += $parts->qty;
					$total_parts_mrp += $parts->rate;
					$total_parts_price += $parts->amount;
					$total_parts_tax += $tax_amount;

					$part_details[$key]['tax_values'] = $tax_values;
					$part_details[$key]['tax_amount'] = $tax_amount;
					$total_amount = $tax_amount + $parts->amount;
					$total_amount = number_format((float) $total_amount, 2, '.', '');
					if ($parts->is_free_service != 1) {
						$parts_amount += $total_amount;
					}
					$part_details[$key]['total_amount'] = $total_amount;
					$i++;
				}
			}

			$total_amount = $parts_amount + $labour_amount;
			$this->data['taxes'] = $taxes;
			$this->data['part_details'] = $part_details;
			$this->data['labour_details'] = $labour_details;
			$this->data['total_labour_qty'] = $total_labour_qty;
			$this->data['total_labour_mrp'] = $total_labour_mrp;
			$this->data['total_labour_price'] = $total_labour_price;
			$this->data['total_labour_tax'] = $total_labour_tax;

			$this->data['total_parts_qty'] = $total_parts_qty;
			$this->data['total_parts_mrp'] = $total_parts_mrp;
			$this->data['total_parts_price'] = $total_parts_price;
			$this->data['total_parts_tax'] = $total_parts_tax;
			$this->data['parts_total_amount'] = number_format($parts_amount, 2);
			$this->data['labour_total_amount'] = number_format($labour_amount, 2);

			$this->data['labour_round_total_amount'] = round($labour_amount);
			$this->data['labour_total_amount'] = number_format($labour_amount, 2);
			$this->data['parts_round_total_amount'] = round($parts_amount);
			$this->data['parts_total_amount'] = number_format($parts_amount, 2);

			
	    $pdf = PDF::loadView('pdf-gigo/insurance-estimate-pdf',$this->data);

		return $pdf->stream('insurance-estimate-pdf');
	}

	public function RevisedEstimate($id) {

		$this->data['revised_estimate'] = $job_order = JobCard::with([
			'gatePasses',
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
		    'jobOrder.serviceAdviser',
		    'jobOrder.roadTestPreferedBy.employee',
		    'jobOrder.jobOrderParts.part',
			'jobOrder.jobOrderParts.part.taxCode',
			'jobOrder.jobOrderParts.part.taxCode.taxes',])
			->select([
				'job_cards.*',
				DB::raw('DATE_FORMAT(job_cards.created_at,"%d/%m/%Y") as jobdate'),
				DB::raw('DATE_FORMAT(job_cards.created_at,"%h:%i %p") as time'),
			])
			->find($id);

			//dd($this->data['gate_pass']->jobOrder->vehicle);

		    $parts_amount = 0;
			$labour_amount = 0;
			$total_amount = 0;

			//Check which tax applicable for customer
			if ($job_order->jobOrder->outlet->state_id == $job_order->jobOrder->vehicle->currentOwner->customer->primaryAddress->state_id) {
				$tax_type = 1160; //Within State
			} else {
				$tax_type = 1161; //Inter State
			}

			//Count Tax Type
			$taxes = Tax::get();

			$labour_details = array();
			if ($job_order->jobOrder->jobOrderRepairOrders) {
				$i = 1;
				$total_labour_qty = 0;
				$total_labour_mrp = 0;
				$total_labour_price = 0;
				$total_labour_tax = 0;
				foreach ($job_order->jobOrder->jobOrderRepairOrders as $key => $labour) {
					$total_amount = 0;
					$labour_details[$key]['sno'] = $i;
					$labour_details[$key]['code'] = $labour->repairOrder->code;
					$labour_details[$key]['hsn_code'] = $labour->repairOrder->taxCode ? $labour->repairOrder->taxCode->code : '-';
					$labour_details[$key]['qty'] = $labour->qty;
					$labour_details[$key]['amount'] = $labour->amount;
					$labour_details[$key]['rate'] = $labour->repairOrder->amount;
					$labour_details[$key]['is_free_service'] = $labour->is_free_service;
					$tax_amount = 0;
					$tax_values = array();
					if ($labour->repairOrder->taxCode) {
						foreach ($labour->repairOrder->taxCode->taxes as $tax_key => $value) {
							$percentage_value = 0;
							if ($value->type_id == $tax_type) {
								$percentage_value = ($labour->amount * $value->pivot->percentage) / 100;
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

					$total_labour_qty += $labour->qty;
					$total_labour_mrp += $labour->amount;
					$total_labour_price += $labour->repairOrder->amount;
					$total_labour_tax += $tax_amount;

					$labour_details[$key]['tax_values'] = $tax_values;
					$labour_details[$key]['tax_amount'] = $tax_amount;
					$total_amount = $tax_amount + $labour->amount;
					$total_amount = number_format((float) $total_amount, 2, '.', '');
					if ($labour->is_free_service != 1) {
						$labour_amount += $total_amount;
					}
					$labour_details[$key]['total_amount'] = $total_amount;
					$i++;
				}
			}

			$part_details = array();
			if ($job_order->jobOrder->jobOrderParts) {
				$i = 1;
				$total_parts_qty = 0;
				$total_parts_mrp = 0;
				$total_parts_price = 0;
				$total_parts_tax = 0;
				foreach ($job_order->jobOrder->jobOrderParts as $key => $parts) {
					$total_amount = 0;
					$part_details[$key]['sno'] = $i;
					$part_details[$key]['code'] = $parts->part->code;
					$part_details[$key]['hsn_code'] = $parts->part->taxCode ? $parts->part->taxCode->code : '-';
					$part_details[$key]['qty'] = $parts->qty;
					$part_details[$key]['rate'] = $parts->rate;
					$part_details[$key]['amount'] = $parts->amount;
					$part_details[$key]['is_free_service'] = $parts->is_free_service;
					$tax_amount = 0;
					$tax_values = array();
					if ($parts->part->taxCode) {
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

					$total_parts_qty += $parts->qty;
					$total_parts_mrp += $parts->rate;
					$total_parts_price += $parts->amount;
					$total_parts_tax += $tax_amount;

					$part_details[$key]['tax_values'] = $tax_values;
					$part_details[$key]['tax_amount'] = $tax_amount;
					$total_amount = $tax_amount + $parts->amount;
					$total_amount = number_format((float) $total_amount, 2, '.', '');
					if ($parts->is_free_service != 1) {
						$parts_amount += $total_amount;
					}
					$part_details[$key]['total_amount'] = $total_amount;
					$i++;
				}
			}

			$total_amount = $parts_amount + $labour_amount;
			$this->data['taxes'] = $taxes;
			$this->data['part_details'] = $part_details;
			$this->data['labour_details'] = $labour_details;
			$this->data['total_labour_qty'] = $total_labour_qty;
			$this->data['total_labour_mrp'] = $total_labour_mrp;
			$this->data['total_labour_price'] = $total_labour_price;
			$this->data['total_labour_tax'] = $total_labour_tax;

			$this->data['total_parts_qty'] = $total_parts_qty;
			$this->data['total_parts_mrp'] = $total_parts_mrp;
			$this->data['total_parts_price'] = $total_parts_price;
			$this->data['total_parts_tax'] = $total_parts_tax;
			$this->data['parts_total_amount'] = number_format($parts_amount, 2);
			$this->data['labour_total_amount'] = number_format($labour_amount, 2);
            $this->data['round_total_amount'] = round($total_amount);
			$this->data['total_amount'] = number_format($total_amount, 2);

			
	    $pdf = PDF::loadView('pdf-gigo/revised-estimate-pdf',$this->data);

		return $pdf->stream('revised-estimate-pdf');
	}

	public function JobCardPDF($id) {

		$this->data['job_card'] = $job_order = JobCard::with([
			'gatePasses',
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
		    'jobOrder.serviceAdviser',
		    'jobOrder.roadTestPreferedBy.employee',
		    'jobOrder.jobOrderParts.part',
			'jobOrder.jobOrderParts.part.taxCode',
			'jobOrder.jobOrderParts.part.taxCode.taxes',])
			->select([
				'job_cards.*',
				DB::raw('DATE_FORMAT(job_cards.created_at,"%d/%m/%Y") as jobdate'),
				DB::raw('DATE_FORMAT(job_cards.created_at,"%h:%i %p") as time'),
			])
			->find($id);

			//dd($this->data['gate_pass']);

		    $parts_amount = 0;
			$labour_amount = 0;
			$total_amount = 0;

			//Check which tax applicable for customer
			if ($job_order->jobOrder->outlet->state_id == $job_order->jobOrder->vehicle->currentOwner->customer->primaryAddress->state_id) {
				$tax_type = 1160; //Within State
			} else {
				$tax_type = 1161; //Inter State
			}

			//Count Tax Type
			$taxes = Tax::get();

			$labour_details = array();
			if ($job_order->jobOrder->jobOrderRepairOrders) {
				$i = 1;
				$total_labour_qty = 0;
				$total_labour_mrp = 0;
				$total_labour_price = 0;
				$total_labour_tax = 0;
				foreach ($job_order->jobOrder->jobOrderRepairOrders as $key => $labour) {
					$total_amount = 0;
					$labour_details[$key]['sno'] = $i;
					$labour_details[$key]['code'] = $labour->repairOrder->code;
					$labour_details[$key]['hsn_code'] = $labour->repairOrder->taxCode ? $labour->repairOrder->taxCode->code : '-';
					$labour_details[$key]['qty'] = $labour->qty;
					$labour_details[$key]['amount'] = $labour->amount;
					$labour_details[$key]['rate'] = $labour->repairOrder->amount;
					$labour_details[$key]['is_free_service'] = $labour->is_free_service;
					$tax_amount = 0;
					$tax_values = array();
					if ($labour->repairOrder->taxCode) {
						foreach ($labour->repairOrder->taxCode->taxes as $tax_key => $value) {
							$percentage_value = 0;
							if ($value->type_id == $tax_type) {
								$percentage_value = ($labour->amount * $value->pivot->percentage) / 100;
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

					$total_labour_qty += $labour->qty;
					$total_labour_mrp += $labour->amount;
					$total_labour_price += $labour->repairOrder->amount;
					$total_labour_tax += $tax_amount;

					$labour_details[$key]['tax_values'] = $tax_values;
					$labour_details[$key]['tax_amount'] = $tax_amount;
					$total_amount = $tax_amount + $labour->amount;
					$total_amount = number_format((float) $total_amount, 2, '.', '');
					if ($labour->is_free_service != 1) {
						$labour_amount += $total_amount;
					}
					$labour_details[$key]['total_amount'] = $total_amount;
					$i++;
				}
			}

			$part_details = array();
			if ($job_order->jobOrder->jobOrderParts) {
				$i = 1;
				$total_parts_qty = 0;
				$total_parts_mrp = 0;
				$total_parts_price = 0;
				$total_parts_tax = 0;
				foreach ($job_order->jobOrder->jobOrderParts as $key => $parts) {
					$total_amount = 0;
					$part_details[$key]['sno'] = $i;
					$part_details[$key]['code'] = $parts->part->code;
					$part_details[$key]['hsn_code'] = $parts->part->taxCode ? $parts->part->taxCode->code : '-';
					$part_details[$key]['qty'] = $parts->qty;
					$part_details[$key]['rate'] = $parts->rate;
					$part_details[$key]['amount'] = $parts->amount;
					$part_details[$key]['is_free_service'] = $parts->is_free_service;
					$tax_amount = 0;
					$tax_values = array();
					if ($parts->part->taxCode) {
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

					$total_parts_qty += $parts->qty;
					$total_parts_mrp += $parts->rate;
					$total_parts_price += $parts->amount;
					$total_parts_tax += $tax_amount;

					$part_details[$key]['tax_values'] = $tax_values;
					$part_details[$key]['tax_amount'] = $tax_amount;
					$total_amount = $tax_amount + $parts->amount;
					$total_amount = number_format((float) $total_amount, 2, '.', '');
					if ($parts->is_free_service != 1) {
						$parts_amount += $total_amount;
					}
					$part_details[$key]['total_amount'] = $total_amount;
					$i++;
				}
			}

			$total_amount = $parts_amount + $labour_amount;
			$this->data['taxes'] = $taxes;
			$this->data['part_details'] = $part_details;
			$this->data['labour_details'] = $labour_details;
			$this->data['total_labour_qty'] = $total_labour_qty;
			$this->data['total_labour_mrp'] = $total_labour_mrp;
			$this->data['total_labour_price'] = $total_labour_price;
			$this->data['total_labour_tax'] = $total_labour_tax;

			$this->data['total_parts_qty'] = $total_parts_qty;
			$this->data['total_parts_mrp'] = $total_parts_mrp;
			$this->data['total_parts_price'] = $total_parts_price;
			$this->data['total_parts_tax'] = $total_parts_tax;
			$this->data['parts_total_amount'] = number_format($parts_amount, 2);
			$this->data['labour_total_amount'] = number_format($labour_amount, 2);
            $this->data['round_total_amount'] = round($total_amount);
			$this->data['total_amount'] = number_format($total_amount, 2);

			
	    $pdf = PDF::loadView('pdf-gigo/job-card-pdf',$this->data);

		return $pdf->stream('job-card-pdf');
	}

	public function JobCardrequisitionPDF($id) {

		$this->data['job_card_requisition'] = $job_order = JobCard::with([
			'gatePasses',
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
		    'jobOrder.serviceAdviser',
		    'jobOrder.roadTestPreferedBy.employee',
		    'jobOrder.jobOrderParts.part',
			'jobOrder.jobOrderParts.part.taxCode',
			'jobOrder.jobOrderParts.part.taxCode.taxes',])
			->select([
				'job_cards.*',
				DB::raw('DATE_FORMAT(job_cards.created_at,"%d/%m/%Y") as jobdate'),
				DB::raw('DATE_FORMAT(job_cards.created_at,"%h:%i %p") as time'),
			])
			->find($id);

			//dd($this->data['gate_pass']);

		   
	    $pdf = PDF::loadView('pdf-gigo/job-card-spare-requistion',$this->data);

		return $pdf->stream('job-card-spare-requistion');
	}

	public function WorkorderOutwardPDF($id) {

		$this->data['work_order_outward'] = $job_order = JobCard::with([
			'gatePasses',
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
		    'jobOrder.serviceAdviser',
		    'jobOrder.roadTestPreferedBy.employee',
		    'jobOrder.jobOrderParts.part',
			'jobOrder.jobOrderParts.part.taxCode',
			'jobOrder.jobOrderParts.part.taxCode.taxes',])
			->select([
				'job_cards.*',
				DB::raw('DATE_FORMAT(job_cards.created_at,"%d/%m/%Y") as jobdate'),
				DB::raw('DATE_FORMAT(job_cards.created_at,"%h:%i %p") as time'),
			])
			->find($id);

			//dd($this->data['gate_pass']);

		   
	    $pdf = PDF::loadView('pdf-gigo/work-order-outward-pdf',$this->data);

		return $pdf->stream('work-order-outward-pdf');
	}

	public function WorkorderInwardPDF($id) {

		$this->data['work_order_inward'] = $job_order = JobCard::with([
			'gatePasses',
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
		    'jobOrder.serviceAdviser',
		    'jobOrder.roadTestPreferedBy.employee',
		    'jobOrder.jobOrderParts.part',
			'jobOrder.jobOrderParts.part.taxCode',
			'jobOrder.jobOrderParts.part.taxCode.taxes',])
			->select([
				'job_cards.*',
				DB::raw('DATE_FORMAT(job_cards.created_at,"%d/%m/%Y") as jobdate'),
				DB::raw('DATE_FORMAT(job_cards.created_at,"%h:%i %p") as time'),
			])
			->find($id);

			//dd($this->data['gate_pass']);

		   
	    $pdf = PDF::loadView('pdf-gigo/work-order-inward-pdf',$this->data);

		return $pdf->stream('work-order-inward-pdf');
	}

}
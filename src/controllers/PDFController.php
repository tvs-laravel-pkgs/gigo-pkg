<?php

namespace Abs\GigoPkg;
use Abs\GigoPkg\JobCard;
use Abs\GigoPkg\JobOrder;
use Abs\TaxPkg\Tax;
use App\Http\Controllers\Controller;
use App\JobOrderEstimate;
use App\SplitOrderType;
use DB;
use PDF;

class PDFController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	public function gatePass($id) {

		$this->data['gate_pass'] = JobCard::with([
			'gatePasses',
			'company',
			'jobOrder',
			'jobOrder.type',
			'jobOrder.quoteType',
			'jobOrder.serviceType',
			'jobOrder.vehicle',
			'jobOrder.vehicle.model',
			'jobOrder.vehicle.status',
			'jobOrder.outlet',
			'jobOrder.gateLog',
			'jobOrder.vehicle.currentOwner.customer',
			'jobOrder.vehicle.currentOwner.customer.primaryAddress',
			'jobOrder.vehicle.currentOwner.customer.primaryAddress.country',
			'jobOrder.vehicle.currentOwner.customer.primaryAddress.state',
			'jobOrder.vehicle.currentOwner.customer.primaryAddress.city',
			'jobOrder.jobOrderRepairOrders.repairOrder',
			'jobOrder.jobOrderRepairOrders.repairOrder.repairOrderType',
			'jobOrder.floorAdviser',
			// 'jobOrder.floorAdviser.user',
			'jobOrder.serviceAdviser',
			// 'jobOrder.serviceAdviser.user',
		])
			->select([
				'job_cards.*',
				DB::raw('DATE_FORMAT(job_cards.created_at,"%d-%m-%Y") as jobdate'),
				DB::raw('DATE_FORMAT(job_cards.created_at,"%h:%i %p") as time'),
			])
			->find($id);

		$params['field_type_id'] = [11, 12];
		$this->data['extras'] = [
			'inventory_type_list' => VehicleInventoryItem::getInventoryList($this->data['gate_pass']->jobOrder->id, $params),
		];

		// dd($this->data['gate_pass']);

		$pdf = PDF::loadView('pdf-gigo/gate-pass-pdf', $this->data);

		return $pdf->stream('gate-pass.pdf');
	}

	public function coveringletter($id) {
		$this->data['covering_letter'] = JobCard::with([
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
			->find($id);

		$gigo_invoice = [];
		if (isset($this->data['covering_letter']->gigoInvoices)) {
			foreach ($this->data['covering_letter']->gigoInvoices as $key => $gigoInvoice) {
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

		$this->data['gigo_invoices'] = $gigo_invoice;

		$pdf = PDF::loadView('pdf-gigo/covering-letter-pdf', $this->data);

		return $pdf->stream('covering-letter.pdf');
	}

	public function estimate($id) {
		$estimate_order = JobOrderEstimate::select('job_order_estimates.id')->join('job_orders', 'job_order_estimates.job_order_id', 'job_orders.id')->join('job_cards', 'job_cards.job_order_id', 'job_orders.id')->where('job_cards.id', $id)->orderBy('job_order_estimates.id', 'ASC')->first();

		$this->data['estimate'] = $job_card = JobCard::with([
			'gatePasses',
			'jobOrder',
			'jobOrder.type',
			'jobOrder.vehicle',
			'jobOrder.vehicle.model',
			'jobOrder.vehicle.status',
			'jobOrder.outlet',
			'jobOrder.gateLog',
			'jobOrder.vehicle.currentOwner.customer',
			'jobOrder.vehicle.currentOwner.customer.primaryAddress',
			'jobOrder.vehicle.currentOwner.customer.primaryAddress.country',
			'jobOrder.vehicle.currentOwner.customer.primaryAddress.state',
			'jobOrder.vehicle.currentOwner.customer.primaryAddress.city',
			'jobOrder.serviceType',
			'jobOrder.jobOrderRepairOrders' => function ($q) use ($estimate_order) {
				$q->where('estimate_order_id', $estimate_order->id);
			},
			'jobOrder.jobOrderRepairOrders.repairOrder',
			'jobOrder.jobOrderRepairOrders.repairOrder.repairOrderType',
			'jobOrder.floorAdviser',
			'jobOrder.serviceAdviser',
			'jobOrder.roadTestPreferedBy.employee',
			'jobOrder.jobOrderParts' => function ($q) use ($estimate_order) {
				$q->where('estimate_order_id', $estimate_order->id);
			},
			'jobOrder.jobOrderParts.part',
			'jobOrder.jobOrderParts.part.taxCode',
			'jobOrder.jobOrderParts.part.taxCode.taxes'])
			->select([
				'job_cards.*',
				DB::raw('DATE_FORMAT(job_cards.created_at,"%d-%m-%Y") as jobdate'),
				DB::raw('DATE_FORMAT(job_cards.created_at,"%h:%i %p") as time'),
			])
			->find($id);

		$parts_amount = 0;
		$labour_amount = 0;
		$total_amount = 0;

		//Check which tax applicable for customer
		if ($job_card->jobOrder->outlet->state_id == $job_card->jobOrder->vehicle->currentOwner->customer->primaryAddress->state_id) {
			$tax_type = 1160; //Within State
		} else {
			$tax_type = 1161; //Inter State
		}

		$customer_paid_type_id = SplitOrderType::where('paid_by_id', '10013')->pluck('id')->toArray();

		//Count Tax Type
		$taxes = Tax::get();

		//GET SEPERATE TAXEX
		$seperate_tax = array();
		for ($i = 0; $i < count($taxes); $i++) {
			$seperate_tax[$i] = 0.00;
		}

		$tax_percentage = 0;
		$labour_details = array();
		if ($job_card->jobOrder->jobOrderRepairOrders) {
			$i = 1;
			$total_labour_qty = 0;
			$total_labour_mrp = 0;
			$total_labour_price = 0;
			$total_labour_tax = 0;
			foreach ($job_card->jobOrder->jobOrderRepairOrders as $key => $labour) {
				if (in_array($labour->split_order_type_id, $customer_paid_type_id) || !$labour->split_order_type_id) {
					if ($labour->is_free_service != 1 && $labour->removal_reason_id == null) {
						$total_amount = 0;
						$labour_details[$key]['sno'] = $i;
						$labour_details[$key]['code'] = $labour->repairOrder->code;
						$labour_details[$key]['name'] = $labour->repairOrder->name;
						$labour_details[$key]['hsn_code'] = $labour->repairOrder->taxCode ? $labour->repairOrder->taxCode->code : '-';
						$labour_details[$key]['qty'] = $labour->qty;
						$labour_details[$key]['amount'] = $labour->amount;
						$labour_details[$key]['rate'] = $labour->repairOrder->amount;
						$labour_details[$key]['is_free_service'] = $labour->is_free_service;
						$tax_amount = 0;
						$tax_percentage = 0;
						$labour_total_cgst = 0;
						$labour_total_sgst = 0;
						$labour_total_igst = 0;
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
						$labour_total_sgst += $labour_total_sgst;
						$labour_total_igst += $labour_total_igst;
						$total_labour_qty += $labour->qty;
						$total_labour_mrp += $labour->amount;
						$total_labour_price += $labour->repairOrder->amount;
						$total_labour_tax += $tax_amount;

						$labour_details[$key]['tax_values'] = $tax_values;
						$labour_details[$key]['tax_amount'] = $tax_amount;
						$total_amount = $tax_amount + $labour->amount;
						$total_amount = number_format((float) $total_amount, 2, '.', '');

						$labour_details[$key]['total_amount'] = $total_amount;
						// if ($labour->is_free_service != 1) {
						$labour_amount += $total_amount;
						// }
					}
				}
				$i++;
			}
		}

		$part_details = array();
		if ($job_card->jobOrder->jobOrderParts) {
			$i = 1;
			$total_parts_qty = 0;
			$total_parts_mrp = 0;
			$total_parts_price = 0;
			$total_parts_tax = 0;
			foreach ($job_card->jobOrder->jobOrderParts as $key => $parts) {
				if (in_array($parts->split_order_type_id, $customer_paid_type_id) || !$parts->split_order_type_id) {
					if ($parts->is_free_service != 1 && $parts->removal_reason_id == null) {
						$total_amount = 0;
						$part_details[$key]['sno'] = $i;
						$part_details[$key]['code'] = $parts->part->code;
						$part_details[$key]['name'] = $parts->part->name;
						$part_details[$key]['hsn_code'] = $parts->part->taxCode ? $parts->part->taxCode->code : '-';
						$part_details[$key]['qty'] = $parts->qty;
						$part_details[$key]['rate'] = $parts->rate;
						$part_details[$key]['amount'] = $parts->amount;
						$part_details[$key]['is_free_service'] = $parts->is_free_service;
						$tax_amount = 0;
						$tax_percentage = 0;
						$tax_values = array();
						if ($parts->part->taxCode) {
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
					}
				}
				$i++;
			}
		}

		foreach ($seperate_tax as $key => $s_tax) {
			$seperate_tax[$key] = convert_number_to_words($s_tax);
		}
		$this->data['seperate_taxes'] = $seperate_tax;

		$total_taxable_amount = $total_labour_tax + $total_parts_tax;
		$this->data['tax_percentage'] = convert_number_to_words($tax_percentage);
		$this->data['total_taxable_amount'] = convert_number_to_words($total_taxable_amount);

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
		//FOR ROUND OFF
		if ($total_amount <= round($total_amount)) {
			$round_off = round($total_amount) - $total_amount;
		} else {
			$round_off = round($total_amount) - $total_amount;
		}
		// dd(number_format($round_off));
		$this->data['round_total_amount'] = number_format($round_off, 2);
		$this->data['total_amount'] = number_format(round($total_amount), 2);

		$pdf = PDF::loadView('pdf-gigo/estimate-pdf', $this->data);

		return $pdf->stream('estimate.pdf');
	}

	public function InsuranceEstimate($id) {

		$this->data['insurance_estimate'] = $job_card = JobCard::with([
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
			'jobOrder.jobOrderParts.part.taxCode.taxes'])
			->select([
				'job_cards.*',
				DB::raw('DATE_FORMAT(job_cards.created_at,"%d-%m-%Y") as jobdate'),
				DB::raw('DATE_FORMAT(job_cards.created_at,"%h:%i %p") as time'),
			])
			->find($id);

		//dd($this->data['gate_pass']);

		$parts_amount = 0;
		$labour_amount = 0;
		$total_amount = 0;
		$tax_percentage = 0;

		//Check which tax applicable for customer
		if ($job_card->jobOrder->outlet->state_id == $job_card->jobOrder->vehicle->currentOwner->customer->primaryAddress->state_id) {
			$tax_type = 1160; //Within State
		} else {
			$tax_type = 1161; //Inter State
		}

		$customer_paid_type_id = SplitOrderType::where('paid_by_id', '10013')->pluck('id')->toArray();

		//Count Tax Type
		$taxes = Tax::get();

		//GET SEPERATE TAXEX
		$seperate_tax = array();
		for ($i = 0; $i < count($taxes); $i++) {
			$seperate_tax[$i] = 0.00;
		}

		$tax_percentage = 0;
		$labour_details = array();
		if ($job_card->jobOrder->jobOrderRepairOrders) {
			$i = 1;
			$total_labour_qty = 0;
			$total_labour_mrp = 0;
			$total_labour_price = 0;
			$total_labour_tax = 0;
			foreach ($job_card->jobOrder->jobOrderRepairOrders as $key => $labour) {
				// if (in_array($labour->split_order_type_id, $customer_paid_type_id)) {
				if ($labour->is_free_service != 1) {
					$total_amount = 0;
					$labour_details[$key]['sno'] = $i;
					$labour_details[$key]['code'] = $labour->repairOrder->code;
					$labour_details[$key]['name'] = $labour->repairOrder->name;
					$labour_details[$key]['hsn_code'] = $labour->repairOrder->taxCode ? $labour->repairOrder->taxCode->code : '-';
					$labour_details[$key]['qty'] = $labour->qty;
					$labour_details[$key]['amount'] = $labour->amount;
					$labour_details[$key]['rate'] = $labour->repairOrder->amount;
					$labour_details[$key]['is_free_service'] = $labour->is_free_service;
					$tax_amount = 0;
					// $tax_percentage = 0;
					$labour_total_cgst = 0;
					$labour_total_sgst = 0;
					$labour_total_igst = 0;
					$tax_values = array();
					if ($labour->repairOrder->taxCode) {
						foreach ($labour->repairOrder->taxCode->taxes as $tax_key => $value) {
							$percentage_value = 0;
							if ($value->type_id == $tax_type) {
								// $tax_percentage += $value->pivot->percentage;
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
					$labour_total_sgst += $labour_total_sgst;
					$labour_total_igst += $labour_total_igst;
					$total_labour_qty += $labour->qty;
					$total_labour_mrp += $labour->amount;
					$total_labour_price += $labour->repairOrder->amount;
					$total_labour_tax += $tax_amount;

					$labour_details[$key]['tax_values'] = $tax_values;
					$labour_details[$key]['tax_amount'] = $tax_amount;
					$total_amount = $tax_amount + $labour->amount;
					$total_amount = number_format((float) $total_amount, 2, '.', '');

					$labour_details[$key]['total_amount'] = $total_amount;
					// if ($labour->is_free_service != 1) {
					$labour_amount += $total_amount;
					// }
				}
				// }
				$i++;
			}
		}

		$part_details = array();
		if ($job_card->jobOrder->jobOrderParts) {
			$i = 1;
			$total_parts_qty = 0;
			$total_parts_mrp = 0;
			$total_parts_price = 0;
			$total_parts_tax = 0;
			foreach ($job_card->jobOrder->jobOrderParts as $key => $parts) {
				// if (in_array($parts->split_order_type_id, $customer_paid_type_id)) {
				if ($parts->is_free_service != 1) {
					$total_amount = 0;
					$part_details[$key]['sno'] = $i;
					$part_details[$key]['code'] = $parts->part->code;
					$part_details[$key]['name'] = $parts->part->name;
					$part_details[$key]['hsn_code'] = $parts->part->taxCode ? $parts->part->taxCode->code : '-';
					$part_details[$key]['qty'] = $parts->qty;
					$part_details[$key]['rate'] = $parts->rate;
					$part_details[$key]['amount'] = $parts->amount;
					$part_details[$key]['is_free_service'] = $parts->is_free_service;
					$tax_amount = 0;
					// $tax_percentage = 0;
					$tax_values = array();
					if ($parts->part->taxCode) {
						foreach ($parts->part->taxCode->taxes as $tax_key => $value) {
							$percentage_value = 0;
							if ($value->type_id == $tax_type) {
								// $tax_percentage += $value->pivot->percentage;
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
				}
				// }
				$i++;
			}
		}

		foreach ($seperate_tax as $key => $s_tax) {
			$seperate_tax[$key] = convert_number_to_words($s_tax);
		}
		$this->data['seperate_taxes'] = $seperate_tax;

		$total_taxable_amount = $total_labour_tax + $total_parts_tax;
		$this->data['tax_percentage'] = convert_number_to_words($tax_percentage);
		$this->data['total_taxable_amount'] = convert_number_to_words($total_taxable_amount);

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

		//FOR ROUND OFF
		if ($total_amount <= round($total_amount)) {
			$round_off = round($total_amount) - $total_amount;
		} else {
			$round_off = round($total_amount) - $total_amount;
		}
		// dd(number_format($round_off));
		$this->data['round_total_amount'] = number_format($round_off, 2);
		$this->data['total_amount'] = number_format(round($total_amount), 2);

		// $this->data['parts_round_total_amount'] = round($parts_amount);
		// $this->data['parts_total_amount'] = number_format($parts_amount, 2);

		$pdf = PDF::loadView('pdf-gigo/insurance-estimate-pdf', $this->data);

		return $pdf->stream('insurance-estimate.pdf');
	}

	public function RevisedEstimate($id) {

		$this->data['revised_estimate'] = $job_card = JobCard::with([
			'gatePasses',
			'jobOrder',
			'jobOrder.type',
			'jobOrder.vehicle',
			'jobOrder.vehicle.model',
			'jobOrder.vehicle.status',
			'jobOrder.outlet',
			'jobOrder.gateLog',
			'jobOrder.vehicle.currentOwner.customer',
			'jobOrder.vehicle.currentOwner.customer.primaryAddress',
			'jobOrder.vehicle.currentOwner.customer.primaryAddress.country',
			'jobOrder.vehicle.currentOwner.customer.primaryAddress.state',
			'jobOrder.vehicle.currentOwner.customer.primaryAddress.city',
			'jobOrder.serviceType',
			'jobOrder.jobOrderRepairOrders',
			'jobOrder.jobOrderRepairOrders.repairOrder',
			'jobOrder.jobOrderRepairOrders.repairOrder.repairOrderType',
			'jobOrder.floorAdviser',
			'jobOrder.serviceAdviser',
			'jobOrder.roadTestPreferedBy.employee',
			'jobOrder.jobOrderParts',
			'jobOrder.jobOrderParts.part',
			'jobOrder.jobOrderParts.part.taxCode',
			'jobOrder.jobOrderParts.part.taxCode.taxes'])
			->select([
				'job_cards.*',
				DB::raw('DATE_FORMAT(job_cards.created_at,"%d-%m-%Y") as jobdate'),
				DB::raw('DATE_FORMAT(job_cards.created_at,"%h:%i %p") as time'),
			])
			->find($id);

		$parts_amount = 0;
		$labour_amount = 0;
		$total_amount = 0;

		//Check which tax applicable for customer
		if ($job_card->jobOrder->outlet->state_id == $job_card->jobOrder->vehicle->currentOwner->customer->primaryAddress->state_id) {
			$tax_type = 1160; //Within State
		} else {
			$tax_type = 1161; //Inter State
		}

		$customer_paid_type_id = SplitOrderType::where('paid_by_id', '10013')->pluck('id')->toArray();

		//Count Tax Type
		$taxes = Tax::get();

		//GET SEPERATE TAXEX
		$seperate_tax = array();
		for ($i = 0; $i < count($taxes); $i++) {
			$seperate_tax[$i] = 0.00;
		}

		$tax_percentage = 0;
		$labour_details = array();
		if ($job_card->jobOrder->jobOrderRepairOrders) {
			$i = 1;
			$total_labour_qty = 0;
			$total_labour_mrp = 0;
			$total_labour_price = 0;
			$total_labour_tax = 0;
			foreach ($job_card->jobOrder->jobOrderRepairOrders as $key => $labour) {
				// if (in_array($labour->split_order_type_id, $customer_paid_type_id)) {
				if ($labour->is_free_service != 1) {
					$total_amount = 0;
					$labour_details[$key]['sno'] = $i;
					$labour_details[$key]['code'] = $labour->repairOrder->code;
					$labour_details[$key]['name'] = $labour->repairOrder->name;
					$labour_details[$key]['hsn_code'] = $labour->repairOrder->taxCode ? $labour->repairOrder->taxCode->code : '-';
					$labour_details[$key]['qty'] = $labour->qty;
					$labour_details[$key]['amount'] = $labour->amount;
					$labour_details[$key]['rate'] = $labour->repairOrder->amount;
					$labour_details[$key]['is_free_service'] = $labour->is_free_service;
					$tax_amount = 0;
					// $tax_percentage = 0;
					$labour_total_cgst = 0;
					$labour_total_sgst = 0;
					$labour_total_igst = 0;
					$tax_values = array();
					if ($labour->repairOrder->taxCode) {
						foreach ($labour->repairOrder->taxCode->taxes as $tax_key => $value) {
							$percentage_value = 0;
							if ($value->type_id == $tax_type) {
								// $tax_percentage += $value->pivot->percentage;
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
					$labour_total_sgst += $labour_total_sgst;
					$labour_total_igst += $labour_total_igst;
					$total_labour_qty += $labour->qty;
					$total_labour_mrp += $labour->amount;
					$total_labour_price += $labour->repairOrder->amount;
					$total_labour_tax += $tax_amount;

					$labour_details[$key]['tax_values'] = $tax_values;
					$labour_details[$key]['tax_amount'] = $tax_amount;
					$total_amount = $tax_amount + $labour->amount;
					$total_amount = number_format((float) $total_amount, 2, '.', '');

					$labour_details[$key]['total_amount'] = $total_amount;
					// if ($labour->is_free_service != 1) {
					$labour_amount += $total_amount;
					// }
				}
				// }
				$i++;
			}
		}

		$part_details = array();
		if ($job_card->jobOrder->jobOrderParts) {
			$i = 1;
			$total_parts_qty = 0;
			$total_parts_mrp = 0;
			$total_parts_price = 0;
			$total_parts_tax = 0;
			foreach ($job_card->jobOrder->jobOrderParts as $key => $parts) {
				// if (in_array($parts->split_order_type_id, $customer_paid_type_id)) {
				if ($parts->is_free_service != 1) {
					$total_amount = 0;
					$part_details[$key]['sno'] = $i;
					$part_details[$key]['code'] = $parts->part->code;
					$part_details[$key]['name'] = $parts->part->name;
					$part_details[$key]['hsn_code'] = $parts->part->taxCode ? $parts->part->taxCode->code : '-';
					$part_details[$key]['qty'] = $parts->qty;
					$part_details[$key]['rate'] = $parts->rate;
					$part_details[$key]['amount'] = $parts->amount;
					$part_details[$key]['is_free_service'] = $parts->is_free_service;
					$tax_amount = 0;
					// $tax_percentage = 0;
					$tax_values = array();
					if ($parts->part->taxCode) {
						foreach ($parts->part->taxCode->taxes as $tax_key => $value) {
							$percentage_value = 0;
							if ($value->type_id == $tax_type) {
								// $tax_percentage += $value->pivot->percentage;
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
				}
				// }
				$i++;
			}
		}

		foreach ($seperate_tax as $key => $s_tax) {
			$seperate_tax[$key] = convert_number_to_words($s_tax);
		}
		$this->data['seperate_taxes'] = $seperate_tax;

		$total_taxable_amount = $total_labour_tax + $total_parts_tax;
		$this->data['tax_percentage'] = convert_number_to_words($tax_percentage);
		$this->data['total_taxable_amount'] = convert_number_to_words($total_taxable_amount);

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
		//FOR ROUND OFF
		if ($total_amount <= round($total_amount)) {
			$round_off = round($total_amount) - $total_amount;
		} else {
			$round_off = round($total_amount) - $total_amount;
		}
		// dd(number_format($round_off));
		$this->data['round_total_amount'] = number_format($round_off, 2);
		$this->data['total_amount'] = number_format(round($total_amount), 2);

		$pdf = PDF::loadView('pdf-gigo/revised-estimate-pdf', $this->data);

		return $pdf->stream('revised-estimate.pdf');
	}

	public function JobCardPDF($id) {

		$this->data['job_card'] = $job_card = JobCard::with([
			'gatePasses',
			'jobOrder',
			'jobOrder.type',
			'jobOrder.vehicle',
			'jobOrder.vehicle.model',
			'jobOrder.vehicle.status',
			'jobOrder.outlet',
			'jobOrder.gateLog',
			'jobOrder.customerVoices',
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
			'jobOrder.jobOrderParts.part.taxCode.taxes'])
			->select([
				'job_cards.*',
				DB::raw('DATE_FORMAT(job_cards.created_at,"%d-%m-%Y") as jobdate'),
				DB::raw('DATE_FORMAT(job_cards.created_at,"%h:%i %p") as time'),
			])
			->find($id);

		//dd($this->data['job_card']);

		$parts_amount = 0;
		$labour_amount = 0;
		$total_amount = 0;

		//Check which tax applicable for customer
		if ($job_card->jobOrder->outlet->state_id == $job_card->jobOrder->vehicle->currentOwner->customer->primaryAddress->state_id) {
			$tax_type = 1160; //Within State
		} else {
			$tax_type = 1161; //Inter State
		}

		//Count Tax Type
		$taxes = Tax::get();

		$labour_details = array();
		if ($job_card->jobOrder->jobOrderRepairOrders) {
			$i = 1;
			$total_labour_qty = 0;
			foreach ($job_card->jobOrder->jobOrderRepairOrders as $key => $labour) {
				$total_amount = 0;
				$labour_details[$key]['sno'] = $i;
				$labour_details[$key]['code'] = $labour->repairOrder->code;
				$labour_details[$key]['description'] = $labour->repairOrder->name ? $labour->repairOrder->name : '-';
				if ($job_card->jobOrder->customerVoices[$key]->repair_order_id == $labour->repair_order_id) {
					$labour_details[$key]['customer_voice'] = $job_card->jobOrder->customerVoices[$key]->name;
				} else {
					$labour_details[$key]['customer_voice'] = "-";
				}

				$labour_details[$key]['job_type'] = $labour->repairOrder->code;
				$labour_details[$key]['qty'] = $labour->qty;
				$total_labour_qty += $labour->qty;
				$i++;
			}
		}

		$part_details = array();
		if ($job_card->jobOrder->jobOrderParts) {
			$i = 1;
			$total_parts_qty = 0;

			foreach ($job_card->jobOrder->jobOrderParts as $key => $parts) {
				$total_amount = 0;
				$part_details[$key]['sno'] = $i;
				$part_details[$key]['code'] = $parts->part->code;
				$part_details[$key]['description'] = $parts->part->name ? $parts->part->name : '-';
				$part_details[$key]['customer_voice'] = "-";
				$part_details[$key]['job_type'] = "-";
				$part_details[$key]['qty'] = $parts->qty;
				$total_parts_qty += $parts->qty;
				$i++;
			}
		}

		$total_qty = $total_labour_qty + $total_parts_qty;

		$this->data['part_details'] = $part_details;
		$this->data['labour_details'] = $labour_details;
		$this->data['total_labour_qty'] = $total_labour_qty;
		$this->data['total_parts_qty'] = $total_parts_qty;
		$this->data['total_qty'] = $total_qty;

		$pdf = PDF::loadView('pdf-gigo/job-card-pdf', $this->data);

		return $pdf->stream('job-card.pdf');
	}

	public function JobCardrequisitionPDF($id) {

		$this->data['job_card_requisition'] = JobCard::with([
			'gatePasses',
			'jobOrder',
			'jobOrder.type',
			'jobOrder.vehicle',
			'jobOrder.vehicle.model',
			'jobOrder.vehicle.status',
			'jobOrder.outlet',
			'jobOrder.gateLog',
			'jobOrder.vehicle.currentOwner.customer',
			'jobOrder.vehicle.currentOwner.customer.primaryAddress',
			'jobOrder.vehicle.currentOwner.customer.primaryAddress.country',
			'jobOrder.vehicle.currentOwner.customer.primaryAddress.state',
			'jobOrder.vehicle.currentOwner.customer.primaryAddress.city',
			'jobOrder.serviceType',
			// 'jobOrder.jobOrderRepairOrders.repairOrder',
			// 'jobOrder.jobOrderRepairOrders.repairOrder.repairOrderType',
			'jobOrder.floorAdviser',
			'jobOrder.serviceAdviser',
			'jobOrder.roadTestPreferedBy.employee',
			'jobOrder.jobOrderParts.jobOrderIssuedParts',
			'jobOrder.jobOrderParts.jobOrderIssuedParts.jobOrderPart',
			'jobOrder.jobOrderParts.jobOrderIssuedParts.jobOrderPart.part',
			'jobOrder.jobOrderParts.jobOrderIssuedParts.jobOrderPart.part.uom',
			// 'jobOrder.jobOrderParts.part',
			// 'jobOrder.jobOrderParts.part.taxCode',
			// 'jobOrder.jobOrderParts.part.taxCode.taxes',
		])
			->select([
				'job_cards.*',
				DB::raw('DATE_FORMAT(job_cards.created_at,"%d-%m-%Y") as jobdate'),
				DB::raw('DATE_FORMAT(job_cards.created_at,"%h:%i %p") as time'),
			])
			->find($id);

		$pdf = PDF::loadView('pdf-gigo/job-card-spare-requistion', $this->data);

		return $pdf->stream('job-card-spare-requistion.pdf');
	}

	public function WorkorderOutwardPDF($id, $gate_pass_id) {

		$this->data['work_order_outward'] = $job_card = JobCard::with([
			'gatePasses',
			'jobOrder',
			'jobOrder.type',
			'jobOrder.vehicle',
			'jobOrder.vehicle.model',
			'jobOrder.vehicle.status',
			'jobOrder.outlet',
			'jobOrder.gateLog',
			'jobOrder.vehicle.currentOwner.customer',
			'jobOrder.vehicle.currentOwner.customer.primaryAddress',
			'jobOrder.vehicle.currentOwner.customer.primaryAddress.country',
			'jobOrder.vehicle.currentOwner.customer.primaryAddress.state',
			'jobOrder.vehicle.currentOwner.customer.primaryAddress.city',
			'jobOrder.serviceType',
			'jobOrder.jobOrderRepairOrders.repairOrder',
			'jobOrder.jobOrderRepairOrders.repairOrder.repairOrderType',
			'jobOrder.floorAdviser',
			'jobOrder.serviceAdviser',
			'jobOrder.roadTestPreferedBy.employee',
			'jobOrder.jobOrderParts.part',
			'jobOrder.jobOrderParts.part.taxCode',
			'jobOrder.jobOrderParts.part.taxCode.taxes'])
			->select([
				'job_cards.*',
				DB::raw('DATE_FORMAT(job_cards.created_at,"%d-%m-%Y") as jobdate'),
				DB::raw('DATE_FORMAT(job_cards.created_at,"%h:%i %p") as time'),
			])
			->find($id);

		$this->data['gate_pass'] = GatePass::with([
			'gatePassDetail',
			'gatePassDetail.vendor',
			'gatePassDetail.vendorType',
			'gatePassItems',
		])
			->find($gate_pass_id);

		// dd($this->data['work_order_outward']);

		$pdf = PDF::loadView('pdf-gigo/work-order-outward-pdf', $this->data);

		return $pdf->stream('work-order-outward.pdf');
	}

	public function WorkorderInwardPDF($id, $gate_pass_id) {

		$this->data['work_order_inward'] = $job_card = JobCard::with([
			'jobOrder',
			'jobOrder.type',
			'jobOrder.vehicle',
			'jobOrder.vehicle.model',
			'jobOrder.vehicle.status',
			'jobOrder.outlet',
			'jobOrder.gateLog',
			'jobOrder.vehicle.currentOwner.customer',
			'jobOrder.vehicle.currentOwner.customer.primaryAddress',
			'jobOrder.vehicle.currentOwner.customer.primaryAddress.country',
			'jobOrder.vehicle.currentOwner.customer.primaryAddress.state',
			'jobOrder.vehicle.currentOwner.customer.primaryAddress.city',
			'jobOrder.serviceType',
			'jobOrder.jobOrderRepairOrders.repairOrder',
			'jobOrder.jobOrderRepairOrders.repairOrder.repairOrderType',
			'jobOrder.floorAdviser',
			'jobOrder.serviceAdviser',
			'jobOrder.roadTestPreferedBy.employee',
			'jobOrder.jobOrderParts.part',
			'jobOrder.jobOrderParts.part.taxCode',
			'jobOrder.jobOrderParts.part.taxCode.taxes'])
			->select([
				'job_cards.*',
				DB::raw('DATE_FORMAT(job_cards.created_at,"%d-%m-%Y") as jobdate'),
				DB::raw('DATE_FORMAT(job_cards.created_at,"%h:%i %p") as time'),
			])
			->find($id);

		$this->data['gate_pass'] = GatePass::with([
			'gatePassDetail',
			'gatePassDetail.vendor',
			'gatePassDetail.vendorType',
			'gatePassItems',
		])
			->find($gate_pass_id);

		//dd($this->data['gate_pass']);

		$pdf = PDF::loadView('pdf-gigo/work-order-inward-pdf', $this->data);

		return $pdf->stream('work-order-inward.pdf');
	}

	public function WarrentyPickListPDF($id) {

		$this->data['warrenty_pick_list'] = $job_card = JobCard::with([
			'gatePasses',
			'jobOrder',
			'jobOrder.type',
			'jobOrder.vehicle',
			'jobOrder.vehicle.model',
			'jobOrder.vehicle.status',
			'jobOrder.outlet',
			'jobOrder.gateLog',
			'jobOrder.vehicle.currentOwner.customer',
			'jobOrder.vehicle.currentOwner.customer.primaryAddress',
			'jobOrder.vehicle.currentOwner.customer.primaryAddress.country',
			'jobOrder.vehicle.currentOwner.customer.primaryAddress.state',
			'jobOrder.vehicle.currentOwner.customer.primaryAddress.city',
			'jobOrder.serviceType',
			'jobOrder.jobOrderRepairOrders.repairOrder',
			'jobOrder.jobOrderRepairOrders.repairOrder.repairOrderType',
			'jobOrder.floorAdviser',
			'jobOrder.serviceAdviser',
			'jobOrder.roadTestPreferedBy.employee',
			'jobOrder.jobOrderParts.part',
			'jobOrder.jobOrderParts.part.taxCode',
			'jobOrder.jobOrderParts.part.taxCode.taxes'])
			->select([
				'job_cards.*',
				DB::raw('DATE_FORMAT(job_cards.created_at,"%d-%m-%Y") as jobdate'),
				DB::raw('DATE_FORMAT(job_cards.created_at,"%h:%i %p") as time'),
			])
			->find($id);

		//dd($this->data['gate_pass']);

		$pdf = PDF::loadView('pdf-gigo/warrenty-pick-list-pdf', $this->data);

		return $pdf->stream('warrenty-pick-list-pdf');
	}

	public function VehicleInwardPDF($id) {

		$this->data['vehicle_inward'] = $job_card = JobCard::with([
			'jobOrder',
			'jobOrder.type',
			'jobOrder.vehicle',
			'jobOrder.vehicle.model',
			'jobOrder.vehicle.status',
			'jobOrder.outlet',
			'jobOrder.gateLog',
			'jobOrder.gateLog.gatePass',
			'jobOrder.vehicle.currentOwner.customer',
			'jobOrder.vehicle.currentOwner.customer.primaryAddress',
			'jobOrder.vehicle.currentOwner.customer.primaryAddress.country',
			'jobOrder.vehicle.currentOwner.customer.primaryAddress.state',
			'jobOrder.vehicle.currentOwner.customer.primaryAddress.city',
			'jobOrder.vehicle.lastJobOrder',
			'jobOrder.serviceType',
			'jobOrder.jobOrderRepairOrders.repairOrder',
			'jobOrder.jobOrderRepairOrders.repairOrder.repairOrderType',
			'jobOrder.floorAdviser',
			'jobOrder.serviceAdviser',
			'jobOrder.roadTestPreferedBy.employee',
			'jobOrder.jobOrderParts.part',
			'jobOrder.jobOrderParts.part.taxCode',
			'jobOrder.jobOrderParts.part.taxCode.taxes'])
			->select([
				'job_cards.*',
				DB::raw('DATE_FORMAT(job_cards.created_at,"%d-%m-%Y") as jobdate'),
				DB::raw('DATE_FORMAT(job_cards.created_at,"%h:%i %p") as time'),
			])
			->find($id);

		// dd($job_card);

		//dd($this->data['vehicle_inward']->jobOrder->vehicle->lastJobOrder);

		$pdf = PDF::loadView('pdf-gigo/vehicle-inward-pdf', $this->data);

		return $pdf->stream('vehicle-inward.pdf');
	}

	public function VehicleInspectionPDF($id) {

		$this->data['vehicle_inspection'] = $job_card = JobCard::with([
			'gatePasses',
			'jobOrder',
			'jobOrder.type',
			'jobOrder.vehicle',
			'jobOrder.vehicle.model',
			'jobOrder.vehicle.status',
			'jobOrder.outlet',
			'jobOrder.gateLog',
			'jobOrder.vehicle.currentOwner.customer',
			'jobOrder.vehicle.currentOwner.customer.primaryAddress',
			'jobOrder.vehicle.currentOwner.customer.primaryAddress.country',
			'jobOrder.vehicle.currentOwner.customer.primaryAddress.state',
			'jobOrder.vehicle.currentOwner.customer.primaryAddress.city',
			'jobOrder.vehicle.lastJobOrder',
			'jobOrder.serviceType',
			'jobOrder.jobOrderRepairOrders.repairOrder',
			'jobOrder.jobOrderRepairOrders.repairOrder.repairOrderType',
			'jobOrder.floorAdviser',
			'jobOrder.serviceAdviser',
			'jobOrder.roadTestPreferedBy.employee',
			'jobOrder.jobOrderParts.part',
			'jobOrder.jobOrderParts.part.taxCode',
			'jobOrder.jobOrderParts.part.taxCode.taxes'])
			->select([
				'job_cards.*',
				DB::raw('DATE_FORMAT(job_cards.created_at,"%d-%m-%Y") as jobdate'),
				DB::raw('DATE_FORMAT(job_cards.created_at,"%h:%i %p") as time'),
			])
			->find($id);

		//dd($this->data['vehicle_inward']->jobOrder->vehicle->lastJobOrder);

		$pdf = PDF::loadView('pdf-gigo/vehicle-inspection-report-pdf', $this->data);

		return $pdf->stream('vehicle-inspection-report-pdf');
	}

	public function TaxInvoicePDF($id) {

		$split_order_type_ids = SplitOrderType::where('paid_by_id', '10013')->pluck('id')->toArray();

		$this->data['tax_invoice'] = $job_card = JobCard::with([
			'gatePasses',
			'jobOrder',
			'jobOrder.type',
			'jobOrder.vehicle',
			'jobOrder.vehicle.model',
			'jobOrder.vehicle.status',
			'jobOrder.outlet',
			'jobOrder.gateLog',
			'jobOrder.vehicle.currentOwner.customer',
			'jobOrder.vehicle.currentOwner.customer.primaryAddress',
			'jobOrder.vehicle.currentOwner.customer.primaryAddress.country',
			'jobOrder.vehicle.currentOwner.customer.primaryAddress.state',
			'jobOrder.vehicle.currentOwner.customer.primaryAddress.city',
			'jobOrder.vehicle.lastJobOrder',
			'jobOrder.serviceType',
			'jobOrder.jobOrderRepairOrders' => function ($query) use ($split_order_type_ids) {
				$query->where('job_order_repair_orders.split_order_type_id', $split_order_type_ids)->whereNull('removal_reason_id');
			},
			'jobOrder.jobOrderRepairOrders.repairOrder',
			'jobOrder.jobOrderRepairOrders.repairOrder.repairOrderType',
			'jobOrder.floorAdviser',
			'jobOrder.serviceAdviser',
			'jobOrder.roadTestPreferedBy.employee',
			'jobOrder.jobOrderParts.part',
			'jobOrder.jobOrderParts.part.taxCode',
			'jobOrder.jobOrderParts.part.taxCode.taxes'])
			->select([
				'job_cards.*',
				DB::raw('DATE_FORMAT(job_cards.created_at,"%d-%m-%Y") as jobdate'),
				DB::raw('DATE_FORMAT(job_cards.created_at,"%h:%i %p") as time'),
			])
			->find($id);

		//dd($this->data['vehicle_inward']->jobOrder->vehicle->lastJobOrder);

		$parts_amount = 0;
		$labour_amount = 0;
		$total_amount = 0;

		//Check which tax applicable for customer
		if ($job_card->jobOrder->outlet->state_id == $job_card->jobOrder->vehicle->currentOwner->customer->primaryAddress->state_id) {
			$tax_type = 1160; //Within State
		} else {
			$tax_type = 1161; //Inter State
		}

		//Count Tax Type
		$taxes = Tax::get();

		//GET SEPERATE TAXEX
		$seperate_tax = array();
		for ($i = 0; $i < count($taxes); $i++) {
			$seperate_tax[$i] = 0.00;
		}

		$tax_percentage = 0;
		$labour_details = array();
		if ($job_card->jobOrder->jobOrderRepairOrders) {
			$i = 1;
			$total_labour_qty = 0;
			$total_labour_mrp = 0;
			$total_labour_price = 0;
			$total_labour_tax = 0;
			foreach ($job_card->jobOrder->jobOrderRepairOrders as $key => $labour) {
				// if (in_array($labour->split_order_type_id, $customer_paid_type_id)) {
				if ($labour->is_free_service != 1) {
					$total_amount = 0;
					$labour_details[$key]['sno'] = $i;
					$labour_details[$key]['code'] = $labour->repairOrder->code;
					$labour_details[$key]['name'] = $labour->repairOrder->name;
					$labour_details[$key]['hsn_code'] = $labour->repairOrder->taxCode ? $labour->repairOrder->taxCode->code : '-';
					$labour_details[$key]['qty'] = $labour->qty;
					$labour_details[$key]['amount'] = $labour->amount;
					$labour_details[$key]['rate'] = $labour->repairOrder->amount;
					$labour_details[$key]['is_free_service'] = $labour->is_free_service;
					$tax_amount = 0;
					// $tax_percentage = 0;
					$labour_total_cgst = 0;
					$labour_total_sgst = 0;
					$labour_total_igst = 0;
					$tax_values = array();
					if ($labour->repairOrder->taxCode) {
						foreach ($labour->repairOrder->taxCode->taxes as $tax_key => $value) {
							$percentage_value = 0;
							if ($value->type_id == $tax_type) {
								// $tax_percentage += $value->pivot->percentage;
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
					$labour_total_sgst += $labour_total_sgst;
					$labour_total_igst += $labour_total_igst;
					$total_labour_qty += $labour->qty;
					$total_labour_mrp += $labour->amount;
					$total_labour_price += $labour->repairOrder->amount;
					$total_labour_tax += $tax_amount;

					$labour_details[$key]['tax_values'] = $tax_values;
					$labour_details[$key]['tax_amount'] = $tax_amount;
					$total_amount = $tax_amount + $labour->amount;
					$total_amount = number_format((float) $total_amount, 2, '.', '');

					$labour_details[$key]['total_amount'] = $total_amount;
					// if ($labour->is_free_service != 1) {
					$labour_amount += $total_amount;
					// }
				}
				// }
				$i++;
			}
		}

		foreach ($seperate_tax as $key => $s_tax) {
			$seperate_tax[$key] = convert_number_to_words($s_tax);
		}
		$this->data['seperate_taxes'] = $seperate_tax;

		$total_taxable_amount = $total_labour_tax;
		$this->data['tax_percentage'] = convert_number_to_words($tax_percentage);
		$this->data['total_taxable_amount'] = convert_number_to_words($total_taxable_amount);

		$total_amount = $labour_amount;
		$this->data['taxes'] = $taxes;
		$this->data['labour_details'] = $labour_details;
		$this->data['total_labour_qty'] = $total_labour_qty;
		$this->data['total_labour_mrp'] = $total_labour_mrp;
		$this->data['total_labour_price'] = $total_labour_price;
		$this->data['total_labour_tax'] = $total_labour_tax;
		$this->data['labour_total_amount'] = number_format($labour_amount, 2);

		//FOR ROUND OFF
		if ($total_amount <= round($total_amount)) {
			$round_off = round($total_amount) - $total_amount;
		} else {
			$round_off = round($total_amount) - $total_amount;
		}
		// dd(number_format($round_off));
		$this->data['round_total_amount'] = number_format($round_off, 2);
		$this->data['total_amount'] = number_format(round($total_amount), 2);
		// $this->data['round_total_amount'] = round($total_amount);
		// $this->data['total_amount'] = number_format($total_amount, 2);

		$pdf = PDF::loadView('pdf-gigo/tax-invoice-pdf', $this->data);

		return $pdf->stream('tax-invoice-pdf');
	}

	public function serviceProformaPDF($id) {

		$split_order_type_ids = SplitOrderType::where('paid_by_id', '10013')->pluck('id')->toArray();

		$this->data['service_proforma'] = $job_card = JobCard::with([
			'gatePasses',
			'jobOrder',
			'jobOrder.type',
			'jobOrder.vehicle',
			'jobOrder.vehicle.model',
			'jobOrder.vehicle.status',
			'jobOrder.outlet',
			'jobOrder.gateLog',
			'jobOrder.vehicle.currentOwner.customer',
			'jobOrder.vehicle.currentOwner.customer.primaryAddress',
			'jobOrder.vehicle.currentOwner.customer.primaryAddress.country',
			'jobOrder.vehicle.currentOwner.customer.primaryAddress.state',
			'jobOrder.vehicle.currentOwner.customer.primaryAddress.city',
			'jobOrder.serviceType',
			'jobOrder.jobOrderRepairOrders' => function ($query) use ($split_order_type_ids) {
				$query->whereIn('job_order_repair_orders.split_order_type_id', $split_order_type_ids)->whereNull('removal_reason_id');
			},
			'jobOrder.jobOrderRepairOrders.repairOrder',
			'jobOrder.jobOrderRepairOrders.repairOrder.repairOrderType',
			'jobOrder.floorAdviser',
			'jobOrder.serviceAdviser',
			'jobOrder.roadTestPreferedBy.employee',
			'jobOrder.jobOrderParts' => function ($query) use ($split_order_type_ids) {
				$query->whereIn('job_order_parts.split_order_type_id', $split_order_type_ids)->whereNull('removal_reason_id');
			},
			'jobOrder.jobOrderParts.part',
			'jobOrder.jobOrderParts.part.taxCode',
			'jobOrder.jobOrderParts.part.taxCode.taxes'])
			->select([
				'job_cards.*',
				DB::raw('DATE_FORMAT(job_cards.created_at,"%d-%m-%Y") as jobdate'),
				DB::raw('DATE_FORMAT(job_cards.created_at,"%h:%i %p") as time'),
			])
			->find($id);

		//dd($this->data['gate_pass']);

		$parts_amount = 0;
		$labour_amount = 0;
		$total_amount = 0;

		//Check which tax applicable for customer
		if ($job_card->jobOrder->outlet->state_id == $job_card->jobOrder->vehicle->currentOwner->customer->primaryAddress->state_id) {
			$tax_type = 1160; //Within State
		} else {
			$tax_type = 1161; //Inter State
		}

		//Count Tax Type
		$taxes = Tax::get();

		//GET SEPERATE TAXEX
		$seperate_tax = array();
		for ($i = 0; $i < count($taxes); $i++) {
			$seperate_tax[$i] = 0.00;
		}

		$tax_percentage = 0;
		$labour_details = array();
		if ($job_card->jobOrder->jobOrderRepairOrders) {
			$i = 1;
			$total_labour_qty = 0;
			$total_labour_mrp = 0;
			$total_labour_price = 0;
			$total_labour_tax = 0;
			foreach ($job_card->jobOrder->jobOrderRepairOrders as $key => $labour) {
				// if (in_array($labour->split_order_type_id, $customer_paid_type_id)) {
				if ($labour->is_free_service != 1) {
					$total_amount = 0;
					$labour_details[$key]['sno'] = $i;
					$labour_details[$key]['code'] = $labour->repairOrder->code;
					$labour_details[$key]['name'] = $labour->repairOrder->name;
					$labour_details[$key]['hsn_code'] = $labour->repairOrder->taxCode ? $labour->repairOrder->taxCode->code : '-';
					$labour_details[$key]['qty'] = $labour->qty;
					$labour_details[$key]['amount'] = $labour->amount;
					$labour_details[$key]['rate'] = $labour->repairOrder->amount;
					$labour_details[$key]['is_free_service'] = $labour->is_free_service;
					$tax_amount = 0;
					// $tax_percentage = 0;
					$labour_total_cgst = 0;
					$labour_total_sgst = 0;
					$labour_total_igst = 0;
					$tax_values = array();
					if ($labour->repairOrder->taxCode) {
						foreach ($labour->repairOrder->taxCode->taxes as $tax_key => $value) {
							$percentage_value = 0;
							if ($value->type_id == $tax_type) {
								// $tax_percentage += $value->pivot->percentage;
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
					$labour_total_sgst += $labour_total_sgst;
					$labour_total_igst += $labour_total_igst;
					$total_labour_qty += $labour->qty;
					$total_labour_mrp += $labour->amount;
					$total_labour_price += $labour->repairOrder->amount;
					$total_labour_tax += $tax_amount;

					$labour_details[$key]['tax_values'] = $tax_values;
					$labour_details[$key]['tax_amount'] = $tax_amount;
					$total_amount = $tax_amount + $labour->amount;
					$total_amount = number_format((float) $total_amount, 2, '.', '');

					$labour_details[$key]['total_amount'] = $total_amount;
					// if ($labour->is_free_service != 1) {
					$labour_amount += $total_amount;
					// }
				}
				// }
				$i++;
			}
		}

		$part_details = array();
		if ($job_card->jobOrder->jobOrderParts) {
			$i = 1;
			$total_parts_qty = 0;
			$total_parts_mrp = 0;
			$total_parts_price = 0;
			$total_parts_tax = 0;
			foreach ($job_card->jobOrder->jobOrderParts as $key => $parts) {
				// if (in_array($parts->split_order_type_id, $customer_paid_type_id)) {
				if ($parts->is_free_service != 1) {
					$total_amount = 0;
					$part_details[$key]['sno'] = $i;
					$part_details[$key]['code'] = $parts->part->code;
					$part_details[$key]['name'] = $parts->part->name;
					$part_details[$key]['hsn_code'] = $parts->part->taxCode ? $parts->part->taxCode->code : '-';
					$part_details[$key]['qty'] = $parts->qty;
					$part_details[$key]['rate'] = $parts->rate;
					$part_details[$key]['amount'] = $parts->amount;
					$part_details[$key]['is_free_service'] = $parts->is_free_service;
					$tax_amount = 0;
					// $tax_percentage = 0;
					$tax_values = array();
					if ($parts->part->taxCode) {
						foreach ($parts->part->taxCode->taxes as $tax_key => $value) {
							$percentage_value = 0;
							if ($value->type_id == $tax_type) {
								// $tax_percentage += $value->pivot->percentage;
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
				}
				// }
				$i++;
			}
		}

		foreach ($seperate_tax as $key => $s_tax) {
			$seperate_tax[$key] = convert_number_to_words($s_tax);
		}
		$this->data['seperate_taxes'] = $seperate_tax;

		$total_taxable_amount = $total_labour_tax + $total_parts_tax;
		$this->data['tax_percentage'] = convert_number_to_words($tax_percentage);
		$this->data['total_taxable_amount'] = convert_number_to_words($total_taxable_amount);

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

		//FOR ROUND OFF
		if ($total_amount <= round($total_amount)) {
			$round_off = round($total_amount) - $total_amount;
		} else {
			$round_off = round($total_amount) - $total_amount;
		}
		// dd(number_format($round_off));
		$this->data['round_total_amount'] = number_format($round_off, 2);
		$this->data['total_amount'] = number_format(round($total_amount), 2);

		$pdf = PDF::loadView('pdf-gigo/service-proforma-pdf', $this->data);

		return $pdf->stream('service-proforma.pdf');
	}

	public function serviceProformaCumulativePDF($id) {

		$split_order_type_ids = SplitOrderType::where('paid_by_id', '10013')->pluck('id')->toArray();

		$this->data['service_proforma_cumulative'] = $job_card = JobCard::with([
			'gatePasses',
			'jobOrder',
			'jobOrder.type',
			'jobOrder.vehicle',
			'jobOrder.vehicle.model',
			'jobOrder.vehicle.status',
			'jobOrder.outlet',
			'jobOrder.gateLog',
			'jobOrder.vehicle.currentOwner.customer',
			'jobOrder.vehicle.currentOwner.customer.primaryAddress',
			'jobOrder.vehicle.currentOwner.customer.primaryAddress.country',
			'jobOrder.vehicle.currentOwner.customer.primaryAddress.state',
			'jobOrder.vehicle.currentOwner.customer.primaryAddress.city',
			'jobOrder.serviceType',
			'jobOrder.jobOrderRepairOrders' => function ($query) use ($split_order_type_ids) {
				$query->whereIn('job_order_repair_orders.split_order_type_id', $split_order_type_ids)->whereNull('removal_reason_id');
			},
			'jobOrder.jobOrderRepairOrders.repairOrder',
			'jobOrder.jobOrderRepairOrders.repairOrder.repairOrderType',
			'jobOrder.floorAdviser',
			'jobOrder.serviceAdviser',
			'jobOrder.roadTestPreferedBy.employee',
			'jobOrder.jobOrderParts' => function ($query) use ($split_order_type_ids) {
				$query->whereIn('job_order_parts.split_order_type_id', $split_order_type_ids)->whereNull('removal_reason_id');
			},
			'jobOrder.jobOrderParts.part',
			'jobOrder.jobOrderParts.part.uom',
			'jobOrder.jobOrderParts.part.taxCode',
			'jobOrder.jobOrderParts.part.taxCode.taxes',
		])
			->select([
				'job_cards.*',
				DB::raw('DATE_FORMAT(job_cards.created_at,"%d-%m-%Y") as jobdate'),
				DB::raw('DATE_FORMAT(job_cards.created_at,"%h:%i %p") as time'),
			])
			->find($id);

		//dd($this->data['gate_pass']);

		$parts_amount = 0;
		$labour_amount = 0;
		$total_amount = 0;

		//Check which tax applicable for customer
		if ($job_card->jobOrder->outlet->state_id == $job_card->jobOrder->vehicle->currentOwner->customer->primaryAddress->state_id) {
			$tax_type = 1160; //Within State
		} else {
			$tax_type = 1161; //Inter State
		}

		//Count Tax Type
		$taxes = Tax::get();

		//GET SEPERATE TAXEX
		$seperate_tax = array();
		for ($i = 0; $i < count($taxes); $i++) {
			$seperate_tax[$i] = 0.00;
		}

		$tax_percentage = 0;
		$labour_details = array();
		if ($job_card->jobOrder->jobOrderRepairOrders) {
			$i = 1;
			$total_labour_qty = 0;
			$total_labour_mrp = 0;
			$total_labour_price = 0;
			$total_labour_tax = 0;
			foreach ($job_card->jobOrder->jobOrderRepairOrders as $key => $labour) {
				// if (in_array($labour->split_order_type_id, $customer_paid_type_id)) {
				if ($labour->is_free_service != 1) {
					$total_amount = 0;
					$labour_details[$key]['sno'] = $i;
					$labour_details[$key]['code'] = $labour->repairOrder->code;
					$labour_details[$key]['name'] = $labour->repairOrder->name;
					$labour_details[$key]['hsn_code'] = $labour->repairOrder->taxCode ? $labour->repairOrder->taxCode->code : '-';
					$labour_details[$key]['qty'] = $labour->qty;
					$labour_details[$key]['amount'] = $labour->amount;
					$labour_details[$key]['rate'] = $labour->repairOrder->amount;
					$labour_details[$key]['is_free_service'] = $labour->is_free_service;
					$labour_details[$key]['type'] = $labour->repairOrder->repairOrderType->name;
					$tax_amount = 0;
					// $tax_percentage = 0;
					$labour_total_cgst = 0;
					$labour_total_sgst = 0;
					$labour_total_igst = 0;
					$tax_values = array();
					if ($labour->repairOrder->taxCode) {
						foreach ($labour->repairOrder->taxCode->taxes as $tax_key => $value) {
							$percentage_value = 0;
							if ($value->type_id == $tax_type) {
								// $tax_percentage += $value->pivot->percentage;
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
					$labour_total_sgst += $labour_total_sgst;
					$labour_total_igst += $labour_total_igst;
					$total_labour_qty += $labour->qty;
					$total_labour_mrp += $labour->amount;
					$total_labour_price += $labour->repairOrder->amount;
					$total_labour_tax += $tax_amount;

					$labour_details[$key]['tax_values'] = $tax_values;
					$labour_details[$key]['tax_amount'] = $tax_amount;
					$total_amount = $tax_amount + $labour->amount;
					$total_amount = number_format((float) $total_amount, 2, '.', '');

					$labour_details[$key]['total_amount'] = $total_amount;
					// if ($labour->is_free_service != 1) {
					$labour_amount += $total_amount;
					// }
				}
				// }
				$i++;
			}
		}

		$part_details = array();
		if ($job_card->jobOrder->jobOrderParts) {
			$i = 1;
			$total_parts_qty = 0;
			$total_parts_mrp = 0;
			$total_parts_price = 0;
			$total_parts_tax = 0;
			foreach ($job_card->jobOrder->jobOrderParts as $key => $parts) {
				// if (in_array($parts->split_order_type_id, $customer_paid_type_id)) {
				if ($parts->is_free_service != 1) {
					$total_amount = 0;
					$part_details[$key]['sno'] = $i;
					$part_details[$key]['code'] = $parts->part->code;
					$part_details[$key]['name'] = $parts->part->name;
					$part_details[$key]['hsn_code'] = $parts->part->taxCode ? $parts->part->taxCode->code : '-';
					$part_details[$key]['qty'] = $parts->qty;
					$part_details[$key]['rate'] = $parts->rate;
					$part_details[$key]['amount'] = $parts->amount;
					$part_details[$key]['is_free_service'] = $parts->is_free_service;
					$part_details[$key]['uom'] = $parts->part->uom->code;
					$tax_amount = 0;
					// $tax_percentage = 0;
					$tax_values = array();
					if ($parts->part->taxCode) {
						foreach ($parts->part->taxCode->taxes as $tax_key => $value) {
							$percentage_value = 0;
							if ($value->type_id == $tax_type) {
								// $tax_percentage += $value->pivot->percentage;
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
				}
				// }
				$i++;
			}
		}

		foreach ($seperate_tax as $key => $s_tax) {
			$seperate_tax[$key] = convert_number_to_words($s_tax);
		}
		$this->data['seperate_taxes'] = $seperate_tax;

		$total_taxable_amount = $total_labour_tax + $total_parts_tax;
		$this->data['tax_percentage'] = convert_number_to_words($tax_percentage);
		$this->data['total_taxable_amount'] = convert_number_to_words($total_taxable_amount);

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

		//FOR ROUND OFF LABOUR
		if ($labour_amount <= round($labour_amount)) {
			$labour_round_off = round($labour_amount) - $labour_amount;
		} else {
			$labour_round_off = round($labour_amount) - $labour_amount;
		}
		//FOR ROUND OFF PART
		if ($parts_amount <= round($parts_amount)) {
			$part_round_off = round($parts_amount) - $parts_amount;
		} else {
			$part_round_off = round($parts_amount) - $parts_amount;
		}
		// dd(number_format($round_off));
		$this->data['labour_round_total_amount'] = number_format($labour_round_off, 2);
		$this->data['labour_total_amount'] = number_format(round($labour_amount), 2);
		$this->data['parts_round_total_amount'] = number_format($part_round_off, 2);
		$this->data['parts_total_amount'] = number_format(round($parts_amount), 2);

		$pdf = PDF::loadView('pdf-gigo/service-proforma-cumulative-pdf', $this->data);

		return $pdf->stream('service-proforma-cumulative-pdf');
	}

	public function JobCardBillDetailPDF($id, $split_order_type_id) {
		// dd($id, $split_order_type_id);
		$split_order = SplitOrderType::find($split_order_type_id);
		$this->data['job_card'] = $job_card = JobCard::with([
			'jobOrder',
			'jobOrder.outlet',
			'jobOrder.serviceType',
			'jobOrder.type',
			'jobOrder.vehicle',
			'jobOrder.vehicle.model',
			'jobOrder.jobOrderRepairOrders' => function ($query) use ($split_order_type_id) {
				$query->where('job_order_repair_orders.split_order_type_id', $split_order_type_id)->whereNull('removal_reason_id');
			},
			'jobOrder.jobOrderRepairOrders.repairOrder',
			'jobOrder.jobOrderRepairOrders.repairOrder.repairOrderType',
			'jobOrder.jobOrderRepairOrders.repairOrder.taxCode',
			'jobOrder.jobOrderRepairOrders.repairOrder.taxCode.taxes',
			'jobOrder.jobOrderParts' => function ($query) use ($split_order_type_id) {
				$query->where('job_order_parts.split_order_type_id', $split_order_type_id)->whereNull('removal_reason_id');
			},
			'jobOrder.jobOrderParts.part',
			'jobOrder.jobOrderParts.part.taxCode',
			'jobOrder.jobOrderParts.part.taxCode.taxes',
			'status',
		])
			->find($id);

		if (!$job_card) {
			return response()->json([
				'success' => false,
				'error' => 'Job Card Not found!',
			]);
		}

		$job_card['creation_date'] = date('d-m-Y', strtotime($job_card->created_at));

		$parts_amount = 0;
		$labour_amount = 0;
		$total_amount = 0;

		//Check which tax applicable for customer
		if ($job_card->jobOrder->outlet->state_id == $job_card->jobOrder->vehicle->currentOwner->customer->primaryAddress->state_id) {
			$tax_type = 1160; //Within State
		} else {
			$tax_type = 1161; //Inter State
		}
		$customer_paid_type_id = SplitOrderType::where('paid_by_id', '10013')->pluck('id')->toArray();

		//Count Tax Type
		$taxes = Tax::get();

		//GET SEPERATE TAXEX
		$seperate_tax = array();
		for ($i = 0; $i < count($taxes); $i++) {
			$seperate_tax[$i] = 0.00;
		}

		$tax_percentage = 0;

		$labour_details = array();
		if ($job_card->jobOrder->jobOrderRepairOrders) {
			$i = 1;
			$total_labour_qty = 0;
			$total_labour_mrp = 0;
			$total_labour_price = 0;
			$total_labour_tax = 0;
			foreach ($job_card->jobOrder->jobOrderRepairOrders as $key => $labour) {
				// if (in_array($labour->split_order_type_id, $customer_paid_type_id)) {
				if ($labour->is_free_service != 1) {
					$total_amount = 0;
					$labour_details[$key]['sno'] = $i;
					$labour_details[$key]['code'] = $labour->repairOrder->code;
					$labour_details[$key]['name'] = $labour->repairOrder->name;
					$labour_details[$key]['hsn_code'] = $labour->repairOrder->taxCode ? $labour->repairOrder->taxCode->code : '-';
					$labour_details[$key]['qty'] = $labour->qty;
					$labour_details[$key]['amount'] = $labour->amount;
					$labour_details[$key]['rate'] = $labour->repairOrder->amount;
					$labour_details[$key]['is_free_service'] = $labour->is_free_service;
					$tax_amount = 0;
					// $tax_percentage = 0;
					$labour_total_cgst = 0;
					$labour_total_sgst = 0;
					$labour_total_igst = 0;
					$tax_values = array();
					if ($labour->repairOrder->taxCode) {
						foreach ($labour->repairOrder->taxCode->taxes as $tax_key => $value) {
							$percentage_value = 0;
							if ($value->type_id == $tax_type) {
								// $tax_percentage += $value->pivot->percentage;
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
					$labour_total_sgst += $labour_total_sgst;
					$labour_total_igst += $labour_total_igst;
					$total_labour_qty += $labour->qty;
					$total_labour_mrp += $labour->amount;
					$total_labour_price += $labour->repairOrder->amount;
					$total_labour_tax += $tax_amount;

					$labour_details[$key]['tax_values'] = $tax_values;
					$labour_details[$key]['tax_amount'] = $tax_amount;
					$total_amount = $tax_amount + $labour->amount;
					$total_amount = number_format((float) $total_amount, 2, '.', '');

					$labour_details[$key]['total_amount'] = $total_amount;
					// if ($labour->is_free_service != 1) {
					$labour_amount += $total_amount;
					// }
				}
				// }
				$i++;
			}
		}

		$part_details = array();
		if ($job_card->jobOrder->jobOrderParts) {
			$i = 1;
			$total_parts_qty = 0;
			$total_parts_mrp = 0;
			$total_parts_price = 0;
			$total_parts_tax = 0;
			foreach ($job_card->jobOrder->jobOrderParts as $key => $parts) {
				// if (in_array($parts->split_order_type_id, $customer_paid_type_id)) {
				if ($parts->is_free_service != 1) {
					$total_amount = 0;
					$part_details[$key]['sno'] = $i;
					$part_details[$key]['code'] = $parts->part->code;
					$part_details[$key]['name'] = $parts->part->name;
					$part_details[$key]['hsn_code'] = $parts->part->taxCode ? $parts->part->taxCode->code : '-';
					$part_details[$key]['qty'] = $parts->qty;
					$part_details[$key]['rate'] = $parts->rate;
					$part_details[$key]['amount'] = $parts->amount;
					$part_details[$key]['is_free_service'] = $parts->is_free_service;
					$tax_amount = 0;
					// $tax_percentage = 0;
					$tax_values = array();
					if ($parts->part->taxCode) {
						foreach ($parts->part->taxCode->taxes as $tax_key => $value) {
							$percentage_value = 0;
							if ($value->type_id == $tax_type) {
								// $tax_percentage += $value->pivot->percentage;
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
				}
				// }
				$i++;
			}
		}

		foreach ($seperate_tax as $key => $s_tax) {
			$seperate_tax[$key] = convert_number_to_words($s_tax);
		}
		$this->data['seperate_taxes'] = $seperate_tax;
		$total_taxable_amount = $total_labour_tax + $total_parts_tax;
		$this->data['tax_percentage'] = convert_number_to_words($tax_percentage);
		$this->data['total_taxable_amount'] = convert_number_to_words($total_taxable_amount);

		$total_amount = $parts_amount + $labour_amount;
		$this->data['taxes'] = $taxes;
		$this->data['total_amount'] = number_format($total_amount, 2);

		//FOR ROUND OFF
		if ($total_amount <= round($total_amount)) {
			$round_off = round($total_amount) - $total_amount;
		} else {
			$round_off = round($total_amount) - $total_amount;
		}

		$this->data['round_total_amount'] = number_format($round_off, 2);

		$this->data['labour_details'] = $labour_details;
		$this->data['total_labour_qty'] = $total_labour_qty;
		$this->data['total_labour_mrp'] = $total_labour_mrp;
		$this->data['total_labour_price'] = $total_labour_price;
		$this->data['total_labour_tax'] = $total_labour_tax;
		$this->data['labour_round_total_amount'] = round($labour_amount);
		$this->data['labour_total_amount'] = number_format($labour_amount, 2);

		$this->data['part_details'] = $part_details;
		$this->data['total_parts_qty'] = $total_parts_qty;
		$this->data['total_parts_mrp'] = $total_parts_mrp;
		$this->data['total_parts_price'] = $total_parts_price;
		$this->data['total_parts_tax'] = $total_parts_tax;
		$this->data['parts_round_total_amount'] = round($parts_amount);
		$this->data['parts_total_amount'] = number_format($parts_amount, 2);

		$pdf = PDF::loadView('pdf-gigo/bill-detail-split-order-pdf', $this->data);

		$file_name = $split_order->name . '- Bill-Detail.pdf';
		$file_name = str_replace(' ', '', $file_name);
		return $pdf->stream($file_name);
	}

	public function LabourBillDeatilPDF($id) {
		// dd($id);
		//CUSTOMER PAID SPLIT ORDERS
		$split_order_type_ids = SplitOrderType::where('paid_by_id', '10013')->pluck('id')->toArray();
		// dd($split_order_type_ids);

		$this->data['job_card'] = $job_card = JobCard::with([
			'jobOrder',
			'jobOrder.outlet',
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
			->find($id);

		if (!$job_card) {
			return response()->json([
				'success' => false,
				'error' => 'Job Card Not found!',
			]);
		}

		$job_card['creation_date'] = date('d-m-Y', strtotime($job_card->created_at));

		$labour_amount = 0;
		$total_amount = 0;

		//Check which tax applicable for customer
		if ($job_card->jobOrder->outlet->state_id == $job_card->jobOrder->vehicle->currentOwner->customer->primaryAddress->state_id) {
			$tax_type = 1160; //Within State
		} else {
			$tax_type = 1161; //Inter State
		}
		$customer_paid_type_id = SplitOrderType::where('paid_by_id', '10013')->pluck('id')->toArray();

		//Count Tax Type
		$taxes = Tax::get();

		//GET SEPERATE TAXEX
		$seperate_tax = array();
		for ($i = 0; $i < count($taxes); $i++) {
			$seperate_tax[$i] = 0.00;
		}

		$tax_percentage = 0;

		$labour_details = array();
		if ($job_card->jobOrder->jobOrderRepairOrders) {
			$i = 1;
			$total_labour_qty = 0;
			$total_labour_mrp = 0;
			$total_labour_price = 0;
			$total_labour_tax = 0;
			foreach ($job_card->jobOrder->jobOrderRepairOrders as $key => $labour) {
				// if (in_array($labour->split_order_type_id, $customer_paid_type_id)) {
				if ($labour->is_free_service != 1) {
					$total_amount = 0;
					$labour_details[$key]['sno'] = $i;
					$labour_details[$key]['code'] = $labour->repairOrder->code;
					$labour_details[$key]['name'] = $labour->repairOrder->name;
					$labour_details[$key]['hsn_code'] = $labour->repairOrder->taxCode ? $labour->repairOrder->taxCode->code : '-';
					$labour_details[$key]['qty'] = $labour->qty;
					$labour_details[$key]['amount'] = $labour->amount;
					$labour_details[$key]['rate'] = $labour->repairOrder->amount;
					$labour_details[$key]['is_free_service'] = $labour->is_free_service;
					$tax_amount = 0;
					// $tax_percentage = 0;
					$labour_total_cgst = 0;
					$labour_total_sgst = 0;
					$labour_total_igst = 0;
					$tax_values = array();
					if ($labour->repairOrder->taxCode) {
						foreach ($labour->repairOrder->taxCode->taxes as $tax_key => $value) {
							$percentage_value = 0;
							if ($value->type_id == $tax_type) {
								// $tax_percentage += $value->pivot->percentage;
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
					$labour_total_sgst += $labour_total_sgst;
					$labour_total_igst += $labour_total_igst;
					$total_labour_qty += $labour->qty;
					$total_labour_mrp += $labour->amount;
					$total_labour_price += $labour->repairOrder->amount;
					$total_labour_tax += $tax_amount;

					$labour_details[$key]['tax_values'] = $tax_values;
					$labour_details[$key]['tax_amount'] = $tax_amount;
					$total_amount = $tax_amount + $labour->amount;
					$total_amount = number_format((float) $total_amount, 2, '.', '');

					$labour_details[$key]['total_amount'] = $total_amount;
					// if ($labour->is_free_service != 1) {
					$labour_amount += $total_amount;
					// }
				}
				// }
				$i++;
			}
		}

		foreach ($seperate_tax as $key => $s_tax) {
			$seperate_tax[$key] = convert_number_to_words($s_tax);
		}
		$this->data['seperate_taxes'] = $seperate_tax;

		$total_taxable_amount = $total_labour_tax; //+ $total_parts_tax;
		$this->data['tax_percentage'] = convert_number_to_words($tax_percentage);
		$this->data['total_taxable_amount'] = convert_number_to_words($total_taxable_amount);

		$total_amount = $labour_amount;
		$this->data['taxes'] = $taxes;
		$this->data['total_amount'] = number_format($total_amount, 2);
		$this->data['round_total_amount'] = round($total_amount);

		$this->data['labour_details'] = $labour_details;
		$this->data['total_labour_qty'] = $total_labour_qty;
		$this->data['total_labour_mrp'] = $total_labour_mrp;
		$this->data['total_labour_price'] = $total_labour_price;
		$this->data['total_labour_tax'] = $total_labour_tax;
		$this->data['labour_round_total_amount'] = round($labour_amount);
		$this->data['labour_total_amount'] = number_format($labour_amount, 2);

		$pdf = PDF::loadView('pdf-gigo/bill-detail-labour-pdf', $this->data);

		return $pdf->stream('bill-detail-labour-pdf');
	}

	public function PartBillDetailPDF($id) {
		// dd($id);

		//CUSTOMER PAID SPLIT ORDERS
		$split_order_type_ids = SplitOrderType::where('paid_by_id', '10013')->pluck('id')->toArray();
		// dd($split_order_type_ids);

		$this->data['job_card'] = $job_card = JobCard::with([
			'jobOrder',
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
			->find($id);

		if (!$job_card) {
			return response()->json([
				'success' => false,
				'error' => 'Job Card Not found!',
			]);
		}

		$job_card['creation_date'] = date('d-m-Y', strtotime($job_card->created_at));

		$parts_amount = 0;
		$total_amount = 0;

		//Check which tax applicable for customer
		if ($job_card->jobOrder->outlet->state_id == $job_card->jobOrder->vehicle->currentOwner->customer->primaryAddress->state_id) {
			$tax_type = 1160; //Within State
		} else {
			$tax_type = 1161; //Inter State
		}
		$customer_paid_type_id = SplitOrderType::where('paid_by_id', '10013')->pluck('id')->toArray();

		//Count Tax Type
		$taxes = Tax::get();

		//GET SEPERATE TAXEX
		$seperate_tax = array();
		for ($i = 0; $i < count($taxes); $i++) {
			$seperate_tax[$i] = 0.00;
		}

		$tax_percentage = 0;

		$part_details = array();
		if ($job_card->jobOrder->jobOrderParts) {
			$i = 1;
			$total_parts_qty = 0;
			$total_parts_mrp = 0;
			$total_parts_price = 0;
			$total_parts_tax = 0;
			foreach ($job_card->jobOrder->jobOrderParts as $key => $parts) {
				// if (in_array($parts->split_order_type_id, $customer_paid_type_id)) {
				if ($parts->is_free_service != 1) {
					$total_amount = 0;
					$part_details[$key]['sno'] = $i;
					$part_details[$key]['code'] = $parts->part->code;
					$part_details[$key]['name'] = $parts->part->name;
					$part_details[$key]['hsn_code'] = $parts->part->taxCode ? $parts->part->taxCode->code : '-';
					$part_details[$key]['qty'] = $parts->qty;
					$part_details[$key]['rate'] = $parts->rate;
					$part_details[$key]['amount'] = $parts->amount;
					$part_details[$key]['is_free_service'] = $parts->is_free_service;
					$tax_amount = 0;
					// $tax_percentage = 0;
					$tax_values = array();
					if ($parts->part->taxCode) {
						foreach ($parts->part->taxCode->taxes as $tax_key => $value) {
							$percentage_value = 0;
							if ($value->type_id == $tax_type) {
								// $tax_percentage += $value->pivot->percentage;
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
				}
				// }
				$i++;
			}
		}

		foreach ($seperate_tax as $key => $s_tax) {
			$seperate_tax[$key] = convert_number_to_words($s_tax);
		}
		$this->data['seperate_taxes'] = $seperate_tax;

		$total_taxable_amount = $total_parts_tax;
		$this->data['tax_percentage'] = convert_number_to_words($tax_percentage);
		$this->data['total_taxable_amount'] = convert_number_to_words($total_taxable_amount);

		$total_amount = $parts_amount;
		$this->data['taxes'] = $taxes;
		$this->data['total_amount'] = number_format($total_amount, 2);
		$this->data['round_total_amount'] = round($total_amount);

		$this->data['part_details'] = $part_details;
		$this->data['total_parts_qty'] = $total_parts_qty;
		$this->data['total_parts_mrp'] = $total_parts_mrp;
		$this->data['total_parts_price'] = $total_parts_price;
		$this->data['total_parts_tax'] = $total_parts_tax;
		$this->data['parts_round_total_amount'] = round($parts_amount);
		$this->data['parts_total_amount'] = number_format($parts_amount, 2);

		$pdf = PDF::loadView('pdf-gigo/bill-detail-part-pdf', $this->data);

		return $pdf->stream('bill-detail-part-pdf');
	}

}

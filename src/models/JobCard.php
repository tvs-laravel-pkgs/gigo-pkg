<?php

namespace Abs\GigoPkg;
use Abs\GigoPkg\JobOrder;
use Abs\HelperPkg\Traits\SeederTrait;
use Abs\TaxPkg\Tax;
use App\BaseModel;
use App\Company;
use App\Config;
use App\SplitOrderType;
use Auth;
use DB;
use Illuminate\Database\Eloquent\Model;
use PDF;
use Storage;

// use Illuminate\Database\Eloquent\SoftDeletes;

class JobCard extends BaseModel {
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
	public function getRegistrationNumberAttribute($value) {
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

	public function getDateAttribute($value) {
		return empty($value) ? '' : date('d-m-Y', strtotime($value));
	}

	public function getCreatedAtAttribute($value) {
		return empty($value) ? '' : date('d-m-Y h:i A', strtotime($value));
	}

	public function setDateOfJoinAttribute($date) {
		return $this->attributes['date_of_join'] = empty($date) ? NULL : date('Y-m-d', strtotime($date));
	}

	public function jobOrder() {
		return $this->belongsTo('Abs\GigoPkg\JobOrder', 'job_order_id');
	}

	public function outlet() {
		return $this->belongsTo('App\Outlet', 'outlet_id')->where('company_id', Auth::user()->company_id);
	}

	public function company() {
		return $this->belongsTo('App\Company', 'company_id');
	}

	public function workOrders() {
		return $this->hasMany('App\WorkOrder');
	}

	public function business() {
		return $this->belongsTo('App\Business', 'business_id')->where('company_id', Auth::user()->company_id);
	}

	public function sacCode() {
		return $this->belongsTo('App\Entity', 'sac_code_id')->where('company_id', Auth::user()->company_id);
	}

	public function model() {
		return $this->belongsTo('App\Entity', 'model_id')->where('company_id', Auth::user()->company_id);
	}

	public function segment() {
		return $this->belongsTo('App\Entity', 'segment_id')->where('company_id', Auth::user()->company_id);
	}

	public function bay() {
		return $this->belongsTo('App\Bay', 'bay_id');
	}

	public function status() {
		return $this->belongsTo('App\Config', 'status_id');
	}

	public function gatePasses() {
		return $this->hasMany('App\GatePass', 'job_card_id', 'id');
	}

	public function jobCardReturnableItems() {
		return $this->hasMany('Abs\GigoPkg\JobCardReturnableItem');
	}

	public function gigoInvoices() {
		return $this->hasMany('Abs\GigoPkg\GigoInvoice', 'entity_id', 'id');
	}

	public function attachment() {
		return $this->hasOne('App\Attachment', 'entity_id', 'id')->where('attachment_of_id', 228)->where('attachment_type_id', 255);
	}

	// Query Scopes --------------------------------------------------------------

	public function scopeFilterSearch($query, $term) {
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

	public static function createFromObject($record_data) {

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

	public static function getList($params = [], $add_default = true, $default_text = 'Select Job Card') {
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

	public static function generateRevisedEstimatePDF($job_card_id) {

		$data['revised_estimate'] = $job_card = JobCard::with([
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
			->find($job_card_id);

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
						$i++;
					}
				}
				// }
			}
		}

		$part_details = array();
		if ($job_card->jobOrder->jobOrderParts) {
			$j = 1;
			$total_parts_qty = 0;
			$total_parts_mrp = 0;
			$total_parts_price = 0;
			$total_parts_tax = 0;
			foreach ($job_card->jobOrder->jobOrderParts as $key => $parts) {
				if (in_array($parts->split_order_type_id, $customer_paid_type_id) || !$parts->split_order_type_id) {
					if ($parts->is_free_service != 1 && $parts->removal_reason_id == null) {
						$total_amount = 0;
						$part_details[$key]['sno'] = $j;
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
						$j++;
					}
				}
			}
		}

		foreach ($seperate_tax as $key => $s_tax) {
			$seperate_tax[$key] = convert_number_to_words($s_tax);
		}
		$data['seperate_taxes'] = $seperate_tax;

		$total_taxable_amount = $total_labour_tax + $total_parts_tax;
		$data['tax_percentage'] = convert_number_to_words($tax_percentage);
		$data['total_taxable_amount'] = convert_number_to_words($total_taxable_amount);

		$total_amount = $parts_amount + $labour_amount;
		$data['taxes'] = $taxes;
		$data['part_details'] = $part_details;
		$data['labour_details'] = $labour_details;
		$data['total_labour_qty'] = $total_labour_qty;
		$data['total_labour_mrp'] = $total_labour_mrp;
		$data['total_labour_price'] = $total_labour_price;
		$data['total_labour_tax'] = $total_labour_tax;

		$data['total_parts_qty'] = $total_parts_qty;
		$data['total_parts_mrp'] = $total_parts_mrp;
		$data['total_parts_price'] = $total_parts_price;
		$data['total_parts_tax'] = $total_parts_tax;
		$data['parts_total_amount'] = number_format($parts_amount, 2);
		$data['labour_total_amount'] = number_format($labour_amount, 2);
		$data['date'] = date('d-m-Y');
		//FOR ROUND OFF
		if ($total_amount <= round($total_amount)) {
			$round_off = round($total_amount) - $total_amount;
		} else {
			$round_off = round($total_amount) - $total_amount;
		}
		// dd(number_format($round_off));
		$data['round_total_amount'] = number_format($round_off, 2);
		$data['total_amount'] = number_format(round($total_amount), 2);

		if (!Storage::disk('public')->has('gigo/pdf/')) {
			Storage::disk('public')->makeDirectory('gigo/pdf/');
		}

		$save_path = storage_path('app/public/gigo/pdf');
		Storage::makeDirectory($save_path, 0777);

		$name = $job_card->jobOrder->id . '_revised_estimate.pdf';

		$pdf = PDF::loadView('pdf-gigo/revised-estimate-pdf', $data)->setPaper('a4', 'portrait');

		$pdf->save(storage_path('app/public/gigo/pdf/' . $name));

		return true;
	}

	public static function generateJobcardLabourPDF($job_card_id) {

		$split_order_type_ids = SplitOrderType::where('paid_by_id', '10013')->pluck('id')->toArray();

		$data['job_card'] = $job_card = JobCard::with([
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
		$data['seperate_taxes'] = $seperate_tax;

		$total_taxable_amount = $total_labour_tax; //+ $total_parts_tax;
		$data['tax_percentage'] = convert_number_to_words($tax_percentage);
		$data['total_taxable_amount'] = convert_number_to_words($total_taxable_amount);

		$total_amount = $labour_amount;
		$data['taxes'] = $taxes;
		$data['total_amount'] = number_format($total_amount, 2);
		$data['round_total_amount'] = number_format($total_amount, 2);

		$data['labour_details'] = $labour_details;
		$data['total_labour_qty'] = $total_labour_qty;
		$data['total_labour_mrp'] = $total_labour_mrp;
		$data['total_labour_price'] = $total_labour_price;
		$data['total_labour_tax'] = $total_labour_tax;
		$data['labour_round_total_amount'] = round($labour_amount);
		$data['labour_total_amount'] = number_format($labour_amount, 2);

		$save_path = storage_path('app/public/gigo/pdf');
		Storage::makeDirectory($save_path, 0777);

		$data['date'] = date('d-m-Y');

		$name = $job_card->id . '_labour_invoice.pdf';

		$pdf = PDF::loadView('pdf-gigo/bill-detail-labour-pdf', $data)->setPaper('a4', 'portrait');

		$pdf->save(storage_path('app/public/gigo/pdf/' . $name));

		return true;
	}

	public static function generateGatePassPDF($job_card_id) {
		$data['gate_pass'] = $job_card = JobCard::with([
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
		$data['extras'] = [
			'inventory_type_list' => VehicleInventoryItem::getInventoryList($job_card->jobOrder->id, $params, '', '', $company_id),
		];

		if (!Storage::disk('public')->has('gigo/pdf/')) {
			Storage::disk('public')->makeDirectory('gigo/pdf/');
		}

		$data['date'] = date('d-m-Y');

		$save_path = storage_path('app/public/gigo/pdf');
		Storage::makeDirectory($save_path, 0777);

		$name = $job_card->jobOrder->id . '_job_card_gatepass.pdf';

		$pdf = PDF::loadView('pdf-gigo/job-card-gate-pass-pdf', $data)->setPaper('a4', 'portrait');

		$pdf->save(storage_path('app/public/gigo/pdf/' . $name));

		return true;
	}

}

<?php

namespace Abs\GigoPkg;
use Abs\GigoPkg\JobCard;
use Abs\GigoPkg\JobOrder;
use Abs\GigoPkg\JobOrderIssuedPart;
use Abs\GigoPkg\JobOrderPart;
use Abs\GigoPkg\JobOrderRepairOrder;
use Abs\PartPkg\Part;
use App\Config;
use App\Http\Controllers\Controller;
use App\Outlet;
use App\User;
use App\Vehicle;
use App\VehicleOwner;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class PartsIndentController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.admin_theme');
	}

	public function getPartsIndentFilter() {
		$params = [
			'config_type_id' => 41,
			'add_default' => true,
			'default_text' => "Select Status",
		];
		$this->data['extras'] = [
			'status_list' => Config::getDropDownList($params),
		];
		return response()->json($this->data);
	}

	public function getPartsindentList(Request $request) {
		// dd($request->all());

		$job_cards = JobOrder::select([
			'job_orders.id',
			'job_orders.number as job_order_number',
			'job_cards.job_card_number',
			'users.name as floor_supervisor',
			'service_adv.name as service_advisor',
			DB::raw('DATE_FORMAT(job_orders.created_at,"%d/%m/%Y, %h:%i %p") as job_order_date_time'),
			DB::raw('DATE_FORMAT(job_cards.created_at,"%d/%m/%Y, %h:%i %p") as job_card_date_time'),
			DB::raw('COALESCE(SUM(job_order_issued_parts.issued_qty), "0.00") as issued_qty'),
			DB::raw('COALESCE(SUM(job_order_parts.qty), "0.00") as requested_qty'),
			'job_orders.vehicle_id',
			'outlets.code as outlet_name',
			DB::raw('COALESCE(customers.name, "-") as customer_name'),
			'states.name as state_name',
			'regions.name as region_name',
			'job_order_parts.status_id',
			'configs.name as status',
		])
			->join('users as service_adv', 'service_adv.id', 'job_orders.service_advisor_id')
			->leftJoin('job_cards', 'job_orders.id', 'job_cards.job_order_id')
			->leftJoin('job_order_parts', 'job_order_parts.job_order_id', 'job_orders.id')
			->leftJoin('job_order_issued_parts', 'job_order_issued_parts.job_order_part_id', 'job_order_parts.id')
			->leftJoin('users', 'users.id', 'job_cards.floor_supervisor_id')
			->leftJoin('customers', 'customers.id', 'job_orders.customer_id')
			->leftJoin('outlets', 'outlets.id', 'job_orders.outlet_id')
			->leftJoin('states', 'states.id', 'outlets.state_id')
			->leftJoin('configs', 'configs.id', 'job_order_parts.status_id')
			->leftJoin('regions', 'regions.id', 'outlets.region_id')
			->where(function ($query) use ($request) {
				if (!empty($request->job_card_no)) {
					$query->where('job_cards.job_card_number', 'LIKE', '%' . $request->job_card_no . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->job_card_date)) {
					$query->whereDate('job_cards.created_at', date('Y-m-d', strtotime($request->job_card_date)));
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->job_order_no)) {
					$query->where('job_orders.number', 'LIKE', '%' . $request->job_order_no . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->job_order_date)) {
					$query->whereDate('job_orders.created_at', date('Y-m-d', strtotime($request->job_order_date)));
				}
			})

			->where(function ($query) use ($request) {
				if (!empty($request->customer_id)) {
					$query->where('vehicle_owners.customer_id', $request->customer_id);
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->outlet_id)) {
					$query->where('job_orders.outlet_id', $request->outlet_id);
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->status_id)) {
					$query->where('job_order_parts.status_id', $request->status_id);
				}
			})
			->where('job_orders.company_id', Auth::user()->company_id)
		// ->get()
		;

		if (!Entrust::can('view-overall-outlets-part-indent')) {
			if (Entrust::can('view-mapped-outlet-part-indent')) {
				$job_cards->whereIn('job_orders.outlet_id', Auth::user()->employee->outlets->pluck('id')->toArray());
			} else {
				$job_cards->where('job_orders.outlet_id', Auth::user()->employee->outlet_id);
			}
		}

		// $job_cards->groupBy('job_orders.id')->get();
		// dd($job_cards);
		//
		return Datatables::of($job_cards)
			->editColumn('status', function ($job_cards) {
				if ($job_cards->status_id == 8200 || $job_cards->status_id == 8201) {
					$status = 'blue';
				} elseif ($job_cards->status_id == 8202) {
					$status = 'green';
				} else {
					$status = 'red';
				}
				return '<span class="text-' . $status . '">' . $job_cards->status . '</span>';
			})
			->addColumn('action', function ($job_cards) {
				$view_hover_img = asset("public/theme/img/table/view-hover.svg");
				$view_img = asset("/public/theme/img/table/view.svg");
				$output = '';
				if (Entrust::can('view-parts-indent')) {
					$output .= '<a href="#!/part-indent/vehicle/view/' . $job_cards->id . '" id = "" title="View"><img src="' . $view_img . '" alt="View" class="img-responsive" onmouseover=this.src="' . $view_hover_img . '" onmouseout=this.src="' . $view_img . '"></a>';
				}
				return $output;
			})
			->make(true);
	}

	public function getOutletSearchList(Request $request) {
		return Outlet::search($request);
	}

	public function getPartsIndentData(Request $request) {
		$id = $request->id;
		$this->data['job_cards'] = $job_card = JobCard::select([
			'job_cards.job_card_number', 'job_cards.id as job_card_id',
			'users.name as floor_supervisor',
			'job_cards.job_order_id', 'configs.name as work_status',
			DB::raw('DATE_FORMAT(job_cards.created_at,"%d-%m-%Y") as date_time'),
			'job_orders.vehicle_id',
		])
			->leftJoin('users', 'users.id', 'job_cards.floor_supervisor_id')
			->leftJoin('job_orders', 'job_orders.id', 'job_cards.job_order_id')
			->leftJoin('configs', 'configs.id', 'job_cards.status_id')
			->where('job_cards.id', $id)->first();

		$this->data['vehicle_info'] = Vehicle::select('vehicles.registration_number', 'vehicles.engine_number', 'vehicles.chassis_number', 'models.model_name', 'vehicle_makes.name as make_name')->leftJoin('models', 'models.id', 'vehicles.model_id')->leftJoin('vehicle_makes', 'vehicle_makes.id', 'models.vehicle_make_id')->where('vehicles.id', $job_card->vehicle_id)->first();

		$this->data['customer_details'] = VehicleOwner::select('customers.name as customer_name', 'customers.mobile_no', 'customers.email', 'customers.address', 'customers.gst_number', 'customers.pan_number', 'configs.name')
			->join('customers', 'customers.id', 'vehicle_owners.customer_id')
			->join('configs', 'configs.id', 'vehicle_owners.ownership_id')
			->where('vehicle_owners.vehicle_id', $job_card->vehicle_id)->orderby('from_date', 'desc')->first();

		$this->data['gate_log'] = $gate_log = GateLog::select('gate_logs.id', 'gate_logs.number', DB::raw('DATE_FORMAT(gate_logs.gate_in_date,"%d-%m-%Y") as gate_in_date'), 'job_orders.driver_name', 'job_orders.driver_mobile_number')
			->leftJoin('job_orders', 'job_orders.id', 'gate_logs.job_order_id')->where('gate_logs.job_order_id', $job_card->job_order_id)->first();

		$this->data['labour_details'] = JobOrderRepairOrder::select('repair_orders.code',
			DB::raw('COALESCE(repair_orders.name,"--") as name'), 'split_order_types.name as split_name', 'tax_codes.code as tax_code', 'repair_orders.amount as rate', 'job_order_repair_orders.qty', 'job_order_repair_orders.amount')
			->leftJoin('repair_orders', 'repair_orders.id', 'job_order_repair_orders.repair_order_id')
			->leftJoin('split_order_types', 'split_order_types.id', 'job_order_repair_orders.split_order_type_id')
			->leftJoin('tax_codes', 'tax_codes.id', 'repair_orders.tax_code_id')
			->where('job_order_repair_orders.job_order_id', $job_card->job_order_id)->get();

		$this->data['parts_details'] = JobOrderPart::select('parts.code', 'parts.name', 'tax_codes.code as tax_code', 'split_order_types.name as split_name', 'job_order_parts.rate', 'job_order_parts.amount', 'job_order_parts.qty')
			->leftJoin('parts', 'parts.id', 'job_order_parts.part_id')
			->leftJoin('split_order_types', 'split_order_types.id', 'job_order_parts.split_order_type_id')
			->leftJoin('tax_codes', 'tax_codes.id', 'parts.tax_code_id')
			->where('job_order_parts.job_order_id', $job_card->job_order_id)->get();

		$this->data['customer_voice_details'] = JobOrder::select('job_orders.is_road_test_required', 'job_orders.road_test_report', 'users.name', 'customer_voices.name as customer_voice', 'job_orders.expert_diagnosis_report')
			->leftJoin('users', 'users.id', 'job_orders.road_test_done_by_id')
			->leftJoin('job_order_customer_voice', 'job_order_customer_voice.job_order_id', 'job_orders.id')
			->leftJoin('customer_voices', 'customer_voices.id', 'job_order_customer_voice.customer_voice_id')
			->where('job_orders.id', $job_card->job_order_id)->get();

		$this->data['gate_pass_details'] = JobOrder::
			with([
			'warrentyPolicyAttachment',
			'EWPAttachment',
			'AMCAttachment',
		])->find($job_card->job_order_id);

		$this->data['part_list'] = collect(Part::select('id', 'name')->where('company_id', Auth::user()->company_id)->get())->prepend(['id' => '', 'name' => 'Select Repair Order Type']);

		$this->data['mechanic_list'] = collect(JobOrderRepairOrder::select('users.id', 'users.name')->leftJoin('repair_order_mechanics', 'repair_order_mechanics.job_order_repair_order_id', 'job_order_repair_orders.id')->leftJoin('users', 'users.id', 'repair_order_mechanics.mechanic_id')->where('job_order_repair_orders.job_order_id', $job_card->job_order_id)->distinct()->get())->prepend(['id' => '', 'name' => 'Select Mechanic']);

		$this->data['issued_mode'] = collect(Config::select('id', 'name')->where('config_type_id', 109)->get())->prepend(['id' => '', 'name' => 'Select Issue Mode']);

		$this->data['issued_parts_details'] = JobOrderIssuedPart::select('job_order_issued_parts.id as issued_id', 'parts.code', 'job_order_parts.id', 'job_order_parts.qty', 'job_order_issued_parts.issued_qty', DB::raw('DATE_FORMAT(job_order_issued_parts.created_at,"%d-%m-%Y") as date'), 'users.name as issued_to', 'configs.name as config_name', 'job_order_issued_parts.issued_mode_id', 'job_order_issued_parts.issued_to_id')
			->leftJoin('job_order_parts', 'job_order_parts.id', 'job_order_issued_parts.job_order_part_id')
			->leftJoin('parts', 'parts.id', 'job_order_parts.part_id')
			->leftJoin('users', 'users.id', 'job_order_issued_parts.issued_to_id')
			->leftJoin('configs', 'configs.id', 'job_order_issued_parts.issued_mode_id')
			->where('job_order_parts.job_order_id', $job_card->job_order_id)->groupBy('job_order_issued_parts.id')->get();

		return response()->json($this->data);
	}

	public function getPartDetails(Request $request) {
		$job_order_parts = JobOrderPart::select(
			'job_order_parts.id',
			'job_order_parts.qty',
			'parts.name',
			DB::raw("SUM(job_order_issued_parts.issued_qty) as issued_qty")
		)
			->leftJoin('job_order_issued_parts', 'job_order_issued_parts.job_order_part_id', 'job_order_parts.id')
			->join('parts', 'parts.id', 'job_order_parts.part_id')
			->where('job_order_parts.id', $request->job_order_part_id)
			->first();
		if (!$job_order_parts) {
			return response()->json([
				'success' => false,
				'error' => 'Validation Error',
				'errors' => [
					'Job Order Part Not Found',
				],
			]);
		}
		return response()->json([
			'success' => true,
			'job_order_parts' => $job_order_parts,
		]);
	}

	public function getPartsIndentPartsData(Request $request) {
		$this->data['job_order_issued_part'] = JobOrderIssuedPart::with([
			'jobOrderPart',
			'jobOrderPart.part',
			'issuedTo',
			'issueMode',
		])
			->find($request->job_order_issued_part_id);

		$this->data['job_card'] = $job_card = JobCard::with([
			'jobOrder',
			'jobOrder.type',
			'jobOrder.vehicle',
			'jobOrder.vehicle.model',
			'jobOrder.vehicle.status',
			'status',
		])->find($request->job_card_id);

		$part_list = collect(JobOrderPart::select(
			'job_order_parts.id',
			'parts.code as name'
		)
				->join('parts', 'parts.id', 'job_order_parts.part_id')
				->where('job_order_id', $job_card->job_order_id)
				->groupBy('job_order_parts.id')
				->get())->prepend(['id' => '', 'name' => 'Select Part No']);

		$mechanic_list = collect(JobOrderRepairOrder::select(
			'users.id',
			'users.name'
		)
				->join('repair_order_mechanics', 'repair_order_mechanics.job_order_repair_order_id', 'job_order_repair_orders.id')
				->join('users', 'users.id', 'repair_order_mechanics.mechanic_id')
				->where('job_order_repair_orders.job_order_id', $job_card->job_order_id)
				->groupBy('users.id')
				->get())->prepend(['id' => '', 'name' => 'Select Issued To']);

		$issued_mode_list = Config::getDropDownList(['config_type_id' => 109, 'add_default' => true, 'default_text' => 'Select Issue Mode']);

		$this->data['extras'] = [
			'part_list' => $part_list,
			'mechanic_list' => $mechanic_list,
			'issued_mode_list' => $issued_mode_list,
		];
		return response()->json($this->data);

	}

	public function savePartsindent(Request $request) {
		// dd($request->all());
		try {
			$error_messages = [
				'part_id.required' => 'Part No is Required',
				'issued_qty.required' => 'Issued Qty is Required',
				'issued_mode_id.required' => 'Issue Mode is Required',
				'issued_to_id.required' => 'Issued To is Required',
			];
			$validator = Validator::make($request->all(), [
				'job_order_part_id' => [
					'required',
					'integer',
					'exists:job_order_parts,id',
				],
				'issued_qty' => [
					'required',
				],
				'issued_mode_id' => [
					'required',
					'integer',
					'exists:configs,id',
				],
				'issued_to_id' => [
					'required',
					'integer',
					'exists:users,id',
				],
			], $error_messages);

			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => $validator->errors()->all(),
				]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				if ($request->issued_qty > $request->bal_qty) {
					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => [
							'Issued Quantity Exceed Requested Quantity',
						],
					]);
				}
				$job_order_issued_parts = new JobOrderIssuedPart;
				$job_order_issued_parts->created_by_id = Auth::user()->id;
				$job_order_issued_parts->created_at = Carbon::now();
				$job_order_issued_parts->updated_at = NULL;
			} else {
				$bal_qty = ($request->tot_qty - ($request->already_issued_qty - $request->issued_part_edit_qty));
				if ($request->issued_qty > $bal_qty) {
					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => [
							'Issued Quantity Exceed Requested Quantity',
						],
					]);
				}
				$job_order_issued_parts = JobOrderIssuedPart::withTrashed()->find($request->id);
				$job_order_issued_parts->updated_by_id = Auth::user()->id;
				$job_order_issued_parts->updated_at = Carbon::now();
			}

			$job_order_issued_parts->fill($request->all());
			$job_order_issued_parts->save();

			DB::commit();
			if (!($request->id)) {
				return response()->json([
					'success' => true,
					'message' => 'Job Order Issued Parts Added Successfully',
				]);
			} else {
				return response()->json([
					'success' => true,
					'message' => 'Job Order Issued Parts Updated Successfully',
				]);
			}
		} catch (\Exceprion $e) {
			DB::rollBack();
			return response()->json([
				'success' => false,
				'error' => 'Server Error',
				'errors' => [
					'Error : ' . $e->getMessage() . '. Line : ' . $e->getLine() . '. File : ' . $e->getFile(),
				],
			]);
		}
	}

	public function getIssedParts(Request $request) {
		$job_card = JobCard::select('job_order_id')->where('id', $request->id)->first();
		$this->data['issued_parts_details'] = JobOrderIssuedPart::select('job_order_issued_parts.id', 'parts.code', 'job_order_parts.id', 'job_order_parts.qty', 'job_order_issued_parts.issued_qty', DB::raw('DATE_FORMAT(job_order_issued_parts.created_at,"%d-%m-%Y") as date'), 'users.name as issued_to', 'configs.name as config_name')
			->leftJoin('job_order_parts', 'job_order_parts.id', 'job_order_issued_parts.job_order_part_id')
			->leftJoin('parts', 'parts.id', 'job_order_parts.part_id')
			->leftJoin('users', 'users.id', 'job_order_issued_parts.issued_to_id')
			->leftJoin('configs', 'configs.id', 'job_order_issued_parts.issued_mode_id')
			->where('job_order_parts.job_order_id', $job_card->job_order_id)->groupBy('job_order_issued_parts.id')->get();
		return response()->json($this->data);
	}

	public function deleteIssuedPart(Request $request) {
		DB::beginTransaction();
		try {
			$issued_parts_details = JobOrderIssuedPart::withTrashed()->where('id', $request->id)->forceDelete();
			if ($issued_parts_details) {
				DB::commit();
				return response()->json([
					'success' => true,
					'message' => 'Issued Part Deleted Successfully',
				]);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

}

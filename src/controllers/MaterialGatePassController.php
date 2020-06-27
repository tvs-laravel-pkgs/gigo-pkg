<?php

namespace Abs\GigoPkg;
use App\GatePass;
use App\Http\Controllers\Controller;
use Auth;
use DB;
use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;

class MaterialGatePassController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	public function getMaterialGatePassList(Request $request) {

		$material_gate_passes = GatePass::select([
			'gate_passes.id',
			'job_cards.job_card_number',
			'gate_pass_details.work_order_no',
			'gate_pass_details.vendor_contact_no',
			'gate_passes.number as gate_pass_no',
			'gate_passes.status_id',
			'vendors.name',
			'vendors.code',
			'configs.name as status',
			DB::raw('DATE_FORMAT(gate_passes.created_at,"%d/%m/%Y, %h:%s %p") as date_and_time'),
			DB::raw('COUNT(gate_pass_items.id) as items'),
		])
			->join('job_cards', 'gate_passes.job_card_id', 'job_cards.id')
			->join('gate_pass_details', 'gate_pass_details.gate_pass_id', 'gate_passes.id')
			->join('configs', 'configs.id', 'gate_passes.status_id')
			->join('vendors', 'gate_pass_details.vendor_id', 'vendors.id')
			->join('gate_pass_items', 'gate_pass_items.gate_pass_id', 'gate_passes.id')
			->where(function ($query) use ($request) {
				if (!empty($request->gate_pass_created_date)) {
					$query->whereDate('gate_passes.created_at', date('Y-m-d', strtotime($request->gate_pass_created_date)));
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->number)) {
					$query->where('gate_passes.number', 'LIKE', '%' . $request->number . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->job_card_number)) {
					$query->where('job_cards.job_card_number', 'LIKE', '%' . $request->job_card_number . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->work_order_no)) {
					$query->where('gate_pass_details.work_order_no', 'LIKE', '%' . $request->work_order_no . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->vendor_name)) {
					$query->where('vendors.name', 'LIKE', '%' . $request->vendor_name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->vendor_code)) {
					$query->where('vendors.code', 'LIKE', '%' . $request->vendor_code . '%');
				}
			})
			->where('job_cards.outlet_id', Auth::user()->employee->outlet_id)
			->where('gate_passes.type_id', 8281) // Material Gate Pass
			->groupBy('gate_passes.id')
		;

		return Datatables::of($material_gate_passes)
			->rawColumns(['status', 'action'])
			->editColumn('status', function ($material_gate_pass) {
				$status = $material_gate_pass->status_id == '8302' ? 'green' : 'red';
				return '<span class="text-' . $status . '">' . $material_gate_pass->status . '</span>';
			})
			->addColumn('action', function ($material_gate_pass) {
				$img1 = asset('public/themes/' . $this->data['theme'] . '/img/content/table/view.svg');
				$img1_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/view.svg');
				$output = '';
				$output .= '<a href="#!/material-gate-pass/view/' . $material_gate_pass->id . '" id = "" title="View"><img src="' . $img1 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img1 . '" onmouseout=this.src="' . $img1 . '"></a>';
				//Gate Out Pending
				if ($material_gate_pass->status_id == 8300) {
					$output .= '<button class="btn btn-secondary-dark btn-sm confirm_gate_out_' . $material_gate_pass->id . ' " onclick="angular.element(this).scope().materialGateInOut(' . $material_gate_pass->id . ',' . $material_gate_pass->status_id . ' )" title="Gate Out">Confirm Gate Out</button>';
				}
				//Gate In Pending
				if ($material_gate_pass->status_id == 8301) {
					$output .= '<button class="btn btn-secondary-dark btn-sm confirm_gate_in_' . $material_gate_pass->id . '" onclick="angular.element(this).scope().materialGateInOut(' . $material_gate_pass->id . ',' . $material_gate_pass->status_id . ')" title="Gate In">Confirm Gate In</button>';
				}
				return $output;
			})
			->make(true);
	}

}
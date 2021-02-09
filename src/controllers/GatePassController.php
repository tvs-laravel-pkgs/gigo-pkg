<?php

namespace Abs\GigoPkg;
use App\Customer;
use App\GatePass;
use App\User;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class GatePassController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	public function getGatePassList(Request $request) {
		$gate_passes = GatePass::join('configs as type', 'gate_passes.type_id', 'type.id')
			->leftJoin('configs as status', 'gate_passes.status_id', 'status.id')
			->leftJoin('job_cards', 'job_cards.id', 'gate_passes.job_card_id')
			->leftJoin('outlets', 'outlets.id', 'gate_passes.outlet_id')
			->select(
				'gate_passes.id',
				'gate_passes.status_id',
				DB::raw('DATE_FORMAT(gate_passes.created_at,"%d/%m/%Y, %h:%i %p") as date'),
				'gate_passes.number',
				'status.name as status_name',
				'outlets.code as outlet_code',
				'type.name as type_name',
				'job_cards.job_card_number'
			)
			->where(function ($query) use ($request) {
				if (!empty($request->gate_in_date)) {
					$query->whereDate('gate_passes.gate_in_date', date('Y-m-d', strtotime($request->gate_in_date)));
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->reg_no)) {
					$query->where('gate_passes.number', 'LIKE', '%' . $request->number . '%');
				}
			})

			->where(function ($query) use ($request) {
				if (!empty($request->status_id)) {
					$query->where('gate_passes.status_id', $request->status_id);
				}
			})
			->where('gate_passes.company_id', Auth::user()->company_id)
			->where('gate_passes.outlet_id', Auth::user()->working_outlet_id)
			->whereIn('gate_passes.type_id', [8282, 8283])
		;
		$gate_passes->groupBy('gate_passes.id');
		$gate_passes->orderBy('gate_passes.created_at', 'DESC');

		return Datatables::of($gate_passes)
			->addColumn('status', function ($gate_passes) {
				if ($gate_passes->status_id == 11403 || $gate_passes->status_id == 11404) {
					$status = '<p class="text-green">' . $gate_passes->status_name . '</p>';
				} elseif ($gate_passes->status_id == 11401 || $gate_passes->status_id == 11402) {
					$status = '<p class="text-blue">' . $gate_passes->status_name . '</p>';
				} else {
					$status = '<p class="text-red">' . $gate_passes->status_name . '</p>';
				}
				return $status;
			})
			->addColumn('action', function ($gate_passes) {
				$img2 = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');
				$img2_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow-active.svg');

				$img1 = asset('public/themes/' . $this->data['theme'] . '/img/content/table/view.svg');
				$img1_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/view.svg');
				$output = '';
				if ($gate_passes->status_id == '11400' && Entrust::can('edit-parts-tools-gate-pass')) {
					$output .= '<a href="#!/gate-pass/edit/' . $gate_passes->id . '" id = "" title="View"><img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2 . '" onmouseout=this.src="' . $img2 . '"></a>';
				}

				if (Entrust::can('verify-parts-tools-gate-pass') && $gate_passes->status_id == '11403') {
					$output .= '<a href="#!/gate-pass/verify/view/' . $gate_passes->id . '" id = "" title="View"><img src="' . $img1 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img1 . '" onmouseout=this.src="' . $img1 . '"></a>';
				} elseif (Entrust::can('gate-in-out-parts-tools-gate-pass') && ($gate_passes->status_id == '11400' || $gate_passes->status_id == '11402')) {
					$output .= '<a href="#!/gate-pass/approve/view/' . $gate_passes->id . '" id = "" title="View"><img src="' . $img1 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img1 . '" onmouseout=this.src="' . $img1 . '"></a>';
				} else {
					$output .= '<a href="#!/gate-pass/view/' . $gate_passes->id . '" id = "" title="View"><img src="' . $img1 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img1 . '" onmouseout=this.src="' . $img1 . '"></a>';
				}

				return $output;
			})
			->make(true);
	}

	public function getGatePassFormData(Request $request) {
		$id = $request->id;
		if (!$id) {
			$gate_pass = new GatePass;
			$action = 'Add';
		} else {
			$gate_pass = GatePass::withTrashed()->find($id);
			$action = 'Edit';
		}
		$this->data['success'] = true;
		$this->data['gate_pass'] = $gate_pass;
		$this->data['action'] = $action;
		return response()->json($this->data);
	}

	public function saveGatePass(Request $request) {
		// dd($request->all());
		try {
			$error_messages = [
				'code.required' => 'Short Name is Required',
				'code.unique' => 'Short Name is already taken',
				'code.min' => 'Short Name is Minimum 3 Charachers',
				'code.max' => 'Short Name is Maximum 32 Charachers',
				'name.required' => 'Name is Required',
				'name.unique' => 'Name is already taken',
				'name.min' => 'Name is Minimum 3 Charachers',
				'name.max' => 'Name is Maximum 191 Charachers',
			];
			$validator = Validator::make($request->all(), [
				'code' => [
					'required:true',
					'min:3',
					'max:32',
					'unique:gate_passes,code,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'name' => [
					'required:true',
					'min:3',
					'max:191',
					'unique:gate_passes,name,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$gate_pass = new GatePass;
				$gate_pass->company_id = Auth::user()->company_id;
			} else {
				$gate_pass = GatePass::withTrashed()->find($request->id);
			}
			$gate_pass->fill($request->all());
			if ($request->status == 'Inactive') {
				$gate_pass->deleted_at = Carbon::now();
			} else {
				$gate_pass->deleted_at = NULL;
			}
			$gate_pass->save();

			DB::commit();
			if (!($request->id)) {
				return response()->json([
					'success' => true,
					'message' => 'Gate Pass Added Successfully',
				]);
			} else {
				return response()->json([
					'success' => true,
					'message' => 'Gate Pass Updated Successfully',
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

	public function deleteGatePass(Request $request) {
		DB::beginTransaction();
		// dd($request->id);
		try {
			$gate_pass = GatePass::withTrashed()->where('id', $request->id)->forceDelete();
			if ($gate_pass) {
				DB::commit();
				return response()->json(['success' => true, 'message' => 'Gate Pass Deleted Successfully']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

	public function getGatePasss(Request $request) {
		$gate_passes = GatePass::withTrashed()
			->with([
				'gate-passes',
				'gate-passes.user',
			])
			->select([
				'gate_passes.id',
				'gate_passes.name',
				'gate_passes.code',
				DB::raw('IF(gate_passes.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->where('gate_passes.company_id', Auth::user()->company_id)
			->get();

		return response()->json([
			'success' => true,
			'gate_passes' => $gate_passes,
		]);
	}

	//Customer Search
	public function getCustomerSearchList(Request $request) {
		return Customer::searchCustomer($request);
	}

	//JobCard Search
	public function getJobCardSearchList(Request $request) {
		return JobCard::searchJobCard($request);
	}

	//User Search
	public function getUserSearchList(Request $request) {
		return User::searchUser($request);
	}
}
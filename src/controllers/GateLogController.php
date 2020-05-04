<?php

namespace Abs\GigoPkg;
use App\Http\Controllers\Controller;
use App\GateLog;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class GateLogController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	public function getGateLogList(Request $request) {
		$gate_logs = GateLog::withTrashed()

			->select([
				'gate_logs.id',
				'gate_logs.name',
				'gate_logs.code',

				DB::raw('IF(gate_logs.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->where('gate_logs.company_id', Auth::user()->company_id)

			->where(function ($query) use ($request) {
				if (!empty($request->name)) {
					$query->where('gate_logs.name', 'LIKE', '%' . $request->name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if ($request->status == '1') {
					$query->whereNull('gate_logs.deleted_at');
				} else if ($request->status == '0') {
					$query->whereNotNull('gate_logs.deleted_at');
				}
			})
		;

		return Datatables::of($gate_logs)
			->rawColumns(['name', 'action'])
			->addColumn('name', function ($gate_log) {
				$status = $gate_log->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indicator ' . $status . '"></span>' . $gate_log->name;
			})
			->addColumn('action', function ($gate_log) {
				$img1 = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');
				$img1_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow-active.svg');
				$img_delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				$img_delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');
				$output = '';
				if (Entrust::can('edit-gate_log')) {
					$output .= '<a href="#!/gigo-pkg/gate_log/edit/' . $gate_log->id . '" id = "" title="Edit"><img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1 . '" onmouseout=this.src="' . $img1 . '"></a>';
				}
				if (Entrust::can('delete-gate_log')) {
					$output .= '<a href="javascript:;" data-toggle="modal" data-target="#gate_log-delete-modal" onclick="angular.element(this).scope().deleteGateLog(' . $gate_log->id . ')" title="Delete"><img src="' . $img_delete . '" alt="Delete" class="img-responsive delete" onmouseover=this.src="' . $img_delete . '" onmouseout=this.src="' . $img_delete . '"></a>';
				}
				return $output;
			})
			->make(true);
	}

	public function getGateLogFormData(Request $request) {
		$id = $request->id;
		if (!$id) {
			$gate_log = new GateLog;
			$action = 'Add';
		} else {
			$gate_log = GateLog::withTrashed()->find($id);
			$action = 'Edit';
		}
		$this->data['success'] = true;
		$this->data['gate_log'] = $gate_log;
		$this->data['action'] = $action;
		return response()->json($this->data);
	}

	public function saveGateLog(Request $request) {
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
					'unique:gate_logs,code,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'name' => [
					'required:true',
					'min:3',
					'max:191',
					'unique:gate_logs,name,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$gate_log = new GateLog;
				$gate_log->company_id = Auth::user()->company_id;
			} else {
				$gate_log = GateLog::withTrashed()->find($request->id);
			}
			$gate_log->fill($request->all());
			if ($request->status == 'Inactive') {
				$gate_log->deleted_at = Carbon::now();
			} else {
				$gate_log->deleted_at = NULL;
			}
			$gate_log->save();

			DB::commit();
			if (!($request->id)) {
				return response()->json([
					'success' => true,
					'message' => 'Gate Log Added Successfully',
				]);
			} else {
				return response()->json([
					'success' => true,
					'message' => 'Gate Log Updated Successfully',
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

	public function deleteGateLog(Request $request) {
		DB::beginTransaction();
		// dd($request->id);
		try {
			$gate_log = GateLog::withTrashed()->where('id', $request->id)->forceDelete();
			if ($gate_log) {
				DB::commit();
				return response()->json(['success' => true, 'message' => 'Gate Log Deleted Successfully']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

	public function getGateLogs(Request $request) {
		$gate_logs = GateLog::withTrashed()
			->with([
				'gate-logs',
				'gate-logs.user',
			])
			->select([
				'gate_logs.id',
				'gate_logs.name',
				'gate_logs.code',
				DB::raw('IF(gate_logs.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->where('gate_logs.company_id', Auth::user()->company_id)
			->get();

		return response()->json([
			'success' => true,
			'gate_logs' => $gate_logs,
		]);
	}
}
<?php

namespace Abs\GigoPkg;
use App\Http\Controllers\Controller;
use App\MechanicTimeLog;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class MechanicTimeLogController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	public function getMechanicTimeLogList(Request $request) {
		$mechanic_time_logs = MechanicTimeLog::withTrashed()

			->select([
				'mechanic_time_logs.id',
				'mechanic_time_logs.name',
				'mechanic_time_logs.code',

				DB::raw('IF(mechanic_time_logs.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->where('mechanic_time_logs.company_id', Auth::user()->company_id)

			->where(function ($query) use ($request) {
				if (!empty($request->name)) {
					$query->where('mechanic_time_logs.name', 'LIKE', '%' . $request->name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if ($request->status == '1') {
					$query->whereNull('mechanic_time_logs.deleted_at');
				} else if ($request->status == '0') {
					$query->whereNotNull('mechanic_time_logs.deleted_at');
				}
			})
		;

		return Datatables::of($mechanic_time_logs)
			->rawColumns(['name', 'action'])
			->addColumn('name', function ($mechanic_time_log) {
				$status = $mechanic_time_log->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indicator ' . $status . '"></span>' . $mechanic_time_log->name;
			})
			->addColumn('action', function ($mechanic_time_log) {
				$img1 = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');
				$img1_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow-active.svg');
				$img_delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				$img_delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');
				$output = '';
				if (Entrust::can('edit-mechanic_time_log')) {
					$output .= '<a href="#!/gigo-pkg/mechanic_time_log/edit/' . $mechanic_time_log->id . '" id = "" title="Edit"><img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1 . '" onmouseout=this.src="' . $img1 . '"></a>';
				}
				if (Entrust::can('delete-mechanic_time_log')) {
					$output .= '<a href="javascript:;" data-toggle="modal" data-target="#mechanic_time_log-delete-modal" onclick="angular.element(this).scope().deleteMechanicTimeLog(' . $mechanic_time_log->id . ')" title="Delete"><img src="' . $img_delete . '" alt="Delete" class="img-responsive delete" onmouseover=this.src="' . $img_delete . '" onmouseout=this.src="' . $img_delete . '"></a>';
				}
				return $output;
			})
			->make(true);
	}

	public function getMechanicTimeLogFormData(Request $request) {
		$id = $request->id;
		if (!$id) {
			$mechanic_time_log = new MechanicTimeLog;
			$action = 'Add';
		} else {
			$mechanic_time_log = MechanicTimeLog::withTrashed()->find($id);
			$action = 'Edit';
		}
		$this->data['success'] = true;
		$this->data['mechanic_time_log'] = $mechanic_time_log;
		$this->data['action'] = $action;
		return response()->json($this->data);
	}

	public function saveMechanicTimeLog(Request $request) {
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
					'unique:mechanic_time_logs,code,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'name' => [
					'required:true',
					'min:3',
					'max:191',
					'unique:mechanic_time_logs,name,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$mechanic_time_log = new MechanicTimeLog;
				$mechanic_time_log->company_id = Auth::user()->company_id;
			} else {
				$mechanic_time_log = MechanicTimeLog::withTrashed()->find($request->id);
			}
			$mechanic_time_log->fill($request->all());
			if ($request->status == 'Inactive') {
				$mechanic_time_log->deleted_at = Carbon::now();
			} else {
				$mechanic_time_log->deleted_at = NULL;
			}
			$mechanic_time_log->save();

			DB::commit();
			if (!($request->id)) {
				return response()->json([
					'success' => true,
					'message' => 'Mechanic Time Log Added Successfully',
				]);
			} else {
				return response()->json([
					'success' => true,
					'message' => 'Mechanic Time Log Updated Successfully',
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

	public function deleteMechanicTimeLog(Request $request) {
		DB::beginTransaction();
		// dd($request->id);
		try {
			$mechanic_time_log = MechanicTimeLog::withTrashed()->where('id', $request->id)->forceDelete();
			if ($mechanic_time_log) {
				DB::commit();
				return response()->json(['success' => true, 'message' => 'Mechanic Time Log Deleted Successfully']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

	public function getMechanicTimeLogs(Request $request) {
		$mechanic_time_logs = MechanicTimeLog::withTrashed()
			->with([
				'mechanic-time-logs',
				'mechanic-time-logs.user',
			])
			->select([
				'mechanic_time_logs.id',
				'mechanic_time_logs.name',
				'mechanic_time_logs.code',
				DB::raw('IF(mechanic_time_logs.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->where('mechanic_time_logs.company_id', Auth::user()->company_id)
			->get();

		return response()->json([
			'success' => true,
			'mechanic_time_logs' => $mechanic_time_logs,
		]);
	}
}
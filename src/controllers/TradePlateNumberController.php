<?php

namespace Abs\GigoPkg;
use App\Http\Controllers\Controller;
use App\Outlet;
use App\TradePlateNumber;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class TradePlateNumberController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	public function getTradePlateNumberFilter() {
		$this->data['extras'] = [
			'status' => [
				['id' => '', 'name' => 'Select Status'],
				['id' => '1', 'name' => 'Active'],
				['id' => '0', 'name' => 'Inactive'],
			],

			'outlet_list' => collect(Outlet::select('id', 'code')->where('company_id', Auth::user()->company_id)->get())->prepend(['id' => '', 'code' => 'Select Outlet']),
		];

		return response()->json($this->data);
	}

	public function getTradePlateNumberList(Request $request) {

		if ($request->date_range) {
			$date_range = explode(' to ', $request->date_range);
			$start_date = date('Y-m-d', strtotime($date_range[0]));
			$end_date = date('Y-m-d', strtotime($date_range[1]));
		} else {
			$start_date = '';
			$end_date = '';
		}

		$trade_plate_numbers = TradePlateNumber::withTrashed()

			->select([
				'trade_plate_numbers.id',
				'trade_plate_numbers.trade_plate_number',
				'outlets.code',
				'trade_plate_numbers.insurance_validity_from',
				'trade_plate_numbers.insurance_validity_to',

				DB::raw('IF(trade_plate_numbers.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->join('outlets', 'outlets.id', 'trade_plate_numbers.outlet_id')
			->where('trade_plate_numbers.company_id', Auth::user()->company_id)

			->where(function ($query) use ($request) {
				if (!empty($request->outlet_id) && $request->outlet_id != '<%$ctrl.outlet_id%>') {
					$query->where('trade_plate_numbers.outlet_id', $request->outlet_id);
				}
			})

			->where(function ($query) use ($start_date, $end_date) {
				if ($start_date && $end_date) {
					$query->whereDate('trade_plate_numbers.insurance_validity_from', '>=', $start_date)
						->whereDate('trade_plate_numbers.insurance_validity_to', '<=', $end_date);
				}
			})

			->where(function ($query) use ($request) {
				if ($request->status == '1') {
					$query->whereNull('trade_plate_numbers.deleted_at');
				} else if ($request->status == '0') {
					$query->whereNotNull('trade_plate_numbers.deleted_at');
				}
			})
		;

		return Datatables::of($trade_plate_numbers)

			->addColumn('status', function ($trade_plate_numbers) {
				$status = $trade_plate_numbers->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indicator ' . $status . '"></span>' . $trade_plate_numbers->status;
			})

			->addColumn('insurance_validity_status', function ($trade_plate_numbers) {
				$status = '';
				$current_date_time_stamp = strtotime(date('d-m-Y'));
				$validity_from_date_time_stamp = strtotime($trade_plate_numbers->insurance_validity_from);
				$validity_to_date_time_stamp = strtotime($trade_plate_numbers->insurance_validity_to);

				if ($trade_plate_numbers->insurance_validity_from || $trade_plate_numbers->insurance_validity_to) {
					if (($current_date_time_stamp >= $validity_from_date_time_stamp) && ($current_date_time_stamp <= $validity_to_date_time_stamp)) {
						$status = '<span style="color:#28a745">Active</span>';
					} else {
						if ($current_date_time_stamp > $validity_to_date_time_stamp) {
							$status = '<span style="color:#dc3545">Expired</span>';
						} else {
							$status = '<span style="color:#007bff">Upcoming</span>';
						}
					}
				} else {
					$status = '<span>-</span>';
				}
				return $status;
			})

			->addColumn('action', function ($trade_plate_numbers) {
				$img1 = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');
				$img1_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow-active.svg');
				$img_delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				$img_delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');
				$action = '';

				if (Entrust::can('edit-trade-plate-number')) {
					$action .= '<a href="#!/trade-plate-number/edit/' . $trade_plate_numbers->id . '" id = "" title="Edit"><img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1 . '" onmouseout=this.src="' . $img1 . '"></a>';

				}

				if (Entrust::can('delete-trade-plate-number')) {
					$action .= '<a href="javascript:;" data-toggle="modal" data-target="#delete_trade_plate_number" onclick="angular.element(this).scope().deleteTradePlateNumber(' . $trade_plate_numbers->id . ')" title="Delete"><img src="' . $img_delete . '" alt="Delete" class="img-responsive delete" onmouseover=this.src="' . $img_delete . '" onmouseout=this.src="' . $img_delete . '"></a>';

				}
				return $action;
			})
			->make(true);
	}

	public function getTradePlateNumberFormData(Request $request) {
		$id = $request->id;
		if (!$id) {
			$trade_plate_number_data = new TradePlateNumber;
			$action = 'Add';
		} else {
			$trade_plate_number_data = TradePlateNumber::withTrashed()->find($id);
			$action = 'Edit';
		}
		$this->data['success'] = true;
		$this->data['trade_plate_number_data'] = $trade_plate_number_data;
		$this->data['outlet_list'] = collect(Outlet::select('code', 'id')->where('company_id', Auth::user()->company_id)->get())->prepend(['id' => '', 'code' => 'Select Outlet']);
		$this->data['action'] = $action;
		return response()->json($this->data);
	}

	public function saveTradePlateNumber(Request $request) {
		// dd($request->all());
		try {
			$error_messages = [
				'trade_plate_number.required' => 'Trade Plate Number is Required',
				'trade_plate_number.unique' => 'Trade Plate Number is already taken',
				'trade_plate_number.min' => 'Trade Plate Number is Minimum 3 Charachers',
				'trade_plate_number.max' => 'Trade Plate Number is Maximum 64 Charachers',
				'outlet_id.required' => 'Outlet is Required',
				'insurance_periods.required' => 'Insurance Period is Required',
			];
			$validator = Validator::make($request->all(), [
				'trade_plate_number' => [
					'required:true',
					'min:3',
					'max:64',
					'unique:trade_plate_numbers,trade_plate_number,' . $request->id . ',id,company_id,' . Auth::user()->company_id . ',outlet_id,' . $request->outlet_id,
				],
				'outlet_id' => [
					'required:true',
				],
				'insurance_periods' => [
					'required:true',
				],
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			$insurance_periods = explode(' to ', $request->insurance_periods);

			DB::beginTransaction();
			if (!$request->id) {
				$trade_plate_number = new TradePlateNumber;
				$trade_plate_number->company_id = Auth::user()->company_id;
				$trade_plate_number->created_by_id = Auth::user()->id;
				$trade_plate_number->created_at = Carbon::now();
				$trade_plate_number->status_id = 8240;
			} else {
				$trade_plate_number = TradePlateNumber::withTrashed()->find($request->id);
				$trade_plate_number->updated_by_id = Auth::user()->id;
				$trade_plate_number->updated_at = Carbon::now();
			}

			$trade_plate_number->trade_plate_number = $request->trade_plate_number;
			$trade_plate_number->outlet_id = $request->outlet_id;
			$trade_plate_number->insurance_validity_from = date('Y-m-d', strtotime($insurance_periods[0]));
			$trade_plate_number->insurance_validity_to = date('Y-m-d', strtotime($insurance_periods[1]));

			if ($request->status == 'Inactive') {
				$trade_plate_number->deleted_at = Carbon::now();
			} else {
				$trade_plate_number->deleted_at = NULL;
			}
			$trade_plate_number->save();

			DB::commit();
			if (!($request->id)) {
				return response()->json([
					'success' => true,
					'message' => 'Estimation Type Added Successfully',
				]);
			} else {
				return response()->json([
					'success' => true,
					'message' => 'Estimation Type Updated Successfully',
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

	public function deleteTradePlateNumber(Request $request) {
		DB::beginTransaction();
		// dd($request->id);
		try {
			$estimation_type = TradePlateNumber::withTrashed()->where('id', $request->id)->forceDelete();
			if ($estimation_type) {
				DB::commit();
				return response()->json(['success' => true, 'message' => 'Trade Plate Number Deleted Successfully']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}
}
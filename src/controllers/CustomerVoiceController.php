<?php

namespace Abs\GigoPkg;
use App\Http\Controllers\Controller;
use App\CustomerVoice;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class CustomerVoiceController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	public function getCustomerVoiceList(Request $request) {
		$customer_voices = CustomerVoice::withTrashed()

			->select([
				'customer_voices.id',
				'customer_voices.name',
				'customer_voices.code',

				DB::raw('IF(customer_voices.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->where('customer_voices.company_id', Auth::user()->company_id)

			->where(function ($query) use ($request) {
				if (!empty($request->name)) {
					$query->where('customer_voices.name', 'LIKE', '%' . $request->name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if ($request->status == '1') {
					$query->whereNull('customer_voices.deleted_at');
				} else if ($request->status == '0') {
					$query->whereNotNull('customer_voices.deleted_at');
				}
			})
		;

		return Datatables::of($customer_voices)
			->rawColumns(['name', 'action'])
			->addColumn('name', function ($customer_voice) {
				$status = $customer_voice->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indicator ' . $status . '"></span>' . $customer_voice->name;
			})
			->addColumn('action', function ($customer_voice) {
				$img1 = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');
				$img1_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow-active.svg');
				$img_delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				$img_delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');
				$output = '';
				if (Entrust::can('edit-customer_voice')) {
					$output .= '<a href="#!/gigo-pkg/customer_voice/edit/' . $customer_voice->id . '" id = "" title="Edit"><img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1 . '" onmouseout=this.src="' . $img1 . '"></a>';
				}
				if (Entrust::can('delete-customer_voice')) {
					$output .= '<a href="javascript:;" data-toggle="modal" data-target="#customer_voice-delete-modal" onclick="angular.element(this).scope().deleteCustomerVoice(' . $customer_voice->id . ')" title="Delete"><img src="' . $img_delete . '" alt="Delete" class="img-responsive delete" onmouseover=this.src="' . $img_delete . '" onmouseout=this.src="' . $img_delete . '"></a>';
				}
				return $output;
			})
			->make(true);
	}

	public function getCustomerVoiceFormData(Request $request) {
		$id = $request->id;
		if (!$id) {
			$customer_voice = new CustomerVoice;
			$action = 'Add';
		} else {
			$customer_voice = CustomerVoice::withTrashed()->find($id);
			$action = 'Edit';
		}
		$this->data['success'] = true;
		$this->data['customer_voice'] = $customer_voice;
		$this->data['action'] = $action;
		return response()->json($this->data);
	}

	public function saveCustomerVoice(Request $request) {
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
					'unique:customer_voices,code,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'name' => [
					'required:true',
					'min:3',
					'max:191',
					'unique:customer_voices,name,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$customer_voice = new CustomerVoice;
				$customer_voice->company_id = Auth::user()->company_id;
			} else {
				$customer_voice = CustomerVoice::withTrashed()->find($request->id);
			}
			$customer_voice->fill($request->all());
			if ($request->status == 'Inactive') {
				$customer_voice->deleted_at = Carbon::now();
			} else {
				$customer_voice->deleted_at = NULL;
			}
			$customer_voice->save();

			DB::commit();
			if (!($request->id)) {
				return response()->json([
					'success' => true,
					'message' => 'Customer Voice Added Successfully',
				]);
			} else {
				return response()->json([
					'success' => true,
					'message' => 'Customer Voice Updated Successfully',
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

	public function deleteCustomerVoice(Request $request) {
		DB::beginTransaction();
		// dd($request->id);
		try {
			$customer_voice = CustomerVoice::withTrashed()->where('id', $request->id)->forceDelete();
			if ($customer_voice) {
				DB::commit();
				return response()->json(['success' => true, 'message' => 'Customer Voice Deleted Successfully']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

	public function getCustomerVoices(Request $request) {
		$customer_voices = CustomerVoice::withTrashed()
			->with([
				'customer-voices',
				'customer-voices.user',
			])
			->select([
				'customer_voices.id',
				'customer_voices.name',
				'customer_voices.code',
				DB::raw('IF(customer_voices.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->where('customer_voices.company_id', Auth::user()->company_id)
			->get();

		return response()->json([
			'success' => true,
			'customer_voices' => $customer_voices,
		]);
	}
}
<?php

namespace Abs\GigoPkg;
use Abs\GigoPkg\Campaign;
use Abs\GigoPkg\RepairOrder;
use Abs\PartPkg\Part;
use App\Config;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class CompaignController extends Controller {
	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	public function getCampaignFilterData() {
		$this->data['extras'] = [
			'status' => [
				['id' => '', 'name' => 'Select Status'],
				['id' => '1', 'name' => 'Active'],
				['id' => '0', 'name' => 'Inactive'],
			],
		];
		return response()->json($this->data);
	}

	public function getCampaignList(Request $request) {
		$compaigns = Campaign::withTrashed()
			->join('configs', 'configs.id', 'compaigns.claim_type_id')
			->select([
				'compaigns.id',
				'compaigns.authorisation_no',
				'compaigns.complaint_code',
				'compaigns.fault_code',
				'configs.name as claim_type_name',
				DB::raw('IF(compaigns.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->where('compaigns.company_id', Auth::user()->company_id)
		// ->where(function ($query) use ($request) {
		// 	if (!empty($request->authorisation_no)) {
		// 		$query->where('compaigns.authorisation_no', 'LIKE', '%' . $request->authorisation_no . '%');
		// 	}
		// })
		// ->where(function ($query) use ($request) {
		// 	if (!empty($request->complaint_code)) {
		// 		$query->where('complaint_code.complaint_code', 'LIKE', '%' . $request->complaint_code . '%');
		// 	}
		// })
		// ->where(function ($query) use ($request) {
		// 	if ($request->status == '1') {
		// 		$query->whereNull('compaigns.deleted_at');
		// 	} else if ($request->status == '0') {
		// 		$query->whereNotNull('compaigns.deleted_at');
		// 	}
		// })
		;

		return Datatables::of($compaigns)
			->addColumn('status', function ($compaigns) {
				$status = $compaigns->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indigator ' . $status . '"></span>' . $compaigns->status;
			})
			->addColumn('action', function ($compaigns) {
				$img1 = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');
				$img1_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow-active.svg');
				$img_delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				$img_delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');
				$output = '';
				if (Entrust::can('edit-service-type')) {
					$output .= '<a href="#!/gigo-pkg/compaigns/edit/' . $compaigns->id . '" id = "" title="Edit"><img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1 . '" onmouseout=this.src="' . $img1 . '"></a>';
				}
				if (Entrust::can('delete-service-type')) {
					$output .= '<a href="javascript:;" data-toggle="modal" data-target="#service_type-delete-modal" onclick="angular.element(this).scope().deleteServiceType(' . $compaigns->id . ')" title="Delete"><img src="' . $img_delete . '" alt="Delete" class="img-responsive delete" onmouseover=this.src="' . $img_delete . '" onmouseout=this.src="' . $img_delete . '"></a>';
				}
				return $output;
			})
			->make(true);
	}

	public function getCampaignFormData(Request $request) {
		$id = $request->id;
		if (!$id) {
			$service_type = new Campaign;
			$service_type->campaign_labours = [];
			$service_type->campaign_parts = [];
			$action = 'Add';
		} else {
			$service_type = Campaign::withTrashed()->find($id);
			$service_type->campaign_labours = $labours = $service_type->campaignLabours()->select('id')->get();
			if ($service_type->campaign_labours) {
				foreach ($labours as $key => $labour) {
					$service_type->campaign_labours[$key]->name = RepairOrder::join('repair_order_types', 'repair_order_types.id', 'repair_orders.type_id')->join('compaign_repair_order', 'compaign_repair_order.repair_order_id', 'repair_orders.id')->where('compaign_repair_order.compaign_id', $id)->where('repair_orders.id', $labour->id)->select('repair_orders.id', 'repair_orders.code', 'compaign_repair_order.amount', 'repair_order_types.name as repair_order_type')->first();
				}
			}

			$service_type->campaign_parts = $parts = $service_type->campaignParts()->select('id')->get();
			if ($service_type->campaign_parts) {
				foreach ($parts as $key => $part) {
					$service_type->campaign_parts[$key]->name = Part::join('tax_codes', 'tax_codes.id', 'parts.tax_code_id')->join('compaign_part', 'compaign_part.part_id', 'parts.id')->where('parts.id', $part->id)->where('compaign_part.compaign_id', $id)->select('parts.id', 'parts.code', 'parts.name', 'tax_codes.code as tax_code_type')->first();
				}
			}

			$action = 'Edit';
		}

		$params = [
			'config_type_id' => 121,
			'add_default' => true,
			'default_text' => "Select Type",
		];

		$this->data['success'] = true;
		$this->data['service_type'] = $service_type;
		$this->data['claim_types'] = Config::getDropDownList($params);
		$this->data['action'] = $action;
		return response()->json($this->data);
	}

	public function saveCampaign(Request $request) {
		try {
			$error_messages = [
				'authorisation_no.required' => 'Authorization Code is Required',
				'authorisation_no.unique' => 'Authorization Code is already taken',
				'authorisation_no.min' => 'Authorization Code is Minimum 3 Charachers',
				'authorisation_no.max' => 'Authorization Code is Maximum 32 Charachers',
				'complaint_code.min' => 'Complaint Code is Minimum 3 Charachers',
				'complaint_code.max' => 'Complaint Code is Maximum 32 Charachers',
				'fault_code.min' => 'Fault Code is Minimum 3 Charachers',
				'fault_code.max' => 'Fault Code is Maximum 32 Charachers',

			];
			$validator = Validator::make($request->all(), [
				'authorisation_no' => [
					'required:true',
					'min:3',
					'max:32',
					'unique:compaigns,authorisation_no,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'complaint_code' => [
					'nullable',
					'min:3',
					'max:191',
				],
				'fault_code' => [
					'nullable',
					'min:3',
					'max:191',
				],
				'labours.*.id' => [
					'required',
					'integer',
					'exists:repair_orders,id',
					'distinct',
				],
				'parts.*.id' => [
					'required',
					'integer',
					'exists:parts,id',
					'distinct',
				],
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$service_type = new Campaign;
				$service_type->created_by_id = Auth::user()->id;
				$service_type->created_at = Carbon::now();
				$service_type->updated_at = NULL;
			} else {
				$service_type = Campaign::withTrashed()->find($request->id);
				$service_type->updated_by_id = Auth::user()->id;
				$service_type->updated_at = Carbon::now();
			}
			$service_type->fill($request->all());
			$service_type->company_id = Auth::user()->company_id;
			if ($request->status == 'Inactive') {
				$service_type->deleted_at = Carbon::now();
				$service_type->deleted_by_id = Auth::user()->id;
			} else {
				$service_type->deleted_by_id = NULL;
				$service_type->deleted_at = NULL;
			}
			$service_type->save();

			$service_type->campaignLabours()->sync([]);
			$service_type->campaignParts()->sync([]);

			if ($request->labours) {
				$total_labours = array_column($request->labours, 'id');
				$total_labours_unique = array_unique($total_labours);
				if (count($total_labours) != count($total_labours_unique)) {
					return response()->json(['success' => false, 'errors' => ['Labours already been taken']]);
				}

				foreach ($request->labours as $labour) {
					$service_type->campaignLabours()->attach($labour['id'], ['amount' => $labour['amount']]);
				}
			}

			if ($request->parts) {
				$total_parts = array_column($request->parts, 'id');
				$total_parts_unique = array_unique($total_parts);
				if (count($total_parts) != count($total_parts_unique)) {
					return response()->json(['success' => false, 'errors' => ['Parts already been taken']]);
				}

				foreach ($request->parts as $parts) {
					$service_type->campaignParts()->attach($parts['id']);
				}
			}

			DB::commit();
			if (!($request->id)) {
				return response()->json([
					'success' => true,
					'message' => 'Campaign Added Successfully',
				]);
			} else {
				return response()->json([
					'success' => true,
					'message' => 'Campaign Updated Successfully',
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

	public function deleteCampaign(Request $request) {
		DB::beginTransaction();
		// dd($request->id);
		try {
			$service_type = Campaign::withTrashed()->where('id', $request->id)->forceDelete();
			if ($service_type) {
				DB::commit();
				return response()->json(['success' => true, 'message' => 'Campaign Deleted Successfully']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}
}
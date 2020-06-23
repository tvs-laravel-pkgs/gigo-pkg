<?php

namespace Abs\GigoPkg;
use Abs\GigoPkg\Campaign;
use Abs\GigoPkg\CampaignChassisNumber;
use Abs\GigoPkg\Complaint;
use Abs\GigoPkg\Fault;
use Abs\GigoPkg\RepairOrder;
use Abs\PartPkg\Part;
use App\Config;
use App\Http\Controllers\Controller;
use App\VehicleModel;
use Auth;
use Carbon\Carbon;
use DB;
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
			->join('faults', 'faults.id', 'compaigns.fault_id')
			->join('complaints', 'complaints.id', 'compaigns.complaint_id')
			->leftJoin('models', 'models.id', 'compaigns.vehicle_model_id')
			->select([
				'compaigns.id',
				'compaigns.authorisation_no',
				'complaints.name as complaint_type',
				'faults.name as fault_type',
				'configs.name as claim_type_name',
				'models.model_name as vehicle_model',
				DB::raw('IF(compaigns.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->where('compaigns.company_id', Auth::user()->company_id)
		// ->where(function ($query) use ($request) {
		// 	if (!empty($request->authorisation_code)) {
		// 		$query->where('compaigns.authorisation_no', 'LIKE', '%' . $request->authorisation_no . '%');
		// 	}
		// })
		// ->where(function ($query) use ($request) {
		// 	if (!empty($request->complaint_code)) {
		// 		$query->where('compaigns.complaint_code', 'LIKE', '%' . $request->complaint_code . '%');
		// 	}
		// })
		// ->where(function ($query) use ($request) {
		// 	if (!empty($request->fault_code)) {
		// 		$query->where('compaigns.fault_code', 'LIKE', '%' . $request->fault_code . '%');
		// 	}
		// })
			->where(function ($query) use ($request) {
				if ($request->status == '1') {
					$query->whereNull('compaigns.deleted_at');
				} else if ($request->status == '0') {
					$query->whereNotNull('compaigns.deleted_at');
				}
			})
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
				/*if (Entrust::can('edit-campaign')) {*/
				$output .= '<a href="#!/gigo-pkg/campaign/edit/' . $compaigns->id . '" id = "" title="Edit"><img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1 . '" onmouseout=this.src="' . $img1 . '"></a>';
				/*}*/
				/*if (Entrust::can('delete-campaign')) {*/
				$output .= '<a href="javascript:;" data-toggle="modal" data-target="#campaign-delete-modal" onclick="angular.element(this).scope().deleteCampaign(' . $compaigns->id . ')" title="Delete"><img src="' . $img_delete . '" alt="Delete" class="img-responsive delete" onmouseover=this.src="' . $img_delete . '" onmouseout=this.src="' . $img_delete . '"></a>';
				/*}*/
				return $output;
			})
			->make(true);
	}

	public function getCampaignFormData(Request $request) {
		$id = $request->id;
		if (!$id) {
			$campaign = new Campaign;
			$campaign->campaign_labours = [];
			$campaign->campaign_parts = [];
			$this->data['chassis_number'] = [];
			$action = 'Add';
		} else {
			$campaign = Campaign::withTrashed()->with([
				'vehicleModel',
			])->find($id);

			$this->data['chassis_number'] = CampaignChassisNumber::where('campaign_id', $id)->orderBy('id', 'ASC')->get();

			$campaign->campaign_labours = $labours = $campaign->campaignLabours()->select('id')->get();
			if ($campaign->campaign_labours) {
				foreach ($labours as $key => $labour) {
					$campaign->campaign_labours[$key]->name = RepairOrder::join('repair_order_types', 'repair_order_types.id', 'repair_orders.type_id')->join('compaign_repair_order', 'compaign_repair_order.repair_order_id', 'repair_orders.id')->where('compaign_repair_order.compaign_id', $id)->where('repair_orders.id', $labour->id)->select('repair_orders.id', 'repair_orders.code', 'compaign_repair_order.amount', 'repair_order_types.name as repair_order_type')->first();
				}
			}

			$campaign->campaign_parts = $parts = $campaign->campaignParts()->select('id')->get();
			if ($campaign->campaign_parts) {
				foreach ($parts as $key => $part) {
					$campaign->campaign_parts[$key]->name = Part::join('tax_codes', 'tax_codes.id', 'parts.tax_code_id')->join('compaign_part', 'compaign_part.part_id', 'parts.id')->where('parts.id', $part->id)->where('compaign_part.compaign_id', $id)->select('parts.id', 'parts.code', 'parts.name', 'tax_codes.code as tax_code_type')->first();
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
		$this->data['campaign'] = $campaign;
		$this->data['claim_types'] = Config::getDropDownList($params);
		$this->data['complaint_types'] = Complaint::getList();
		$this->data['fault_types'] = Fault::getList();
		$this->data['action'] = $action;
		return response()->json($this->data);
	}

	public function saveCampaign(Request $request) {
		// dd($request->all());
		try {
			$error_messages = [
				'authorisation_no.required' => 'Authorization Code is Required',
				'authorisation_no.unique' => 'Authorization Code is already taken',
				'authorisation_no.min' => 'Authorization Code is Minimum 3 Charachers',
				'authorisation_no.max' => 'Authorization Code is Maximum 32 Charachers',
				//'manufacture_date.required' => 'Manufacture Date is Required',
				//'vehicle_model_id.required' => 'Vehicle Model is Required',
				'complaint_id.required' => 'Complaint Type is Required',
				'fault_id.required' => 'Fault Type is Required',
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
				'vehicle_model_id' => [
					'required_if:campaign_type,==,0',
					// 'integer',
					// 'exists:models,id',
				],
				'complaint_id' => [
					'required',
					'integer',
					'exists:complaints,id',
				],
				'fault_id' => [
					'required',
					'integer',
					'exists:faults,id',
				],
				'chassis_number.*' => [
					'required:true',
					'min:3',
					'max:64',
				],
				'manufacture_date' => [
					'required_if:campaign_type,==,1',
				],
			], $error_messages);
			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'errors' => $validator->errors()->all(),
				]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$campaign = new Campaign;
				$campaign->created_by_id = Auth::user()->id;
				$campaign->created_at = Carbon::now();
				$campaign->updated_at = NULL;
			} else {
				$campaign = Campaign::withTrashed()->find($request->id);
				$campaign->updated_by_id = Auth::user()->id;
				$campaign->updated_at = Carbon::now();
			}
			$campaign->fill($request->all());
			$campaign->company_id = Auth::user()->company_id;
			if ($request->status == 'Inactive') {
				$campaign->deleted_at = Carbon::now();
				$campaign->deleted_by_id = Auth::user()->id;
			} else {
				$campaign->deleted_by_id = NULL;
				$campaign->deleted_at = NULL;
			}
			$campaign->save();

			$campaign->campaignLabours()->sync([]);
			$campaign->campaignParts()->sync([]);

			if ($request->labours) {
				$total_labours = array_column($request->labours, 'id');
				$total_labours_unique = array_unique($total_labours);
				if (count($total_labours) != count($total_labours_unique)) {
					return response()->json([
						'success' => false,
						'errors' => [
							'Labours already been taken',
						],
					]);
				}

				foreach ($request->labours as $labour) {
					$campaign->campaignLabours()->attach($labour['id'], ['amount' => $labour['amount']]);
				}
			}

			if ($request->parts) {
				$total_parts = array_column($request->parts, 'id');
				$total_parts_unique = array_unique($total_parts);
				if (count($total_parts) != count($total_parts_unique)) {
					return response()->json([
						'success' => false,
						'errors' => [
							'Parts already been taken',
						],
					]);
				}

				foreach ($request->parts as $parts) {
					$campaign->campaignParts()->attach($parts['id']);
				}
			}
			//DELETE CHASSIS NUMBERS
			if (!empty($request->remove_chassis_ids)) {
				$remove_chassis_ids = json_decode($request->remove_chassis_ids);
				CampaignChassisNumber::whereIn('id', $remove_chassis_ids)->forceDelete();
			}
			//SAVE CHASSIS NUMBERS
			if (isset($request->chassis_number)) {
				$chassis_nos = $request->chassis_number;
				$unique_chassis_nos = array_unique($request->chassis_number);
				if (count($chassis_nos) != count($unique_chassis_nos)) {
					return response()->json([
						'success' => false,
						'errors' => [
							'Chassis Number already been taken',
						],
					]);
				}
				foreach ($request->chassis_number as $key => $chassis_number) {
					if (!$request->chassis_number_id[$key]) {
						$chassis_numbers = new CampaignChassisNumber;
						$chassis_numbers->created_by_id = Auth::user()->id;
						$chassis_numbers->created_at = Carbon::now();
					} else {
						$chassis_numbers = CampaignChassisNumber::find($request->chassis_number_id[$key]);
						$chassis_numbers->updated_by_id = Auth::user()->id;
						$chassis_numbers->updated_at = Carbon::now();
					}
					$chassis_numbers->campaign_id = $campaign->id;
					$chassis_numbers->chassis_number = $chassis_number;
					$chassis_numbers->save();
				}}
			//CAMPAIGN TYPE == VEHICLE MODEL
			if ($request->campaign_type == '0') {
				$campaign->manufacture_date = NULL;
				$campaign->chassisNumbers()->forceDelete();
			} elseif ($request->campaign_type == '1') {
				//CAMPAIGN TYPE == MANUFACTURE DATE
				$campaign->vehicle_model_id = NULL;
				$campaign->chassisNumbers()->forceDelete();
			} else {
				//CAMPAIGN TYPE == CHASSIS NUMBER
				$campaign->manufacture_date = NULL;
				$campaign->vehicle_model_id = NULL;
			}
			$campaign->save();
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
		try {
			$campaign = Campaign::withTrashed()->where('id', $request->id)->forceDelete();
			if ($campaign) {
				DB::commit();
				return response()->json(['success' => true, 'message' => 'Campaign Deleted Successfully']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

	public function deleteCampignChassis(Request $request) {
		DB::beginTransaction();
		try {
			$chassis_numbers = CampaignChassisNumber::withTrashed()->where('id', $request->id)->forceDelete();
			if ($chassis_numbers) {
				DB::commit();
				return response()->json(['success' => true, 'message' => 'Campaign Chassis Number Deleted Successfully']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

	public function getVehicleModelSearchList(Request $request) {
		return VehicleModel::searchVehicleModel($request);
	}
}
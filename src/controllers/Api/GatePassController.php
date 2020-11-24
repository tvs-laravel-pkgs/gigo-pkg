<?php

namespace Abs\GigoPkg\Api;

use Abs\SerialNumberPkg\SerialNumberGroup;
use App\Attachment;
use App\Bay;
use App\Config;
use App\FinancialYear;
use App\GateLog;
use App\Customer;
use App\GatePass;
use App\GatePassCustomer;
use App\GatePassInvoice;
use App\GatePassUser;
use App\Http\Controllers\Controller;
use App\JobCard;
use App\JobOrder;
use App\GatePassInvoiceItem;
use App\Outlet;
use App\ShortUrl;
use App\Survey;
use App\SurveyAnswer;
use App\SurveyType;
use App\Vehicle;
use App\VehicleInventoryItem;
use App\User;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Validator;

class GatePassController extends Controller {
	public $successStatus = 200;

	public function getVehicleGatePassList(Request $request) {
		// dd($request->all());
		try {
			$vehicle_gate_pass_list = GatePass::select([
				'job_orders.driver_name',
				'job_orders.driver_mobile_number',
				'vehicles.registration_number',
				'vehicles.engine_number',
				'vehicles.chassis_number',
				'models.model_name',
				'job_orders.number as job_card_number',
				'gate_passes.number as gate_pass_no',
				'gate_passes.id',
				'gate_logs.id as gate_log_id',
				'configs.name as status',
				'gate_passes.status_id',
				DB::raw('DATE_FORMAT(gate_passes.created_at,"%d/%m/%Y, %h:%s %p") as date_and_time'),
			])
				->join('job_orders', 'job_orders.id', 'gate_passes.job_order_id')
				->leftJoin('job_cards', 'job_cards.id', 'gate_passes.job_card_id')
				->join('gate_logs', 'gate_logs.job_order_id', 'job_orders.id')
				->join('vehicles', 'vehicles.id', 'job_orders.vehicle_id')
				->join('models', 'models.id', 'vehicles.model_id')
				->join('configs', 'configs.id', 'gate_passes.status_id')
				->where(function ($query) use ($request) {
					if (!empty($request->search_key)) {
						$query->where('vehicles.registration_number', 'LIKE', '%' . $request->search_key . '%')
							->orWhere('job_orders.driver_name', 'LIKE', '%' . $request->search_key . '%')
							->orWhere('job_orders.driver_mobile_number', 'LIKE', '%' . $request->search_key . '%')
							->orWhere('models.model_name', 'LIKE', '%' . $request->search_key . '%')
						// ->orWhere('job_cards.job_card_number', 'LIKE', '%' . $request->search_key . '%')
							->orWhere('gate_passes.number', 'LIKE', '%' . $request->search_key . '%')
							->orWhere('configs.name', 'LIKE', '%' . $request->search_key . '%')
							->orWhere('job_orders.number', 'LIKE', '%' . $request->search_key . '%')
						;
					}
				})
				->where(function ($query) use ($request) {
					if (!empty($request->gate_pass_created_date)) {
						$query->whereDate('gate_passes.created_at', date('Y-m-d', strtotime($request->gate_pass_created_date)));
					}
				})
				->where(function ($query) use ($request) {
					if (!empty($request->registration_number)) {
						$query->where('vehicles.registration_number', 'LIKE', '%' . $request->registration_number . '%');
					}
				})
				->where(function ($query) use ($request) {
					if (!empty($request->driver_name)) {
						$query->where('job_orders.driver_name', 'LIKE', '%' . $request->driver_name . '%');
					}
				})
				->where(function ($query) use ($request) {
					if (!empty($request->driver_mobile_number)) {
						$query->where('job_orders.driver_mobile_number', 'LIKE', '%' . $request->driver_mobile_number . '%');
					}
				})
				->where(function ($query) use ($request) {
					if (!empty($request->model_id)) {
						$query->where('vehicles.model_id', $request->model_id);
					}
				})
				->where(function ($query) use ($request) {
					if (!empty($request->status_id)) {
						$query->where('gate_passes.status_id', $request->status_id);
					}
				})
				->where(function ($query) use ($request) {
					if (!empty($request->job_card_number)) {
						$query->where('job_orders.number', 'LIKE', '%' . $request->job_card_number . '%');
					}
				})
				->where(function ($query) use ($request) {
					if (!empty($request->number)) {
						$query->where('gate_passes.number', 'LIKE', '%' . $request->number . '%');
					}
				})
			//->where('job_cards.outlet_id', Auth::user()->employee->outlet_id)
				->where('gate_passes.company_id', Auth::user()->company_id)
				->where('gate_passes.type_id', 8280) // Vehicle Gate Pass
				->orderBy('gate_passes.status_id', 'ASC')
				->orderBy('gate_passes.created_at', 'DESC')
				->groupBy('gate_passes.id')
			;

			$total_records = $vehicle_gate_pass_list->get()->count();

			if ($request->offset) {
				$vehicle_gate_pass_list->offset($request->offset);
			}
			if ($request->limit) {
				$vehicle_gate_pass_list->limit($request->limit);
			}

			$vehicle_gate_passes = $vehicle_gate_pass_list->get();

			$params = [
				'config_type_id' => 48,
				'add_default' => true,
				'default_text' => "Select Status",
			];

			$extras = [
				'status_list' => Config::getDropDownList($params),
			];

			return response()->json([
				'success' => true,
				'vehicle_gate_passes' => $vehicle_gate_passes,
				'total_records' => $total_records,
				'extras' => $extras,
			]);
		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Error',
				'errors' => [
					'Error : ' . $e->getMessage() . '. Line : ' . $e->getLine() . '. File : ' . $e->getFile(),
				],
			]);
		}
	}

    public function getFormData(Request $request) {
		// dd($request->all());
		$id = $request->id;
		if (!$id) {
			$gate_pass = new GatePass;
			$gate_pass->gate_pass_invoice_items = [];
			$gate_pass->gate_pass_users = [];
			$action = 'Add';
		} else {
			$gate_pass = GatePass::withTrashed()->with([
				'gatePassInvoice',
				'gatePassInvoiceItems',
				'gatePassInvoiceItems.part',
				'gatePassInvoiceItems.category',
				'gatePassCustomer',
				'gatePassCustomer.customer',
				'gatePassCustomer.customer.primaryAddress',
				'jobCard',
				'purpose',
				'gatePassUsers',
				'gatePassUsers.user',
			])->find($id);

			$action = 'Edit';
		}

		$this->data['success'] = true;
		$this->data['gate_pass'] = $gate_pass;
		$this->data['action'] = $action;

        $extras = [
            'purpose_list' => Config::getDropDownList([
				'config_type_id' => 421,
				'orderBy' => 'id',
                'default_text' => 'Select Purpose',
			]),
			'parts_category_list' => Config::getDropDownList([
				'config_type_id' => 422,
				'orderBy' => 'id',
                'default_text' => 'Select Category',
            ]),
		];
		
		$this->data['extras'] = $extras;

        return response()->json($this->data);
        
	}
	
	public function save(Request $request) {
        // dd($request->all());
		try {
			if($request->form_type == 2)
			{
				$gate_pass = GatePass::find($request->gate_pass_id);

				if(!$gate_pass)
				{
					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => ['Gate Pass Not Found!'],
					]);
				}

				DB::beginTransaction();

				if($gate_pass->status_id == 11400)
				{
					if($gate_pass->type_id == 8282)
					{
						$gate_pass->status_id = 11402;
					}
					else
					{
						$gate_pass->status_id = 11401;
					}

					$gate_pass->gate_out_date = Carbon::now();
				}
				elseif($gate_pass->status_id == 11402)
				{
					$gate_pass->status_id = 11403;
					$gate_pass->gate_in_date = Carbon::now();
				}
				elseif($gate_pass->status_id == 11403)
				{
					$gate_pass->status_id = 11404;
				}

				$gate_pass->save();

				//Update parts Details
				if (($gate_pass->status_id != 11401 && $gate_pass->status_id != 11402 ) && isset($request->invoice_items)) {
					foreach ($request->invoice_items as $key => $invoice_item) {
						
						$gate_pass_item = GatePassInvoiceItem::firstOrNew([
							'id' => $invoice_item['item_id'],
						]);

						if(isset($invoice_item['returned_qty']))
						{
							$gate_pass_item->returned_qty =  $invoice_item['returned_qty'];
							$gate_pass_item->status_id = 11421;
						}
						else
						{
							$gate_pass_item->returned_qty =  NULL;
							$gate_pass_item->status_id = 11420;
						}
						$gate_pass_item->save();

					}	
				}

				//Update Gate Out date time 
				if ($gate_pass->status_id == 11401 && $gate_pass->status_id == 11402 ) 
				{
					$gate_pass_user = GatePassUser::where('gate_pass_id',$gate_pass->id)->update(['gate_out_date_time'=> Carbon::now(),'updated_by_id'=>Auth::user()->id,'updated_at'=> Carbon::now()]);
				}

				//Update Gate In date time 
				if ($gate_pass->status_id == 11403) 
				{
					$gate_pass_user = GatePassUser::where('gate_pass_id',$gate_pass->id)->update(['gate_in_date_time'=> Carbon::now(),'updated_by_id'=>Auth::user()->id,'updated_at'=> Carbon::now()]);
				}

				DB::commit();

				return response()->json([
					'success' => true,
					'message' => 'GatePass Updated successfully!!',
				]);
			}
			else
			{
				$validator = Validator::make($request->all(), [
					'type' => [
						'required',
					],
					'purpose_id' => [
						'required',
						'integer',
						'exists:configs,id',
					],
					// 'other_purpose' => [
					// 	'required',
					// ],
					'hand_over_to' => [
						'required',
					],
				]);
		
				if ($validator->fails()) {
					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => $validator->errors()->all(),
					]);
				}
		
				DB::beginTransaction();
				
				//remove items
				$removal_item_ids = json_decode($request->removal_item_ids);
				if (!empty($removal_item_ids)) {
					$invoice_items = GatePassInvoiceItem::whereIn('id', $removal_item_ids)->forceDelete();
				}

				//remove users
				$removal_user_ids = json_decode($request->removal_user_ids);
				if (!empty($removal_user_ids)) {
					$users = GatePassUser::whereIn('id', $removal_user_ids)->forceDelete();
				}
		
				$gate_pass = GatePass::firstOrNew(['id'=>$request->id]);
				if($request->type == 'Returnable')
				{
					$gate_pass->type_id = 8282;
					$gate_pass->job_card_id = $request->job_card_id;
				}
				else
				{
					$gate_pass->type_id = 8283;
					$gate_pass->job_card_id = NULL;
				}
		
				if (!$gate_pass->exists) {
					if (date('m') > 3) {
						$year = date('Y') + 1;
					} else {
						$year = date('Y');
					}
					//GET FINANCIAL YEAR ID
					$financial_year = FinancialYear::where('from', $year)
						->where('company_id', Auth::user()->company_id)
						->first();
					if (!$financial_year) {
						return response()->json([
							'success' => false,
							'error' => 'Validation Error',
							'errors' => [
								'Fiancial Year Not Found',
							],
						]);
					}
					//GET BRANCH/OUTLET
					$branch = Outlet::where('id', Auth::user()->working_outlet_id)->first();
		
					$generateNumber = SerialNumberGroup::generateNumber(139, $financial_year->id, $branch->state_id, $branch->id);
						if (!$generateNumber['success']) {
							return response()->json([
								'success' => false,
								'error' => 'Validation Error',
								'errors' => [
									'No Serial number found for FY : ' . $financial_year->from . ', State : ' . $branch->state->code . ', Outlet : ' . $branch->code,
								],
							]);
						}
		
						$error_messages_2 = [
							'number.required' => 'Serial number is required',
							'number.unique' => 'Serial number is already taken',
						];
		
						$validator_2 = Validator::make($generateNumber, [
							'number' => [
								'required',
								'unique:gate_passes,number,' . $gate_pass->id . ',id,company_id,' . Auth::user()->company_id,
							],
						], $error_messages_2);
		
						if ($validator_2->fails()) {
							return response()->json([
								'success' => false,
								'error' => 'Validation Error',
								'errors' => $validator_2->errors()->all(),
							]);
						}
					$gate_pass->number = $generateNumber['number'];
				}
		
				$gate_pass->job_card_id = $request->job_card_id;
				$gate_pass->purpose_id = $request->purpose_id;
				$gate_pass->other_remarks = $request->other_purpose;
				$gate_pass->hand_over_to = $request->hand_over_to;
				$gate_pass->status_id = 11400;
				$gate_pass->company_id = Auth::user()->company_id;
				$gate_pass->outlet_id = Auth::user()->working_outlet_id;
				$gate_pass->save();
		
				if (isset($request->part_details)) {
					foreach ($request->part_details as $key => $part_detail) {
						$gate_pass_item = GatePassInvoiceItem::firstOrNew([
							'id' => $part_detail['id'],
						]);
		
						if($gate_pass_item->exists)
						{
							$gate_pass_item->updated_by_id = Auth::user()->id;
							$gate_pass_item->updated_at = Carbon::now();
						}
						else
						{
							$gate_pass_item->created_by_id = Auth::user()->id;
							$gate_pass_item->created_at = Carbon::now();
						}
						
						$gate_pass_item->gate_pass_id = $gate_pass->id;
						$gate_pass_item->category_id =  $part_detail['category_id'];
						$gate_pass_item->entity_id =  isset($part_detail['entity_id']) ? $part_detail['entity_id'] : NULL;
						$gate_pass_item->entity_name = isset($part_detail['entity_name']) ? $part_detail['entity_name'] : NULL;
						$gate_pass_item->entity_description =  $part_detail['entity_description'];
						$gate_pass_item->issue_qty =  $part_detail['issue_qty'];
						$gate_pass_item->status_id = 11420;
						$gate_pass_item->save();
					}	
				}else
				{
					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => ['Kindly add atleast one part or tool!'],
					]);
				}
				
				if($request->type == 'Non Returnable')
				{
					$gate_pass_invoice = GatePassInvoice::firstOrNew(['gate_pass_id'=>$gate_pass->id]);
		
					if (!$gate_pass_invoice->exists) {
						$gate_pass_invoice->created_at = Carbon::now();
						$gate_pass_invoice->created_by_id = Auth::user()->id;
					}
					else
					{
						$gate_pass_invoice->updated_at = Carbon::now();
						$gate_pass_invoice->updated_by_id = Auth::user()->id;
					}
		
					$gate_pass_invoice->invoice_number = $request->invoice_number;
					$gate_pass_invoice->invoice_date = date('Y-m-d', strtotime($request->invoice_date));
					$gate_pass_invoice->invoice_amount = $request->invoice_amount;
					$gate_pass_invoice->save();
				}
		
				//Customer
				$gate_pass_customer = GatePassCustomer::firstOrNew(['gate_pass_id'=>$gate_pass->id]);
		
				if (!$gate_pass_customer->exists) {
					$gate_pass_customer->created_at = Carbon::now();
					$gate_pass_customer->created_by_id = Auth::user()->id;
				}
				else
				{
					$gate_pass_customer->updated_at = Carbon::now();
					$gate_pass_customer->updated_by_id = Auth::user()->id;
				}
		
				$gate_pass_customer->customer_name = $request->customer_name;
				$gate_pass_customer->customer_address = $request->customer_address;
				$gate_pass_customer->customer_id = $request->customer_id;
		
				$gate_pass_customer->save();
				
				//User Save
				if (isset($request->user_details)) {
					foreach ($request->user_details as $key => $user_detail) {
						if(isset($user_detail['user_id']))
						{
							$gate_pass_user = GatePassUser::firstOrNew([
								'id' => $user_detail['id'],
							]);
			
							if($gate_pass_user->exists)
							{
								$gate_pass_user->updated_by_id = Auth::user()->id;
								$gate_pass_user->updated_at = Carbon::now();
							}
							else
							{
								$gate_pass_user->created_by_id = Auth::user()->id;
								$gate_pass_user->created_at = Carbon::now();
							}
							
							$gate_pass_user->gate_pass_id = $gate_pass->id;
							$gate_pass_user->user_id =  $user_detail['user_id'];
							$gate_pass_user->save();
						}
					}	
				}

				DB::commit();
		
				return response()->json([
					'success' => true,
					'message' => 'GatePass Saved successfully!!',
				]);
			}
		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Error',
				'errors' => [
					'Error : ' . $e->getMessage() . '. Line : ' . $e->getLine() . '. File : ' . $e->getFile(),
				],
			]);
		}
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

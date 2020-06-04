<?php

namespace Abs\GigoPkg;
use Abs\GigoPkg\JobOrderRepairOrder;
use Abs\GigoPkg\JobCard;
use Abs\GigoPkg\JobOrder;
use Abs\GigoPkg\JobOrderPart;
use Abs\PartPkg\Part;
use App\Config;
use App\Customer;
use App\User;
use App\VehicleOwner;
use App\Vehicle;
use App\Http\Controllers\Controller;
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
		$this->data['extras'] = [
			'status' => [
				['id' => '', 'name' => 'Select Status'],
				['id' => '1', 'name' => 'Active'],
				['id' => '0', 'name' => 'Inactive'],
			],
		];
		/*$this->data['repair_order_type'] = collect(RepairOrderType::select('id','short_name')->where('company_id',Auth::user()->company_id)->get())->prepend(['id' => '', 'short_name' => 'Select Repair Order Type']);
		$this->data['skill_level'] = collect(SkillLevel::select('id','name')->where('company_id',Auth::user()->company_id)->get())->prepend(['id' => '', 'name' => 'Select Skill Level']);
		$this->data['tax_code'] = collect(TaxCode::select('id','code')->where('company_id',Auth::user()->company_id)->get())->prepend(['id' => '', 'code' => 'Select Tax Code']);*/
		return response()->json($this->data);
	}

	public function getPartsindentList(Request $request) {

		$job_cards = JobCard::select([
				'job_cards.id',
				'job_cards.job_card_number',
				'users.name as floor_supervisor',
				DB::raw('DATE_FORMAT(job_cards.created_at,"%d-%m-%Y") as date_time'),
				//DB::raw("SUM(job_order_parts.qty) as qty"),
				DB::raw("SUM(job_order_issued_parts.issued_qty) as issued_qty"),
				'job_order_parts.qty',
				//'job_order_issued_parts.issued_qty',
				'job_orders.vehicle_id',
				'outlets.code as outlet_name',
				'states.name as state_name',
				'regions.name as region_name',
				DB::raw('IF(job_cards.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->leftJoin('users', 'users.id', 'job_cards.floor_supervisor_id')
			->leftJoin('job_order_parts', 'job_order_parts.job_order_id', 'job_cards.job_order_id')
			->leftJoin('job_order_issued_parts', 'job_order_issued_parts.job_order_part_id', 'job_order_parts.id')
			->leftJoin('job_orders', 'job_orders.id', 'job_cards.job_order_id')
			->leftJoin('outlets', 'outlets.id', 'job_cards.outlet_id')
			->leftJoin('states', 'states.id', 'outlets.state_id')
			->leftJoin('regions', 'regions.id', 'outlets.region_id')
			
			->where(function ($query) use ($request) {
				if (!empty($request->outlet)) {
					$query->where('outlets.code', 'LIKE', '%' . $request->outlet . '%');
				}
			})
			
			->where(function ($query) use ($request) {
				if (!empty($request->date)) {
					$query->whereDate('job_cards.created_at', '>=', $request->date);
				}
			})
			
			->whereNotNull('job_cards.job_order_id')
			->where('job_cards.company_id', Auth::user()->company_id)
			->groupBy('job_cards.id')
		;

		return Datatables::of($job_cards)
			->addColumn('customer_name', function ($job_cards) {
				$customer_name = VehicleOwner::select('customers.code')
		    ->join('customers','customers.id','vehicle_owners.customer_id')
		    ->where('vehicle_owners.vehicle_id',$job_cards->vehicle_id)->orderby('from_date', 'desc')->first();
		    return $customer_name->code;
			})
		    ->addColumn('status', function ($job_cards) {
				$status = $job_cards->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indigator ' . $status . '"></span>' . $job_cards->status;
			})
			->addColumn('action', function ($job_cards) {
				$view_hover_img = asset("public/theme/img/table/view-hover.svg");
				$view_img = asset("/public/theme/img/table/view.svg");
				$output = '';
				if (Entrust::can('view-parts-indent')) {
					$output .= '<a href="#!/gigo-pkg/parts-indent/view/' . $job_cards->id . '" id = "" title="View"><img src="' . $view_img . '" alt="View" class="img-responsive" onmouseover=this.src="' . $view_hover_img . '" onmouseout=this.src="' . $view_img . '"></a>';
				}
				return $output;
			})
			->make(true);
	}

	public function getPartsIndentData(Request $request) {
		$id = $request->id;
		$this->data['job_cards'] = $job_card = JobCard::select([
				'job_cards.job_card_number','job_cards.id as job_card_id',
				'users.name as floor_supervisor',
				'job_cards.job_order_id','configs.name as work_status',
				DB::raw('DATE_FORMAT(job_cards.created_at,"%d-%m-%Y") as date_time'),
				'job_orders.vehicle_id',
			])
			->leftJoin('users', 'users.id', 'job_cards.floor_supervisor_id')
			->leftJoin('job_orders', 'job_orders.id', 'job_cards.job_order_id')
			->leftJoin('configs', 'configs.id', 'job_cards.status_id')
			->where('job_cards.id', $id)->first();
		
		$this->data['vehicle_info'] = Vehicle::select('vehicles.registration_number','vehicles.engine_number','vehicles.chassis_number','models.model_name','vehicle_makes.name as make_name')->leftJoin('models','models.id','vehicles.model_id')->leftJoin('vehicle_makes','vehicle_makes.id','models.vehicle_make_id')->where('vehicles.id',$job_card->vehicle_id)->first();

		$this->data['customer_details'] = VehicleOwner::select('customers.name as customer_name','customers.mobile_no','customers.email','customers.address','customers.gst_number','customers.pan_number','configs.name')
		    ->join('customers','customers.id','vehicle_owners.customer_id')
		    ->join('configs','configs.id','vehicle_owners.ownership_id')
		    ->where('vehicle_owners.vehicle_id',$job_card->vehicle_id)->orderby('from_date', 'desc')->first();

		$this->data['gate_log'] = $gate_log = GateLog::select('gate_logs.id','gate_logs.number',DB::raw('DATE_FORMAT(gate_logs.gate_in_date,"%d-%m-%Y") as gate_in_date'),'job_orders.driver_name','job_orders.driver_mobile_number')
		    ->leftJoin('job_orders','job_orders.id','gate_logs.job_order_id')->where('gate_logs.job_order_id',$job_card->job_order_id)->first();

		$this->data['labour_details'] = JobOrderRepairOrder::select('repair_orders.code',
			DB::raw('COALESCE(repair_orders.name,"--") as name'),'split_order_types.name as split_name','tax_codes.code as tax_code','repair_orders.amount as rate','job_order_repair_orders.qty','job_order_repair_orders.amount')
		    ->leftJoin('repair_orders','repair_orders.id','job_order_repair_orders.repair_order_id')
		    ->leftJoin('split_order_types','split_order_types.id','job_order_repair_orders.split_order_type_id')
		    ->leftJoin('tax_codes','tax_codes.id','repair_orders.tax_code_id')
		    ->where('job_order_repair_orders.job_order_id',$job_card->job_order_id)->get();

		$this->data['parts_details'] = JobOrderPart::select('parts.code','parts.name','tax_codes.code as tax_code','split_order_types.name as split_name','job_order_parts.rate','job_order_parts.amount','job_order_parts.qty')
		    ->leftJoin('parts','parts.id','job_order_parts.part_id')
		    ->leftJoin('split_order_types','split_order_types.id','job_order_parts.split_order_type_id')
		    ->leftJoin('tax_codes','tax_codes.id','parts.tax_code_id')
		    ->where('job_order_parts.job_order_id',$job_card->job_order_id)->get();

		$this->data['customer_voice_details'] = JobOrder::select('job_orders.is_road_test_required','job_orders.road_test_report','users.name','customer_voices.name as customer_voice','job_orders.expert_diagnosis_report')
		     ->leftJoin('users','users.id','job_orders.road_test_done_by_id')
		    ->leftJoin('job_order_customer_voice','job_order_customer_voice.job_order_id','job_orders.id')
		    ->leftJoin('customer_voices','customer_voices.id','job_order_customer_voice.customer_voice_id')
		    ->where('job_orders.id',$job_card->job_order_id)->get();  

		$this->data['gate_pass_details'] = JobOrder::
				with([
				'warrentyPolicyAttachment',
				'EWPAttachment',
				'AMCAttachment',
			])->find($job_card->job_order_id);

		 $this->data['part_list'] = collect(Part::select('id','name')->where('company_id',Auth::user()->company_id)->get())->prepend(['id' => '', 'name' => 'Select Repair Order Type']); 

		 $this->data['mechanic_list'] = collect(JobOrderRepairOrder::select('users.id','users.name')->leftJoin('repair_order_mechanics','repair_order_mechanics.job_order_repair_order_id','job_order_repair_orders.id')->leftJoin('users','users.id','repair_order_mechanics.mechanic_id')->where('job_order_repair_orders.job_order_id',$job_card->job_order_id)->distinct()->get())->prepend(['id' => '', 'name' => 'Select Mechanic']);

		 $this->data['issued_mode'] = collect(Config::select('id','name')->where('config_type_id',109)->get())->prepend(['id' => '', 'name' => 'Select Issue Mode']);

		 $this->data['issued_parts_details'] = JobOrderIssuedPart::select('job_order_issued_parts.id as issued_id','parts.code','job_order_parts.id','job_order_parts.qty','job_order_issued_parts.issued_qty',DB::raw('DATE_FORMAT(job_order_issued_parts.created_at,"%d-%m-%Y") as date'),'users.name as issued_to','configs.name as config_name','job_order_issued_parts.issued_mode_id','job_order_issued_parts.issued_to_id')
		    ->leftJoin('job_order_parts','job_order_parts.id','job_order_issued_parts.job_order_part_id')
	        ->leftJoin('parts','parts.id','job_order_parts.part_id')	
	        ->leftJoin('users','users.id','job_order_issued_parts.issued_to_id')
	        ->leftJoin('configs','configs.id','job_order_issued_parts.issued_mode_id')
	        ->where('job_order_parts.job_order_id',$job_card->job_order_id)->groupBy('job_order_issued_parts.id')->get();

		return response()->json($this->data);
	}

	public function getPartDetails(Request $request)
	{
	$this->data['parts_details'] = JobOrderPart::select('job_order_parts.id','job_order_parts.qty',DB::raw("SUM(job_order_issued_parts.issued_qty) as issued_qty"))->leftJoin('job_order_issued_parts','job_order_issued_parts.job_order_part_id','job_order_parts.id')->where('job_order_parts.part_id',$request->key)->first();
	return response()->json($this->data);
	}

	public function getPartsIndentPartsData(Request $request)
	{
       $this->data['issued_parts_details'] = $job_order_issued = JobOrderIssuedPart::select('job_order_issued_parts.id as issued_id','parts.code','job_order_parts.id','job_order_parts.qty','job_order_issued_parts.issued_qty',DB::raw('DATE_FORMAT(job_order_issued_parts.created_at,"%d-%m-%Y") as date'),'users.name as issued_to','configs.name as config_name','job_order_issued_parts.issued_mode_id','job_order_issued_parts.issued_to_id','job_order_issued_parts.job_order_part_id')
		    ->leftJoin('job_order_parts','job_order_parts.id','job_order_issued_parts.job_order_part_id')
	        ->leftJoin('parts','parts.id','job_order_parts.part_id')	
	        ->leftJoin('users','users.id','job_order_issued_parts.issued_to_id')
	        ->leftJoin('configs','configs.id','job_order_issued_parts.issued_mode_id')
	        ->where('job_order_issued_parts.id',$request->part_id)->first();    

		$this->data['job_cards'] = $job_card = JobCard::select([
				'job_cards.job_card_number',
				'users.name as floor_supervisor',
				'job_cards.job_order_id','configs.name as work_status','job_cards.id as job_card_id',
				DB::raw('DATE_FORMAT(job_cards.created_at,"%d-%m-%Y") as date_time'),
				'job_orders.vehicle_id',
			])
			->leftJoin('users', 'users.id', 'job_cards.floor_supervisor_id')
			->leftJoin('job_orders', 'job_orders.id', 'job_cards.job_order_id')
			->leftJoin('configs', 'configs.id', 'job_cards.status_id')
			->where('job_cards.id', $request->job_card_id)->first();

		$this->data['part_list'] = collect(Part::select('id','name')->where('company_id',Auth::user()->company_id)->get())->prepend(['id' => '', 'name' => 'Select Repair Order Type']); 

		$this->data['mechanic_list'] = collect(JobOrderRepairOrder::select('users.id','users.name')->leftJoin('repair_order_mechanics','repair_order_mechanics.job_order_repair_order_id','job_order_repair_orders.id')->leftJoin('users','users.id','repair_order_mechanics.mechanic_id')->where('job_order_repair_orders.job_order_id',$job_card->job_order_id)->distinct()->get())->prepend(['id' => '', 'name' => 'Select Mechanic']);

		$this->data['issued_mode'] = collect(Config::select('id','name')->where('config_type_id',109)->get())->prepend(['id' => '', 'name' => 'Select Issue Mode']);
		return response()->json($this->data);

	}

	public function savePartsindent(Request $request) {
		try {
			$error_messages = [
				'part_code.required' => 'Part No is Required',
				'issued_qty.required' => 'Issued Qty is Required',
				'issued_to_id.required' => 'Issued to is Required',
			];
			$validator = Validator::make($request->all(), [
				'part_code' => 'required',
				'issued_qty' => 'required',
				'issued_to_id' => 'required',
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}
            
            if (!$request->id) { 
			if($request->bal_qty < $request->issued_qty)
			{
				return response()->json(['success' => false, 'errors' => ['Exception Error' => 'Transfered Quantity Exceed Requested Quantity']]);
			}}
			else
			{
			$total_qty = $request->issued_qty+$request->bal_qty;
			if($total_qty < $request->issued_qty)
			{
				return response()->json(['success' => false, 'errors' => ['Exception Error' => 'Transfered Quantity Exceed Requested Quantity']]);
			}

			}

			DB::beginTransaction();
			if (!$request->id) {
				$job_order_issued_parts = new JobOrderIssuedPart;
				$job_order_issued_parts->created_by_id = Auth::user()->id;
				$job_order_issued_parts->created_at = Carbon::now();
				$job_order_issued_parts->updated_at = NULL;
			} else {
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
		} catch (Exceprion $e) {
			DB::rollBack();
			return response()->json([
				'success' => false,
				'error' => $e->getMessage(),
			]);
		}
	}

	public function getIssedParts(Request $request)
	{
	  $job_card = JobCard::select('job_order_id')->where('id',$request->id)->first();	
	 $this->data['issued_parts_details'] = JobOrderIssuedPart::select('job_order_issued_parts.id','parts.code','job_order_parts.id','job_order_parts.qty','job_order_issued_parts.issued_qty',DB::raw('DATE_FORMAT(job_order_issued_parts.created_at,"%d-%m-%Y") as date'),'users.name as issued_to','configs.name as config_name')
		    ->leftJoin('job_order_parts','job_order_parts.id','job_order_issued_parts.job_order_part_id')
	        ->leftJoin('parts','parts.id','job_order_parts.part_id')	
	        ->leftJoin('users','users.id','job_order_issued_parts.issued_to_id')
	        ->leftJoin('configs','configs.id','job_order_issued_parts.issued_mode_id')
	        ->where('job_order_parts.job_order_id',$job_card->job_order_id)->groupBy('job_order_issued_parts.id')->get();
	return response()->json($this->data);
	}

	public function deleteIssedPart(Request $request) {
		DB::beginTransaction();
		try {
			$issued_parts_details = JobOrderIssuedPart::withTrashed()->where('id', $request->id)->forceDelete();
			if ($issued_parts_details) {
				DB::commit();
				return response()->json(['success' => true, 'message' => 'Issed Part  Deleted Successfully']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

}

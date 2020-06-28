<?php

namespace Abs\GigoPkg\Api;

use Abs\BasicPkg\Traits\CrudTrait;
use App\Http\Controllers\Controller;
use App\WarrantyJobOrderRequest;
use Illuminate\Http\Request;
use DB;
use Auth;
use Yajra\Datatables\Datatables;
use App\Outlet;

class WarrantyJobOrderRequestController extends Controller {
	use CrudTrait;
	public $model = WarrantyJobOrderRequest::class;
	public $successStatus = 200;

	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}
	public function save(Request $request) {
		$result = WarrantyJobOrderRequest::saveFromFormArray($request->all());
		return response()->json($result);
	}

	public function sendToApproval(Request $request) {
		try {
			$warranty_job_order_request = WarrantyJobOrderRequest::find($request->id);
			$warranty_job_order_request->status_id = 9101; //waiting for approval
			$warranty_job_order_request->save();
			return Self::read($warranty_job_order_request->id);
		} catch (Exceprion $e) {
			return response()->json([
				'success' => false,
				'error' => $e->getMessage(),
			]);
		}
	}

	public function approve(Request $request) {
		try {
			$warranty_job_order_request = WarrantyJobOrderRequest::find($request->id);
			$warranty_job_order_request->authorization_number = $request->authorization_number;
			$warranty_job_order_request->remarks = $request->remarks;
			$warranty_job_order_request->status_id = 9102; //approved
			$warranty_job_order_request->save();
			return Self::read($warranty_job_order_request->id);
		} catch (Exceprion $e) {
			return response()->json([
				'success' => false,
				'error' => $e->getMessage(),
			]);
		}
	}

	public function reject(Request $request) {
		try {
			$warranty_job_order_request = WarrantyJobOrderRequest::find($request->id);
			$warranty_job_order_request->rejected_reason = $request->rejected_reason;
			$warranty_job_order_request->status_id = 9103; //rejected
			$warranty_job_order_request->save();
			return Self::read($warranty_job_order_request->id);
		} catch (Exceprion $e) {
			return response()->json([
				'success' => false,
				'error' => $e->getMessage(),
			]);
		}
	}

	public function remove(Request $request)
	{
		DB::beginTransaction();
		try {
			$warranty_job_order = WarrantyJobOrderRequest::find($request->id)->delete();
			if ($warranty_job_order) {
				DB::commit();
				return response()->json(['success' => true, 'message' => 'Warranty Job Order Deleted Successfully']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

	public function list(Request $request)
	{
		$list_data = WarrantyJobOrderRequest::select([
				'warranty_job_order_requests.id',
				'warranty_job_order_requests.created_at as request_date',
				'job_orders.number as job_card_number',
				DB::raw('DATE_FORMAT(warranty_job_order_requests.created_at,"%d/%m/%Y") as request_date'),
				'outlets.code as outlet_name',
				'customers.name as customer_name',
				'vehicles.chassis_number',
				'vehicles.registration_number',
				'models.model_number',
				'users.name as requested_by',
				'warranty_job_order_requests.status_id',
				'configs.name as status'
			])
			->leftJoin('job_orders','job_orders.id','warranty_job_order_requests.job_order_id')
			->leftJoin('outlets','outlets.id','job_orders.outlet_id')
			->leftJoin('customers','customers.id','job_orders.customer_id')
			->leftJoin('vehicles','vehicles.id','job_orders.vehicle_id')
			->leftJoin('models','models.id','vehicles.model_id')
			->leftJoin('configs','configs.id','warranty_job_order_requests.status_id')
			->leftJoin('users','users.id','warranty_job_order_requests.created_by_id')
			->whereIn('configs.id',[9100, 9101, 9103]);

		if ($request->request_date!=null) {
			$date = date('Y-m-d', strtotime($request->request_date));
			$list_data->whereDate('warranty_job_order_requests.created_at',$date);
		}
		if ($request->reg_no!=null) {
			$list_data->where('vehicles.registration_number','like','%'.$request->reg_no.'%');
		}
		if ($request->customer_id != null) {
			$list_data->where('customers.id',$request->customer_id);
		}
		if ($request->model_id != null) {
			$list_data->where('models.id',$request->model_id);
		}
		if ($request->job_card_no!=null) {
			$list_data->where('job_orders.number','like','%'.$request->job_card_no.'%');		
		}

		return Datatables::of($list_data)
			->rawColumns(['action'])
			->addColumn('action', function ($list_data) {

				$view = asset('public/themes/' . $this->data['theme'] . '/img/content/table/view.svg');
				$img1 = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');
				$img1_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow-active.svg');
				$img_delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				$img_delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');
				$output = '';
				

				$output .= '<a title="View" href="#!/warranty-job-order-request/view/'.$list_data->id.'" class="btn btn-sm btn-default"><span class="glyphicon glyphicon glyphicon-eye-open"></span></a>';
				
				if ($list_data->status_id == 9100) {
					//<img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1 . '" onmouseout=this.src="' . $img1 . '">
					$output .= '<a href="#!/warranty-job-order-request/form/' . $list_data->id . '" id = "" title="Edit" class="btn btn-sm btn-default"><span class="glyphicon glyphicon-pencil"></span></a>';

					$output .= '<a onclick="angular.element(this).scope().sendToApproval(' . $list_data->id . ')" id = "" title="Send Approval" class="btn btn-sm btn-default"><span class="glyphicon glyphicon-send"></span></a>';
				}

				$output .= '<a onclick="angular.element(this).scope().confirmDelete(' . $list_data->id . ')" id = "" title="Delete" class="btn btn-sm btn-default"><span class="glyphicon glyphicon-trash"></span></a>';

				
				return $output;
			})
			->make(true);
	}

	public function getOutlets(Request $r)
	{
		$key = $r->key;
        $list = Outlet::where('company_id', Auth::user()->company_id)
            ->select(
                'id',
                'name',
                'code'
            )
            ->where(function ($q) use ($key) {
                $q->where('name', 'like', $key . '%')
                    ->orWhere('code', 'like', $key . '%')
                ;
            })
            ->get();
        return response()->json($list);
		/*$this->data['outlets'] = DB::select('id','code as name')->where('company_id', Auth::user()->company_id)->get();
		return response()->json($this->data);*/
		
	}
}
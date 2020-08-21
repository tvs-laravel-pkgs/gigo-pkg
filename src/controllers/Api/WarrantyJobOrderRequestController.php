<?php

namespace Abs\GigoPkg\Api;

use Abs\BasicPkg\Traits\CrudTrait;
use Abs\GigoPkg\Notifications\WarrantyJobOrderRequest as WjorNotification;
use App\Aggregate;
use App\BharatStage;
use App\Country;
use App\Http\Controllers\Controller;
use App\Outlet;
use App\SplitOrderType;
use App\User;
use App\VehicleModel;
use App\WarrantyJobOrderRequest;
use Auth;
use DB;
use Entrust;
use Illuminate\Http\Request;
use PDF;
use Yajra\Datatables\Datatables;

class WarrantyJobOrderRequestController extends Controller {
	use CrudTrait;
	public $model = WarrantyJobOrderRequest::class;
	public $successStatus = 200;

	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	public function list(Request $request) {
		$list_data = WarrantyJobOrderRequest::select([
			'warranty_job_order_requests.id',
			'warranty_job_order_requests.number',
			'job_orders.number as job_card_number',
			DB::raw('DATE_FORMAT(warranty_job_order_requests.created_at,"%d/%m/%Y %h:%i %p") as request_date'),
			'outlets.code as outlet_name',
			'customers.name as customer_name',
			'vehicles.chassis_number',
			'vehicles.registration_number',
			'models.model_number',
			DB::raw('CONCAT(users.name," / ",users.username) as requested_by'),
			'warranty_job_order_requests.status_id',
			'configs.name as status',
		])
			->leftJoin('job_orders', 'job_orders.id', 'warranty_job_order_requests.job_order_id')
			->leftJoin('outlets', 'outlets.id', 'job_orders.outlet_id')
			->leftJoin('customers', 'customers.id', 'job_orders.customer_id')
			->leftJoin('vehicles', 'vehicles.id', 'job_orders.vehicle_id')
			->leftJoin('models', 'models.id', 'vehicles.model_id')
			->leftJoin('configs', 'configs.id', 'warranty_job_order_requests.status_id')
			->leftJoin('users', 'users.id', 'warranty_job_order_requests.created_by_id')
		;

		if ($request->request_date != null) {
			$date = date('Y-m-d', strtotime($request->request_date));
			$list_data->whereDate('warranty_job_order_requests.created_at', $date);
		}
		if ($request->reg_no != null) {
			$list_data->where('vehicles.registration_number', 'like', '%' . $request->reg_no . '%');
		}
		if ($request->customer_id != null) {
			$list_data->where('customers.id', $request->customer_id);
		}
		if ($request->model_id != null) {
			$list_data->where('models.id', $request->model_id);
		}
		if ($request->job_card_no != null) {
			$list_data->where('job_orders.number', 'like', '%' . $request->job_card_no . '%');
		}

		/*
			if (Entrust::can('own-warranty-job-order-request')) {
				$list_data->where('warranty_job_order_requests.created_by_id', Auth::id());
			} else if (Entrust::can('own-outlet-warranty-job-order-request')) {
				$list_data->where('job_orders.outlet_id', Auth::user()->employee->outlet_id);
			}
		*/
		if (Entrust::can('own-outlet-warranty-job-order-request')) {
			$list_data->where('job_orders.outlet_id', Auth::user()->employee->outlet_id);
		} else if (Entrust::can('mapped-outlets-warranty-job-order-request')) {
			$list_data->leftJoin('employee_outlet', 'employee_outlet.outlet_id', 'outlets.id')
				->where('employee_outlet.employee_id', Auth::user()->employee->id);
		}

		if (Entrust::can('verify-only-warranty-job-order-request')) {
			$list_data->whereIn('warranty_job_order_requests.status_id', [9101]);
		} else {
			// if ($request->status_ids) {
			// $list_data->whereIn('warranty_job_order_requests.status_id', [9100, 9103]);
			// }
		}

		// dump($request->all());

		$list_data->orderBy('warranty_job_order_requests.status_id', 'ASC');
		$list_data->orderBy('warranty_job_order_requests.id', 'DESC');

		return Datatables::of($list_data)
			->rawColumns(['action'])
			->addColumn('action', function ($list_data) {

				$view = asset('public/themes/' . $this->data['theme'] . '/img/content/table/view.svg');
				$img1 = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');
				$img1_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow-active.svg');
				$img_delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				$img_delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');
				$output = '';

				$output .= '<a title="View" href="#!/warranty-job-order-request/view/' . $list_data->id . '" class="btn btn-sm btn-default"><span class="glyphicon glyphicon glyphicon-eye-open"></span></a>';

				if ($list_data->status_id == 9100 || $list_data->status_id == 9103) {
					if (Entrust::can('edit-warranty-job-order-requests')) {
						$output .= '<a href="#!/warranty-job-order-request/form/' . $list_data->id . '" id = "" title="Edit" class="btn btn-sm btn-default"><span class="glyphicon glyphicon-pencil"></span></a>';
					}

					if (Entrust::can('send-to-approval-warranty-job-order-request')) {
						$output .= '<a onclick="angular.element(this).scope().sendToApproval(' . $list_data->id . ')" id = "" title="Send to Approval" class="btn btn-sm btn-default"><span class="glyphicon glyphicon-send"></span></a>';
					}
				}

				if (Entrust::can('delete-warranty-job-order-request')) {
					$output .= '<a onclick="angular.element(this).scope().confirmDelete(' . $list_data->id . ')" id = "" title="Delete" class="btn btn-sm btn-default"><span class="glyphicon glyphicon-trash"></span></a>';
				}

				return $output;
			})
			->make(true);
	}

	private function beforeCrudAction($action, $response, $wjor) {
		// $user = Auth::user();
		// $user->notify(new WjorNotification());

	}

	/**
	 * Presents an opportunity to modify the contents of the ApiResponse before crud action completes
	 * @param string $action = index|create|read|update|delete|options
	 * @param ApiResponse $response
	 */
	// public function alterCrudResponse($action, ApiResponse $Response) {
	// 	// DO NOT PLACE CODE IN HERE, THIS IS FOR DOCUMENTATION PURPOSES ONLY
	// }

	public function save(Request $request) {
		$result = WarrantyJobOrderRequest::saveFromFormArray($request->all());
		return response()->json($result);
	}

	public function sendToApproval(Request $request) {
		// dd($request->all());
		try {
			DB::beginTransaction();
			$warranty_job_order_request = WarrantyJobOrderRequest::find($request->id);
			if (!$warranty_job_order_request) {
				return [
					'success' => false,
					'error' => 'Request not found',
				];
			}
			if (!$warranty_job_order_request->status_id == 9101) {
				return [
					'success' => false,
					'error' => 'Request already sent for approval',
				];
			}
			$warranty_job_order_request->status_id = 9101; //waiting for approval
			$warranty_job_order_request->save();
			$warranty_job_order_request->load($this->model::relationships('read'));

			//SENDING EMAIL TO BUSINESS'S WARRANTY MANAGER
			$warranty_job_order_request->loadBusiness('ALSERV');

			$cc_emails = [];
			if ($warranty_job_order_request->jobOrder->outlet->al_serv_ppr_request_cc_emails) {
				foreach ($warranty_job_order_request->jobOrder->outlet->al_serv_ppr_request_cc_emails as $key => $user) {
					if ($user->email != null) {
						$cc_emails[] = $user->email;
					}
				}
			}
			$warranty_job_order_request->cc_emails = $cc_emails;

			$warranty_manager = User::find($warranty_job_order_request->jobOrder->outlet->business->pivot->warranty_manager_id);

			if (!$warranty_manager) {
				return response()->json([
					'success' => false,
					'error' => 'Warranty manager not configured : Outlet Code - ' . $warranty_job_order_request->jobOrder->outlet->code . ', Business : ' . $warranty_job_order_request->jobOrder->outlet->business->name,
				]);
			}

			$warranty_manager->notify(new WjorNotification([
				'wjor' => $warranty_job_order_request,
			]));

			$warranty_job_order_request->generatePDF();

			DB::commit();

			return Self::read($warranty_job_order_request->id);
		} catch (Exceprion $e) {
			DB::rollBack();
			return response()->json([
				'success' => false,
				'error' => $e->getMessage(),
			]);
		}
	}

	public function pdf() {
		$warranty_job_order_request = WarrantyJobOrderRequest::find(20);
		$warranty_job_order_request->load($this->model::relationships('read'));
		$warranty_job_order_request->loadBusiness('ALSERV');
		// dd($warranty_job_order_request);

		return $warranty_job_order_request->generatePDF()->stream();

	}

	public function approve(Request $request) {
		// dd($request->all());
		try {
			$warranty_job_order_request = WarrantyJobOrderRequest::find($request->id);
			$warranty_job_order_request->authorization_number = $request->authorization_number;
			$warranty_job_order_request->authorization_date = date('Y-m-d');
			$warranty_job_order_request->authorization_by = Auth::id();
			$warranty_job_order_request->remarks = $request->remarks;
			$warranty_job_order_request->status_id = 9102; //approved
			$warranty_job_order_request->save();

			$warranty_job_order_request->load($this->model::relationships('read'));
			$warranty_job_order_request->loadBusiness('ALSERV');

			$warranty_job_order_request->generatePDF();
			return response()->json([
				'success' => true,
				'message' => 'PPR approved successfully',
			]);

		} catch (Exceprion $e) {
			return response()->json([
				'success' => false,
				'error' => $e->getMessage(),
			]);
		}
	}

	public function reject(Request $request) {
		// dd($request->all());
		try {
			$warranty_job_order_request = WarrantyJobOrderRequest::find($request->id);
			$warranty_job_order_request->rejected_reason = $request->rejected_reason;
			$warranty_job_order_request->status_id = 9103; //rejected
			$warranty_job_order_request->save();

			$warranty_job_order_request->load($this->model::relationships('read'));

			//SENDING EMAIL TO BUSINESS'S WARRANTY MANAGER
			$warranty_job_order_request->loadBusiness('ALSERV');

			$cc_emails = [];
			if ($warranty_job_order_request->jobOrder->outlet->al_serv_ppr_request_cc_emails) {
				foreach ($warranty_job_order_request->jobOrder->outlet->al_serv_ppr_request_cc_emails as $key => $user) {
					if ($user->email != null) {
						$cc_emails[] = $user->email;
					}
				}
			}
			$warranty_job_order_request->cc_emails = $cc_emails;

			$warranty_manager = User::find($warranty_job_order_request->jobOrder->outlet->business->pivot->warranty_manager_id);

			if (!$warranty_manager) {
				return [
					'success' => false,
					'error' => 'Warranty manager not configured : Outlet Code - ' . $warranty_job_order_request->jobOrder->outlet->code . ', Business : ' . $warranty_job_order_request->jobOrder->outlet->business->name,
				];

			}
			$warranty_manager->notify(new WjorNotification([
				'wjor' => $warranty_job_order_request,
			]));

			return Self::read($warranty_job_order_request->id);
		} catch (Exceprion $e) {
			return response()->json([
				'success' => false,
				'error' => $e->getMessage(),
			]);
		}
	}

	public function remove(Request $request) {
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

	public function getOutlets(Request $r) {
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
	public function getFormData() {
		try {
			$employee_outlets = Auth::user()->employee->employee_outlets;
			$models = VehicleModel::where('company_id', Auth::user()->company_id)->get();
			$bharat_stages = BharatStage::where('company_id', Auth::user()->company_id)->get();
			$split_order_types = SplitOrderType::where('company_id', Auth::user()->company_id)->where('claim_category_id', 11112)->get();
			$aggregates = Aggregate::all();
			// $aggregates = Aggregate::where('company_id', Auth::user()->company_id)->get();
			return response()->json([
				'success' => true,
				'extras' => [
					'country_list' => Country::getDropDownList(),
					'state_list' => [], //State::getDropDownList(),
					'city_list' => [], //City::getDropDownList(),
					'models' => $models,
					'employee_outlets' => $employee_outlets,
					'bharat_stages' => $bharat_stages,
					'split_order_types' => $split_order_types,
					'aggregates' => $aggregates,
				],
			]);

		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'error' => 'Server Error',
				'errors' => [
					'Error : ' . $e->getMessage() . '. Line : ' . $e->getLine() . '. File : ' . $e->getFile(),
				],
			]);
		}
	}
}

<?php

namespace Abs\GigoPkg\Api;

use Abs\BasicPkg\Traits\CrudTrait;
use Abs\GigoPkg\Notifications\WarrantyJobOrderRequest as WjorNotification;
use App\Aggregate;
use App\BharatStage;
use App\Country;
use App\FailureType;
use App\Http\Controllers\Controller;
use App\Outlet;
use App\PprRejectReason;
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
		// dd($request->all());
		$list_data = WarrantyJobOrderRequest::select([
			'warranty_job_order_requests.id',
			'warranty_job_order_requests.number',
			// 'job_orders.number as job_card_number',
			'job_cards.job_card_number',
			DB::raw('DATE_FORMAT(warranty_job_order_requests.created_at,"%d/%m/%Y %h:%i %p") as request_date'),
			'outlets.code as outlet_name',
			'customers.name as customer_name',
			'vehicles.chassis_number',
			'vehicles.registration_number',
			// 'models.model_number',
			'mod.model_name as mod_name',
			DB::raw('((warranty_job_order_requests.total_labour_amount + warranty_job_order_requests.total_part_amount) - warranty_job_order_requests.total_part_cushioning_charge ) as total_claim_amount'),
			DB::raw('CONCAT(users.name," / ",users.username) as requested_by'),
			'warranty_job_order_requests.status_id',
			'warranty_job_order_requests.failure_date',
			'warranty_job_order_requests.created_at',
			'warranty_job_order_requests.rejected_reason',
			'configs.name as status',
			'bharat_stages.name as bharat_stage',
		])
			->leftJoin('job_orders', 'job_orders.id', 'warranty_job_order_requests.job_order_id')
			->leftJoin('job_cards', 'job_cards.job_order_id', 'job_orders.id')
			->leftJoin('outlets', 'outlets.id', 'job_orders.outlet_id')
			->leftJoin('customers', 'customers.id', 'job_orders.customer_id')
			->leftJoin('vehicles', 'vehicles.id', 'job_orders.vehicle_id')
			->leftJoin('bharat_stages', 'bharat_stages.id', 'vehicles.bharat_stage_id')
			->leftJoin('models as mod', 'mod.id', 'vehicles.model_id')
			->leftJoin('configs', 'configs.id', 'warranty_job_order_requests.status_id')
			->leftJoin('users', 'users.id', 'warranty_job_order_requests.created_by_id')
		;

		// dd($list_data->get()->toArray());
		if ($request->request_date != null) {
			// dd($request->request_date);
			$exploded_date = explode(' to ', $request->request_date);
			$from_date = date('Y-m-d', strtotime($exploded_date[0]));
			$to_date = date('Y-m-d', strtotime($exploded_date[1]));
			// $date = date('Y-m-d', strtotime($request->request_date));
			// $list_data->whereDate('warranty_job_order_requests.created_at', $date);
			$list_data->whereBetween('warranty_job_order_requests.created_at', [$from_date . " 00:00:00", $to_date . " 23:59:59"]);
		}
		if ($request->failure_date != null) {
			$exploded_date = explode(' to ', $request->failure_date);
			$from_date = date('Y-m-d', strtotime($exploded_date[0]));
			$to_date = date('Y-m-d', strtotime($exploded_date[1]));
			$list_data->whereBetween('warranty_job_order_requests.failure_date', [$from_date, $to_date]);
		}
		if ($request->reg_no != null) {
			$list_data->where('vehicles.registration_number', 'like', '%' . $request->reg_no . '%');
		}
		if (substr($request->customer_id, -2) != "%>" && $request->customer_id != null) {
			$list_data->where('customers.id', $request->customer_id);
		}
		if (substr($request->model_id, -2) != "%>" && $request->model_id != null) {
			$list_data->where('mod.id', $request->model_id);
		}

		if ($request->statusIds != null) {
			$status_id = json_decode($request->statusIds);
			if ($status_id) {
				$list_data->where('warranty_job_order_requests.status_id', $status_id->id);
			}
		}
		if ($request->outletIds != null) {
			$outlet_ids = json_decode($request->outletIds);
			if ($outlet_ids) {
				$outlet_ids = array_column($outlet_ids, 'id');
				$list_data->whereIn('job_orders.outlet_id', $outlet_ids);
			}
		}
		if ($request->job_card_no != null) {
			$list_data->where('job_cards.job_card_number', 'like', '%' . $request->job_card_no . '%');
			//job_orders.number
		}

		/*
			if (Entrust::can('own-warranty-job-order-request')) {
				$list_data->where('warranty_job_order_requests.created_by_id', Auth::id());
			} else if (Entrust::can('own-outlet-warranty-job-order-request')) {
				$list_data->where('job_orders.outlet_id', Auth::user()->employee->outlet_id);
			}
		*/
		if (Entrust::can('all-outlet-warranty-job-order-request')) {

		} elseif (Entrust::can('verify-mapped-warranty-job-order-request')) {
			$list_data->leftJoin('business_outlet', 'business_outlet.outlet_id', 'outlets.id')
				->where('business_outlet.warranty_manager_id', Auth::user()->id);
		} elseif (Entrust::can('mapped-outlets-warranty-job-order-request')) {
			$list_data->leftJoin('employee_outlet', 'employee_outlet.outlet_id', 'outlets.id')
				->where('employee_outlet.employee_id', Auth::user()->employee->id);
		} elseif (Entrust::can('own-outlet-warranty-job-order-request')) {
			$list_data->where('job_orders.outlet_id', Auth::user()->employee->outlet_id);
		}

		if (Entrust::can('verify-only-warranty-job-order-request')) {
			$status_id = json_decode($request->statusIds);
			if ($status_id) {
				if ($status_id->id == 9103) {
					$list_data->whereIn('warranty_job_order_requests.status_id', [9103]);
				} elseif (9102) {
					$list_data->whereIn('warranty_job_order_requests.status_id', [9102]);
				} else {
					$list_data->whereIn('warranty_job_order_requests.status_id', [9101]);
				}
				// $list_data->where('warranty_job_order_requests.status_id', $status_id->id);
			} else {
				$list_data->whereIn('warranty_job_order_requests.status_id', [9101]);
			}
		} else {
			// if ($request->status_ids) {
			// $list_data->whereIn('warranty_job_order_requests.status_id', [9100, 9103]);
			// }
		}

		// dump($request->all());
		$list_data->whereNotIn('warranty_job_order_requests.status_id', [9104]);
		// $list_data->orderBy('warranty_job_order_requests.status_id', 'ASC');
		// $list_data->orderBy('warranty_job_order_requests.id', 'DESC');

		return Datatables::of($list_data)
			->rawColumns(['action'])
			->editColumn('created_at', function ($list_data) {
				return [
					'display' => e($list_data->created_at->format('d/m/Y h:i A')),
					'timestamp' => $list_data->created_at->timestamp,
				];
			})
			->addColumn('status', function ($list_data) {
				if ($list_data->status_id == 9102) {
					$status = '<p class="text-green">' . $list_data->status . '</p>';
				} elseif ($list_data->status_id == 9103) {

					// $status = '<a href="javascript:void(0)" class="my-tooltip"  data-html="true"  data-toggle="tooltip" data-placement="top"  data-title="' . $list_data->rejected_reason . '" title="' . $list_data->rejected_reason . '">' . $list_data->status . '</a>';

					$status = '<p class="text-red">' . $list_data->status . '</p>';
				} elseif ($list_data->status_id == 9101) {
					$status = '<p class="text-blue">' . $list_data->status . '</p>';
				} else {
					$status = $list_data->status;
				}
				return $status;
			})
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
				$logged_user_mail = Auth::user()->email;
				if ($logged_user_mail != null && !in_array($logged_user_mail, $cc_emails)) {
					$cc_emails[] = $logged_user_mail;
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
		$warranty_job_order_request = WarrantyJobOrderRequest::find(36);
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
			$warranty_job_order_request->approval_rating = $request->approval_rating;
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
			$warranty_job_order_request->ppr_reject_reason_id = $request->ppr_reject_reason_id;
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
				$logged_user_mail = Auth::user()->email;
				if ($logged_user_mail != null && !in_array($logged_user_mail, $cc_emails)) {
					$cc_emails[] = $logged_user_mail;
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
	public function saveTempData(Request $request) {
		// dd($request->all());
		$result = WarrantyJobOrderRequest::saveTempData($request->all());
		return response()->json($result);
	}
	public function getTempData() {
		try {
			$temp_data = WarrantyJobOrderRequest::where('status_id', 9104)->where('created_by_id', Auth::id())->first();
			if ($temp_data) {
				$temp_data = $temp_data->load($this->model::relationships('read'));
			}

			return response()->json([
				'success' => true,
				'request' => $temp_data,
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
	public function getFormData() {
		try {
			$employee_outlets = Auth::user()->employee ? Auth::user()->employee->employee_outlets : [];
			$models = VehicleModel::where('company_id', Auth::user()->company_id)->get();
			$bharat_stages = BharatStage::where('company_id', Auth::user()->company_id)->get();
			$split_order_types = SplitOrderType::where('company_id', Auth::user()->company_id)->where('claim_category_id', 11112)->get();
			$aggregates = Aggregate::all();
			$failure_types = FailureType::where('company_id', Auth::user()->company_id)->get()->prepend(['id' => '', 'name' => 'Select Failure Type']);
			$reject_reasons = PprRejectReason::where('company_id', Auth::user()->company_id)->get()->prepend(['id' => '', 'name' => 'Select Reason']);
			// $aggregates = Aggregate::where('company_id', Auth::user()->company_id)->get();
			// $temp_data = WarrantyJobOrderRequest::where('status_id', 9104)->first();
			// $temp_data = $temp_data->load($this->model::relationships('read'));

			return response()->json([
				'success' => true,
				'extras' => [
					'country_list' => Country::getDropDownList(),
					'default_country' => Country::where('name', 'India')->first(),
					'state_list' => [], //State::getDropDownList(),
					'city_list' => [], //City::getDropDownList(),
					'models' => $models,
					'employee_outlets' => $employee_outlets,
					'bharat_stages' => $bharat_stages,
					'split_order_types' => $split_order_types,
					'aggregates' => $aggregates,
					'failure_types' => $failure_types,
					'reject_reasons' => $reject_reasons,
					// 'temp_data' => $temp_data,
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

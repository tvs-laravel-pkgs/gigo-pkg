<?php

namespace Abs\GigoPkg;
use Abs\GigoPkg\JobCard;
use App\Config;
use App\Http\Controllers\Controller;
use App\QuoteType;
use App\ServiceOrderType;
use App\ServiceType;
use App\User;
use App\Vendor;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class JobCardController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	public function getJobCardFilter() {
		$params = [
			'config_type_id' => 42,
			'add_default' => true,
			'default_text' => "Select Status",
		];

		$this->data['extras'] = [
			'job_order_type_list' => ServiceOrderType::getDropDownList(),
			'service_type_list' => ServiceType::getDropDownList(),
			'quote_type_list' => QuoteType::getDropDownList(),
			'status_list' => Config::getDropDownList($params),
		];
		return response()->json($this->data);
	}

	public function getJobCardList(Request $request) {
		//dd($request->all());
		$job_cards = JobCard::select([
			'job_cards.id as job_card_id',
			'job_cards.job_card_number',
			'job_cards.bay_id',
			'job_orders.id as job_order_id',
			'job_orders.created_at',
			// DB::raw('DATE_FORMAT(job_cards.created_at,"%d/%m/%Y - %h:%i %p") as date'),
			'vehicles.registration_number',
			'models.model_name',
			'customers.name as customer_name',
			'job_cards.status_id',
			'configs.name as status',
			'service_types.name as service_type',
			'quote_types.name as quote_type',
			'outlets.code as outlet_code',
			'service_order_types.name as job_order_type',
			'bays.name as bay_name',
		])
			->join('job_orders', 'job_orders.id', 'job_cards.job_order_id')
			->leftJoin('bays', 'bays.job_order_id', 'job_orders.id')
			->join('outlets', 'outlets.id', 'job_cards.outlet_id')
			->join('gate_logs', 'gate_logs.job_order_id', 'job_orders.id')
			->leftJoin('vehicles', 'job_orders.vehicle_id', 'vehicles.id')
			->leftJoin('models', 'models.id', 'vehicles.model_id')
			->leftJoin('vehicle_owners', function ($join) {
				$join->on('vehicle_owners.vehicle_id', 'job_orders.vehicle_id')
					->whereRaw('vehicle_owners.from_date = (select MAX(vehicle_owners1.from_date) from vehicle_owners as vehicle_owners1 where vehicle_owners1.vehicle_id = job_orders.vehicle_id)');
			})
			->leftJoin('customers', 'vehicle_owners.customer_id', 'customers.id')
			->leftJoin('configs', 'configs.id', 'job_cards.status_id')
			->leftJoin('service_types', 'service_types.id', 'job_orders.service_type_id')
			->leftJoin('quote_types', 'quote_types.id', 'job_orders.quote_type_id')
			->leftJoin('service_order_types', 'service_order_types.id', 'job_orders.type_id')
		/*->whereRaw("IF (job_cards.`status_id` = '8220', job_cards.`floor_supervisor_id` IS  NULL, job_cards.`floor_supervisor_id` = '" . Auth::user()->id . "')")*/
			->where(function ($query) use ($request) {
				if (!empty($request->date)) {
					$query->whereDate('job_cards.created_at', date('Y-m-d', strtotime($request->date)));
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->reg_no)) {
					$query->where('vehicles.registration_number', 'LIKE', '%' . $request->reg_no . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->job_card_no)) {
					$query->where('job_cards.job_card_number', 'LIKE', '%' . $request->job_card_no . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->customer_id)) {
					$query->where('vehicle_owners.customer_id', $request->customer_id);
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->model_id)) {
					$query->where('vehicles.model_id', $request->model_id);
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->status_id)) {
					$query->where('job_cards.status_id', $request->status_id);
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->quote_type_id)) {
					$query->where('job_orders.quote_type_id', $request->quote_type_id);
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->service_type_id)) {
					$query->where('job_orders.service_type_id', $request->service_type_id);
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->job_order_type_id)) {
					$query->where('job_orders.type_id', $request->job_order_type_id);
				}
			});

		if (!Entrust::can('view-overall-outlets-job-card')) {
			if (Entrust::can('view-mapped-outlet-job-card')) {
				$job_cards->whereIn('job_cards.outlet_id', Auth::user()->employee->outlets->pluck('id')->toArray());
			} else if (Entrust::can('view-own-outlet-job-card')) {
				// $job_cards->where('job_cards.outlet_id', Auth::user()->employee->outlet_id)->whereRaw("IF (job_cards.`status_id` = '8220', job_cards.`floor_supervisor_id` IS  NULL, job_cards.`floor_supervisor_id` = '" . $request->floor_supervisor_id . "')");
				$job_cards->where('job_cards.outlet_id', Auth::user()->working_outlet_id);
			} else {
				$job_cards->where('job_cards.outlet_id', Auth::user()->working_outlet_id);
			}
		}

		$job_cards->whereNotNull('job_cards.status_id')->groupBy('job_cards.id')
			->orderBy('job_cards.created_at', 'DESC');
		//dd($job_cards);
		return Datatables::of($job_cards)
			->rawColumns(['status', 'action'])
			->editColumn('status', function ($job_card) {
				$status = $job_card->status_id == '8220' ? 'blue' : 'green';
				return '<span class="text-' . $status . '">' . $job_card->status . '</span>';
			})
			->addColumn('action', function ($job_card) {
				$img1 = asset('./public/theme/img/table/cndn/view.svg');
				$img1_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow-active.svg');
				$img_delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				$img_delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');
				$output = '';
				if (Entrust::can('gigo-job-cards')) {
					if (Entrust::can('inward-job-card-tab-bay-edit')) {
						$output .= '<a href="#!/job-card/bay-view/' . $job_card->job_card_id . '" class=""><img class="img-responsive" src="' . $img1 . '" alt="View" /></a>';
						if (!$job_card->bay_id) {
							$output .= '<a href="#!/job-card/assign-bay/' . $job_card->job_card_id . '"  class="btn btn-secondary-dark btn-sm">Assign Bay</a>';
						}
					} else {
						// if ($job_card->status_id == 8224 || $job_card->status_id == 8225 || $job_card->status_id == 8226 || $job_card->status_id == 8228) {
						$output .= '<a href="#!/job-card/estimate/' . $job_card->job_card_id . '" class=""><img class="img-responsive" src="' . $img1 . '" alt="View" /></a>';
						// }
					}
				}

				return $output;
			})
			->make(true);
	}

	public function getWarrantyJobOrderRequestList(Request $request) {
		//dd($request->all());
		$job_cards = JobCard::select([
			'job_cards.id as job_card_id',
			'job_cards.job_card_number',
			'job_cards.bay_id',
			'job_orders.id as job_order_id',
			DB::raw('DATE_FORMAT(job_cards.created_at,"%d/%m/%Y - %h:%i %p") as date'),
			'vehicles.registration_number',
			'models.model_name',
			'customers.name as customer_name',
			'configs.name as status',
			'service_types.name as service_type',
			'quote_types.name as quote_type',
			'service_order_types.name as job_order_type',

		])
			->leftJoin('job_orders', 'job_orders.id', 'job_cards.job_order_id')
			->leftJoin('gate_passes', 'gate_passes.job_card_id', 'job_cards.id')
			->leftJoin('vehicles', 'job_orders.vehicle_id', 'vehicles.id')
			->leftJoin('models', 'models.id', 'vehicles.model_id')
			->leftJoin('vehicle_owners', function ($join) {
				$join->on('vehicle_owners.vehicle_id', 'job_orders.vehicle_id')
					->whereRaw('vehicle_owners.from_date = (select MAX(vehicle_owners1.from_date) from vehicle_owners as vehicle_owners1 where vehicle_owners1.vehicle_id = job_orders.vehicle_id)');
			})
			->leftJoin('customers', 'vehicle_owners.customer_id', 'customers.id')
			->leftJoin('configs', 'configs.id', 'job_cards.status_id')
			->leftJoin('service_types', 'service_types.id', 'job_orders.service_type_id')
			->leftJoin('quote_types', 'quote_types.id', 'job_orders.quote_type_id')
			->leftJoin('service_order_types', 'service_order_types.id', 'job_orders.type_id')
			->whereRaw("IF (job_cards.`status_id` = '8220', job_cards.`floor_supervisor_id` IS  NULL, job_cards.`floor_supervisor_id` = '" . Auth::user()->id . "')")
			->where(function ($query) use ($request) {
				if (!empty($request->date)) {
					$query->whereDate('job_cards.created_at', date('Y-m-d', strtotime($request->date)));
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->reg_no)) {
					$query->where('vehicles.registration_number', 'LIKE', '%' . $request->reg_no . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->job_card_no)) {
					$query->where('job_cards.job_card_number', 'LIKE', '%' . $request->job_card_no . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->customer_id)) {
					$query->where('vehicle_owners.customer_id', $request->customer_id);
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->model_id)) {
					$query->where('vehicles.model_id', $request->model_id);
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->status_id)) {
					$query->where('job_cards.status_id', $request->status_id);
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->quote_type_id)) {
					$query->where('job_orders.quote_type_id', $request->quote_type_id);
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->service_type_id)) {
					$query->where('job_orders.service_type_id', $request->service_type_id);
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->job_order_type_id)) {
					$query->where('job_orders.type_id', $request->job_order_type_id);
				}
			})

			->groupBy('job_cards.id')
		//->get()
		;
		//dd($job_cards);
		return Datatables::of($job_cards)
			->rawColumns(['name', 'action'])
		/*->addColumn('name', function ($job_card) {
				$status = $job_card->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indicator ' . $status . '"></span>' . $job_card->name;
			})*/
			->addColumn('action', function ($job_card) {
				$img1 = asset('./public/theme/img/table/cndn/view.svg');
				$img1_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow-active.svg');
				$img_delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				$img_delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');
				$output = '';
				if (Entrust::can('gigo-job-cards')) {
					$output .= '<a href="#!/gigo-pkg/job-card/material-gatepass/' . $job_card->job_card_id . '" class=""><img class="img-responsive" src="' . $img1 . '" alt="View" /></a>';
					if (!$job_card->bay_id) {
						$output .= '<a href="#!/job-card/assign-bay/' . $job_card->job_card_id . '"  class="btn btn-secondary-dark btn-sm">Assign Bay</a>';
					}
				}

				return $output;
			})
			->make(true);
	}
	public function getJobCardFormData(Request $request) {
		$id = $request->id;
		if (!$id) {
			$job_card = new JobCard;
			$action = 'Add';
		} else {
			$job_card = JobCard::withTrashed()->find($id);
			$action = 'Edit';
		}
		$this->data['success'] = true;
		$this->data['job_card'] = $job_card;
		$this->data['action'] = $action;
		return response()->json($this->data);
	}

	public function saveJobCard(Request $request) {
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
					'unique:job_cards,code,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'name' => [
					'required:true',
					'min:3',
					'max:191',
					'unique:job_cards,name,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$job_card = new JobCard;
				$job_card->company_id = Auth::user()->company_id;
			} else {
				$job_card = JobCard::withTrashed()->find($request->id);
			}
			$job_card->fill($request->all());
			if ($request->status == 'Inactive') {
				$job_card->deleted_at = Carbon::now();
			} else {
				$job_card->deleted_at = NULL;
			}
			$job_card->save();

			DB::commit();
			if (!($request->id)) {
				return response()->json([
					'success' => true,
					'message' => 'Job Card Added Successfully',
				]);
			} else {
				return response()->json([
					'success' => true,
					'message' => 'Job Card Updated Successfully',
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

	public function deleteJobCard(Request $request) {
		DB::beginTransaction();
		// dd($request->id);
		try {
			$job_card = JobCard::withTrashed()->where('id', $request->id)->forceDelete();
			if ($job_card) {
				DB::commit();
				return response()->json(['success' => true, 'message' => 'Job Card Deleted Successfully']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

	public function getJobCards(Request $request) {
		$job_cards = JobCard::withTrashed()
			->with([
				'job-cards',
				'job-cards.user',
			])
			->select([
				'job_cards.id',
				'job_cards.name',
				'job_cards.code',
				DB::raw('IF(job_cards.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->where('job_cards.company_id', Auth::user()->company_id)
			->get();

		return response()->json([
			'success' => true,
			'job_cards' => $job_cards,
		]);
	}

	public function getVendorCodeSearchList(Request $request) {
		return Vendor::searchVendorCode($request);
	}

	public function getVendorDetails(Request $request) {

		$vendor_details = Vendor::with([
			'primaryAddress',
		])
			->find($request->id);

		if (!$vendor_details) {
			return response()->json([
				'success' => true,
				'error' => 'Vendor Not Found',
			]);
		}

		return response()->json([
			'success' => true,
			'vendor_details' => $vendor_details,
		]);
	}

//Myjob card table list
	public function getMyJobCardtableList(Request $request) {
		// $user_id = Auth::user()->id;
		$user_id = $request->user_id;
		$user_details = User::with([
			'employee',
			'employee.outlet',
			'employee.outlet.state',
		])
			->find($user_id);

		$date = explode('to', $request->date);

		$my_job_table_list = JobCard::select([
			'job_cards.id',
			'job_cards.job_card_number as jc_number',
			'vehicles.registration_number',
			DB::raw('COUNT(job_order_repair_orders.id) as no_of_ROTs'),
			DB::raw('CASE
                        WHEN count((CASE WHEN repair_order_mechanics.status_id = "8264" THEN repair_order_mechanics.status_id END )) > 0 THEN "Rescheduled"
                        WHEN count((CASE WHEN repair_order_mechanics.status_id = "8263" THEN repair_order_mechanics.status_id END )) =  COUNT(job_order_repair_orders.id) THEN "Completed"
                        WHEN count((CASE WHEN repair_order_mechanics.status_id = "8260" THEN repair_order_mechanics.status_id END )) =  COUNT(job_order_repair_orders.id) THEN "Not Yet Started"
                        WHEN count((CASE WHEN repair_order_mechanics.status_id = "8262" THEN repair_order_mechanics.status_id END )) > 0 && count((CASE WHEN repair_order_mechanics.status_id = "8264" THEN repair_order_mechanics.status_id END )) = 0 && count((CASE WHEN repair_order_mechanics.status_id = "8261" THEN repair_order_mechanics.status_id END )) = 0 THEN "Work Paused"
                        ELSE "Work InProgress" END AS status'),
			// 'configs.name as status',
			'job_cards.created_at',
			'models.model_number',
			'customers.name as customer_name',
		])
			->join('job_orders', 'job_orders.id', 'job_cards.job_order_id')
			->join('job_order_repair_orders', 'job_order_repair_orders.job_order_id', 'job_orders.id')
			->join('repair_order_mechanics', 'repair_order_mechanics.job_order_repair_order_id', 'job_order_repair_orders.id')
			->join('vehicles', 'vehicles.id', 'job_orders.vehicle_id')
			->join('vehicle_owners', function ($join) {
				$join->on('vehicle_owners.vehicle_id', 'job_orders.vehicle_id')
					->whereRaw('vehicle_owners.from_date = (select MAX(vehicle_owners1.from_date) from vehicle_owners as vehicle_owners1 where vehicle_owners1.vehicle_id = job_orders.vehicle_id)');
			})
			->join('customers', 'customers.id', 'vehicle_owners.customer_id')
			->join('models', 'models.id', 'vehicles.model_id')
		// ->join('configs', 'configs.id', 'repair_order_mechanics.status_id')
			->where('repair_order_mechanics.mechanic_id', $user_id)
			->groupBy('job_order_repair_orders.job_order_id')
			->orderBy('job_cards.created_at', 'DESC')

			->where(function ($query) use ($request, $date) {
				if (!empty($request->get('date'))) {
					$query->whereDate('job_cards.created_at', '>=', date('Y-m-d', strtotime($date[0])))
						->whereDate('job_cards.created_at', '<=', date('Y-m-d', strtotime($date[1])));
				}
			})

			->where(function ($query) use ($request) {
				if (!empty($request->reg_no)) {
					$query->where('vehicles.registration_number', 'LIKE', '%' . $request->reg_no . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->job_card_no)) {
					$query->where('job_cards.job_card_number', 'LIKE', '%' . $request->job_card_no . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->status_id)) {
					$query->where('job_cards.status_id', $request->status_id);
				}
			});

		return Datatables::of($my_job_table_list)
			->addColumn('status', function ($my_job_table_list) {
				if ($my_job_table_list->status == 'Rescheduled') {
					$status = '<span style="color:#fd7e14">Rescheduled</span>';
				} elseif ($my_job_table_list->status == 'Completed') {
					$status = '<span style="color:#28a745">Completed</span>';
				} elseif ($my_job_table_list->status == 'Not Yet Started') {
					$status = '<span style="color:#dc3545">Not Yet Started</span>';
				} elseif ($my_job_table_list->status == 'Work Paused') {
					$status = '<span style="color:#4fc3f7">Work Paused</span>';
				} else {
					$status = '<span style="color:#007bff">Work Inprogress</span>';
				}
				return $status;
			})

			->addColumn('action', function ($my_job_table_list) use ($user_id) {
				$img1 = asset('./public/theme/img/table/cndn/view.svg');
				$img1_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow-active.svg');
				$img_delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				$img_delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');
				$output = '';
				if (Entrust::can('mobile-my-job-cards')) {
					$output .= '<a href="#!/my-jobcard/view/' . $user_id . '/' . $my_job_table_list->id . '" class=""><img class="img-responsive" src="' . $img1 . '" alt="View" /></a>';

				}

				return $output;
			})
			->make(true);
	}

}
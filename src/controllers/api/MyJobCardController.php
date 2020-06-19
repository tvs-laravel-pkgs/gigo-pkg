<?php

namespace Abs\GigoPkg\Api;

use Abs\GigoPkg\JobCard;
use App\Http\Controllers\Controller;
use App\User;
use DB;
use Illuminate\Http\Request;
use Validator;

class MyJobCardController extends Controller {
	public $successStatus = 200;

	public function getMyJobCardList(Request $request) {
		// dd($request->all());
		try {
			$validator = Validator::make($request->all(), [
				'user_id' => [
					'required',
					'exists:users,id',
					'integer',
				],
			]);
			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'errors' => $validator->errors()->all(),
				]);
			}

			$user_details = User::with([
				'employee',
				'employee.outlet',
				'employee.outlet.state',
			])
				->find($request->user_id);

			$my_job_card_list = JobCard::select([
				'job_cards.id',
				'job_cards.job_card_number as jc_number',
				'vehicles.registration_number',
				DB::raw('COUNT(job_order_repair_orders.id) as no_of_ROTs'),
				'configs.name as status',
				DB::raw('DATE_FORMAT(job_cards.created_at,"%d/%m/%Y") as date'),
				DB::raw('DATE_FORMAT(job_cards.created_at,"%h:%i %p") as time'),
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
				->join('configs', 'configs.id', 'job_cards.status_id')
				->where('repair_order_mechanics.mechanic_id', $request->user_id)
				->groupBy('job_order_repair_orders.job_order_id')
				->get();

			return response()->json([
				'success' => true,
				'user_details' => $user_details,
				'my_job_card_list' => $my_job_card_list,
			]);
		} catch (Exception $e) {
			return response()->json([
				'success' => false,
				'errors' => ['Exception Error' => $e->getMessage()],
			]);
		}
	}
}
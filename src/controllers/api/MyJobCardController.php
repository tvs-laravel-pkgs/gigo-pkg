<?php

namespace Abs\GigoPkg\Api;

use App\Employee;
use App\Http\Controllers\Controller;
use DB;
use Illuminate\Http\Request;
use Validator;

class MyJobCardController extends Controller {
	public $successStatus = 200;

	public function getMyJobCardList(Request $request) {
		// dd($request->all());
		try {
			$validator = Validator::make($request->all(), [
				'employee_id' => [
					'required',
					'exists:employees,id',
					'integer',
				],
			]);
			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'errors' => $validator->errors()->all(),
				]);
			}

			$my_job_card_list = Employee::select([
				'job_cards.id',
				'job_cards.job_card_number as jc_number',
				'vehicles.registration_number',
				DB::raw('COUNT(job_order_repair_orders.id) as no_of_ROTs'),
				'configs.name as status',
			])
				->join('users', 'users.entity_id', 'employees.id')
				->join('repair_order_mechanics', 'repair_order_mechanics.mechanic_id', 'users.id')
				->join('job_order_repair_orders', 'job_order_repair_orders.id', 'repair_order_mechanics.job_order_repair_order_id')
				->join('job_orders', 'job_orders.id', 'job_order_repair_orders.job_order_id')
				->join('gate_logs', 'gate_logs.id', 'job_orders.gate_log_id')
				->join('vehicles', 'vehicles.id', 'gate_logs.vehicle_id')
				->join('job_cards', 'job_cards.job_order_id', 'job_orders.id')
				->join('configs', 'configs.id', 'job_cards.status_id')
				->where('users.user_type_id', 1)
				->where('employees.id', $request->employee_id)
				->groupBy('job_cards.job_card_number')
				->get();

			return response()->json([
				'success' => true,
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
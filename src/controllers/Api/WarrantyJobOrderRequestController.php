<?php

namespace Abs\GigoPkg\Api;

use Abs\BasicPkg\Traits\CrudTrait;
use App\Http\Controllers\Controller;
use App\WarrantyJobOrderRequest;
use Illuminate\Http\Request;

class WarrantyJobOrderRequestController extends Controller {
	use CrudTrait;
	public $model = WarrantyJobOrderRequest::class;
	public $successStatus = 200;

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
}
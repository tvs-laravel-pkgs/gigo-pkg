<?php

namespace Abs\GigoPkg\Api;

use App\GateLog;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class VehicleGatePassController extends Controller {
	public $successStatus = 200;

	public function __construct() {
		$this->success_code = 200;
		$this->permission_denied_code = 401;
	}

	public function saveVehicleGateInEntry(Request $request) {
		// dd($request->all());
		return GateLog::saveVehicleGateInEntry($request);

	}

}

<?php

namespace Abs\GigoPkg\Api;

use Abs\BasicPkg\Traits\CrudTrait;
use App\Http\Controllers\Controller;
use App\VehicleServiceScheduleServiceType;

class VehicleServiceScheduleServiceTypeController extends Controller {
	use CrudTrait;
	public $model = VehicleServiceScheduleServiceType::class;
	public $successStatus = 200;

}
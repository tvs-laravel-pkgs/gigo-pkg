<?php

namespace Abs\GigoPkg\Api;

use Abs\BasicPkg\Traits\CrudTrait;
use App\Http\Controllers\Controller;
use App\VehiclePrimaryApplication;

class VehiclePrimaryApplicationController extends Controller {
	use CrudTrait;
	public $model = VehiclePrimaryApplication::class;
	public $successStatus = 200;

}
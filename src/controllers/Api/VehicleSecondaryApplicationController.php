<?php

namespace Abs\GigoPkg\Api;

use Abs\BasicPkg\Traits\CrudTrait;
use App\Http\Controllers\Controller;
use App\VehicleSecondaryApplication;

class VehicleSecondaryApplicationController extends Controller {
	use CrudTrait;
	public $model = VehicleSecondaryApplication::class;
	public $successStatus = 200;

}
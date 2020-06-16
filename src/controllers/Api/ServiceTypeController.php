<?php

namespace Abs\GigoPkg\Api;

use Abs\BasicPkg\Traits\CrudTrait;
use App\Http\Controllers\Controller;
use App\ServiceType;

class ServiceTypeController extends Controller {
	use CrudTrait;
	public $model = ServiceType::class;
	public $successStatus = 200;

}
<?php

namespace Abs\GigoPkg\Api;

use Abs\BasicPkg\Traits\CrudTrait;
use App\Http\Controllers\Controller;
use App\RepairOrder;

class RepairOrderController extends Controller {
	use CrudTrait;
	public $model = RepairOrder::class;
	public $successStatus = 200;

}
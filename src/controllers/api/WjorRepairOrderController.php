<?php

namespace Abs\GigoPkg\Api;

use Abs\BasicPkg\Traits\CrudTrait;
use App\Http\Controllers\Controller;
use App\WjorRepairOrder;

class WjorRepairOrderController extends Controller {
	use CrudTrait;
	public $model = WjorRepairOrder::class;
	public $successStatus = 200;

}
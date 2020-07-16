<?php

namespace Abs\GigoPkg\Api;

use Abs\BasicPkg\Traits\CrudTrait;
use App\Http\Controllers\Controller;
use App\SplitOrderType;

class SplitOrderTypeController extends Controller {
	use CrudTrait;
	public $model = SplitOrderType::class;
	public $successStatus = 200;

}
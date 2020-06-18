<?php

namespace Abs\GigoPkg\Api;

use Abs\BasicPkg\Traits\CrudTrait;
use App\Http\Controllers\Controller;
use App\WarrantyJobOrderRequest;

class WarrantyJobOrderRequestController extends Controller {
	use CrudTrait;
	public $model = WarrantyJobOrderRequest::class;
	public $successStatus = 200;

}
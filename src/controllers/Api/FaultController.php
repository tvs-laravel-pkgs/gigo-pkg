<?php

namespace Abs\GigoPkg\Api;

use Abs\BasicPkg\Traits\CrudTrait;
use App\Fault;
use App\Http\Controllers\Controller;

class FaultController extends Controller {
	use CrudTrait;
	public $model = Fault::class;
	public $successStatus = 200;

}
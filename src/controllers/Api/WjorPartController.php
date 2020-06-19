<?php

namespace Abs\GigoPkg\Api;

use Abs\BasicPkg\Traits\CrudTrait;
use App\Http\Controllers\Controller;
use App\WjorPart;

class WjorPartController extends Controller {
	use CrudTrait;
	public $model = WjorPart::class;
	public $successStatus = 200;

}
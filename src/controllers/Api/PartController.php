<?php

namespace Abs\GigoPkg\Api;

use Abs\BasicPkg\Traits\CrudTrait;
use App\Http\Controllers\Controller;
use App\Part;

class PartController extends Controller {
	use CrudTrait;
	public $model = Part::class;
	public $successStatus = 200;

}
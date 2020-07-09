<?php

namespace Abs\GigoPkg\Api;

use Abs\BasicPkg\Traits\CrudTrait;
use App\ComplaintGroup;
use App\Http\Controllers\Controller;

class ComplaintGroupController extends Controller {
	use CrudTrait;
	public $model = ComplaintGroup::class;
	public $successStatus = 200;

}
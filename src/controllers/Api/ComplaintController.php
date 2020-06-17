<?php

namespace Abs\GigoPkg\Api;

use Abs\BasicPkg\Traits\CrudTrait;
use App\Complaint;
use App\Http\Controllers\Controller;

class ComplaintController extends Controller {
	use CrudTrait;
	public $model = Complaint::class;
	public $successStatus = 200;

}
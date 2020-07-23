<?php

namespace Abs\GigoPkg\Api;

use Abs\BasicPkg\Traits\CrudTrait;
use App\Http\Controllers\Controller;
use App\Vendor;

class VendorController extends Controller {
	use CrudTrait;
	public $model = Vendor::class;

	public $successStatus = 200;

}

<?php

namespace Abs\GigoPkg\Api;

use Abs\BasicPkg\Traits\CrudTrait;
use App\Http\Controllers\Controller;
use App\PartSupplier;

class PartSupplierController extends Controller {
	use CrudTrait;
	public $model = PartSupplier::class;
	public $successStatus = 200;

}
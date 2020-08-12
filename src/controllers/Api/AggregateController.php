<?php

namespace Abs\GigoPkg\Api;

use Abs\BasicPkg\Traits\CrudTrait;
use App\Aggregate;
use App\Http\Controllers\Controller;
use App\SubAggregate;
use App\User;
use Auth;
use Illuminate\Http\Request;

class AggregateController extends Controller {
	use CrudTrait;
	public $model = Aggregate::class;
	public $successStatus = 200;

	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	public function getSubAggregates(Request $r) {
		$sub_aggregates = SubAggregate::where('company_id', Auth::user()->company_id)->where('aggregate_id', $r->id)->get();
		return response()->json(['success' => true, 'options' => $sub_aggregates]);

	}
}

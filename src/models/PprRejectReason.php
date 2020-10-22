<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;

class PprRejectReason extends BaseModel {
	use SeederTrait;
	protected $table = 'ppr_reject_reasons';

	protected $fillable = [
		"id",
		"company_id",
		"name",
	];
}

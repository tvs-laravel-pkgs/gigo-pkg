<?php

namespace Abs\GigoPkg;
use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;

class PendingReason extends BaseModel {
	use SeederTrait;
	protected $table = 'pending_reasons';
	public $timestamps = false;
	protected $fillable = [
		"company_id", "name"
	];
}

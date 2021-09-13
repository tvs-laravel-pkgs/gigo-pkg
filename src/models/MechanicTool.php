<?php

namespace Abs\GigoPkg;
use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;

class ToolsLog extends BaseModel {
	use SeederTrait;
	protected $table = 'tools_logs';
	public $timestamps = false;
	protected $fillable = [
		"company_id",
		"employee_id",
		// "name",
	];

	public function getCreatedAtAttribute($value) {
		return empty($value) ? '' : date('d-m-Y h:i A', strtotime($value));
	}
}

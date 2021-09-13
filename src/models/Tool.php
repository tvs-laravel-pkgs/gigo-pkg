<?php

namespace Abs\GigoPkg;
use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;

class Tool extends BaseModel {
	use SeederTrait;
	protected $table = 'tools';
	public $timestamps = false;
	protected $fillable = [
		"company_id",
		"code",
		"name",
	];

	public function getCreatedAtAttribute($value) {
		return empty($value) ? '' : date('d-m-Y h:i A', strtotime($value));
	}
}

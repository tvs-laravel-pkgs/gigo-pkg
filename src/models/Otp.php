<?php

namespace Abs\GigoPkg;
use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;

class Otp extends BaseModel {
	use SeederTrait;
	protected $table = 'otps';
	public $timestamps = false;
	protected $fillable = [
		"entity_type_id",
		"entity_id",
		"otp_no",
	];

}

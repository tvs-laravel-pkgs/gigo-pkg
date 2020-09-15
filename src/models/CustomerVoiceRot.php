<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;

class CustomerVoiceRot extends BaseModel {
	use SeederTrait;
	protected $table = 'customer_voice_repair_orders';
	public $timestamps = false;
	protected $fillable =
		["customer_voice_id", "repair_order_id"]
	;
}

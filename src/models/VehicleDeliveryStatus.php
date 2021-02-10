<?php

namespace Abs\GigoPkg;
use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;

class VehicleDeliveryStatus extends BaseModel {
	use SeederTrait;
	protected $table = 'vehicle_delivery_statuses';
	public $timestamps = false;
	protected $fillable = [
		"company_id",
		"name",
	];
}

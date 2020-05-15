<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\Company;
use App\Config;
use App\Vendor;
use Abs\GigoPkg\GatePass;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GatePassDetail extends Model {
    use SeederTrait;
	use SoftDeletes;
	protected $table = 'gate_pass_details';
	public $timestamps = true;
	protected $fillable =
		["id","gate_pass_id","vendor_type_id","vendor_id","work_order_no","work_order_description"]
	;
}

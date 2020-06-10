<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use Abs\StatusPkg\Status;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bay extends Model {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'bays';
	public $timestamps = true;
	protected $fillable =
		["id", "short_name", "outlet_id", "name", "status_id", "job_order_id", "area_type_id"]
	;

	public function status() {
		//issue: wrong relation
		// return $this->belongsTo('Abs\StatusPkg\Status', 'status_id');
		return $this->belongsTo('App\Config', 'status_id');
	}

	public function jobOrder() {
		return $this->belongsTo('App\JobOrder', 'job_order_id');
	}

}

<?php

namespace Abs\GigoPkg;
use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class FloatStock extends BaseModel {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'floating_stocks';
	public $timestamps = true;
	protected $fillable = [];

	public function status() {
		return $this->belongsTo('App\Config', 'status_id');
	}

	public function part() {
		return $this->belongsTo('App\Part', 'part_id');
	}
}

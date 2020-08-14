<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PartStock extends Model {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'part_stocks';
	public $timestamps = true;
	protected $fillable = [
		'company_id',
		'outlet_id',
		'part_id',
		'stock',
		'cost_price',
		'mrp',
	];

	public function outlet() {
		return $this->belongsTo('App\Outlet', 'outlet_id');
	}

}
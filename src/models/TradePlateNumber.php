<?php

namespace Abs\GigoPkg;
use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use \Venturecraft\Revisionable\RevisionableTrait;
class TradePlateNumber extends BaseModel {
	use SeederTrait;
	use SoftDeletes;
	use RevisionableTrait;
	protected $table = 'trade_plate_numbers';
	public $timestamps = true;
	protected $fillable = [
		"company_id",
		"outlet_id",
		"trade_plate_number",
	];

	protected $revisionCreationsEnabled = true;
    protected $revisionForceDeleteEnabled = true;

	public function getInsuranceValidityFromAttribute($value) {
		return empty($value) ? '' : date('d-m-Y', strtotime($value));
	}

	public function getInsuranceValidityToAttribute($value) {
		return empty($value) ? '' : date('d-m-Y', strtotime($value));
	}

	public function outlet() {
		return $this->belongsTo('App\Outlet', 'outlet_id');
	}

}

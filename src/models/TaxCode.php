<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\Company;
use App\Config;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaxCode extends Model {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'tax_codes';
	public $timestamps = true;
	protected $fillable = [
		'company_id',
		'code',
		'type_id',
	];

}

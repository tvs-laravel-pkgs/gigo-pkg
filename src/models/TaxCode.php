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

	public static function getList($params = [], $add_default = true, $default_text = 'Select Tax Code') {
		$list = Collect(Self::select([
			'id',
			'code as name',
		])
				->orderBy('code')
				->get());
		if ($add_default) {
			$list->prepend(['id' => '', 'name' => $default_text]);
		}
		return $list;
	}

}

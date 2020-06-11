<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Fault extends Model {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'faults';
	public $timestamps = true;
	protected $fillable = [
		"id",
		"company_id",
		"code",
		"name",
	];

	public static function getList($params = [], $add_default = true, $default_text = 'Select Fault Type') {
		$list = Collect(Self::select([
			'id',
			'name',
		])
				->orderBy('name')
				->get());
		if ($add_default) {
			$list->prepend(['id' => '', 'name' => $default_text]);
		}
		return $list;
	}
}

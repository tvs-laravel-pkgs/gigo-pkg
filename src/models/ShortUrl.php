<?php

namespace Abs\GigoPkg;
use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;

class ShortUrl extends BaseModel {
	use SeederTrait;
	protected $table = 'short_urls';
	public $timestamps = false;

	protected $fillable = [
		"url",
		"token",
		"created_by_id",
	];

	public static $chars = "abcdfghjkmnpqrstvwxyz|ABCDFGHJKLMNPQRSTVWXYZ|0123456789";

	public static function generateRandomString($length) {
		$sets = explode('|', self::$chars);
		$all = '';
		$randString = '';
		foreach ($sets as $set) {
			$randString .= $set[array_rand(str_split($set))];
			$all .= $set;
		}
		$all = str_split($all);
		for ($i = 0; $i < $length - count($sets); $i++) {
			$randString .= $all[array_rand($all)];
		}
		$randString = str_shuffle($randString);
		return $randString;
	}

	public static function createLink($url, $maxlength) {

		$shortCode = self::generateRandomString($maxlength);

		//Check URL already exist
		// $link = ShortUrl::where('url', $url)->first();

		// if (!$link) {
		// 	$link = new ShortUrl;
		// }

		$short_url = self::formatLink($shortCode);

		dd($short_url);
		return $short_url;
	}

	public static function formatLink($shortCode) {
		$short_url = env('APP_URL') . '/' . $shortCode;
		dump(env('APP_URL'));
		dd($short_url);
		return $short_url;
	}
}

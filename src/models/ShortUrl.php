<?php

namespace Abs\GigoPkg;
use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;
use Auth;
use Carbon\Carbon;

class ShortUrl extends BaseModel {
	use SeederTrait;
	protected $table = 'short_urls';
	public $timestamps = false;

	protected $fillable = [
		"url",
		"token",
	];

	protected static $chars = "abcdfghjkmnpqrstvwxyz|ABCDFGHJKLMNPQRSTVWXYZ|0123456789";

	public static function generateRandomString($maxlength) {

		$sets = explode('|', self::$chars);
		$all = '';
		$randString = '';
		foreach ($sets as $set) {
			$randString .= $set[array_rand(str_split($set))];
			$all .= $set;
		}
		$all = str_split($all);
		for ($i = 0; $i < $maxlength - count($sets); $i++) {
			$randString .= $all[array_rand($all)];
		}
		$randString = str_shuffle($randString);
		return $randString;
	}

	public static function createShortLink($url, $maxlength, $created_by = null) {

		$shortCode = self::generateRandomString($maxlength);

		//Check URL already exist
		$link = ShortUrl::where('url', $url)->first();
		if ($link) {
			$link->updated_by_id = $created_by ? $created_by : Auth::user()->id;
			$link->updated_at = Carbon::now();
		} else {
			$link = new ShortUrl;
			$link->created_by_id =  $created_by ? $created_by : Auth::user()->id;
			$link->created_at = Carbon::now();
			$link->url = $url;
		}

		$short_url = url('/link/' . $shortCode);

		$link->token = $short_url;
		$link->save();

		return $short_url;
	}
}

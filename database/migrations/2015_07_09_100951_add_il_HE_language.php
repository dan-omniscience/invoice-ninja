<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIlHELanguage extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        DB::table('languages')->insert(['name' => 'Hebrew', 'locale' => 'il_HE']);
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        if ($language = \App\Models\Language::whereLocale('il_HE')->first()) {
            $language->delete();
        }
	}

}

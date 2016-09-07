<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateInfoOnUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->char('gender')->nullable()->default(null)->after('remember_token');
            $table->string('location')->nullable()->default(null)->after('gender');
            $table->string('contact')->nullable()->default(null)->after('location');
            $table->string('avatar')->nullable()->default(null)->after('contact');
            $table->date('birth_date')->nullable()->default(null)->after('avatar');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('gender');
            $table->dropColumn('location');
            $table->dropColumn('contact');
            $table->dropColumn('avatar');
            $table->dropColumn('birth_date');
        });
    }
}

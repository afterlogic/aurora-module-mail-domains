<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

class CreateDomainsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Capsule::schema()->create('domains', function (Blueprint $table) {
            $table->increments('Id');
            $table->integer('TenantId')->default(0);
            $table->integer('MailServerId')->default(0);
            $table->string('Name')->default('');
            $table->integer('Count')->default(0);
            $table->json('Properties')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Capsule::schema()->dropIfExists('domains');
    }
}

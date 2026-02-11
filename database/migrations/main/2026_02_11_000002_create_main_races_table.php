<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'main';

    public function up()
    {
        Schema::connection('main')->create('main_races', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('tenant_race_id');
            $table->unsignedBigInteger('tenant_event_id');
            $table->string('name');
            $table->integer('distance')->nullable();
            $table->time('start_time')->nullable();
            $table->integer('duration')->nullable();
            $table->integer('display_order')->default(0);
            $table->timestamps();

            $table->index('account_id');
            $table->index(['account_id', 'tenant_race_id']);
            $table->index(['account_id', 'tenant_event_id']);
        });
    }

    public function down()
    {
        Schema::connection('main')->dropIfExists('main_races');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'main';

    public function up()
    {
        Schema::connection('main')->create('main_waves', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('tenant_wave_id');
            $table->unsignedBigInteger('tenant_event_id');
            $table->unsignedBigInteger('tenant_race_id');
            $table->string('name');
            $table->integer('wave_number');
            $table->time('scheduled_start_time');
            $table->timestamp('real_start_time')->nullable();
            $table->string('status')->default('pending'); // pending, started, finished
            $table->timestamps();

            $table->index('account_id');
            $table->index(['account_id', 'tenant_wave_id']);
            $table->index(['account_id', 'tenant_event_id']);
        });
    }

    public function down()
    {
        Schema::connection('main')->dropIfExists('main_waves');
    }
};

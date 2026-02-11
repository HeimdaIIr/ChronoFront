<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'main';

    public function up()
    {
        Schema::connection('main')->create('main_entrants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('tenant_entrant_id');
            $table->unsignedBigInteger('tenant_event_id')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('bib_number')->nullable();
            $table->string('gender')->nullable();
            $table->integer('age')->nullable();
            $table->string('category')->nullable();
            $table->string('club')->nullable();
            $table->string('team')->nullable();
            $table->string('rfid')->nullable();
            $table->time('start_time')->nullable();
            $table->timestamps();

            $table->index('account_id');
            $table->index(['account_id', 'tenant_entrant_id']);
            $table->index(['account_id', 'tenant_event_id']);
            $table->index(['account_id', 'bib_number']);
        });
    }

    public function down()
    {
        Schema::connection('main')->dropIfExists('main_entrants');
    }
};

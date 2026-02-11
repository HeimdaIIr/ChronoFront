<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'main';

    public function up()
    {
        Schema::connection('main')->create('main_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('tenant_result_id');
            $table->unsignedBigInteger('tenant_entrant_id');
            $table->unsignedBigInteger('tenant_race_id');
            $table->unsignedBigInteger('tenant_event_id');
            $table->string('status')->default('ns'); // ns, v, dns, dnf, dsq
            $table->time('time')->nullable();
            $table->integer('rank_scratch')->nullable();
            $table->integer('rank_category')->nullable();
            $table->integer('rank_gender')->nullable();
            $table->timestamps();

            $table->index('account_id');
            $table->index(['account_id', 'tenant_result_id']);
            $table->index(['account_id', 'tenant_event_id']);
            $table->index(['account_id', 'tenant_race_id']);
        });
    }

    public function down()
    {
        Schema::connection('main')->dropIfExists('main_results');
    }
};

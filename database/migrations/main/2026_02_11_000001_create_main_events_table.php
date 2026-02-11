<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'main';

    public function up()
    {
        Schema::connection('main')->create('main_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id'); // Reference to accounts table
            $table->unsignedBigInteger('tenant_event_id'); // Original ID in tenant DB
            $table->string('name');
            $table->date('date')->nullable();
            $table->string('location')->nullable();
            $table->string('organizer')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('alert_threshold')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('account_id');
            $table->index(['account_id', 'tenant_event_id']);
        });
    }

    public function down()
    {
        Schema::connection('main')->dropIfExists('main_events');
    }
};

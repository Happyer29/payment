<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shops', function (Blueprint $table) {
            $table->id();

            // shop name
            $table->string('name')->nullable(false);
            // shop host
            $table->string('host')->nullable(false);
            // shop owner
            $table->unsignedBigInteger('owner_id')->nullable(false);
            $table->foreign('owner_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade')
            ;
            // shop active/inactive
            $table->boolean('active')->nullable(false)->default(false);
            // shop moderated
            $table->boolean('moderated')->nullable(false)->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shops');
    }
};

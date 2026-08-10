<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('project_recaps', function (Blueprint $table) {
            $table->string('id', 20)->primary(); // Custom ID: RP-00001, RP-00002

            $table->string('project_name', 255);
            $table->unsignedBigInteger('total_rab')->default(0);
            $table->string('design_file')->nullable();
            $table->string('design_file_name', 255)->nullable();

            $table->uuid('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();

            $table->timestamps();

            $table->index('project_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_recaps');
    }
};

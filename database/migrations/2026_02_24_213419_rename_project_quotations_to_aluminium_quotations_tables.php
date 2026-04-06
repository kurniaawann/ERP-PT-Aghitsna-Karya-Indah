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
        // Rename tables from project_quotations to aluminium_quotations
        Schema::rename('project_quotations', 'aluminium_quotations');
        Schema::rename('project_quotation_groups', 'aluminium_quotation_groups');
        Schema::rename('project_quotation_items', 'aluminium_quotation_items');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to project_quotations
        Schema::rename('aluminium_quotations', 'project_quotations');
        Schema::rename('aluminium_quotation_groups', 'project_quotation_groups');
        Schema::rename('aluminium_quotation_items', 'project_quotation_items');
    }
};

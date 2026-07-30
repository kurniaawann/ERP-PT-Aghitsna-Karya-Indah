<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aluminium_quotations', function (Blueprint $table) {
            $table->renameColumn('recipient_address', 'project_description');
        });

        Schema::table('project_quotations', function (Blueprint $table) {
            $table->renameColumn('recipient_address', 'project_description');
        });
    }

    public function down(): void
    {
        Schema::table('aluminium_quotations', function (Blueprint $table) {
            $table->renameColumn('project_description', 'recipient_address');
        });

        Schema::table('project_quotations', function (Blueprint $table) {
            $table->renameColumn('project_description', 'recipient_address');
        });
    }
};

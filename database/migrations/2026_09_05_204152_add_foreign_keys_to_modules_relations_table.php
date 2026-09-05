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
        Schema::table('modules_relations', function (Blueprint $table) {
            $table->foreign(['modules_father_id'])->references(['id'])->on('modules')->onDelete('cascade');
            $table->foreign(['modules_son_id'])->references(['id'])->on('modules')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('modules_relations', function (Blueprint $table) {
            $table->dropForeign('modules_relations_modules_father_id_foreign');
            $table->dropForeign('modules_relations_modules_son_id_foreign');
        });
    }
};

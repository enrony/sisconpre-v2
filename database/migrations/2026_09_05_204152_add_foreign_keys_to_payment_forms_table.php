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
        Schema::table('payment_forms', function (Blueprint $table) {
            $table->foreign(['grupos_trabajos_user_id'])->references(['id'])->on('grupos_trabajos_users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_forms', function (Blueprint $table) {
            $table->dropForeign('payment_forms_grupos_trabajos_user_id_foreign');
        });
    }
};

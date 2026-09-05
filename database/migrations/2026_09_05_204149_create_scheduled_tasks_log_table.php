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
        Schema::create('scheduled_tasks_log', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('task_name');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->boolean('successful')->default(true);
            $table->text('error_message')->nullable();
            $table->unsignedInteger('records_processed')->default(0);
            $table->boolean('status')->default(false)->comment('0: Pendiente/En Ejecución, 1: Terminado (Éxito o Fallo)');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_tasks_log');
    }
};

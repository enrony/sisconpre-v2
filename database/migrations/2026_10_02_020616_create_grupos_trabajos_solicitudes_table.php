<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Solicitudes para unirse a un grupo de trabajo con su código: las aprueba
     * o rechaza el dueño del grupo (o un super-usuario si el grupo no tiene
     * dueño). Al aprobarse se crea la membresía (`grupos_trabajos_users`).
     */
    public function up(): void
    {
        Schema::create('grupos_trabajos_solicitudes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('idgrupo_trabajo');
            $table->unsignedBigInteger('user_id');
            $table->string('estatus', 12)->default('pendiente'); // pendiente | aprobada | rechazada
            $table->unsignedBigInteger('decidido_por')->nullable();
            $table->timestamp('decidido_at')->nullable();
            $table->timestamps();

            $table->index(['idgrupo_trabajo', 'estatus']);
            $table->index(['user_id', 'estatus']);
            $table->foreign('idgrupo_trabajo')->references('id')->on('grupos_trabajos')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('decidido_por')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grupos_trabajos_solicitudes');
    }
};

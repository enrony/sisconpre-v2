<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            // Archivos de rutas por módulo del panel (portados del sistema legado).
            // Cada uno se protege con `permission:<clave>.listar`: sin ese permiso
            // no se accede a ninguna acción del módulo. El ajuste fino por acción
            // (registrar / editar / eliminar) es trabajo de Fase 6/9.
            $panel = [
                'action' => 'Action.php',
                'banks' => 'Bank.php',
                'bank_account_types' => 'BankAccountTypes.php',
                'franquicias' => 'Franquicias.php',
                'payment_forms' => 'PaymentForm.php',
                'payment_methods' => 'PaymentMethod.php',
                'payment_report' => 'PaymentReport.php',
                'payment_report:estatu' => 'PaymentReportsMovementsEstatuRoute.php',
                'profile' => 'Profile.php',
                'type_payment_record' => 'TypePaymentRecord.php',
                'cities' => 'cities.php',
                'clientes' => 'clientes.php',
                'departamentos' => 'departamentos.php',
                'festivos' => 'festivos.php',
                'frecuencias' => 'frecuencias.php',
                'grupos_trabajo' => 'gruposTrabajo.php',
                'modules' => 'modules.php',
                'prestamos' => 'prestamos.php',
                'tipo_documentos' => 'tipo_documentos.php',
                'tipo_prestamo' => 'tipo_prestamos.php',
            ];

            foreach ($panel as $key => $file) {
                $permission = explode(':', $key)[0].'.listar';

                Route::middleware(['web', 'auth', 'verified', "permission:{$permission}"])
                    ->group(base_path("routes/{$file}"));
            }
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Alias de middleware de spatie/laravel-permission (v6 no los registra solo).
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

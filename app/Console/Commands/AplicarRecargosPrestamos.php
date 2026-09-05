<?php

namespace App\Console\Commands;

use App\Models\PrestamosDias;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class AplicarRecargosPrestamos extends Command
{
    protected $signature = 'prestamos:aplicar-recargos';

    protected $description = 'Aplica recargos a los préstamos según las condiciones.';

    public function handle()
    {
        $fechaActual = Carbon::today();

        $prestamosDias = PrestamosDias::where('estado', 1)
            ->where('surcharge_applied', false)
            ->where('day_apply_surcharge', '<=', $fechaActual)
            ->where('apply_surcharge', true)
            ->where('pagado', false)
            ->whereHas('Prestamo', function ($query) {
                $query->where('estado', 1);
            })
            // ->with('Prestamo.country')
            ->with('Prestamo')
            ->get();

        $countryIds = $prestamosDias->pluck('Prestamo.country_id')->unique()->toArray();

        $festivosPorPais = CountryHoliday::whereIn('country_id', $countryIds)->get()->groupBy('country_id');

        foreach ($prestamosDias as $prestamoDia) {
            $fechaRecargo = Carbon::parse($prestamoDia->day_apply_surcharge);
            $countryId = $prestamoDia->Prestamo->country_id;

            // Obtener festivos para el país actual (como array asociativo)
            $festivos = array_flip($festivosPorPais->get($countryId)->pluck('date')->map(function ($date) {
                return Carbon::parse($date)->format('Y-m-d');
            })->toArray());

            if ($prestamoDia->Prestamo->days_apply_surcharge > 0) {
                $diasAgregados = 0;
                $diasPorAgregar = $prestamoDia->Prestamo->days_apply_surcharge;

                while ($diasAgregados < $diasPorAgregar) {
                    $fechaRecargo->addDay();
                    $fechaFormateada = $fechaRecargo->format('Y-m-d');

                    if (
                        (! isset($festivos[$fechaFormateada]) || $prestamoDia->Prestamo->incluir_festivos_surcharge) &&
                        (! $fechaRecargo->isSunday() || $prestamoDia->Prestamo->incluir_domingos_surcharge)
                    ) {
                        $diasAgregados++;
                    }
                }
            }

            // Crear nuevo registro en PrestamosDias
            $nuevoPrestamoDia = $prestamoDia->replicate();
            $nuevoPrestamoDia->cuota = $prestamoDia->cuota * (1 + ($prestamoDia->surcharge / 100));
            $nuevoPrestamoDia->is_surcharge = true;
            $nuevoPrestamoDia->prestamo_dia_id = $prestamoDia->id;
            $nuevoPrestamoDia->surcharge_applied = true;
            $nuevoPrestamoDia->day_apply_surcharge = $fechaRecargo->format('Y-m-d');
            $nuevoPrestamoDia->save();

            // Marcar el registro original como procesado
            $prestamoDia->surcharge_applied = true;
            $prestamoDia->save();
        }

        $this->info('Recargos aplicados correctamente.');
    }
}

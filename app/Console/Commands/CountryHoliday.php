<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CountryHoliday extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'holiday:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $year_current = date('Y');

        $countries = \DB::table('countries')->where('estatus', 1)->get();

        if ($countries && count($countries) > 0) {
            for ($i = 0; $i < 2; $i++) {
                $year = $year_current + $i;
                foreach ($countries as $country) {
                    if (! \DB::table('country_holidays')->where('year', $year)->where('country_id', $country->id)->exists()) {
                        $listHolidays = file_get_contents("https://api.generadordni.es/v2/holidays/holidays?country={$country->Code2}&year={$year}");
                        if ($listHolidays) {
                            $listas = json_decode($listHolidays);
                            foreach ($listas as $lista) {
                                \DB::table('country_holidays')->insert([
                                    ['year' => $year, 'name' => $lista->name, 'country_id' => $country->id, 'date' => date('Y-m-d', strtotime($lista->date)), 'created_at' => now(), 'updated_at' => now()],
                                ]);
                            }
                        }
                    }
                }
            }
        }

        return 0;
    }
}

<?php

namespace Database\Seeders;

use App\Models\DunningSchedule;
use Illuminate\Database\Seeder;

class DunningScheduleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (DunningSchedule::defaults() as $schedule) {
            DunningSchedule::firstOrCreate(
                ['dunning_type' => $schedule['dunning_type']],
                $schedule
            );
        }

        $this->command->info('督促スケジュールのデフォルトデータを登録しました。');
    }
}

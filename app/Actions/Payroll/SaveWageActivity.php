<?php

namespace App\Actions\Payroll;

use App\Models\WageActivity;
use Illuminate\Support\Facades\DB;

class SaveWageActivity
{
    /**
     * @param  array{name: string, bonus_amount: int, is_active?: bool}  $data
     */
    public function handle(?WageActivity $activity, array $data): WageActivity
    {
        return DB::transaction(function () use ($activity, $data) {
            $activity ??= new WageActivity;
            $activity->fill([
                'name' => $data['name'],
                'bonus_amount' => $data['bonus_amount'],
                'is_active' => $data['is_active'] ?? true,
            ])->save();

            return $activity;
        });
    }
}

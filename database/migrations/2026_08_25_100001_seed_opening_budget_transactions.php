<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        DB::table('budgets')->where('amount', '>', 0)->orderBy('id')->chunkById(200, function ($budgets) use ($now): void {
            DB::table('budget_transactions')->insert($budgets->map(fn ($budget): array => [
                'user_id' => $budget->user_id,
                'purchase_id' => null,
                'type' => 'OPENING_BALANCE',
                'amount' => $budget->amount,
                'description' => 'Opening balance',
                'occurred_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all());
        });
    }

    public function down(): void
    {
        DB::table('budget_transactions')->where('type', 'OPENING_BALANCE')->whereNull('purchase_id')->delete();
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $moves = [
        ['game' => 'lmu', 'name' => 'Brody Lawless',   'insertBefore' => 'Alex Lucky'],
        ['game' => 'acc', 'name' => 'Melvin Milasten', 'insertBefore' => 'Florian Ochsmann'],
    ];

    public function up(): void
    {
        foreach ($this->moves as $move) {
            $target = DB::table('esports_drivers')->where('game', $move['game'])->where('name', $move['name'])->first();
            $insertSortOrder = DB::table('esports_drivers')->where('game', $move['game'])->where('name', $move['insertBefore'])->value('sort_order');

            if (! $target || $insertSortOrder === null || $target->sort_order === $insertSortOrder) {
                continue;
            }

            DB::table('esports_drivers')
                ->where('game', $move['game'])
                ->where('id', '!=', $target->id)
                ->where('sort_order', '>=', $insertSortOrder)
                ->increment('sort_order');

            DB::table('esports_drivers')->where('id', $target->id)->update(['sort_order' => $insertSortOrder]);
        }
    }

    public function down(): void
    {
        foreach ($this->moves as $move) {
            $target = DB::table('esports_drivers')->where('game', $move['game'])->where('name', $move['name'])->first();

            if (! $target) {
                continue;
            }

            $newSortOrder = (int) DB::table('esports_drivers')->where('game', $move['game'])->where('id', '!=', $target->id)->max('sort_order') + 1;

            DB::table('esports_drivers')
                ->where('game', $move['game'])
                ->where('id', '!=', $target->id)
                ->where('sort_order', '>', $target->sort_order)
                ->decrement('sort_order');

            DB::table('esports_drivers')->where('id', $target->id)->update(['sort_order' => $newSortOrder]);
        }
    }
};

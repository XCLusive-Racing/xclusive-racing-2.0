<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->primary(['user_id', 'role_id']);
        });

        $now = now();
        DB::table('roles')->insert([
            ['name' => 'Owner',         'slug' => 'owner',         'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Admin',         'slug' => 'admin',         'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Moderator',     'slug' => 'moderator',     'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Event Manager', 'slug' => 'event_manager', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Steward',       'slug' => 'steward',       'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Driver',        'slug' => 'driver',        'created_at' => $now, 'updated_at' => $now],
        ]);

        // Migrate existing role column to pivot
        $roleMap = [
            'super_admin' => 'owner',
            'admin'       => 'admin',
            'manager'     => 'moderator',
            'driver'      => 'driver',
        ];
        $roleIds = DB::table('roles')->pluck('id', 'slug');

        $inserts = [];
        DB::table('users')->select('id', 'role')->get()->each(function ($user) use ($roleMap, $roleIds, &$inserts) {
            $slug = $roleMap[$user->role] ?? 'driver';
            $inserts[] = ['user_id' => $user->id, 'role_id' => $roleIds[$slug]];
        });

        foreach (array_chunk($inserts, 500) as $chunk) {
            DB::table('role_user')->insert($chunk);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('roles');
    }
};
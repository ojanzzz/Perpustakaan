<?php

namespace Database\Factories;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Permission> */
class PermissionFactory extends Factory
{
    protected $model = Permission::class;

    public function definition(): array
    {
        $group = fake()->randomElement(['dashboard', 'books', 'users', 'settings']);

        return [
            'name' => $group.'.'.fake()->unique()->word(),
            'group' => $group,
            'description' => fake()->sentence(),
        ];
    }
}

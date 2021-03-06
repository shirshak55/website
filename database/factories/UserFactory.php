<?php

declare(strict_types=1);

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Helpers\Str;
use App\Models\User;
use Faker\Generator as Faker;

$randomGenders = [
    'Man',
    'Vrouw',
    'Onbekend',
    'Apache Gevechtshelikopter',
    'Aggressieve wasbeer',
];

$factory->define(User::class, static fn (Faker $faker) => [
        'first_name' => $faker->firstName,
        'last_name' => $faker->lastName,
        'email' => $faker->unique()->safeEmail,
        'gender' => $faker->randomElement($randomGenders),
        'password' => password_hash('password', PASSWORD_DEFAULT), // password
        'remember_token' => Str::random(10),
    ]);

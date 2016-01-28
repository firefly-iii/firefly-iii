<?php

$factory->define(
    FireflyIII\User::class, function (Faker\Generator $faker) {
    return [
        'name'           => $faker->name,
        'email'          => $faker->email,
        'password'       => bcrypt(str_random(10)),
        'remember_token' => str_random(10),
    ];
}
);

$factory->define(
    FireflyIII\Models\TransactionType::class, function (Faker\Generator $faker) {
    return [
        'type' => $faker->name,
    ];
}
);


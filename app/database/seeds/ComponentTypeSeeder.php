<?php


class ComponentTypeSeeder extends Seeder
{

    public function run()
    {

        DB::table('component_types')->delete();

        ComponentType::create(['type' => 'category']);
        ComponentType::create(['type' => 'budget']);
    }

} 
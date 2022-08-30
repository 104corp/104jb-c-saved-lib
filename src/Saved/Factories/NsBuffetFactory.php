<?php

namespace Corp104\Jbc\Saved\Factories;

use Corp104\Jbc\Saved\Models\NsBuffet;
use Illuminate\Database\Eloquent\Factories\Factory;

class NsBuffetFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = NsBuffet::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'id_no' => 3781639100603,
            'jobno' => 8299744,
            'custno' => 28577855000,
        ];
    }
}

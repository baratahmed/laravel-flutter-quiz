<?php

namespace Database\Factories;

use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class QuestionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Question::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            // 'name' => $this->faker->name,
            'quiz_id' => 1,
            // 'email' => $this->faker->unique()->safeEmail,
            'qn_no' => 1,
            'question' => 'Ques 1 sbhd jfshjd fhsjbdfhjsdfs dfg?',
            'category' => 'single',
            'qn_photo_location' => null,
        ];
    }
}

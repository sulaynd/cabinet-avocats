<?php

namespace Database\Factories;

use App\Models\Intervenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class IntervenantFactory extends Factory
{
    protected $model = Intervenant::class;

    public function definition(): array
    {
        return [
            'nom' => $this->faker->name(),
            'fonction' => $this->faker->randomElement(['avocat_adverse', 'expert', 'greffier', 'huissier', 'mediateur_arbitre', 'notaire', 'autre']),
            'organisation' => $this->faker->company(),
            'email' => $this->faker->safeEmail(),
            'telephone' => $this->faker->phoneNumber(),
            'notes' => null,
        ];
    }
}

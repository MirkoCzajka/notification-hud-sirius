<?php

namespace Database\Factories;

use App\Models\Message;
use App\Models\User;
use App\Models\Service;
use App\Models\MessageStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        return [
            'content' => $this->faker->sentence(6),
            'user_id' => User::factory(),
            'service_id' => Service::factory(),
            'message_status_id' => function() {
                return MessageStatus::query()->inRandomOrder()->value('id')
                    ?? MessageStatus::create(['key'=>'pending','name'=>'Pending'])->id;
            },
            'date_sent' => null,
            'provider_response' => null,
        ];
    }
}

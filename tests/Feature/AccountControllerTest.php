<?php

namespace dnj\Account\Tests\Feature;

use dnj\Account\Models\Account;
use dnj\Account\Tests\Models\User;
use dnj\Account\Tests\TestCase;
use dnj\Currency\Models\Currency;
use Illuminate\Testing\Fluent\AssertableJson;

class AccountControllerTest extends TestCase
{
    public function testStore(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $secondUser = User::factory()->create();
        $USD = Currency::factory()->asUSD()->create();
        $data = [
            'title' => 'account1',
            'can_send' => false,
            'can_receive' => false,
            'currency_id' => $USD->getID(),
            'user_id' => $secondUser->id,
            'meta' => [
                'name' => 'john',
                'age' => 30,
                'cat' => null,
            ],
        ];
        $this->postJson(route('accounts.store'), $data)
            ->assertStatus(201)
            ->assertJson(fn (AssertableJson $json) => $json->has('data.id')->etc())
            ->assertJson([
                'data' => $data,
            ]);
    }

    public function testUpdate(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $account = Account::factory()->create();
        $this->putJson(route('accounts.update', ['account' => $account->id]), [
            'meta' => [
                'key' => 'value',
            ],
        ])
            ->assertStatus(200)
            ->assertJson(function (AssertableJson $json) use ($account) {
                $json->where('data.title', $account->title);
                $json->where('data.currency_id', $account->currency_id);
            });
    }

    public function testDestroy()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $account = Account::factory()->create();
        $this->deleteJson(route('accounts.destroy', ['account' => $account->id]))
            ->assertStatus(204);
    }
}

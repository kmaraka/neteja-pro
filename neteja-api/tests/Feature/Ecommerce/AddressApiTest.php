<?php

use App\Models\User;
use App\Modules\Ecommerce\Models\Address;
use App\Modules\Ecommerce\Models\DeliveryZone;

beforeEach(function () {
    DeliveryZone::factory()->create(['commune' => 'Cocody']);
    DeliveryZone::factory()->create(['commune' => 'Yopougon']);
    DeliveryZone::factory()->create(['commune' => 'Bingerville', 'is_active' => false]);

    $this->user = consenting(User::factory()->create());
});

function addressPayload(array $overrides = []): array
{
    return array_merge([
        'label' => 'Maison',
        'recipient_name' => 'Awa Koné',
        'phone' => '07 00 00 00 21',
        'commune' => 'Cocody',
        'address_line' => 'Rue des Jardins, Riviera 2',
        'additional_info' => 'Portail bleu',
    ], $overrides);
}

it('exige une connexion', function () {
    $this->getJson('/api/addresses')->assertUnauthorized();
});

it('crée une adresse, normalise le téléphone et la met par défaut', function () {
    $this->actingAs($this->user, 'sanctum')
        ->postJson('/api/addresses', addressPayload())
        ->assertCreated()
        ->assertJsonPath('data.phone', '+2250700000021')
        ->assertJsonPath('data.is_default', true);
});

it('ne met pas la deuxième adresse par défaut', function () {
    Address::factory()->for($this->user)->create(['is_default' => true]);

    $this->actingAs($this->user, 'sanctum')
        ->postJson('/api/addresses', addressPayload(['label' => 'Bureau']))
        ->assertCreated()
        ->assertJsonPath('data.is_default', false);
});

it('refuse une commune non desservie ou inconnue', function () {
    $this->actingAs($this->user, 'sanctum');

    $this->postJson('/api/addresses', addressPayload(['commune' => 'Bingerville']))
        ->assertUnprocessable()->assertJsonValidationErrors('commune');

    $this->postJson('/api/addresses', addressPayload(['commune' => 'Paris']))
        ->assertUnprocessable()->assertJsonValidationErrors('commune');
});

it('refuse plus de 10 adresses', function () {
    Address::factory()->count(10)->for($this->user)->create();

    $this->actingAs($this->user, 'sanctum')
        ->postJson('/api/addresses', addressPayload())
        ->assertUnprocessable()
        ->assertJsonValidationErrors('label');
});

it("change l'adresse par défaut", function () {
    $first = Address::factory()->for($this->user)->create(['is_default' => true]);
    $second = Address::factory()->for($this->user)->create();

    $this->actingAs($this->user, 'sanctum')
        ->postJson("/api/addresses/{$second->id}/default")
        ->assertOk();

    expect($second->fresh()->is_default)->toBeTrue()
        ->and($first->fresh()->is_default)->toBeFalse();
});

it('modifie une adresse', function () {
    $address = Address::factory()->for($this->user)->create();

    $this->actingAs($this->user, 'sanctum')
        ->putJson("/api/addresses/{$address->id}", addressPayload(['commune' => 'Yopougon', 'label' => 'Bureau']))
        ->assertOk()
        ->assertJsonPath('data.commune', 'Yopougon');
});

it('cache les adresses des autres utilisateurs', function () {
    $other = Address::factory()->create();

    $this->actingAs($this->user, 'sanctum');

    $this->getJson('/api/addresses')->assertOk()->assertJsonCount(0, 'data');
    $this->putJson("/api/addresses/{$other->id}", addressPayload())->assertNotFound();
    $this->postJson("/api/addresses/{$other->id}/default")->assertNotFound();
    $this->deleteJson("/api/addresses/{$other->id}")->assertNotFound();

    expect(Address::find($other->id))->not->toBeNull();
});

it("remplace l'adresse par défaut quand on la supprime", function () {
    $default = Address::factory()->for($this->user)->create(['is_default' => true]);
    $other = Address::factory()->for($this->user)->create();

    $this->actingAs($this->user, 'sanctum')
        ->deleteJson("/api/addresses/{$default->id}")
        ->assertNoContent();

    expect(Address::find($default->id))->toBeNull()
        ->and($other->fresh()->is_default)->toBeTrue();
});

it("supprime les adresses lors de l'effacement du compte", function () {
    Address::factory()->count(2)->for($this->user)->create();

    $this->actingAs($this->user, 'sanctum')->deleteJson('/api/account')->assertNoContent();

    expect(Address::where('user_id', $this->user->id)->count())->toBe(0);
});

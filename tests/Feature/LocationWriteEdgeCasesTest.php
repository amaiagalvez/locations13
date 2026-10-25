<?php

namespace Locations13\Tests\Feature;

use PDOException;
use App\Models\User;
use Locations13\Models\Town;
use Locations13\Models\State;
use Locations13\Models\Country;
use Locations13\Tests\TestCase;
use Locations13\Models\District;
use Locations13\Models\Community;
use Locations13\Models\Neighbourhood;
use Illuminate\Database\QueryException;
use PHPUnit\Framework\Attributes\DataProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @phpstan-type LocationModel State|Community|Country|District|Town|Neighbourhood
 */
class LocationWriteEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: class-string<LocationModel>, 1: string, 2: string}>
     */
    public static function resources(): array
    {
        return [
            'state' => [State::class, 'states', 'state'],
            'community' => [Community::class, 'communities', 'community'],
            'country' => [Country::class, 'countries', 'country'],
            'district' => [District::class, 'districts', 'district'],
            'town' => [Town::class, 'towns', 'town'],
            'neighbourhood' => [Neighbourhood::class, 'neighbourhoods', 'neighbourhood'],
        ];
    }

    /**
     * @param  class-string<LocationModel>  $model
     */
    #[DataProvider('resources')]
    public function test_a_trashed_name_asks_to_confirm_before_creating(string $model, string $prefix, string $singular): void
    {
        $this->signIn();
        $trashed = $model::factory()->trashed()->create(['name' => 'Trashed name']);
        $payload = $trashed instanceof Neighbourhood
            ? ['name' => 'Trashed name', 'town_id' => $trashed->town_id]
            : $this->payload($model, 'Trashed name');

        $this->from(route($prefix.'.index'))
            ->post(route($prefix.'.store'), $payload)
            ->assertRedirect(route($prefix.'.index'))
            ->assertSessionHas('deleted_'.$singular.'_conflict');

        $this->assertDatabaseCount($trashed->getTable(), 1);
    }

    /**
     * @param  class-string<LocationModel>  $model
     */
    #[DataProvider('resources')]
    public function test_a_json_store_returns_the_created_record(string $model, string $prefix, string $singular): void
    {
        $this->signIn();

        $this->postJson(route($prefix.'.store'), $this->payload($model, 'JSON name'))
            ->assertCreated()
            ->assertJsonPath('name', 'JSON name')
            ->assertJsonStructure(['id', 'name']);
    }

    /**
     * @param  class-string<LocationModel>  $model
     */
    #[DataProvider('resources')]
    public function test_a_unique_constraint_race_on_store_returns_a_name_error(string $model, string $prefix, string $singular): void
    {
        $this->signIn();
        $payload = $this->payload($model, 'Concurrent name');
        $model::creating(self::duplicateKey(...));

        $this->post(route($prefix.'.store'), $payload)
            ->assertSessionHasErrors(['name' => __('validation.unique', ['attribute' => __('Name')])]);

        $this->assertSame(0, $model::query()->where('name', 'Concurrent name')->count());
    }

    /**
     * @param  class-string<LocationModel>  $model
     */
    #[DataProvider('resources')]
    public function test_a_unique_constraint_race_on_update_keeps_the_record(string $model, string $prefix, string $singular): void
    {
        $this->signIn();
        $record = $model::factory()->create(['name' => 'Original name']);
        $payload = $this->payload($model, 'Concurrent name');
        $model::updating(self::duplicateKey(...));

        $this->put(route($prefix.'.update', [$singular => $record]), $payload)
            ->assertSessionHasErrors(['name' => __('validation.unique', ['attribute' => __('Name')])]);

        $this->assertDatabaseHas($record->getTable(), ['id' => $record->getKey(), 'name' => 'Original name']);
    }

    /**
     * @return array<string, array{0: class-string<LocationModel>, 1: string, 2: string, 3: class-string<LocationModel>|null, 4: string|null}>
     */
    public static function guardedDeletes(): array
    {
        return [
            'community with a country' => [Community::class, 'communities', 'community', Country::class, 'community_id'],
            'country with a district' => [Country::class, 'countries', 'country', District::class, 'country_id'],
            'neighbourhood the model refuses' => [Neighbourhood::class, 'neighbourhoods', 'neighbourhood', null, null],
        ];
    }

    /**
     * @param  class-string<LocationModel>  $model
     * @param  class-string<LocationModel>|null  $child
     */
    #[DataProvider('guardedDeletes')]
    public function test_a_refused_delete_keeps_the_record(string $model, string $prefix, string $singular, ?string $child, ?string $foreignKey): void
    {
        $this->signIn();
        $record = $model::factory()->create();

        if ($child !== null && $foreignKey !== null) {
            $child::factory()->create([$foreignKey => $record->getKey()]);
        } else {
            $model::deleting(static fn (): bool => false);
        }

        $this->delete(route($prefix.'.destroy', [$singular => $record]))
            ->assertRedirect(route($prefix.'.index'))
            ->assertSessionHas('error', __('basics13::messages.cannot_delete_related'));

        $this->assertNotSoftDeleted($record);
    }

    public function test_a_country_keeps_its_archived_community_but_cannot_pick_another(): void
    {
        $this->signIn();
        $archived = Community::factory()->archived()->create();
        $country = Country::factory()->create(['community_id' => $archived->id]);
        $otherArchived = Community::factory()->archived()->create();

        $this->put(route('countries.update', $country), ['name' => 'Gipuzkoa', 'community_id' => $archived->id])
            ->assertSessionHasNoErrors();
        $this->put(route('countries.update', $country), ['name' => 'Gipuzkoa', 'community_id' => $otherArchived->id])
            ->assertSessionHasErrors('community_id');

        $this->assertSame($archived->id, $country->refresh()->community_id);
    }

    public function test_a_town_keeps_its_archived_district(): void
    {
        $this->signIn();
        $archived = District::factory()->archived()->create();
        $town = Town::factory()->create(['district_id' => $archived->id]);

        $this->put(route('towns.update', $town), ['name' => 'Urretxu', 'district_id' => $archived->id])
            ->assertSessionHasNoErrors();

        $this->assertSame('Urretxu', $town->refresh()->name);
    }

    private static function duplicateKey(): never
    {
        $exception = new PDOException('Duplicate name');
        $exception->errorInfo = ['23000', 19, 'UNIQUE constraint failed'];

        throw new QueryException('sqlite', 'write', [], $exception);
    }

    /**
     * @param  class-string<LocationModel>  $model
     * @return array<string, mixed>
     */
    private function payload(string $model, string $name): array
    {
        return match ($model) {
            Neighbourhood::class => ['name' => $name, 'town_id' => Town::factory()->create()->id],
            default => ['name' => $name],
        };
    }

    private function signIn(): void
    {
        $this->actingAs($this->admin(User::create([
            'name' => 'Test user',
            'email' => 'test@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ])));
    }
}

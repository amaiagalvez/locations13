<?php

namespace Locations13\Database\Seeders;

use Locations13\Models\Town;
use Locations13\Models\State;
use Illuminate\Database\Seeder;
use Locations13\Models\Country;
use Locations13\Models\District;
use Locations13\Models\Community;
use Locations13\Models\Neighbourhood;

/**
 * The development catalogue of Euskal Herria: one coherent tree from the state down to a
 * neighbourhood with its postal codes, plus enough synthetic towns for the lists to paginate.
 */
class DevelopLocationSeeder extends Seeder
{
    public function run(int $towns = 40): void
    {
        $state = State::factory()->create(['name' => 'Euskal Herria', 'ine_code' => 'EH']);

        $eae = Community::factory()->create(['name' => 'EAE', 'ine_code' => '16', 'state_id' => $state->id]);
        Community::factory()->create(['name' => 'Nafarroa', 'ine_code' => '31', 'state_id' => $state->id]);
        Community::factory()->create(['name' => 'IEH', 'ine_code' => '64', 'state_id' => $state->id]);

        $gipuzkoa = Country::factory()->create(['name' => 'Gipuzkoa', 'ine_code' => '20', 'community_id' => $eae->id]);
        $bizkaia = Country::factory()->create(['name' => 'Bizkaia', 'ine_code' => '48', 'community_id' => $eae->id]);
        $araba = Country::factory()->create(['name' => 'Araba', 'ine_code' => '01', 'community_id' => $eae->id]);

        $goierri = District::factory()->create(['name' => 'Goierri', 'country_id' => $gipuzkoa->id]);
        $tolosaldea = District::factory()->create(['name' => 'Tolosaldea', 'country_id' => $gipuzkoa->id]);
        District::factory()->create(['name' => 'Lizarraldea', 'country_id' => $araba->id]);

        $urretxu = Town::factory()->create([
            'name' => 'Urretxu',
            'ine_code' => '20058',
            'district_id' => $goierri->id,
        ]);
        $urretxu->postalCodes()->createMany([['code' => '20700']]);
        Neighbourhood::factory()->create(['name' => 'Iparragirre', 'town_id' => $urretxu->id]);
        Neighbourhood::factory()->create(['name' => 'Elizkale', 'town_id' => $urretxu->id]);

        $donostia = Town::factory()->create([
            'name' => 'Donostia',
            'ine_code' => '20069',
            'district_id' => $tolosaldea->id,
        ]);
        $donostia->postalCodes()->createMany([['code' => '20001'], ['code' => '20002']]);

        $districts = [$goierri, $tolosaldea, District::factory()->create(['name' => 'Bilbo Handia', 'country_id' => $bizkaia->id])];

        for ($number = 1; $number <= $towns; $number++) {
            $town = Town::factory()->create([
                'name' => sprintf('Develop Town %03d', $number),
                'district_id' => $districts[($number - 1) % count($districts)]->id,
            ]);
            $town->postalCodes()->create(['code' => sprintf('20%03d', $number)]);
            Neighbourhood::factory()->create(['town_id' => $town->id]);
        }
    }
}

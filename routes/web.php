<?php

use Illuminate\Support\Facades\Route;
use Locations13\Http\Controllers\TownController;
use Locations13\Http\Controllers\StateController;
use Locations13\Http\Controllers\CountryController;
use Locations13\Http\Controllers\DistrictController;
use Locations13\Http\Controllers\CommunityController;
use Locations13\Http\Controllers\TownTrashController;
use Locations13\Http\Controllers\StateTrashController;
use Locations13\Http\Controllers\CountryTrashController;
use Locations13\Http\Controllers\TownArchivedController;
use Locations13\Http\Controllers\DistrictTrashController;
use Locations13\Http\Controllers\NeighbourhoodController;
use Locations13\Http\Controllers\StateArchivedController;
use Locations13\Http\Controllers\CommunityTrashController;
use Locations13\Http\Controllers\CountryArchivedController;
use Locations13\Http\Controllers\DistrictArchivedController;
use Locations13\Http\Controllers\CommunityArchivedController;
use Locations13\Http\Controllers\NeighbourhoodTrashController;
use Locations13\Http\Controllers\NeighbourhoodArchivedController;

/**
 * Every route of the package lives under `locations`, and keeps the bare `countries.…`,
 * `states.…` names the policies, menus and transformers address.
 */
Route::prefix('locations')->middleware(['web', 'auth', 'verified'])->group(function (): void {
    Route::get('countries', [CountryController::class, 'index'])->name('countries.index');
    Route::post('countries', [CountryController::class, 'store'])->name('countries.store');
    Route::put('countries/{country}', [CountryController::class, 'update'])->whereNumber('country')->name('countries.update');
    Route::delete('countries/{country}', [CountryController::class, 'destroy'])->whereNumber('country')->name('countries.destroy');
    Route::get('countries/options', [CountryController::class, 'selectOptions'])->name('countries.options')->middleware('throttle:60,1');
    Route::get('countries/archived', [CountryArchivedController::class, 'index'])->name('countries.archived.index');
    Route::patch('countries/{country}/archive', [CountryArchivedController::class, 'archive'])->whereNumber('country')->name('countries.archive');
    Route::patch('countries/archived/{country}', [CountryArchivedController::class, 'activate'])->whereNumber('country')->name('countries.archived.activate');
    Route::get('countries/trash', [CountryTrashController::class, 'index'])->name('countries.trash.index');
    Route::patch('countries/trash/{country}', [CountryTrashController::class, 'restore'])->whereNumber('country')->name('countries.trash.restore');
    Route::delete('countries/trash/{country}', [CountryTrashController::class, 'destroy'])->whereNumber('country')->name('countries.trash.destroy');

    Route::get('states', [StateController::class, 'index'])->name('states.index');
    Route::post('states', [StateController::class, 'store'])->name('states.store');
    Route::put('states/{state}', [StateController::class, 'update'])->whereNumber('state')->name('states.update');
    Route::delete('states/{state}', [StateController::class, 'destroy'])->whereNumber('state')->name('states.destroy');
    Route::get('states/options', [StateController::class, 'selectOptions'])->name('states.options')->middleware('throttle:60,1');
    Route::get('states/archived', [StateArchivedController::class, 'index'])->name('states.archived.index');
    Route::patch('states/{state}/archive', [StateArchivedController::class, 'archive'])->whereNumber('state')->name('states.archive');
    Route::patch('states/archived/{state}', [StateArchivedController::class, 'activate'])->whereNumber('state')->name('states.archived.activate');
    Route::get('states/trash', [StateTrashController::class, 'index'])->name('states.trash.index');
    Route::patch('states/trash/{state}', [StateTrashController::class, 'restore'])->whereNumber('state')->name('states.trash.restore');
    Route::delete('states/trash/{state}', [StateTrashController::class, 'destroy'])->whereNumber('state')->name('states.trash.destroy');

    Route::get('communities', [CommunityController::class, 'index'])->name('communities.index');
    Route::post('communities', [CommunityController::class, 'store'])->name('communities.store');
    Route::put('communities/{community}', [CommunityController::class, 'update'])->whereNumber('community')->name('communities.update');
    Route::delete('communities/{community}', [CommunityController::class, 'destroy'])->whereNumber('community')->name('communities.destroy');
    Route::get('communities/options', [CommunityController::class, 'selectOptions'])->name('communities.options')->middleware('throttle:60,1');
    Route::get('communities/archived', [CommunityArchivedController::class, 'index'])->name('communities.archived.index');
    Route::patch('communities/{community}/archive', [CommunityArchivedController::class, 'archive'])->whereNumber('community')->name('communities.archive');
    Route::patch('communities/archived/{community}', [CommunityArchivedController::class, 'activate'])->whereNumber('community')->name('communities.archived.activate');
    Route::get('communities/trash', [CommunityTrashController::class, 'index'])->name('communities.trash.index');
    Route::patch('communities/trash/{community}', [CommunityTrashController::class, 'restore'])->whereNumber('community')->name('communities.trash.restore');
    Route::delete('communities/trash/{community}', [CommunityTrashController::class, 'destroy'])->whereNumber('community')->name('communities.trash.destroy');

    Route::get('districts', [DistrictController::class, 'index'])->name('districts.index');
    Route::post('districts', [DistrictController::class, 'store'])->name('districts.store');
    Route::put('districts/{district}', [DistrictController::class, 'update'])->whereNumber('district')->name('districts.update');
    Route::delete('districts/{district}', [DistrictController::class, 'destroy'])->whereNumber('district')->name('districts.destroy');
    Route::get('districts/options', [DistrictController::class, 'selectOptions'])->name('districts.options')->middleware('throttle:60,1');
    Route::get('districts/archived', [DistrictArchivedController::class, 'index'])->name('districts.archived.index');
    Route::patch('districts/{district}/archive', [DistrictArchivedController::class, 'archive'])->whereNumber('district')->name('districts.archive');
    Route::patch('districts/archived/{district}', [DistrictArchivedController::class, 'activate'])->whereNumber('district')->name('districts.archived.activate');
    Route::get('districts/trash', [DistrictTrashController::class, 'index'])->name('districts.trash.index');
    Route::patch('districts/trash/{district}', [DistrictTrashController::class, 'restore'])->whereNumber('district')->name('districts.trash.restore');
    Route::delete('districts/trash/{district}', [DistrictTrashController::class, 'destroy'])->whereNumber('district')->name('districts.trash.destroy');

    Route::get('towns', [TownController::class, 'index'])->name('towns.index');
    Route::post('towns', [TownController::class, 'store'])->name('towns.store');
    Route::put('towns/{town}', [TownController::class, 'update'])->whereNumber('town')->name('towns.update');
    Route::delete('towns/{town}', [TownController::class, 'destroy'])->whereNumber('town')->name('towns.destroy');
    Route::get('towns/options', [TownController::class, 'selectOptions'])->name('towns.options')->middleware('throttle:60,1');
    Route::get('towns/archived', [TownArchivedController::class, 'index'])->name('towns.archived.index');
    Route::patch('towns/{town}/archive', [TownArchivedController::class, 'archive'])->whereNumber('town')->name('towns.archive');
    Route::patch('towns/archived/{town}', [TownArchivedController::class, 'activate'])->whereNumber('town')->name('towns.archived.activate');
    Route::get('towns/trash', [TownTrashController::class, 'index'])->name('towns.trash.index');
    Route::patch('towns/trash/{town}', [TownTrashController::class, 'restore'])->whereNumber('town')->name('towns.trash.restore');
    Route::delete('towns/trash/{town}', [TownTrashController::class, 'destroy'])->whereNumber('town')->name('towns.trash.destroy');

    Route::get('neighbourhoods', [NeighbourhoodController::class, 'index'])->name('neighbourhoods.index');
    Route::get('neighbourhoods/options', [NeighbourhoodController::class, 'selectOptions'])->name('neighbourhoods.options')->middleware('throttle:60,1');
    Route::post('neighbourhoods', [NeighbourhoodController::class, 'store'])->name('neighbourhoods.store');
    Route::put('neighbourhoods/{neighbourhood}', [NeighbourhoodController::class, 'update'])->whereNumber('neighbourhood')->name('neighbourhoods.update');
    Route::delete('neighbourhoods/{neighbourhood}', [NeighbourhoodController::class, 'destroy'])->whereNumber('neighbourhood')->name('neighbourhoods.destroy');
    Route::get('neighbourhoods/archived', [NeighbourhoodArchivedController::class, 'index'])->name('neighbourhoods.archived.index');
    Route::patch('neighbourhoods/{neighbourhood}/archive', [NeighbourhoodArchivedController::class, 'archive'])->whereNumber('neighbourhood')->name('neighbourhoods.archive');
    Route::patch('neighbourhoods/archived/{neighbourhood}', [NeighbourhoodArchivedController::class, 'activate'])->whereNumber('neighbourhood')->name('neighbourhoods.archived.activate');
    Route::get('neighbourhoods/trash', [NeighbourhoodTrashController::class, 'index'])->name('neighbourhoods.trash.index');
    Route::patch('neighbourhoods/trash/{neighbourhood}', [NeighbourhoodTrashController::class, 'restore'])->whereNumber('neighbourhood')->name('neighbourhoods.trash.restore');
    Route::delete('neighbourhoods/trash/{neighbourhood}', [NeighbourhoodTrashController::class, 'destroy'])->whereNumber('neighbourhood')->name('neighbourhoods.trash.destroy');
});

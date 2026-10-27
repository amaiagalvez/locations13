<?php

namespace Locations13\Tests\Unit\Validation;

use Locations13\Tests\TestCase;
use Illuminate\Validation\Rules\Unique;
use Basics13\Support\Validation\MaxLength;
use Locations13\Http\Requests\TownRequest;
use Basics13\Support\Testing\PermissionsActor;

/**
 * The rules of a payload are read here and run against the same payload, so the unique rule is
 * never reached: it is the one rule that needs the database, and a value that already failed a rule
 * of its own field or was left out makes the validator skip it. Uniqueness is covered where it
 * belongs, against the database and through the form.
 */
class TownRequestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->app->make('config');
        $config->set('basics13.validation.max_length', ['string' => 123, 'longtext' => 456]);
    }

    public function test_authorize_needs_the_create_permission(): void
    {
        $request = new TownRequest;
        $request->setUserResolver(static fn (): PermissionsActor => PermissionsActor::holding(['towns.view']));
        $this->assertFalse($request->authorize());
    }

    public function test_authorize_returns_true_when_creating(): void
    {
        $request = new TownRequest;
        $request->setUserResolver(static fn (): PermissionsActor => PermissionsActor::holding(['towns.manage']));
        $this->assertTrue($request->authorize());
    }

    public function test_unauthenticated_users_cannot_create_towns(): void
    {
        $this->assertFalse((new TownRequest)->authorize());
    }

    public function test_name_is_required(): void
    {
        $request = TownRequest::create('/', 'POST', []);
        $this->assertRequestValidationFails($request, static fn (): array => $request->rules(), 'name');
    }

    public function test_name_must_be_at_least_two_characters(): void
    {
        $request = TownRequest::create('/', 'POST', ['name' => 'A']);
        $this->assertRequestValidationFails($request, static fn (): array => $request->rules(), 'name');
    }

    public function test_name_cannot_exceed_the_configured_maximum(): void
    {
        $request = TownRequest::create('/', 'POST', ['name' => str_repeat('a', MaxLength::string() + 1)]);
        $this->assertRequestValidationFails($request, static fn (): array => $request->rules(), 'name');
    }

    /**
     * Every payload below fails the `name` rule first, so the unique lookup never reaches the
     * database: `Rule::unique()` only runs for a name that passed the other rules of its field.
     */
    public function test_ine_code_cannot_exceed_the_maximum(): void
    {
        $request = TownRequest::create('/', 'POST', ['name' => 'A', 'ine_code' => str_repeat('a', MaxLength::string() + 1)]);
        $this->assertRequestValidationFails($request, static fn (): array => $request->rules(), 'ine_code');

        $request = TownRequest::create('/', 'POST', ['name' => 'A']);
        $validator = $this->validatorForRequest($request, static fn (): array => $request->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayNotHasKey('ine_code', $validator->errors()->toArray());
    }

    public function test_notes_cannot_exceed_long_text_maximum(): void
    {
        $request = TownRequest::create('/', 'POST', [
            'name' => 'A',
            'notes' => str_repeat('a', MaxLength::longText() + 1),
        ]);
        $this->assertRequestValidationFails($request, static fn (): array => $request->rules(), 'notes');
    }

    public function test_reuse_deleted_name_is_sometimes_boolean(): void
    {
        $request = TownRequest::create('/', 'POST', ['name' => 'A', 'reuse_deleted_name' => 'not-a-boolean']);
        $this->assertRequestValidationFails($request, static fn (): array => $request->rules(), 'reuse_deleted_name');
    }

    public function test_the_name_rule_is_unique_among_the_towns_outside_the_trash(): void
    {
        $request = TownRequest::create('/', 'POST', ['name' => 'Euskal Herria']);
        $rules = $request->rules();

        $uniqueOptions = array_filter($rules['name'], static fn (mixed $rule): bool => $rule instanceof Unique);

        $this->assertCount(1, $uniqueOptions);
    }

    public function test_prepare_for_validation_trims_the_fields(): void
    {
        $request = TownRequest::create('/', 'POST', ['name' => '  Euskal Herria  ', 'ine_code' => ' EH ']);

        $this->prepareForValidation($request);

        $this->assertSame('Euskal Herria', $request->input('name'));
        $this->assertSame('EH', $request->input('ine_code'));
    }

    public function test_prepare_for_validation_turns_a_blank_code_into_null(): void
    {
        $request = TownRequest::create('/', 'POST', ['name' => 'Euskal Herria', 'ine_code' => '   ']);

        $this->prepareForValidation($request);

        $this->assertNull($request->input('ine_code'));
    }

    public function test_prepare_for_validation_splits_trims_and_deduplicates_the_postal_codes(): void
    {
        $request = TownRequest::create('/', 'POST', ['name' => 'Azpeitia', 'postal_codes' => " 20730\n\n20730 \n20737"]);

        $this->prepareForValidation($request);

        $this->assertSame(['20730', '20737'], $request->input('postal_codes'));
    }

    public function test_postal_codes_cannot_exceed_the_maximum(): void
    {
        $request = TownRequest::create('/', 'POST', ['name' => 'A', 'postal_codes' => [str_repeat('1', MaxLength::string() + 1)]]);
        $this->assertRequestValidationFails($request, static fn (): array => $request->rules(), 'postal_codes.0');
    }

    private function prepareForValidation(TownRequest $request): void
    {
        (new \ReflectionMethod($request, 'prepareForValidation'))->invoke($request);
    }
}

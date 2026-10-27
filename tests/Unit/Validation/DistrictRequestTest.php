<?php

namespace Locations13\Tests\Unit\Validation;

use Locations13\Tests\TestCase;
use Illuminate\Validation\Rules\Unique;
use Basics13\Support\Validation\MaxLength;
use Basics13\Support\Testing\PermissionsActor;
use Locations13\Http\Requests\DistrictRequest;

/**
 * The rules of a payload are read here and run against the same payload, so the unique rule is
 * never reached: it is the one rule that needs the database, and a value that already failed a rule
 * of its own field or was left out makes the validator skip it. Uniqueness is covered where it
 * belongs, against the database and through the form.
 */
class DistrictRequestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->app->make('config');
        $config->set('basics13.validation.max_length', ['string' => 123, 'longtext' => 456]);
    }

    public function test_authorize_needs_the_create_permission(): void
    {
        $request = new DistrictRequest;
        $request->setUserResolver(static fn (): PermissionsActor => PermissionsActor::holding(['districts.view']));
        $this->assertFalse($request->authorize());
    }

    public function test_authorize_returns_true_when_creating(): void
    {
        $request = new DistrictRequest;
        $request->setUserResolver(static fn (): PermissionsActor => PermissionsActor::holding(['districts.manage']));
        $this->assertTrue($request->authorize());
    }

    public function test_unauthenticated_users_cannot_create_districts(): void
    {
        $this->assertFalse((new DistrictRequest)->authorize());
    }

    public function test_name_is_required(): void
    {
        $request = DistrictRequest::create('/', 'POST', []);
        $this->assertRequestValidationFails($request, static fn (): array => $request->rules(), 'name');
    }

    public function test_name_must_be_at_least_two_characters(): void
    {
        $request = DistrictRequest::create('/', 'POST', ['name' => 'A']);
        $this->assertRequestValidationFails($request, static fn (): array => $request->rules(), 'name');
    }

    public function test_name_cannot_exceed_the_configured_maximum(): void
    {
        $request = DistrictRequest::create('/', 'POST', ['name' => str_repeat('a', MaxLength::string() + 1)]);
        $this->assertRequestValidationFails($request, static fn (): array => $request->rules(), 'name');
    }

    /**
     * Every payload below fails the `name` rule first, so the unique lookup never reaches the
     * database: `Rule::unique()` only runs for a name that passed the other rules of its field.
     */
    public function test_ine_code_cannot_exceed_the_maximum(): void
    {
        $request = DistrictRequest::create('/', 'POST', ['name' => 'A', 'ine_code' => str_repeat('a', MaxLength::string() + 1)]);
        $this->assertRequestValidationFails($request, static fn (): array => $request->rules(), 'ine_code');

        $request = DistrictRequest::create('/', 'POST', ['name' => 'A']);
        $validator = $this->validatorForRequest($request, static fn (): array => $request->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayNotHasKey('ine_code', $validator->errors()->toArray());
    }

    public function test_notes_cannot_exceed_long_text_maximum(): void
    {
        $request = DistrictRequest::create('/', 'POST', [
            'name' => 'A',
            'notes' => str_repeat('a', MaxLength::longText() + 1),
        ]);
        $this->assertRequestValidationFails($request, static fn (): array => $request->rules(), 'notes');
    }

    public function test_reuse_deleted_name_is_sometimes_boolean(): void
    {
        $request = DistrictRequest::create('/', 'POST', ['name' => 'A', 'reuse_deleted_name' => 'not-a-boolean']);
        $this->assertRequestValidationFails($request, static fn (): array => $request->rules(), 'reuse_deleted_name');
    }

    public function test_the_name_rule_is_unique_among_the_districts_outside_the_trash(): void
    {
        $request = DistrictRequest::create('/', 'POST', ['name' => 'Euskal Herria']);
        $rules = $request->rules();

        $uniqueOptions = array_filter($rules['name'], static fn (mixed $rule): bool => $rule instanceof Unique);

        $this->assertCount(1, $uniqueOptions);
    }

    public function test_prepare_for_validation_trims_the_fields(): void
    {
        $request = DistrictRequest::create('/', 'POST', ['name' => '  Euskal Herria  ', 'ine_code' => ' EH ']);

        $this->prepareForValidation($request);

        $this->assertSame('Euskal Herria', $request->input('name'));
        $this->assertSame('EH', $request->input('ine_code'));
    }

    public function test_prepare_for_validation_turns_a_blank_code_into_null(): void
    {
        $request = DistrictRequest::create('/', 'POST', ['name' => 'Euskal Herria', 'ine_code' => '   ']);

        $this->prepareForValidation($request);

        $this->assertNull($request->input('ine_code'));
    }

    private function prepareForValidation(DistrictRequest $request): void
    {
        (new \ReflectionMethod($request, 'prepareForValidation'))->invoke($request);
    }
}

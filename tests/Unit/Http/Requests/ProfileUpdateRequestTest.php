<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;
use Illuminate\Validation\Rule;

class ProfileUpdateRequestTest extends TestCase
{
    use RefreshDatabase;

    private ProfileUpdateRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new ProfileUpdateRequest();
    }

    public function test_authorize_returns_true_for_authenticated_user()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // We need to set the user on the request for the authorize method to work
        $this->request->setUserResolver(function () use ($user) {
            return $user;
        });

        $this->assertTrue($this->request->authorize());
    }

    public function test_authorize_returns_false_for_unauthenticated_user()
    {
        // Ensure no user is authenticated
        auth()->logout();

        $this->request->setUserResolver(function () {
            return null;
        });

        $this->assertFalse($this->request->authorize());
    }

    public function test_rules_are_correct()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->request->setUserResolver(function () use ($user) {
            return $user;
        });

        $expectedRules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id),
            ],
        ];

        $this->assertEquals($expectedRules, $this->request->rules());
    }

    public function test_name_is_required()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->request->setUserResolver(function () use ($user) {
            return $user;
        });

        $validator = Validator::make(['email' => 'test@example.com'], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_email_must_be_unique_except_for_the_current_user()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $this->actingAs($user1);
        $this->request->setUserResolver(function () use ($user1) {
            return $user1;
        });

        // Test with user1's own email (should pass)
        $validator = Validator::make(['name' => 'Test', 'email' => $user1->email], $this->request->rules());
        $this->assertFalse($validator->fails());

        // Test with user2's email (should fail)
        $validator = Validator::make(['name' => 'Test', 'email' => $user2->email], $this->request->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }
}

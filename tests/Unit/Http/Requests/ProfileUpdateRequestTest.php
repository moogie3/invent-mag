<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;
use Illuminate\Validation\Rule;
use PHPUnit\Framework\Attributes\Test;
use Tests\Traits\CreatesTenant;

class ProfileUpdateRequestTest extends TestCase
{
    use RefreshDatabase, CreatesTenant;

    private ProfileUpdateRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant(); // Creates $this->tenant and $this->user, and calls actingAs
        $this->request = new ProfileUpdateRequest();
    }

    #[Test]
    public function test_authorize_returns_true_for_authenticated_user()
    {
        // We need to set the user on the request for the authorize method to work
        $this->request->setUserResolver(function () {
            return $this->user;
        });

        $this->assertTrue($this->request->authorize());
    }

    #[Test]
    public function test_authorize_returns_false_for_unauthenticated_user()
    {
        // Ensure no user is authenticated
        auth()->logout();

        $this->request->setUserResolver(function () {
            return null;
        });

        $this->assertFalse($this->request->authorize());
    }

    #[Test]
    public function test_rules_are_correct()
    {
        $this->request->setUserResolver(function () {
            return $this->user;
        });

        $expectedRules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user->id),
            ],
        ];

        $this->assertEquals($expectedRules, $this->request->rules());
    }

    #[Test]
    public function test_name_is_required()
    {
        $this->request->setUserResolver(function () {
            return $this->user;
        });

        $validator = Validator::make(['email' => 'test@example.com'], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    #[Test]
    public function test_email_must_be_unique_except_for_the_current_user()
    {
        $user2 = User::factory()->create();
        $this->request->setUserResolver(function () {
            return $this->user;
        });

        // Test with user1's own email (should pass)
        $validator = Validator::make(['name' => 'Test', 'email' => $this->user->email], $this->request->rules());
        $this->assertFalse($validator->fails());

        // Test with user2's email (should fail)
        $validator = Validator::make(['name' => 'Test', 'email' => $user2->email], $this->request->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }
}

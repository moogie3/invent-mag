<?php

namespace Tests\Feature\Api;

use App\Mail\ContactFormMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_submission_sends_email(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/contact', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'company' => 'ACME Corp',
            'topic' => 'sales',
            'message' => 'I would like a demo of the Enterprise plan.',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        Mail::assertQueued(ContactFormMail::class, function (ContactFormMail $mail) {
            return $mail->hasTo(config('app.contact_email'))
                && $mail->name === 'John Doe'
                && $mail->topic === 'Sales';
        });
    }

    public function test_contact_submission_returns_validation_errors(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/contact', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'email', 'topic', 'message']);

        Mail::assertNothingQueued();
    }

    public function test_contact_submission_rejects_invalid_topic(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/contact', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'topic' => 'scam',
            'message' => 'Hello',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['topic']);

        Mail::assertNothingQueued();
    }

    public function test_contact_submission_ignores_honeypot(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/contact', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'topic' => 'sales',
            'message' => 'Hello',
            'botcheck' => 'true',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        Mail::assertNothingQueued();
    }
}

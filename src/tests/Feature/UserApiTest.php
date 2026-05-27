<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class UserApiTest extends TestCase
{
    use RefreshDatabase;

    // POST /api/users — happy path

    public function test_create_user_returns_201_with_correct_shape(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/users', [
            'name'     => 'Test User',
            'email'    => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'email', 'name', 'role', 'created_at'],
            ])
            ->assertJson(['success' => true])
            ->assertJsonPath('data.email', 'test@example.com');
    }

    public function test_create_user_sends_welcome_and_admin_notification_emails(): void
    {
        Mail::fake();

        $this->postJson('/api/users', [
            'name'     => 'Test User',
            'email'    => 'test@example.com',
            'password' => 'password123',
        ]);

        Mail::assertSent(\App\Mail\WelcomeUserMail::class);
        Mail::assertSent(\App\Mail\NewUserAdminNotificationMail::class);
    }

    // POST /api/users — validation errors

    public function test_create_user_returns_422_when_email_is_missing(): void
    {
        $response = $this->postJson('/api/users', [
            'name'     => 'Test User',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonStructure(['success', 'message', 'errors' => ['email']]);
    }

    public function test_create_user_returns_422_when_email_is_already_taken(): void
    {
        Mail::fake();

        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->postJson('/api/users', [
            'name'     => 'Another User',
            'email'    => 'taken@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonStructure(['errors' => ['email']]);
    }

    public function test_create_user_returns_422_when_password_is_too_short(): void
    {
        $response = $this->postJson('/api/users', [
            'name'     => 'Test User',
            'email'    => 'test@example.com',
            'password' => 'short',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['password']]);
    }

    // GET /api/users — unauthenticated

    public function test_list_users_returns_401_when_unauthenticated(): void
    {
        $this->getJson('/api/users')->assertStatus(401);
    }

    // GET /api/users — admin can_edit all

    public function test_list_users_as_admin_has_can_edit_true_for_all_users(): void
    {
        $admin   = User::factory()->create(['role' => UserRole::Administrator]);
        $manager = User::factory()->create(['role' => UserRole::Manager]);
        $user    = User::factory()->create(['role' => UserRole::User]);

        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/users');

        $response->assertStatus(200)->assertJson(['success' => true]);

        $users = $response->json('data.users');
        foreach ($users as $listedUser) {
            $this->assertTrue($listedUser['can_edit'], "Admin should be able to edit user id {$listedUser['id']}");
        }
    }

    // GET /api/users — manager can_edit only role=user

    public function test_list_users_as_manager_can_edit_only_users_with_role_user(): void
    {
        $manager = User::factory()->create(['role' => UserRole::Manager]);
        $admin   = User::factory()->create(['role' => UserRole::Administrator]);
        $user    = User::factory()->create(['role' => UserRole::User]);

        $token = $manager->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/users');
        $response->assertStatus(200);

        $users = collect($response->json('data.users'))->keyBy('id');

        $this->assertFalse($users[$admin->id]['can_edit'], 'Manager should not edit admin');
        $this->assertFalse($users[$manager->id]['can_edit'], 'Manager should not edit other managers');
        $this->assertTrue($users[$user->id]['can_edit'], 'Manager should edit users');
    }

    // GET /api/users — user can_edit only self

    public function test_list_users_as_user_can_edit_only_self(): void
    {
        $admin   = User::factory()->create(['role' => UserRole::Administrator]);
        $manager = User::factory()->create(['role' => UserRole::Manager]);
        $self    = User::factory()->create(['role' => UserRole::User]);
        $other   = User::factory()->create(['role' => UserRole::User]);

        $token = $self->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/users');
        $response->assertStatus(200);

        $users = collect($response->json('data.users'))->keyBy('id');

        $this->assertTrue($users[$self->id]['can_edit'], 'User should edit self');
        $this->assertFalse($users[$admin->id]['can_edit'], 'User should not edit admin');
        $this->assertFalse($users[$manager->id]['can_edit'], 'User should not edit manager');
        $this->assertFalse($users[$other->id]['can_edit'], 'User should not edit other user');
    }
}

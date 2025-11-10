<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Mockery;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Illuminate\Pagination\LengthAwarePaginator;

class UserControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutExceptionHandling();

        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        Permission::findOrCreate('view-users', 'web');
        Permission::findOrCreate('create-users', 'web');
        Permission::findOrCreate('edit-users', 'web');
        Permission::findOrCreate('delete-users', 'web');

        // Create an admin user and assign the 'superuser' role
        $this->admin = User::factory()->create();
        $superUserRole = Role::findOrCreate('superuser', 'web');
        $superUserRole->givePermissionTo(Permission::all()); // Give all permissions to superuser
        $this->admin->assignRole($superUserRole);
        $this->actingAs($this->admin);
    }

    public function test_index_displays_user_management_page()
    {
        $users = User::factory()->count(3)->create();
        $perPage = 10;
        $currentPage = 1;
        $total = $users->count();

        $paginatedUsers = new LengthAwarePaginator(
            $users->forPage($currentPage, $perPage),
            $total,
            $perPage,
            $currentPage,
            ['path' => route('admin.users.index')]
        );

        $roles = Role::all();
        $permissions = Permission::all();

        $userData = [
            'users' => $paginatedUsers,
            'roles' => $roles,
            'permissions' => $permissions,
            'entries' => $perPage,
        ];

        // Mock the UserService
        $this->instance(
            UserService::class,
            Mockery::mock(UserService::class, function ($mock) use ($userData) {
                $mock->shouldReceive('getUserIndexData')->once()->andReturn($userData);
            })
        );

        $response = $this->get(route('admin.users.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.users.index');
        $response->assertViewHas('users', $userData['users']);
        $response->assertViewHas('roles', $userData['roles']);
        $response->assertViewHas('permissions', $userData['permissions']);
        $response->assertViewHas('entries', $userData['entries']);
    }

    public function test_store_user_successfully()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'roles' => ['staff'],
            'permissions' => ['view-users'],
        ];

        // Ensure the 'staff' role and 'view-users' permission exist
        Role::findOrCreate('staff', 'web');
        Permission::findOrCreate('view-users', 'web');

        // Mock the UserService
        $this->instance(
            UserService::class,
            Mockery::mock(UserService::class, function ($mock) use ($userData) {
                $mock->shouldReceive('createUser')->once()->with(Mockery::on(function ($arg) use ($userData) {
                    return $arg['name'] === $userData['name'] &&
                           $arg['email'] === $userData['email'] &&
                           $arg['password'] === $userData['password'] &&
                           $arg['roles'] === $userData['roles'] &&
                           $arg['permissions'] === $userData['permissions'];
                }));
            })
        );

        $response = $this->post(route('admin.users.store'), $userData);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success', 'User created successfully.');
    }

    public function test_store_user_with_invalid_data_returns_validation_errors()
    {
        $invalidData = [
            'name' => '', // Required
            'email' => 'invalid-email', // Invalid email format
            'password' => 'short', // Min 8 characters
            'password_confirmation' => 'mismatch', // Confirmed
        ];

        // Mock the UserService to ensure createUser is NOT called
        $this->instance(
            UserService::class,
            Mockery::mock(UserService::class, function ($mock) {
                $mock->shouldNotReceive('createUser');
            })
        );

        $response = $this->post(route('admin.users.store'), $invalidData);

        $this->assertDatabaseMissing('users', ['email' => 'invalid-email']);
        $response->assertSessionHasErrors(['name', 'email', 'password']);
        $response->assertStatus(302); // Redirect back on validation error
    }

    public function test_edit_user_displays_edit_page()
    {
        $user = User::factory()->create();
        $roles = Role::all();
        $permissions = Permission::all();

        $editData = [
            'user' => $user,
            'roles' => $roles,
            'permissions' => $permissions,
        ];

        // Mock the UserService
        $this->instance(
            UserService::class,
            Mockery::mock(UserService::class, function ($mock) use ($user, $editData) {
                $mock->shouldReceive('getUserEditData')->once()->with(Mockery::on(function ($arg) use ($user) {
                    return $arg->id === $user->id;
                }))->andReturn($editData);
            })
        );

        $response = $this->get(route('admin.users.edit', $user));

        $response->assertStatus(200);
        $response->assertViewIs('admin.users.edit');
        $response->assertViewHas('user', $editData['user']);
        $response->assertViewHas('roles', $editData['roles']);
        $response->assertViewHas('permissions', $editData['permissions']);
    }

    public function test_edit_user_returns_json_for_ajax_request()
    {
        $user = User::factory()->create();
        $roles = Role::all();
        $permissions = Permission::all();

        $editData = [
            'user' => $user,
            'roles' => $roles,
            'permissions' => $permissions,
        ];

        // Mock the UserService
        $this->instance(
            UserService::class,
            Mockery::mock(UserService::class, function ($mock) use ($user, $editData) {
                $mock->shouldReceive('getUserEditData')->once()->with(Mockery::on(function ($arg) use ($user) {
                    return $arg->id === $user->id;
                }))->andReturn($editData);
            })
        );

        $response = $this->get(route('admin.users.edit', $user), ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertStatus(200);
        $response->assertJson($editData);
    }
}
<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Foundation\Testing\WithFaker;
use Mockery;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\CreatesTenant;
use Illuminate\Pagination\LengthAwarePaginator;
use Database\Seeders\RoleSeeder;

class UserControllerTest extends TestCase
{
    use WithFaker, CreatesTenant, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenant();
        $this->seed(RoleSeeder::class);
        $this->user->assignRole('superuser');
        $this->actingAs($this->user);
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_index_displays_user_management_page()
    {
        $users = User::factory()->count(1)->make();
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
        $response->assertViewHas('permissions', $permissions);
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

    public function test_show_user_returns_json_for_ajax_request()
    {
        $user = User::factory()->create();
        $user->assignRole('staff'); // Assign a role to the user
        $user->givePermissionTo('view-users'); // Assign a permission to the user

        $roles = Role::all();
        $permissions = Permission::all();

        $showData = [
            'user' => $user->toArray(), // Convert user model to array
            'roles' => $roles->toArray(), // Convert roles collection to array
            'permissions' => $permissions->toArray(), // Convert permissions collection to array
            'userRoles' => $user->roles->pluck('name')->toArray(),
            'userPermissions' => $user->getAllPermissions()->pluck('name')->toArray(),
        ];

        // Mock the UserService
        $this->instance(
            UserService::class,
            Mockery::mock(UserService::class, function ($mock) use ($user, $showData) {
                $mock->shouldReceive('getUserEditData')->once()->with(Mockery::on(function ($arg) use ($user) {
                    return $arg->id === $user->id;
                }))->andReturn($showData);
            })
        );

        $response = $this->get(route('admin.users.show', $user), ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertStatus(200);
        $response->assertJson($showData);
    }



    public function test_edit_user_returns_json_for_ajax_request()
    {
        $user = User::factory()->create();
        $user->assignRole('staff'); // Assign a role to the user
        $user->givePermissionTo('view-users'); // Assign a permission to the user

        $roles = Role::all();
        $permissions = Permission::all();

        $editData = [
            'user' => $user->toArray(), // Convert user model to array
            'roles' => $roles->toArray(), // Convert roles collection to array
            'permissions' => $permissions->toArray(), // Convert permissions collection to array
            'userRoles' => $user->roles->pluck('name')->toArray(),
            'userPermissions' => $user->getAllPermissions()->pluck('name')->toArray(),
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

    public function test_update_user_successfully()
    {
        $user = User::factory()->create();
        $user->assignRole('staff');
        $user->givePermissionTo('view-users');

        $updatedData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'roles' => ['superuser'],
            'permissions' => ['create-users'],
        ];

        Role::findOrCreate('superuser', 'web');
        Permission::findOrCreate('create-users', 'web');

        $response = $this->put(route('admin.users.update', $user), $updatedData);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success', 'User updated successfully.');
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);
    }

    public function test_update_user_successfully_via_ajax()
    {
        $user = User::factory()->create();
        $updatedData = [
            'name' => 'Updated AJAX Name',
            'email' => 'updatedajax@example.com',
            'roles' => [],
            'permissions' => [],
        ];

        $mockService = Mockery::mock(UserService::class);
        $mockService->shouldReceive('updateUser')->once();
        $this->app->instance(UserService::class, $mockService);

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->json('PUT', route('admin.users.update', $user), $updatedData);

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'User updated successfully.']);
    }

    public function test_update_user_with_invalid_data_returns_validation_errors()
    {
        $user = User::factory()->create();
        $user->assignRole('staff');
        $user->givePermissionTo('view-users');

        $invalidData = [
            'name' => '', // Required
            'email' => 'invalid-email', // Invalid email format
        ];

        // Mock the UserService to ensure updateUser is NOT called
        $this->instance(
            UserService::class,
            Mockery::mock(UserService::class, function ($mock) {
                $mock->shouldNotReceive('updateUser');
            })
        );

        $response = $this->put(route('admin.users.update', $user), $invalidData);

        $response->assertSessionHasErrors(['name', 'email']);
        $response->assertStatus(302); // Redirect back on validation error
    }

    public function test_destroy_user_successfully()
    {
        $user = User::factory()->create();
        $user->assignRole('staff');
        $user->givePermissionTo('delete-users'); // Ensure user has permission to delete

        $response = $this->delete(route('admin.users.destroy', $user));

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success', 'User deleted successfully.');
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_destroy_user_successfully_via_ajax()
    {
        $user = User::factory()->create();

        $mockService = Mockery::mock(UserService::class);
        $mockService->shouldReceive('deleteUser')->once();
        $this->app->instance(UserService::class, $mockService);

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->json('DELETE', route('admin.users.destroy', $user));

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'User deleted successfully.']);
    }

    public function test_store_handles_service_exception()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];

        $mockService = Mockery::mock(UserService::class);
        $mockService->shouldReceive('createUser')->once()->andThrow(new \Exception('Service Error'));
        $this->app->instance(UserService::class, $mockService);

        $response = $this->post(route('admin.users.store'), $userData);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_update_handles_service_exception()
    {
        $user = User::factory()->create();
        $updatedData = ['name' => 'New Name', 'email' => 'new@example.com'];

        $mockService = Mockery::mock(UserService::class);
        $mockService->shouldReceive('updateUser')->once()->andThrow(new \Exception('Service Error'));
        $this->app->instance(UserService::class, $mockService);

        $response = $this->put(route('admin.users.update', $user), $updatedData);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_update_handles_service_exception_via_ajax()
    {
        $user = User::factory()->create();
        $updatedData = ['name' => 'New Name', 'email' => 'new@example.com'];

        $mockService = Mockery::mock(UserService::class);
        $mockService->shouldReceive('updateUser')->once()->andThrow(new \Exception('Service Error'));
        $this->app->instance(UserService::class, $mockService);

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->json('PUT', route('admin.users.update', $user), $updatedData);

        $response->assertStatus(500)
            ->assertJson(['success' => false]);
    }

    public function test_destroy_handles_service_exception()
    {
        $user = User::factory()->create();

        $mockService = Mockery::mock(UserService::class);
        $mockService->shouldReceive('deleteUser')->once()->andThrow(new \Exception('Service Error'));
        $this->app->instance(UserService::class, $mockService);

        $response = $this->delete(route('admin.users.destroy', $user));

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('error');
    }

    public function test_destroy_handles_service_exception_via_ajax()
    {
        $user = User::factory()->create();

        $mockService = Mockery::mock(UserService::class);
        $mockService->shouldReceive('deleteUser')->once()->andThrow(new \Exception('Service Error'));
        $this->app->instance(UserService::class, $mockService);

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->json('DELETE', route('admin.users.destroy', $user));

        $response->assertStatus(500)
            ->assertJson(['success' => false]);
    }

    public function test_get_role_permissions_returns_json()
    {
        // Create some roles and permissions
        $role1 = Role::findOrCreate('role1', 'web');
        $role2 = Role::findOrCreate('role2', 'web');
        $staffRole = Role::findOrCreate('staff', 'web');
        $posRole = Role::findOrCreate('pos', 'web');

        $permission1 = Permission::findOrCreate('permission1', 'web');
        $permission2 = Permission::findOrCreate('permission2', 'web');
        $permission3 = Permission::findOrCreate('permission3', 'web');
        $viewUsersPermission = Permission::findOrCreate('view-users', 'web');
        $createUsersPermission = Permission::findOrCreate('create-users', 'web');
        $editUsersPermission = Permission::findOrCreate('edit-users', 'web');
        $deleteUsersPermission = Permission::findOrCreate('delete-users', 'web');

        $role1->givePermissionTo($permission1);
        $role2->givePermissionTo([$permission2, $permission3]);
        $staffRole->givePermissionTo($viewUsersPermission);
        $posRole->givePermissionTo($createUsersPermission);

        // Explicitly define expected permissions based on test setup
        $expectedSuperuserPermissions = [
            'view-users', 'create-users', 'edit-users', 'delete-users'
        ];
        sort($expectedSuperuserPermissions);

        $expectedStaffPermissions = [
            'view-users'
        ];
        sort($expectedStaffPermissions);

        $expectedPosPermissions = [
            'create-users'
        ];
        sort($expectedPosPermissions);

        $expectedRolePermissionsSubset = [
            'role1' => ['permission1'],
            'role2' => ['permission2', 'permission3'],
            'staff' => $expectedStaffPermissions,
            'pos' => $expectedPosPermissions,
            'superuser' => $expectedSuperuserPermissions,
        ];
        // Sort the inner arrays for consistent comparison
        foreach ($expectedRolePermissionsSubset as $roleName => $permissions) {
            sort($expectedRolePermissionsSubset[$roleName]);
        }

        $expectedAllPermissionsSubset = [
            'view-users', 'create-users', 'edit-users', 'delete-users',
            'permission1', 'permission2', 'permission3'
        ];
        sort($expectedAllPermissionsSubset);

        $response = $this->get(route('admin.roles-permissions'));

        $response->assertStatus(200);

        $responseData = $response->json();

        // Assert rolePermissions subset
        foreach ($expectedRolePermissionsSubset as $roleName => $permissions) {
            $this->assertArrayHasKey($roleName, $responseData['rolePermissions']);
            sort($responseData['rolePermissions'][$roleName]); // Sort actual for comparison
            $this->assertEquals($permissions, array_intersect($permissions, $responseData['rolePermissions'][$roleName]));
        }

        // Assert allPermissions subset
        sort($responseData['allPermissions']); // Sort actual for comparison
        $this->assertEquals($expectedAllPermissionsSubset, array_intersect($expectedAllPermissionsSubset, $responseData['allPermissions']));
    }
}
<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\UserService;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\Unit\BaseUnitTestCase;
use PHPUnit\Framework\Attributes\Test;

class UserServiceTest extends BaseUnitTestCase
{
    protected UserService $userService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userService = new UserService();

        // Ensure roles and permissions exist for tests that need them
        Role::findOrCreate('superuser', 'web');
        Role::findOrCreate('staff', 'web');
        Role::findOrCreate('pos', 'web');
        Permission::findOrCreate('view-users', 'web');
        Permission::findOrCreate('create-users', 'web');
        Permission::findOrCreate('edit-users', 'web');
        Permission::findOrCreate('delete-users', 'web');
        Permission::findOrCreate('permission1', 'web');
        Permission::findOrCreate('permission2', 'web');
        Permission::findOrCreate('permission3', 'web');
    }

    #[Test]
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(UserService::class, $this->userService);
    }

    #[Test]
    public function it_can_get_user_index_data()
    {
        User::factory()->count(3)->create();
        $data = $this->userService->getUserIndexData();

        $this->assertArrayHasKey('users', $data);
        $this->assertArrayHasKey('roles', $data);
        $this->assertArrayHasKey('permissions', $data);
        $this->assertCount(3, $data['users']); // Now only expects the 3 users created in this test
    }

    #[Test]
    public function it_can_get_user_edit_data()
    {
        $user = User::factory()->create();
        $role = Role::first();
        $permission = Permission::first();
        $user->assignRole($role);
        $user->givePermissionTo($permission);

        $data = $this->userService->getUserEditData($user);

        $this->assertArrayHasKey('user', $data);
        $this->assertArrayHasKey('roles', $data);
        $this->assertArrayHasKey('permissions', $data);
        $this->assertArrayHasKey('userRoles', $data);
        $this->assertArrayHasKey('userPermissions', $data);
        $this->assertEquals($user->id, $data['user']->id);
        $this->assertContains($role->name, $data['userRoles']);
        $this->assertContains($permission->name, $data['userPermissions']);
    }

    #[Test]
    public function it_can_create_a_user()
    {
        $data = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ];

        $user = $this->userService->createUser($data);

        $this->assertInstanceOf(User::class, $user);
        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    #[Test]
    public function it_can_create_a_user_with_roles_and_permissions()
    {
        $role = Role::first();
        $permission = Permission::first();
        $data = [
            'name' => 'Test User 2',
            'email' => 'test2@example.com',
            'password' => 'password',
            'roles' => [$role->name],
            'permissions' => [$permission->name],
        ];

        $user = $this->userService->createUser($data);

        $this->assertTrue($user->hasRole($role));
        $this->assertTrue($user->hasPermissionTo($permission));
    }

    #[Test]
    public function it_can_update_a_user()
    {
        $user = User::factory()->create();
        $data = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ];

        $this->userService->updateUser($user, $data);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Updated Name']);
    }

    #[Test]
    public function it_can_update_a_user_with_new_roles_and_permissions()
    {
        $user = User::factory()->create();
        $role1 = Role::first();
        $role2 = Role::create(['name' => 'new-role', 'guard_name' => 'web']);
        $permission1 = Permission::first();
        $permission2 = Permission::create(['name' => 'new-permission', 'guard_name' => 'web']);

        $user->assignRole($role1);
        $user->givePermissionTo($permission1);

        $data = [
            'name' => $user->name,
            'email' => $user->email,
            'roles' => [$role2->name],
            'permissions' => [$permission2->name],
        ];

        $this->userService->updateUser($user, $data);

        $this->assertFalse($user->fresh()->hasRole($role1));
        $this->assertTrue($user->fresh()->hasRole($role2));
        $this->assertFalse($user->fresh()->hasPermissionTo($permission1));
        $this->assertTrue($user->fresh()->hasPermissionTo($permission2));
    }

    #[Test]
    public function it_can_delete_a_user()
    {
        $user = User::factory()->create();
        $this->userService->deleteUser($user);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }
}

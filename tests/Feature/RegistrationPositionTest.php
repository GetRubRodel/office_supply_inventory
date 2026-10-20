<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\Register;
use App\Models\User;
use Filament\Forms\Components\Select;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RegistrationPositionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (User::$roles as $role) {
            Role::findOrCreate($role, 'web');
        }
    }

    public function test_position_options_are_unique_and_complete(): void
    {
        $options = User::positionOptions();

        $this->assertCount(31, $options);
        $this->assertSame($options, array_unique($options), 'Position options must not contain duplicates');
        $this->assertArrayHasKey('Administrative Aide I', $options);
        $this->assertArrayHasKey('Administrative Aide VI', $options);
        $this->assertArrayHasKey('Administrative Officer IV', $options);
        $this->assertArrayHasKey('Administrative Assistant III', $options);
        $this->assertArrayHasKey('Security Officer II', $options);
        $this->assertArrayHasKey('Process Server', $options);
        $this->assertArrayHasKey('Special Investigator IV', $options);
        $this->assertArrayHasKey('Information Officer II', $options);
        $this->assertArrayHasKey('Training Specialist II', $options);
        $this->assertArrayHasKey('Attorney V', $options);
        $this->assertArrayHasKey('OIC Director', $options);
        $this->assertArrayHasKey('Regional Director', $options);
    }

    public function test_register_page_has_required_position_field(): void
    {
        $component = Livewire::test(Register::class);

        $component->assertFormFieldExists('position', function (Select $field): bool {
            return $field->isRequired();
        });
    }

    public function test_registration_saves_position(): void
    {
        $component = Livewire::test(Register::class)
            ->fillForm([
                'name' => 'Juan Dela Cruz',
                'email' => 'juan@example.com',
                'password' => 'password',
                'passwordConfirmation' => 'password',
                'division' => 'Administrative',
                'position' => 'Administrative Aide III',
            ]);

        $component->call('register');

        $user = User::where('email', 'juan@example.com')->first();

        $this->assertNotNull($user, 'User should be created');
        $this->assertSame('Administrative Aide III', $user->position);
        $this->assertSame(User::ROLE_STAFF, $user->role);
        $this->assertSame(User::STATUS_PENDING, $user->status);
    }
}
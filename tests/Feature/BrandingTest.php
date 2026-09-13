<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BrandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_and_replace_organization_branding(): void
    {
        Storage::fake('public');
        $this->seed(DatabaseSeeder::class);
        $user = User::firstOrFail();
        $token = 'branding-token';

        $this->actingAs($user)->withSession(['_token' => $token])->put(route('branding.update'), [
            '_token' => $token,
            'logo' => UploadedFile::fake()->image('principal.png', 800, 300)->size(200),
            'favicon' => UploadedFile::fake()->image('favicon.png', 64, 64)->size(20),
        ])->assertRedirect();

        $organization = Organization::firstOrFail();
        $oldLogo = $organization->branding['logo'];
        Storage::disk('public')->assertExists($oldLogo);
        Storage::disk('public')->assertExists($organization->branding['favicon']);
        $this->assertSame($organization->brandingUrl('logo'), $organization->brandingUrl('login_logo'));

        $this->actingAs($user)->withSession(['_token' => $token])->put(route('branding.update'), [
            '_token' => $token,
            'logo' => UploadedFile::fake()->image('reemplazo.jpg', 800, 300)->size(200),
        ])->assertRedirect();

        $organization->refresh();
        Storage::disk('public')->assertMissing($oldLogo);
        Storage::disk('public')->assertExists($organization->branding['logo']);
    }

    public function test_non_admin_is_forbidden_and_login_has_text_fallback(): void
    {
        Storage::fake('public');
        $this->seed(DatabaseSeeder::class);
        $organization = Organization::firstOrFail();
        $user = User::create(['organization_id' => $organization->id, 'name' => 'Consulta', 'email' => 'consulta@example.test', 'role' => UserRole::Consulta, 'active' => true, 'password' => 'password']);

        $this->actingAs($user)->get(route('branding.edit'))->assertForbidden();
        $token = 'forbidden-token';
        $this->actingAs($user)->withSession(['_token' => $token])->put(route('branding.update'), ['_token' => $token, 'logo' => UploadedFile::fake()->image('forbidden.png')])->assertForbidden();
        Auth::logout();
        $this->get(route('login'))->assertOk()->assertSee('EDUTECH')->assertDontSee('<img', false);
    }
}

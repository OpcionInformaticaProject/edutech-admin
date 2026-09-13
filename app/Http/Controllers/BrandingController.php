<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateBrandingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BrandingController extends Controller
{
    public function edit(Request $request)
    {
        $this->authorizeUser($request);

        return view('settings.branding', ['organization' => $request->user()->organization]);
    }

    public function update(UpdateBrandingRequest $request)
    {
        $organization = $request->user()->organization;
        $branding = $organization->branding ?? [];
        $oldPaths = [];

        foreach (['logo', 'login_logo', 'favicon'] as $field) {
            if (! $request->hasFile($field)) {
                continue;
            }
            if (isset($branding[$field])) {
                $oldPaths[] = $branding[$field];
            }
            $branding[$field] = $request->file($field)->store('branding/'.$organization->id, 'public');
        }

        $organization->update(['branding' => $branding]);
        foreach ($oldPaths as $path) {
            if (str_starts_with($path, 'branding/'.$organization->id.'/') && ! in_array($path, $branding, true)) {
                Storage::disk('public')->delete($path);
            }
        }

        return back()->with('success', 'Apariencia actualizada correctamente.');
    }

    private function authorizeUser(Request $request): void
    {
        abort_unless(in_array($request->user()->role->value, ['superadmin', 'admin'], true), 403);
    }
}

<?php

namespace App\Http\Controllers;

use App\Jobs\CreateStripeCustomerJob;
use App\Jobs\IngestKnowledgeJob;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class OnboardingController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'agency_name' => 'required|string|max:255',
            'slug' => 'required|string|max:50|unique:tenants,slug|alpha_dash',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
            'plan' => 'required|in:starter,growth,pro',
        ]);
        DB::transaction(function () use ($validated) {
            $tenant = Tenant::create([
                'name' => $validated['agency_name'],
                'slug' => $validated['slug'],
                'plan' => $validated['plan'],
                'status' => 'trial',
                'trial_ends_at' => now()->addDays(14),
                'system_prompt' => $this->defaultSystemPrompt($validated['agency_name']),
            ]);
            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => $validated['agency_name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'owner',
            ]);
            CreateStripeCustomerJob::dispatch($tenant);
            Auth::login($user);
        });

        return response()->json(['success' => true, 'redirect' => '/dashboard']);
    }

    public function triggerIngestion(Request $request): JsonResponse
    {
        $tenant = tenant();
        $validated = $request->validate([
            'urls' => 'required|array|min:1',
            'urls.*' => 'url',
        ]);
        IngestKnowledgeJob::dispatch($tenant, $validated['urls']);

        return response()->json(['status' => 'queued']);
    }

    private function defaultSystemPrompt(string $agencyName): string
    {
        return "You are the AI assistant for {$agencyName}. Your role is to help website visitors understand our services and pricing, capture their contact details, and connect them with our team. Be professional, warm, and consultative.\n\n## SERVICES & PRICING\n[Configure your services and pricing below]\n\n## STRICT RULES\n- Never make up services or prices\n- Always try to collect name and email before ending a lead conversation";
    }
}

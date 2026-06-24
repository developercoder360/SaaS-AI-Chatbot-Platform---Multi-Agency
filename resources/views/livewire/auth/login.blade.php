<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use function Livewire\Volt\{state, layout};

layout('layouts.app');

state([
    'email' => '',
    'password' => '',
    'remember' => false,
]);

$login = function () {
    $credentials = $this->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    if (Auth::validate($credentials)) {
        $user = User::where('email', $this->email)->first();

        $host = request()->getHost();
        $tenant = Tenant::where('custom_domain', $host)->first();
        if (!$tenant) {
            $subdomain = explode('.', $host)[0];
            $tenant = Tenant::where('slug', $subdomain)->first();
        }

        // Strict isolation check
        if ($user->role !== 'super_admin') {
            if (!$tenant || $user->tenant_id !== $tenant->id) {
                throw ValidationException::withMessages([
                    'email' => 'Please log in from your specific agency domain.',
                ]);
            }

            if ($tenant->status === 'suspended') {
                throw ValidationException::withMessages([
                    'email' => 'This agency is currently suspended.',
                ]);
            }
        }

        Auth::login($user, $this->remember);
        session()->regenerate();

        if ($user->role === 'super_admin') {
            return redirect()->intended('/super-admin');
        }

        return redirect()->intended('/dashboard');
    }

    throw ValidationException::withMessages([
        'email' => trans('auth.failed'),
    ]);
};

?>

<div class="min-h-screen bg-[#09090b] flex flex-col justify-center py-12 sm:px-6 lg:px-8 relative overflow-hidden">
    <!-- Global background glow -->
    <div
        class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-2xl h-[400px] bg-blue-600/10 blur-[120px] rounded-full pointer-events-none -z-10 mix-blend-screen">
    </div>

    <div class="sm:mx-auto sm:w-full sm:max-w-md relative z-10">
        <div class="flex justify-center mb-6">
            <div
                class="w-12 h-12 rounded-2xl bg-gradient-to-br from-neutral-800 to-black border border-white/10 flex items-center justify-center shadow-2xl">
                <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                        d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" />
                </svg>
            </div>
        </div>
        <h2 class="text-center text-3xl font-extrabold tracking-tight text-white mb-2">Welcome back</h2>
        <p class="text-center text-sm text-zinc-400 mb-8">Sign in to your agency dashboard</p>

        <x-ui.glass-card class="py-8 px-4 sm:px-10">
            <form wire:submit="login" class="space-y-6">
                <div>
                    <label for="email" class="block text-sm font-medium text-zinc-300">Email address</label>
                    <div class="mt-1 relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <input wire:model="email" id="email" name="email" type="email" autocomplete="email"
                            required
                            class="appearance-none block w-full pl-10 pr-3 py-2.5 border border-white/10 rounded-xl bg-black/20 text-white placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 sm:text-sm transition-all">
                    </div>
                    @error('email')
                        <span class="text-red-400 text-xs mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-zinc-300">Password</label>
                    <div class="mt-1 relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <input wire:model="password" id="password" name="password" type="password"
                            autocomplete="current-password" required
                            class="appearance-none block w-full pl-10 pr-3 py-2.5 border border-white/10 rounded-xl bg-black/20 text-white placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 sm:text-sm transition-all">
                    </div>
                    @error('password')
                        <span class="text-red-400 text-xs mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input wire:model="remember" id="remember-me" name="remember-me" type="checkbox"
                            class="h-4 w-4 text-blue-500 focus:ring-blue-500/50 border-white/10 rounded bg-black/20">
                        <label for="remember-me" class="ml-2 block text-sm text-zinc-400">
                            Remember me
                        </label>
                    </div>

                    <div class="text-sm">
                        <!-- Password resets are not currently configured -->
                        <!-- <a href="#" class="font-medium text-blue-400 hover:text-blue-300 transition-colors">
                            Forgot your password?
                        </a> -->
                    </div>
                </div>

                <div>
                    <x-ui.glow-button type="submit" class="w-full flex justify-center py-2.5">
                        Sign in
                    </x-ui.glow-button>
                </div>
            </form>
        </x-ui.glass-card>
    </div>
</div>

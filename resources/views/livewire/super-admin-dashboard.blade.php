<?php

use App\Models\Tenant;
use App\Models\Lead;
use function Livewire\Volt\{state, layout, computed};

layout('layouts.app');

state([
    'tab' => 'overview',
    'isCreating' => false,
    'isEditing' => false,
    'editingTenantId' => null,
    'tenantForm' => [
        'name' => '',
        'slug' => '',
        'plan' => 'starter',
        'status' => 'trial',
    ],
]);

$tenants = computed(function () {
    return Tenant::withCount([
        'conversations' => fn($q) => $q->withoutGlobalScope('tenant'),
        'leads' => fn($q) => $q->withoutGlobalScope('tenant'),
    ])
        ->with(['usageLogs' => fn($q) => $q->withoutGlobalScope('tenant')])
        ->latest()
        ->paginate(20);
});

$totalRevenue = computed(function () {
    return Tenant::where('status', 'active')
        ->get()
        ->sum(function ($tenant) {
            return match ($tenant->plan) {
                'starter' => 49,
                'growth' => 99,
                'pro' => 199,
                default => 0,
            };
        });
});

$totalLeads = computed(function () {
    return Lead::withoutGlobalScope('tenant')->count();
});

$activeTrials = computed(function () {
    return Tenant::where('status', 'trial')->where('trial_ends_at', '>', now())->count();
});

$suspendTenant = function (string $tenantId) {
    Tenant::findOrFail($tenantId)->update(['status' => 'suspended']);
};

$extendTrial = function (string $tenantId, int $days = 7) {
    $tenant = Tenant::findOrFail($tenantId);
    $tenant->update([
        'trial_ends_at' => $tenant->trial_ends_at ? $tenant->trial_ends_at->addDays($days) : now()->addDays($days),
    ]);
};

$resetForm = function () {
    $this->isCreating = false;
    $this->isEditing = false;
    $this->editingTenantId = null;
    $this->tenantForm = [
        'name' => '',
        'slug' => '',
        'plan' => 'starter',
        'status' => 'trial',
    ];
};

$createTenant = function () {
    $this->resetForm();
    $this->isCreating = true;
};

$editTenant = function (string $id) {
    $this->resetForm();
    $tenant = Tenant::findOrFail($id);
    $this->editingTenantId = $tenant->id;
    $this->tenantForm = [
        'name' => $tenant->name,
        'slug' => $tenant->slug,
        'plan' => $tenant->plan,
        'status' => $tenant->status,
    ];
    $this->isEditing = true;
};

$saveTenant = function () {
    $validated = $this->validate([
        'tenantForm.name' => 'required|string|max:255',
        'tenantForm.slug' => 'required|string|max:255|unique:tenants,slug,' . $this->editingTenantId,
        'tenantForm.plan' => 'required|in:starter,growth,pro',
        'tenantForm.status' => 'required|in:trial,active,suspended',
    ]);

    if ($this->isEditing) {
        Tenant::findOrFail($this->editingTenantId)->update($validated['tenantForm']);
    } else {
        Tenant::create($validated['tenantForm']);
    }

    $this->resetForm();
};

$deleteTenant = function (string $id) {
    Tenant::findOrFail($id)->delete();
};

?>
<div
    class="min-h-screen bg-[#09090b] flex flex-col font-sans text-zinc-100 selection:bg-blue-500/30 relative overflow-hidden">
    <!-- Global background glow -->
    <div
        class="absolute top-0 left-1/2 -translate-x-1/2 w-full max-w-4xl h-[600px] bg-purple-600/10 blur-[150px] rounded-full pointer-events-none -z-10 mix-blend-screen">
    </div>

    <nav class="sticky top-0 z-40 w-full bg-[#09090b]/80 backdrop-blur-xl border-b border-white/5">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 justify-between items-center">
                <div class="flex items-center gap-6">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-8 h-8 rounded-xl bg-gradient-to-br from-neutral-800 to-black border border-white/10 flex items-center justify-center shadow-lg">
                            <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" />
                            </svg>
                        </div>
                        <span class="text-base font-bold tracking-tight text-white">SaaS Command Center</span>
                    </div>
                    <div class="hidden md:flex items-center gap-6 text-sm font-semibold text-zinc-400">
                        <button wire:click="$set('tab', 'overview')"
                            class="{{ $tab === 'overview' ? 'text-white drop-shadow-[0_0_10px_rgba(255,255,255,0.5)]' : 'hover:text-white transition-colors' }}">Overview</button>
                        <button wire:click="$set('tab', 'agencies')"
                            class="{{ $tab === 'agencies' ? 'text-white drop-shadow-[0_0_10px_rgba(255,255,255,0.5)]' : 'hover:text-white transition-colors' }}">Agencies</button>
                        <button wire:click="$set('tab', 'billing')"
                            class="{{ $tab === 'billing' ? 'text-white drop-shadow-[0_0_10px_rgba(255,255,255,0.5)]' : 'hover:text-white transition-colors' }}">Billing</button>
                        <button wire:click="$set('tab', 'settings')"
                            class="{{ $tab === 'settings' ? 'text-white drop-shadow-[0_0_10px_rgba(255,255,255,0.5)]' : 'hover:text-white transition-colors' }}">Settings</button>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <button
                        class="relative p-2 text-zinc-400 hover:text-white transition-colors rounded-xl hover:bg-white/5">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <span
                            class="absolute top-2 right-2 w-2 h-2 rounded-full bg-blue-500 shadow-[0_0_8px_rgba(59,130,246,1)]"></span>
                    </button>
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" @click.away="open = false"
                            class="w-9 h-9 rounded-xl border border-white/10 bg-white/5 flex items-center justify-center text-xs font-bold shadow-inner hover:bg-white/10 transition-colors">
                            SA
                        </button>
                        <div x-show="open" style="display: none;"
                            class="absolute right-0 mt-2 w-48 rounded-xl bg-[#121214] border border-white/10 shadow-2xl overflow-hidden py-1 z-50">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                    class="w-full text-left px-4 py-2 text-sm text-zinc-300 hover:bg-white/5 hover:text-white transition-colors">
                                    Sign out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <main class="flex-1 py-10 relative z-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if ($tab === 'overview')
                <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-10 gap-4">
                    <div>
                        <h1 class="text-3xl font-extrabold leading-tight tracking-tight mb-2 text-white">Executive
                            Overview</h1>
                        <p class="text-sm text-zinc-400">Monitor your platform's growth and health.</p>
                    </div>
                    <div class="flex gap-3">
                        <x-ui.glow-button variant="secondary"
                            class="h-10 px-4 bg-white/5 border border-white/10 text-white hover:bg-white/10 text-sm flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg> Export Report
                        </x-ui.glow-button>
                    </div>
                </div>

                <!-- Metrics Grid -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 mb-10">
                    <!-- MRR -->
                    <div
                        class="bg-white/5 backdrop-blur-xl rounded-3xl p-6 border border-white/10 shadow-xl relative overflow-hidden group hover:bg-white/10 transition-all duration-300">
                        <div
                            class="absolute -top-10 -right-10 w-32 h-32 bg-green-500/20 blur-[50px] group-hover:bg-green-500/30 transition-colors">
                        </div>
                        <div class="flex justify-between items-start mb-4 relative z-10">
                            <dt class="text-xs font-bold text-zinc-500 uppercase tracking-widest">Monthly Recurring
                                Revenue</dt>
                            <span
                                class="inline-flex items-center gap-1 rounded-full bg-green-500/10 border border-green-500/20 px-2 py-1 text-[10px] font-bold text-green-400 shadow-[0_0_10px_rgba(34,197,94,0.1)]">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                                24%
                            </span>
                        </div>
                        <dd class="text-4xl font-extrabold tracking-tight text-white mb-6 relative z-10">
                            ${{ number_format($this->totalRevenue) }}</dd>
                        <div
                            class="w-full h-14 flex items-end justify-between gap-1.5 opacity-40 group-hover:opacity-100 transition-opacity duration-500 relative z-10">
                            <div class="w-full bg-green-500/20 rounded-t-sm" style="height: 30%"></div>
                            <div class="w-full bg-green-500/30 rounded-t-sm" style="height: 45%"></div>
                            <div class="w-full bg-green-500/40 rounded-t-sm" style="height: 40%"></div>
                            <div class="w-full bg-green-500/50 rounded-t-sm" style="height: 60%"></div>
                            <div class="w-full bg-green-500/60 rounded-t-sm" style="height: 55%"></div>
                            <div class="w-full bg-green-500/80 rounded-t-sm" style="height: 75%"></div>
                            <div class="w-full bg-green-400 shadow-[0_0_10px_rgba(74,222,128,0.8)] rounded-t-sm"
                                style="height: 100%"></div>
                        </div>
                    </div>

                    <!-- Active Trials -->
                    <div
                        class="bg-white/5 backdrop-blur-xl rounded-3xl p-6 border border-white/10 shadow-xl relative overflow-hidden group hover:bg-white/10 transition-all duration-300">
                        <div
                            class="absolute -top-10 -right-10 w-32 h-32 bg-indigo-500/20 blur-[50px] group-hover:bg-indigo-500/30 transition-colors">
                        </div>
                        <div class="flex justify-between items-start mb-4 relative z-10">
                            <dt class="text-xs font-bold text-zinc-500 uppercase tracking-widest">Active Trials</dt>
                            <span
                                class="inline-flex items-center gap-1 rounded-full bg-indigo-500/10 border border-indigo-500/20 px-2 py-1 text-[10px] font-bold text-indigo-400 shadow-[0_0_10px_rgba(99,102,241,0.1)]">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                                12%
                            </span>
                        </div>
                        <dd class="text-4xl font-extrabold tracking-tight text-white mb-6 relative z-10">
                            {{ $this->activeTrials }}</dd>
                        <div
                            class="w-full h-14 flex items-end justify-between gap-1.5 opacity-40 group-hover:opacity-100 transition-opacity duration-500 relative z-10">
                            <div class="w-full bg-indigo-500/20 rounded-t-sm" style="height: 50%"></div>
                            <div class="w-full bg-indigo-500/30 rounded-t-sm" style="height: 40%"></div>
                            <div class="w-full bg-indigo-500/40 rounded-t-sm" style="height: 60%"></div>
                            <div class="w-full bg-indigo-500/50 rounded-t-sm" style="height: 55%"></div>
                            <div class="w-full bg-indigo-500/60 rounded-t-sm" style="height: 80%"></div>
                            <div class="w-full bg-indigo-500/80 rounded-t-sm" style="height: 70%"></div>
                            <div class="w-full bg-indigo-400 shadow-[0_0_10px_rgba(129,140,248,0.8)] rounded-t-sm"
                                style="height: 90%"></div>
                        </div>
                    </div>

                    <!-- Total Leads -->
                    <div
                        class="bg-white/5 backdrop-blur-xl rounded-3xl p-6 border border-white/10 shadow-xl relative overflow-hidden group hover:bg-white/10 transition-all duration-300">
                        <div
                            class="absolute -top-10 -right-10 w-32 h-32 bg-blue-500/20 blur-[50px] group-hover:bg-blue-500/30 transition-colors">
                        </div>
                        <div class="flex justify-between items-start mb-4 relative z-10">
                            <dt class="text-xs font-bold text-zinc-500 uppercase tracking-widest">Total Leads Generated
                            </dt>
                            <span
                                class="inline-flex items-center gap-1 rounded-full bg-white/5 border border-white/10 px-2 py-1 text-[10px] font-bold text-zinc-400">
                                Stable
                            </span>
                        </div>
                        <dd class="text-4xl font-extrabold tracking-tight text-white mb-6 relative z-10">
                            {{ number_format($this->totalLeads) }}</dd>
                        <div
                            class="w-full h-14 flex items-end justify-between gap-1.5 opacity-40 group-hover:opacity-100 transition-opacity duration-500 relative z-10">
                            <div class="w-full bg-blue-500/20 rounded-t-sm" style="height: 60%"></div>
                            <div class="w-full bg-blue-500/30 rounded-t-sm" style="height: 65%"></div>
                            <div class="w-full bg-blue-500/40 rounded-t-sm" style="height: 70%"></div>
                            <div class="w-full bg-blue-500/50 rounded-t-sm" style="height: 65%"></div>
                            <div class="w-full bg-blue-500/60 rounded-t-sm" style="height: 75%"></div>
                            <div class="w-full bg-blue-500/80 rounded-t-sm" style="height: 80%"></div>
                            <div class="w-full bg-blue-400 shadow-[0_0_10px_rgba(96,165,250,0.8)] rounded-t-sm"
                                style="height: 85%"></div>
                        </div>
                    </div>
                </div>

                <!-- Secondary Metrics Row -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-3 mb-12">
                    <div
                        class="flex items-center p-5 bg-[#121214] rounded-2xl border border-white/5 shadow-inner gap-5 relative overflow-hidden">
                        <div
                            class="w-12 h-12 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6 text-zinc-400" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-zinc-500 uppercase tracking-widest mb-1">Total Users
                            </p>
                            <p class="text-2xl font-bold text-white">1,248</p>
                        </div>
                    </div>
                    <div
                        class="flex items-center p-5 bg-[#121214] rounded-2xl border border-white/5 shadow-inner gap-5 relative overflow-hidden">
                        <div
                            class="w-12 h-12 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6 text-zinc-400" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-zinc-500 uppercase tracking-widest mb-1">Conversations
                                (MTD)</p>
                            <p class="text-2xl font-bold text-white">45.2k</p>
                        </div>
                    </div>
                    <div
                        class="flex items-center p-5 bg-[#121214] rounded-2xl border border-white/5 shadow-inner gap-5 relative overflow-hidden">
                        <div
                            class="w-12 h-12 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6 text-zinc-400" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-zinc-500 uppercase tracking-widest mb-1">Active
                                Subscriptions</p>
                            <p class="text-2xl font-bold text-white">89</p>
                        </div>
                    </div>
                </div>

                <!-- Agency Directory Table -->
                <div class="bg-[#121214] border border-white/10 rounded-3xl shadow-2xl overflow-hidden relative">
                    <div
                        class="absolute top-0 inset-x-0 h-px bg-gradient-to-r from-transparent via-white/20 to-transparent">
                    </div>

                    <div
                        class="px-6 py-5 border-b border-white/5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white/[0.02]">
                        <h3 class="text-lg font-bold text-white">Agency Directory</h3>
                        <div class="flex items-center gap-3">
                            <div class="relative max-w-sm w-full">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-zinc-500" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <input type="text"
                                    class="block w-full pl-11 pr-4 py-2 border border-white/10 rounded-xl bg-white/5 text-sm text-white placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 shadow-inner"
                                    placeholder="Search agencies...">
                            </div>
                            <x-ui.glow-button wire:click="createTenant" variant="primary"
                                class="h-10 px-4 text-sm whitespace-nowrap">
                                <svg class="w-4 h-4 mr-2 inline" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4" />
                                </svg> New Agency
                            </x-ui.glow-button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-white/5">
                            <thead class="bg-[#09090b]">
                                <tr>
                                    <th scope="col"
                                        class="py-4 pl-6 pr-3 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-widest">
                                        Agency</th>
                                    <th scope="col"
                                        class="px-3 py-4 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-widest">
                                        Plan</th>
                                    <th scope="col"
                                        class="px-3 py-4 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-widest">
                                        Status</th>
                                    <th scope="col"
                                        class="px-3 py-4 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-widest">
                                        Usage (Msgs/Leads)</th>
                                    <th scope="col"
                                        class="relative py-4 pl-3 pr-6 text-right text-[10px] font-bold text-zinc-500 uppercase tracking-widest">
                                        Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5 bg-[#121214]">
                                @forelse($this->tenants as $tenant)
                                    <tr class="hover:bg-white/[0.02] transition-colors group">
                                        <td class="whitespace-nowrap py-5 pl-6 pr-3 text-sm">
                                            <div class="flex items-center gap-4">
                                                <div
                                                    class="w-10 h-10 rounded-xl border border-white/10 overflow-hidden shrink-0 shadow-sm">
                                                    <img src="https://i.pravatar.cc/150?u={{ $tenant->id }}"
                                                        alt="" class="w-full h-full object-cover">
                                                </div>
                                                <div>
                                                    <div class="font-bold text-white mb-0.5">{{ $tenant->name }}</div>
                                                    <div class="text-xs text-zinc-500 font-mono">
                                                        {{ $tenant->slug }}.yoursaas.io</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-5 text-sm">
                                            <span
                                                class="inline-flex items-center rounded-full bg-white/5 border border-white/10 px-2.5 py-1 text-xs font-bold text-zinc-300 capitalize">{{ $tenant->plan }}</span>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-5 text-sm">
                                            @if ($tenant->status === 'active')
                                                <span
                                                    class="inline-flex items-center gap-1.5 rounded-full bg-green-500/10 border border-green-500/20 px-2.5 py-1 text-xs font-bold text-green-400 shadow-[0_0_10px_rgba(34,197,94,0.1)]">
                                                    <span
                                                        class="w-1.5 h-1.5 rounded-full bg-green-500 shadow-[0_0_5px_rgba(34,197,94,0.8)]"></span>
                                                    Active
                                                </span>
                                            @elseif($tenant->status === 'trial')
                                                <span
                                                    class="inline-flex items-center gap-1.5 rounded-full bg-blue-500/10 border border-blue-500/20 px-2.5 py-1 text-xs font-bold text-blue-400 shadow-[0_0_10px_rgba(59,130,246,0.1)]">
                                                    <span
                                                        class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></span>
                                                    Trial ({{ $tenant->trial_ends_at?->diffInDays(now()) }}d)
                                                </span>
                                            @else
                                                <span
                                                    class="inline-flex items-center gap-1.5 rounded-full bg-red-500/10 border border-red-500/20 px-2.5 py-1 text-xs font-bold text-red-400 shadow-[0_0_10px_rgba(239,68,68,0.1)]">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Suspended
                                                </span>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-5 text-sm text-zinc-400 font-mono">
                                            {{ $tenant->usageLogs->sum('messages_count') }} <span
                                                class="text-zinc-600">/</span> {{ $tenant->leads_count }}
                                        </td>
                                        <td class="whitespace-nowrap py-5 pl-3 pr-6 text-right text-sm font-medium">
                                            <div x-data="{ open: false }" class="relative inline-block text-left">
                                                <button @click="open = !open" @click.away="open = false"
                                                    type="button"
                                                    class="flex items-center text-zinc-500 hover:text-white transition-colors focus:outline-none p-1 rounded-lg hover:bg-white/5">
                                                    <svg class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor">
                                                        <path
                                                            d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                                    </svg>
                                                </button>
                                                <div x-show="open"
                                                    x-transition:enter="transition ease-out duration-100"
                                                    x-transition:enter-start="transform opacity-0 scale-95"
                                                    x-transition:enter-end="transform opacity-100 scale-100"
                                                    x-transition:leave="transition ease-in duration-75"
                                                    x-transition:leave-start="transform opacity-100 scale-100"
                                                    x-transition:leave-end="transform opacity-0 scale-95"
                                                    class="origin-top-right absolute right-0 mt-2 w-48 rounded-xl shadow-2xl bg-[#1a1a1c] border border-white/10 ring-1 ring-black ring-opacity-5 focus:outline-none z-10 py-1"
                                                    style="display: none;">
                                                    @if ($tenant->status !== 'suspended')
                                                        <button wire:click="suspendTenant('{{ $tenant->id }}')"
                                                            class="block w-full text-left px-4 py-2 text-sm text-red-400 hover:bg-white/5 transition-colors font-semibold">Suspend
                                                            Account</button>
                                                    @endif
                                                    @if ($tenant->status === 'trial')
                                                        <button wire:click="extendTrial('{{ $tenant->id }}')"
                                                            class="block w-full text-left px-4 py-2 text-sm text-zinc-300 hover:text-white hover:bg-white/5 transition-colors font-semibold">Extend
                                                            Trial (+7 Days)</button>
                                                    @endif
                                                    <button wire:click="editTenant('{{ $tenant->id }}')"
                                                        class="block w-full text-left px-4 py-2 text-sm text-zinc-300 hover:text-white hover:bg-white/5 transition-colors font-semibold">Edit
                                                        Agency</button>
                                                    <button wire:click="deleteTenant('{{ $tenant->id }}')"
                                                        onclick="confirm('Are you sure you want to delete this agency? This action cannot be undone.') || event.stopImmediatePropagation()"
                                                        class="block w-full text-left px-4 py-2 text-sm text-red-500 hover:text-red-400 hover:bg-white/5 transition-colors font-semibold">
                                                        Delete Agency
                                                    </button>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-16 text-center">
                                            <div
                                                class="mx-auto w-16 h-16 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center mb-4 shadow-inner">
                                                <svg class="w-8 h-8 text-zinc-600" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="1.5"
                                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                                </svg>
                                            </div>
                                            <h3 class="text-base font-bold text-white mb-1">No agencies found</h3>
                                            <p class="text-sm text-zinc-500">Get started by onboarding your first
                                                agency.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($this->tenants->hasPages())
                        <div class="border-t border-white/5 px-6 py-4 bg-white/[0.01]">
                            {{ $this->tenants->links() }}
                        </div>
                    @endif
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-20 text-center">
                    <div
                        class="w-16 h-16 mb-4 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center">
                        <svg class="w-8 h-8 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">Section Under Construction</h3>
                    <p class="text-zinc-400 max-w-md">The {{ ucfirst($tab) }} view is currently being updated. Please
                        check back later.</p>
                </div>
            @endif
        </div>
    </main>

    <!-- Create / Edit Modal -->
    @if ($isCreating || $isEditing)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto overflow-x-hidden bg-black/60 backdrop-blur-sm p-4">
            <div
                class="relative w-full max-w-lg rounded-3xl bg-[#121214] border border-white/10 shadow-2xl overflow-hidden">
                <div class="px-6 py-4 border-b border-white/5 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-white">{{ $isEditing ? 'Edit Agency' : 'New Agency' }}</h3>
                    <button wire:click="resetForm" class="text-zinc-500 hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <form wire:submit="saveTenant" class="p-6 space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-zinc-300 mb-1">Agency Name</label>
                        <input wire:model="tenantForm.name" type="text"
                            class="w-full px-4 py-2.5 bg-black/50 border border-white/10 rounded-xl text-white placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50"
                            placeholder="Acme Corp" required>
                        @error('tenantForm.name')
                            <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-300 mb-1">Subdomain Slug</label>
                        <div class="flex">
                            <input wire:model="tenantForm.slug" type="text"
                                class="w-full px-4 py-2.5 bg-black/50 border border-white/10 rounded-l-xl text-white placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 border-r-0"
                                placeholder="acme" required>
                            <span
                                class="inline-flex items-center px-4 rounded-r-xl border border-white/10 border-l-0 bg-white/5 text-zinc-500 text-sm">.yoursaas.io</span>
                        </div>
                        @error('tenantForm.slug')
                            <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-zinc-300 mb-1">Subscription Plan</label>
                            <select wire:model="tenantForm.plan"
                                class="w-full px-4 py-2.5 bg-black/50 border border-white/10 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-blue-500/50 appearance-none">
                                <option value="starter">Starter ($49/mo)</option>
                                <option value="growth">Growth ($99/mo)</option>
                                <option value="pro">Pro ($199/mo)</option>
                            </select>
                            @error('tenantForm.plan')
                                <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-zinc-300 mb-1">Status</label>
                            <select wire:model="tenantForm.status"
                                class="w-full px-4 py-2.5 bg-black/50 border border-white/10 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-blue-500/50 appearance-none">
                                <option value="trial">Trial</option>
                                <option value="active">Active</option>
                                <option value="suspended">Suspended</option>
                            </select>
                            @error('tenantForm.status')
                                <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="pt-4 flex justify-end gap-3 border-t border-white/5">
                        <button type="button" wire:click="resetForm"
                            class="px-5 py-2.5 rounded-xl border border-white/10 bg-transparent text-sm font-bold text-zinc-300 hover:text-white hover:bg-white/5 transition-colors">Cancel</button>
                        <x-ui.glow-button type="submit" variant="primary"
                            class="px-5 py-2.5 text-sm">{{ $isEditing ? 'Save Changes' : 'Create Agency' }}</x-ui.glow-button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>

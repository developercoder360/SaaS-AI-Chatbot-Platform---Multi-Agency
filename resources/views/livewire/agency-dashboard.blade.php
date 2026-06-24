<?php

use App\Models\Tenant;
use App\Models\UsageLog;
use Illuminate\Support\Facades\DB;
use App\Jobs\IngestKnowledgeJob;
use function Livewire\Volt\{state, layout, computed, mount};

layout('layouts.app');

state([
    'tab' => 'overview', // overview, settings, widget
    'primary_color' => '',
    'accent_color' => '',
    'system_prompt' => '',
    'url_input' => '',
    'urls' => [],
    'isCreatingLead' => false,
    'isEditingLead' => false,
    'editingLeadId' => null,
    'leadForm' => [
        'name' => '',
        'email' => '',
        'notes' => '',
    ],
]);

mount(function () {
    $tenant = tenant();
    if ($tenant) {
        $this->primary_color = $tenant->primary_color;
        $this->accent_color = $tenant->accent_color;
        $this->system_prompt = $tenant->system_prompt;
    }
});

$tenant = computed(function () {
    return tenant();
});

$stats = computed(function () {
    $tenant = tenant();
    return [
        'leads' => $tenant->leads()->count(),
        'conversations' => $tenant->conversations()->count(),
        'messages_this_month' => UsageLog::where('tenant_id', $tenant->id)->whereMonth('date', now()->month)->sum('messages_count'),
        // Static placeholders for UI purposes
        'total_bots' => 1,
        'active_channels' => 2,
        'team_members' => 1,
    ];
});

$knowledgeDocuments = computed(function () {
    return tenant()->knowledgeDocuments()->latest()->get();
});

$leads = computed(function () {
    return tenant()->leads()->latest()->paginate(15);
});

$saveSettings = function () {
    $tenant = tenant();
    $tenant->update([
        'primary_color' => $this->primary_color,
        'accent_color' => $this->accent_color,
        'system_prompt' => $this->system_prompt,
    ]);

    session()->flash('success', 'Settings saved successfully.');
};

$addUrl = function () {
    if (!empty($this->url_input) && filter_var($this->url_input, FILTER_VALIDATE_URL)) {
        $this->urls[] = $this->url_input;
        $this->url_input = '';
    }
};

$removeUrl = function ($index) {
    unset($this->urls[$index]);
    $this->urls = array_values($this->urls);
};

$ingestUrls = function () {
    if (count($this->urls) > 0) {
        IngestKnowledgeJob::dispatch(tenant(), $this->urls);
        $this->urls = [];
        session()->flash('success', 'URLs queued for ingestion. They will appear in the Knowledge Base shortly.');
    }
};

$resetLeadForm = function () {
    $this->isCreatingLead = false;
    $this->isEditingLead = false;
    $this->editingLeadId = null;
    $this->leadForm = [
        'name' => '',
        'email' => '',
        'notes' => '',
    ];
};

$createLead = function () {
    $this->resetLeadForm();
    $this->isCreatingLead = true;
};

$editLead = function (string $id) {
    $this->resetLeadForm();
    $lead = tenant()->leads()->findOrFail($id);
    $this->editingLeadId = $lead->id;
    $this->leadForm = [
        'name' => $lead->name,
        'email' => $lead->email,
        'notes' => $lead->notes,
    ];
    $this->isEditingLead = true;
};

$saveLead = function () {
    $validated = $this->validate([
        'leadForm.name' => 'required|string|max:255',
        'leadForm.email' => 'required|email|max:255',
        'leadForm.notes' => 'nullable|string',
    ]);

    if ($this->isEditingLead) {
        $lead = tenant()->leads()->findOrFail($this->editingLeadId);
        $lead->update($validated['leadForm']);
    } else {
        tenant()->leads()->create($validated['leadForm']);
    }

    $this->resetLeadForm();
    session()->flash('success', 'Lead saved successfully.');
};

$deleteLead = function (string $id) {
    $lead = tenant()->leads()->findOrFail($id);
    $lead->delete();
    session()->flash('success', 'Lead deleted.');
};

?>
<div class="min-h-screen bg-[#09090b] flex flex-col font-sans text-zinc-100 selection:bg-blue-500/30 relative overflow-hidden">
    <!-- Global background glow -->
    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full max-w-3xl h-[500px] bg-blue-600/10 blur-[120px] rounded-full pointer-events-none -z-10 mix-blend-screen"></div>

    <!-- Navbar -->
    <nav class="sticky top-0 z-40 w-full bg-[#09090b]/80 backdrop-blur-xl border-b border-white/5">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between">
                <div class="flex items-center gap-6">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-lg shadow-blue-500/30">
                            <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                        </div>
                        <span class="text-base font-bold tracking-tight text-white">{{ $this->tenant->name }}</span>
                    </div>
                    <div class="hidden md:flex items-center gap-2">
                        <span class="text-zinc-700">/</span>
                        <span class="px-2 py-1 text-sm font-medium text-zinc-400">Dashboard</span>
                    </div>
                </div>
                <div class="flex items-center gap-5">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/5 border border-white/10 px-3 py-1.5 text-xs font-medium text-zinc-300 shadow-inner">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-400 animate-pulse"></span>
                        {{ ucfirst($this->tenant->plan) }} Plan
                    </span>
                    <button class="w-9 h-9 rounded-full border-2 border-white/10 overflow-hidden shadow-sm transition-transform hover:scale-105 hover:border-blue-500/50">
                        <img src="https://i.pravatar.cc/150?u={{ $this->tenant->id }}" alt="Avatar" class="w-full h-full object-cover">
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <main class="flex-1 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 flex flex-col md:flex-row gap-8 lg:gap-12 relative z-10">
        
        <!-- Modern Sidebar -->
        <div class="w-full md:w-56 shrink-0">
            <nav class="sticky top-28 flex flex-col gap-2">
                <button wire:click="$set('tab', 'overview')" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition-all {{ $tab === 'overview' ? 'bg-white/10 text-white shadow-[0_0_15px_rgba(255,255,255,0.05)] border border-white/10' : 'text-zinc-400 hover:bg-white/5 hover:text-white border border-transparent' }}">
                    <svg class="w-5 h-5 {{ $tab === 'overview' ? 'text-blue-400' : 'text-zinc-500' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>
                    Overview
                </button>
                <button wire:click="$set('tab', 'settings')" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition-all {{ $tab === 'settings' ? 'bg-white/10 text-white shadow-[0_0_15px_rgba(255,255,255,0.05)] border border-white/10' : 'text-zinc-400 hover:bg-white/5 hover:text-white border border-transparent' }}">
                    <svg class="w-5 h-5 {{ $tab === 'settings' ? 'text-indigo-400' : 'text-zinc-500' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" /></svg>
                    Settings
                </button>
                <button wire:click="$set('tab', 'widget')" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition-all {{ $tab === 'widget' ? 'bg-white/10 text-white shadow-[0_0_15px_rgba(255,255,255,0.05)] border border-white/10' : 'text-zinc-400 hover:bg-white/5 hover:text-white border border-transparent' }}">
                    <svg class="w-5 h-5 {{ $tab === 'widget' ? 'text-pink-400' : 'text-zinc-500' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.25 9.75L16.5 12l-2.25 2.25m-4.5 0L7.5 12l2.25-2.25M6 20.25h12A2.25 2.25 0 0020.25 18V6A2.25 2.25 0 0018 3.75H6A2.25 2.25 0 003.75 6v12A2.25 2.25 0 006 20.25z" /></svg>
                    Embed Widget
                </button>
                <button wire:click="$set('tab', 'leads')" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition-all {{ $tab === 'leads' ? 'bg-white/10 text-white shadow-[0_0_15px_rgba(255,255,255,0.05)] border border-white/10' : 'text-zinc-400 hover:bg-white/5 hover:text-white border border-transparent' }}">
                    <svg class="w-5 h-5 {{ $tab === 'leads' ? 'text-green-400' : 'text-zinc-500' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                    Leads Management
                </button>
            </nav>
        </div>

        <!-- Content Area -->
        <div class="flex-1 min-w-0">
            <!-- Global Flash Messages -->
            @if (session()->has('success'))
                <div x-data="{ show: true }" x-show="show" x-transition.duration.300ms class="mb-8 overflow-hidden rounded-2xl bg-green-500/10 border border-green-500/20 shadow-[0_0_20px_rgba(34,197,94,0.1)] flex items-start gap-4 p-5 relative backdrop-blur-md">
                    <div class="w-8 h-8 rounded-full bg-green-500/20 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                    </div>
                    <div class="pt-1.5">
                        <p class="text-sm font-semibold text-green-400">{{ session('success') }}</p>
                    </div>
                    <button @click="show = false" class="absolute top-5 right-5 text-green-400/50 hover:text-green-400 transition-colors"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
                </div>
            @endif

            @if ($tab === 'overview')
                <div x-data x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
                    
                    <!-- Hero Banner -->
                    <div class="relative overflow-hidden rounded-3xl bg-[#121214] p-8 sm:p-12 mb-8 border border-white/5 shadow-2xl group">
                        <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-10"></div>
                        <div class="absolute -right-20 -top-20 w-80 h-80 bg-blue-500 rounded-full blur-[120px] opacity-20 group-hover:opacity-30 transition-opacity duration-1000"></div>
                        <div class="absolute -left-20 -bottom-20 w-60 h-60 bg-purple-500 rounded-full blur-[100px] opacity-10"></div>
                        
                        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                            <div>
                                <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-white mb-3">Welcome back, Admin</h1>
                                <p class="text-zinc-400 text-base max-w-lg leading-relaxed">Your AI assistants are actively running. You've generated <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-indigo-400 font-bold">{{ number_format($this->stats['leads']) }} leads</span> so far.</p>
                            </div>
                            <div class="flex flex-wrap gap-3 shrink-0">
                                <x-ui.glow-button variant="primary" class="h-11 px-6">Create Bot</x-ui.glow-button>
                                <x-ui.glow-button variant="secondary" class="h-11 px-6 bg-white/5 border-white/10 hover:bg-white/10 text-white">View Analytics</x-ui.glow-button>
                            </div>
                        </div>
                    </div>

                    <!-- Bento Grid KPIs -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 mb-8">
                        <!-- Leads -->
                        <div class="bg-white/5 backdrop-blur-xl rounded-3xl p-6 border border-white/10 shadow-xl hover:bg-white/10 transition-all duration-300 group relative overflow-hidden">
                            <div class="absolute -top-10 -right-10 w-32 h-32 bg-blue-500/20 blur-[50px] group-hover:bg-blue-500/30 transition-colors"></div>
                            <div class="absolute top-6 right-6 opacity-30 group-hover:opacity-100 group-hover:scale-110 transition-all duration-500">
                                <svg class="w-10 h-10 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                            </div>
                            <dt class="text-xs font-bold text-zinc-500 uppercase tracking-widest mb-3">Leads Captured</dt>
                            <dd class="text-4xl font-extrabold tracking-tight text-white mb-2">{{ number_format($this->stats['leads']) }}</dd>
                            <div class="flex items-center text-xs font-bold text-green-400 bg-green-400/10 w-max px-2 py-1 rounded-full border border-green-400/20">
                                <svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18" /></svg>
                                12% from last week
                            </div>
                        </div>

                        <!-- Conversations -->
                        <div class="bg-white/5 backdrop-blur-xl rounded-3xl p-6 border border-white/10 shadow-xl hover:bg-white/10 transition-all duration-300 group relative overflow-hidden">
                            <div class="absolute -top-10 -right-10 w-32 h-32 bg-indigo-500/20 blur-[50px] group-hover:bg-indigo-500/30 transition-colors"></div>
                            <div class="absolute top-6 right-6 opacity-30 group-hover:opacity-100 group-hover:scale-110 transition-all duration-500">
                                <svg class="w-10 h-10 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                            </div>
                            <dt class="text-xs font-bold text-zinc-500 uppercase tracking-widest mb-3">Conversations</dt>
                            <dd class="text-4xl font-extrabold tracking-tight text-white mb-2">{{ number_format($this->stats['conversations']) }}</dd>
                            <div class="flex items-center text-xs font-bold text-green-400 bg-green-400/10 w-max px-2 py-1 rounded-full border border-green-400/20">
                                <svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18" /></svg>
                                5% from last week
                            </div>
                        </div>

                        <!-- Messages -->
                        <div class="bg-white/5 backdrop-blur-xl rounded-3xl p-6 border border-white/10 shadow-xl hover:bg-white/10 transition-all duration-300 group relative overflow-hidden">
                            <div class="absolute -top-10 -right-10 w-32 h-32 bg-purple-500/20 blur-[50px] group-hover:bg-purple-500/30 transition-colors"></div>
                            <div class="absolute top-6 right-6 opacity-30 group-hover:opacity-100 group-hover:scale-110 transition-all duration-500">
                                <svg class="w-10 h-10 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                            </div>
                            <dt class="text-xs font-bold text-zinc-500 uppercase tracking-widest mb-3">Messages (MTD)</dt>
                            <dd class="text-4xl font-extrabold tracking-tight text-white mb-2">{{ number_format($this->stats['messages_this_month']) }}</dd>
                            <div class="flex items-center text-xs font-bold text-zinc-400 bg-white/5 w-max px-2 py-1 rounded-full border border-white/10">
                                On track for limits
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">
                        <div class="bg-[#121214] rounded-2xl p-5 border border-white/5 shadow-inner">
                            <dt class="text-[10px] font-bold text-zinc-500 uppercase tracking-widest mb-1">Total Bots</dt>
                            <dd class="text-2xl font-bold text-white">{{ $this->stats['total_bots'] }}</dd>
                        </div>
                        <div class="bg-[#121214] rounded-2xl p-5 border border-white/5 shadow-inner">
                            <dt class="text-[10px] font-bold text-zinc-500 uppercase tracking-widest mb-1">Active Channels</dt>
                            <dd class="text-2xl font-bold text-white">{{ $this->stats['active_channels'] }}</dd>
                        </div>
                        <div class="bg-[#121214] rounded-2xl p-5 border border-white/5 shadow-inner">
                            <dt class="text-[10px] font-bold text-zinc-500 uppercase tracking-widest mb-1">Team Members</dt>
                            <dd class="text-2xl font-bold text-white">{{ $this->stats['team_members'] }}</dd>
                        </div>
                    </div>

                    <!-- Knowledge Base Table -->
                    <div class="bg-[#121214] border border-white/10 rounded-3xl shadow-2xl overflow-hidden relative">
                        <div class="absolute top-0 inset-x-0 h-px bg-gradient-to-r from-transparent via-white/20 to-transparent"></div>
                        <div class="px-6 py-5 border-b border-white/5 flex items-center justify-between bg-white/[0.02]">
                            <h3 class="text-lg font-bold text-white">Knowledge Base Activity</h3>
                            <button wire:click="$set('tab', 'settings')" class="text-sm font-semibold text-blue-400 hover:text-blue-300 transition-colors flex items-center gap-1">Add Source <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-white/5">
                                <thead class="bg-[#09090b]">
                                    <tr>
                                        <th class="py-4 px-6 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-widest">Source URL</th>
                                        <th class="py-4 px-6 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-widest">Status</th>
                                        <th class="py-4 px-6 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-widest">Added</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/5 bg-[#121214]">
                                    @forelse($this->knowledgeDocuments as $doc)
                                        <tr class="hover:bg-white/[0.02] transition-colors group">
                                            <td class="py-4 px-6 text-sm text-zinc-300">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-8 h-8 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center group-hover:bg-white/10 transition-colors">
                                                        <svg class="w-4 h-4 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>
                                                    </div>
                                                    {{ Str::limit($doc->source_url, 50) }}
                                                </div>
                                            </td>
                                            <td class="py-4 px-6">
                                                @if ($doc->status === 'done')
                                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-green-500/10 border border-green-500/20 px-2.5 py-1 text-xs font-bold text-green-400 shadow-[0_0_10px_rgba(34,197,94,0.1)]">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500 shadow-[0_0_5px_rgba(34,197,94,0.8)]"></span> Active
                                                    </span>
                                                @elseif($doc->status === 'processing')
                                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-yellow-500/10 border border-yellow-500/20 px-2.5 py-1 text-xs font-bold text-yellow-400 shadow-[0_0_10px_rgba(234,179,8,0.1)]">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-yellow-500 animate-pulse shadow-[0_0_5px_rgba(234,179,8,0.8)]"></span> Processing
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center rounded-full bg-white/5 border border-white/10 px-2.5 py-1 text-xs font-bold text-zinc-400">{{ ucfirst($doc->status) }}</span>
                                                @endif
                                            </td>
                                            <td class="py-4 px-6 text-sm text-zinc-500">{{ $doc->created_at->diffForHumans() }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="py-16 text-center">
                                                <div class="mx-auto w-16 h-16 bg-white/5 border border-white/10 rounded-2xl flex items-center justify-center mb-4 shadow-inner">
                                                    <svg class="h-8 w-8 text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                                                </div>
                                                <h3 class="text-base font-bold text-white mb-1">No data sources</h3>
                                                <p class="text-sm text-zinc-500">Get started by adding URLs in Settings.</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            @if ($tab === 'settings')
                <div x-data x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" class="max-w-4xl">
                    <h2 class="text-3xl font-extrabold mb-8 tracking-tight text-white">Chatbot Configuration</h2>
                    
                    <div class="bg-[#121214] rounded-3xl shadow-2xl border border-white/10 overflow-hidden mb-10 relative">
                        <div class="absolute top-0 inset-x-0 h-px bg-gradient-to-r from-transparent via-white/20 to-transparent"></div>
                        <div class="px-8 py-6 border-b border-white/5 bg-white/[0.02]">
                            <h3 class="text-xl font-bold text-white">Appearance & Behavior</h3>
                            <p class="mt-1 text-sm text-zinc-400">Update how your chatbot looks and behaves on your site.</p>
                        </div>
                        
                        <form wire:submit.prevent="saveSettings" class="p-8 space-y-8">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-8">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-widest text-zinc-500 mb-3">Primary Brand Color</label>
                                    <div class="flex items-center gap-4">
                                        <div class="relative w-12 h-12 rounded-xl overflow-hidden shadow-inner border border-white/10 shrink-0 bg-transparent">
                                            <input type="color" wire:model="primary_color" class="absolute -top-4 -left-4 w-20 h-20 cursor-pointer border-0 bg-transparent p-0">
                                        </div>
                                        <input wire:model="primary_color" type="text" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:ring-2 focus:ring-blue-500/50 focus:border-transparent font-mono text-sm shadow-inner" />
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-widest text-zinc-500 mb-3">Accent Color</label>
                                    <div class="flex items-center gap-4">
                                        <div class="relative w-12 h-12 rounded-xl overflow-hidden shadow-inner border border-white/10 shrink-0 bg-transparent">
                                            <input type="color" wire:model="accent_color" class="absolute -top-4 -left-4 w-20 h-20 cursor-pointer border-0 bg-transparent p-0">
                                        </div>
                                        <input wire:model="accent_color" type="text" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:ring-2 focus:ring-blue-500/50 focus:border-transparent font-mono text-sm shadow-inner" />
                                    </div>
                                </div>
                            </div>
                            
                            <div class="relative group">
                                <label class="block text-xs font-bold uppercase tracking-widest text-zinc-500 mb-3 flex items-center justify-between">
                                    System Prompt
                                    <span class="text-[10px] font-bold text-zinc-600 bg-white/5 border border-white/5 px-2 py-1 rounded">MARKDOWN SUPPORTED</span>
                                </label>
                                <div class="absolute -inset-0.5 bg-gradient-to-r from-blue-500 to-purple-600 rounded-2xl blur opacity-0 group-focus-within:opacity-30 transition duration-500 pointer-events-none mt-8"></div>
                                <textarea wire:model="system_prompt" rows="10"
                                    class="relative block w-full rounded-2xl border border-white/10 p-5 text-sm text-zinc-200 bg-[#09090b] shadow-inner focus:ring-0 focus:border-white/20 font-mono resize-y transition-all"></textarea>
                                <p class="mt-3 text-xs text-zinc-500">This prompt dictates the core persona and rules of your AI agent.</p>
                            </div>

                            <div class="flex items-center justify-end pt-6 border-t border-white/5">
                                <x-ui.glow-button type="submit" variant="primary" class="px-8 h-12">Save Changes</x-ui.glow-button>
                            </div>
                        </form>
                    </div>

                    <!-- Add Knowledge Form -->
                    <div class="bg-[#121214] rounded-3xl shadow-2xl border border-white/10 overflow-hidden relative">
                        <div class="absolute top-0 inset-x-0 h-px bg-gradient-to-r from-transparent via-white/20 to-transparent"></div>
                        <div class="px-8 py-6 border-b border-white/5 flex justify-between items-center bg-white/[0.02]">
                            <div>
                                <h3 class="text-xl font-bold text-white">Knowledge Sources</h3>
                                <p class="mt-1 text-sm text-zinc-400">Feed the AI with content from URLs.</p>
                            </div>
                            <span class="text-[10px] font-bold uppercase tracking-widest bg-blue-500/10 text-blue-400 border border-blue-500/20 px-3 py-1.5 rounded-full shadow-[0_0_10px_rgba(59,130,246,0.2)]">{{ count($urls) }} queued</span>
                        </div>
                        <div class="p-8">
                            <div class="flex flex-col sm:flex-row gap-4 mb-8">
                                <div class="flex-1 relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>
                                    </div>
                                    <input wire:model="url_input" wire:keydown.enter="addUrl" type="url" placeholder="https://example.com/pricing" class="w-full bg-white/5 border border-white/10 rounded-xl pl-11 pr-4 py-3.5 text-white placeholder-zinc-600 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-transparent transition-all shadow-inner h-12" />
                                </div>
                                <x-ui.glow-button wire:click="addUrl" variant="secondary" class="shrink-0 h-12 px-8">Queue URL</x-ui.glow-button>
                            </div>

                            @if (count($urls) > 0)
                                <div class="bg-[#09090b] border border-white/5 rounded-2xl p-2 mb-8 shadow-inner">
                                    <ul class="divide-y divide-white/5">
                                        @foreach ($urls as $index => $url)
                                            <li class="flex items-center justify-between gap-x-4 py-3 px-4 hover:bg-white/5 rounded-xl transition-colors group">
                                                <div class="flex items-center gap-3 min-w-0">
                                                    <span class="text-xs font-bold text-zinc-600 w-6">#{{ $index+1 }}</span>
                                                    <span class="text-sm font-medium text-zinc-300 truncate">{{ $url }}</span>
                                                </div>
                                                <button wire:click="removeUrl({{ $index }})" class="text-zinc-500 hover:text-red-400 opacity-0 group-hover:opacity-100 transition-all p-2 rounded-lg hover:bg-red-500/10"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                                <x-ui.glow-button wire:click="ingestUrls" variant="primary" class="w-full h-14 text-base">Start Processing Queue</x-ui.glow-button>
                            @else
                                <div class="text-center py-12 bg-white/[0.02] border border-white/5 border-dashed rounded-2xl">
                                    <p class="text-sm text-zinc-500">Add a URL above to queue it for processing.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            @if ($tab === 'widget')
                <div x-data x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" class="max-w-4xl">
                    <h2 class="text-3xl font-extrabold mb-8 tracking-tight text-white">Installation</h2>
                    
                    <div class="bg-[#121214] rounded-3xl shadow-2xl border border-white/10 overflow-hidden relative group">
                        <div class="absolute inset-y-0 left-0 w-1.5 bg-gradient-to-b from-blue-500 to-purple-500"></div>
                        <div class="p-10">
                            <h3 class="text-2xl font-bold text-white mb-3">Embed Code</h3>
                            <p class="text-base text-zinc-400 mb-8">Copy and paste this snippet just before the closing 
                                <code class="text-xs font-bold bg-white/10 text-pink-400 px-2 py-1 rounded-md border border-white/10 font-mono tracking-wider">&lt;/body&gt;</code> 
                                tag on your website or client's website.
                            </p>

                            <div class="relative group mt-4">
                                <div class="absolute -inset-1 bg-gradient-to-r from-blue-500 to-purple-600 rounded-2xl blur opacity-20 group-hover:opacity-40 transition duration-1000 group-hover:duration-200"></div>
                                <div class="relative bg-[#09090b] rounded-2xl overflow-hidden shadow-2xl border border-white/10">
                                    <div class="flex items-center justify-between px-5 py-4 border-b border-white/5 bg-[#121214]">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-3.5 h-3.5 rounded-full bg-red-500/80 border border-red-500/50 shadow-[0_0_5px_rgba(239,68,68,0.5)]"></div>
                                            <div class="w-3.5 h-3.5 rounded-full bg-yellow-500/80 border border-yellow-500/50 shadow-[0_0_5px_rgba(234,179,8,0.5)]"></div>
                                            <div class="w-3.5 h-3.5 rounded-full bg-green-500/80 border border-green-500/50 shadow-[0_0_5px_rgba(34,197,94,0.5)]"></div>
                                        </div>
                                        <span class="text-[10px] font-bold text-zinc-600 uppercase tracking-widest bg-white/5 px-2 py-1 rounded">HTML</span>
                                    </div>
                                    <pre class="p-8 text-sm md:text-base text-zinc-300 overflow-x-auto font-mono leading-loose tracking-tight selection:bg-blue-500/30"><code><span class="text-pink-400">&lt;script</span>
  <span class="text-blue-400">src=</span><span class="text-green-400">"https://yoursaas.io/widget.js"</span>
  <span class="text-blue-400">data-tenant=</span><span class="text-green-400">"{{ $this->tenant->slug }}"</span>
  <span class="text-blue-400">data-theme=</span><span class="text-green-400">"{{ $this->tenant->primary_color }}"</span>
  <span class="text-purple-400">async</span><span class="text-pink-400">&gt;</span>
<span class="text-pink-400">&lt;/script&gt;</span></code></pre>
                                </div>
                                <button onclick="navigator.clipboard.writeText(document.querySelector('code').innerText); alert('Copied snippet!')" class="absolute top-20 right-6 text-xs font-bold uppercase tracking-wider bg-white/10 hover:bg-white/20 text-white backdrop-blur-md px-4 py-2.5 rounded-lg transition-all shadow-lg border border-white/10 hover:scale-105 active:scale-95">Copy Code</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            @if ($tab === 'leads')
                <div x-data x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" class="max-w-6xl">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h2 class="text-3xl font-extrabold tracking-tight text-white">Leads Database</h2>
                            <p class="text-sm text-zinc-400 mt-1">Manage and export leads captured by your AI Assistant.</p>
                        </div>
                        <x-ui.glow-button wire:click="createLead" variant="primary" class="h-10 px-4 text-sm">Add Lead</x-ui.glow-button>
                    </div>
                    
                    <div class="bg-[#121214] border border-white/10 rounded-3xl shadow-2xl overflow-hidden relative">
                        <div class="absolute top-0 inset-x-0 h-px bg-gradient-to-r from-transparent via-white/20 to-transparent"></div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-white/5">
                                <thead class="bg-[#09090b]">
                                    <tr>
                                        <th class="py-4 px-6 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-widest">Name</th>
                                        <th class="py-4 px-6 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-widest">Contact Info</th>
                                        <th class="py-4 px-6 text-left text-[10px] font-bold text-zinc-500 uppercase tracking-widest">Notes</th>
                                        <th class="py-4 px-6 text-right text-[10px] font-bold text-zinc-500 uppercase tracking-widest">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/5 bg-[#121214]">
                                    @forelse($this->leads as $lead)
                                        <tr class="hover:bg-white/[0.02] transition-colors group">
                                            <td class="py-4 px-6 text-sm text-white font-bold">{{ $lead->name }}</td>
                                            <td class="py-4 px-6 text-sm text-zinc-300">{{ $lead->email }}</td>
                                            <td class="py-4 px-6 text-sm text-zinc-500 truncate max-w-[200px]">{{ $lead->notes ?: '--' }}</td>
                                            <td class="py-4 px-6 text-right text-sm">
                                                <button wire:click="editLead('{{ $lead->id }}')" class="text-blue-400 hover:text-blue-300 font-semibold mr-3 transition-colors">Edit</button>
                                                <button wire:click="deleteLead('{{ $lead->id }}')" onclick="confirm('Delete this lead?') || event.stopImmediatePropagation()" class="text-red-500 hover:text-red-400 font-semibold transition-colors">Delete</button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="py-16 text-center text-zinc-500">No leads captured yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($this->leads->hasPages())
                        <div class="border-t border-white/5 px-6 py-4 bg-white/[0.01]">
                            {{ $this->leads->links() }}
                        </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>

    </main>

    <!-- Modal for Create/Edit Lead -->
    @if($isCreatingLead || $isEditingLead)
    <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto overflow-x-hidden bg-black/60 backdrop-blur-sm p-4">
        <div class="relative w-full max-w-lg rounded-3xl bg-[#121214] border border-white/10 shadow-2xl overflow-hidden" @click.away="$wire.resetLeadForm()">
            <div class="px-6 py-4 border-b border-white/5 flex items-center justify-between">
                <h3 class="text-lg font-bold text-white">{{ $isEditingLead ? 'Edit Lead' : 'New Lead' }}</h3>
                <button wire:click="resetLeadForm" class="text-zinc-500 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <form wire:submit="saveLead" class="p-6 space-y-5">
                <div>
                    <label class="block text-sm font-medium text-zinc-300 mb-1">Name</label>
                    <input wire:model="leadForm.name" type="text" class="w-full px-4 py-2.5 bg-black/50 border border-white/10 rounded-xl text-white placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50" placeholder="John Doe" required>
                    @error('leadForm.name') <span class="text-xs text-red-400 mt-1">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-300 mb-1">Email</label>
                    <input wire:model="leadForm.email" type="email" class="w-full px-4 py-2.5 bg-black/50 border border-white/10 rounded-xl text-white placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50" placeholder="john@example.com" required>
                    @error('leadForm.email') <span class="text-xs text-red-400 mt-1">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-300 mb-1">Notes</label>
                    <textarea wire:model="leadForm.notes" rows="4" class="w-full px-4 py-2.5 bg-black/50 border border-white/10 rounded-xl text-white placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50" placeholder="Any additional details..."></textarea>
                    @error('leadForm.notes') <span class="text-xs text-red-400 mt-1">{{ $message }}</span> @enderror
                </div>
                <div class="pt-4 flex justify-end gap-3 border-t border-white/5">
                    <button type="button" wire:click="resetLeadForm" class="px-5 py-2.5 rounded-xl border border-white/10 bg-transparent text-sm font-bold text-zinc-300 hover:text-white hover:bg-white/5 transition-colors">Cancel</button>
                    <x-ui.glow-button type="submit" variant="primary" class="px-5 py-2.5 text-sm">{{ $isEditingLead ? 'Save Changes' : 'Create Lead' }}</x-ui.glow-button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>

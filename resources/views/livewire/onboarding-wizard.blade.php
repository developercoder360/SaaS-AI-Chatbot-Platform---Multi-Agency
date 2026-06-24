<?php

use App\Models\Tenant;
use App\Models\User;
use App\Jobs\CreateStripeCustomerJob;
use App\Jobs\IngestKnowledgeJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use function Livewire\Volt\{state, rules, layout, updated};

layout('layouts.app');

state([
    'step' => 1,
    'agency_name' => '',
    'slug' => '',
    'email' => '',
    'password' => '',
    'password_confirmation' => '',
    'plan' => 'starter',
    'primary_color' => '#6366f1',
    'accent_color' => '#8b5cf6',
    'system_prompt' => '',
    'url_input' => '',
    'urls' => [],
    'tenant_id' => null,
]);

rules(function () {
    $rules = [];
    if ($this->step >= 1) {
        $rules = array_merge($rules, [
            'agency_name' => 'required|string|max:255',
            'slug' => 'required|string|max:50|unique:tenants,slug|alpha_dash',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);
    }
    if ($this->step >= 2) {
        $rules = array_merge($rules, [
            'plan' => 'required|in:starter,growth,pro',
        ]);
    }
    if ($this->step >= 3) {
        $rules = array_merge($rules, [
            'primary_color' => 'required|string|size:7',
            'accent_color' => 'required|string|size:7',
        ]);
    }
    if ($this->step >= 4) {
        $rules = array_merge($rules, [
            'system_prompt' => 'required|string',
        ]);
    }
    return $rules;
});

updated(['agency_name' => function () {
    if (empty($this->slug)) {
        $this->slug = Str::slug($this->agency_name);
    }
    if (empty($this->system_prompt)) {
        $this->system_prompt = "You are the AI assistant for {$this->agency_name}. Your role is to help website visitors understand our services and pricing, capture their contact details, and connect them with our team. Be professional, warm, and consultative.\n\n## SERVICES & PRICING\n[Configure your services and pricing below]\n\n## STRICT RULES\n- Never make up services or prices\n- Always try to collect name and email before ending a lead conversation";
    }
}]);

$nextStep = function () {
    $this->validate();

    if ($this->step === 5) {
        $this->registerTenant();
    }

    if ($this->step < 6) {
        $this->step++;
    }
};

$prevStep = function () {
    if ($this->step > 1) {
        $this->step--;
    }
};

$addUrl = function () {
    $this->validate(['url_input' => 'required|url']);
    $this->urls[] = $this->url_input;
    $this->url_input = '';
};

$removeUrl = function ($index) {
    unset($this->urls[$index]);
    $this->urls = array_values($this->urls);
};

$registerTenant = function () {
    DB::transaction(function () {
        $tenant = Tenant::create([
            'name' => $this->agency_name,
            'slug' => $this->slug,
            'plan' => $this->plan,
            'primary_color' => $this->primary_color,
            'accent_color' => $this->accent_color,
            'system_prompt' => $this->system_prompt,
            'status' => 'trial',
            'trial_ends_at' => now()->addDays(14),
        ]);

        User::create([
            'tenant_id' => $tenant->id,
            'name' => $this->agency_name . ' Admin',
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'role' => 'owner',
        ]);

        $this->tenant_id = $tenant->id;

        CreateStripeCustomerJob::dispatch($tenant);

        if (count($this->urls) > 0) {
            IngestKnowledgeJob::dispatch($tenant, $this->urls);
        }
    });
};

$finishOnboarding = function () {
    return redirect('/dashboard');
};

?>
<div class="min-h-screen flex font-sans bg-[#09090b] text-zinc-100 selection:bg-blue-500/30">

    <!-- Left Pane: Dynamic Marketing/Value Prop (Hidden on Mobile) -->
    <div
        class="hidden lg:flex lg:w-[45%] relative overflow-hidden bg-[#09090b] border-r border-white/5 flex-col justify-between p-12">
        <!-- Abstract gradient blobs -->
        <div class="absolute top-0 left-0 w-full h-full overflow-hidden -z-10 pointer-events-none">
            <div
                class="absolute -top-[20%] -left-[10%] w-[70%] h-[70%] rounded-full bg-blue-600/20 blur-[120px] mix-blend-screen animate-blob">
            </div>
            <div
                class="absolute bottom-[10%] right-[10%] w-[50%] h-[50%] rounded-full bg-indigo-600/20 blur-[100px] mix-blend-screen animate-blob animation-delay-2000">
            </div>
            <div
                class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/stardust.png')] opacity-20">
            </div>
        </div>

        <div>
            <div class="flex items-center gap-3 mb-16">
                <div
                    class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-lg shadow-blue-500/30">
                    <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <span class="text-2xl font-bold tracking-tight text-white">OkyAi</span>
            </div>

            <div class="space-y-8 relative z-10" x-data="{ currentStep: @entangle('step') }">
                <!-- Step 1 Content -->
                <div x-show="currentStep === 1" x-transition:enter="transition ease-out duration-700"
                    x-transition:enter-start="opacity-0 translate-y-8"
                    x-transition:enter-end="opacity-100 translate-y-0" style="display: none;">
                    <h2 class="text-5xl font-extrabold text-white tracking-tight leading-tight mb-6">
                        <x-ui.gradient-text>Start building</x-ui.gradient-text><br />your AI agency.
                    </h2>
                    <p class="text-lg text-zinc-400 max-w-md leading-relaxed">Join hundreds of agencies providing
                        intelligent, round-the-clock chatbots to their clients.</p>
                </div>

                <!-- Step 2 Content -->
                <div x-show="currentStep === 2" x-transition:enter="transition ease-out duration-700"
                    x-transition:enter-start="opacity-0 translate-y-8"
                    x-transition:enter-end="opacity-100 translate-y-0" style="display: none;">
                    <h2 class="text-5xl font-extrabold text-white tracking-tight leading-tight mb-6">
                        <x-ui.gradient-text>Scale</x-ui.gradient-text><br />effortlessly.
                    </h2>
                    <p class="text-lg text-zinc-400 max-w-md leading-relaxed">Choose a plan that fits your growth. All
                        plans include our core intelligence engine.</p>
                </div>

                <!-- Step 3 Content -->
                <div x-show="currentStep === 3" x-transition:enter="transition ease-out duration-700"
                    x-transition:enter-start="opacity-0 translate-y-8"
                    x-transition:enter-end="opacity-100 translate-y-0" style="display: none;">
                    <h2 class="text-5xl font-extrabold text-white tracking-tight leading-tight mb-6">
                        <x-ui.gradient-text>Make it</x-ui.gradient-text><br />yours.
                    </h2>
                    <p class="text-lg text-zinc-400 max-w-md leading-relaxed">Customize the chatbot to match your brand
                        perfectly. A seamless experience for your clients.</p>
                </div>

                <!-- Step 4 Content -->
                <div x-show="currentStep === 4" x-transition:enter="transition ease-out duration-700"
                    x-transition:enter-start="opacity-0 translate-y-8"
                    x-transition:enter-end="opacity-100 translate-y-0" style="display: none;">
                    <h2 class="text-5xl font-extrabold text-white tracking-tight leading-tight mb-6">
                        <x-ui.gradient-text>Train</x-ui.gradient-text><br />your AI.
                    </h2>
                    <p class="text-lg text-zinc-400 max-w-md leading-relaxed">Set the rules, tone, and guardrails for
                        your AI. It acts exactly how you instruct it to.</p>
                </div>

                <!-- Step 5 Content -->
                <div x-show="currentStep === 5" x-transition:enter="transition ease-out duration-700"
                    x-transition:enter-start="opacity-0 translate-y-8"
                    x-transition:enter-end="opacity-100 translate-y-0" style="display: none;">
                    <h2 class="text-5xl font-extrabold text-white tracking-tight leading-tight mb-6">
                        <x-ui.gradient-text>Feed it</x-ui.gradient-text><br />knowledge.
                    </h2>
                    <p class="text-lg text-zinc-400 max-w-md leading-relaxed">Simply paste URLs. Our system will crawl,
                        chunk, and embed the data instantly.</p>
                </div>

                <!-- Step 6 Content -->
                <div x-show="currentStep === 6" x-transition:enter="transition ease-out duration-700"
                    x-transition:enter-start="opacity-0 translate-y-8"
                    x-transition:enter-end="opacity-100 translate-y-0" style="display: none;">
                    <h2 class="text-5xl font-extrabold text-white tracking-tight leading-tight mb-6">
                        <x-ui.gradient-text>Ready to</x-ui.gradient-text><br />launch.
                    </h2>
                    <p class="text-lg text-zinc-400 max-w-md leading-relaxed">Your platform is configured. Copy the
                        embed code and start capturing leads immediately.</p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-4 text-sm text-zinc-500 relative z-10">
            <div class="flex -space-x-2">
                <img class="w-8 h-8 rounded-full border-2 border-[#09090b]" src="https://i.pravatar.cc/100?img=1"
                    alt="User">
                <img class="w-8 h-8 rounded-full border-2 border-[#09090b]" src="https://i.pravatar.cc/100?img=2"
                    alt="User">
                <img class="w-8 h-8 rounded-full border-2 border-[#09090b]" src="https://i.pravatar.cc/100?img=3"
                    alt="User">
            </div>
            <p>Trusted by 500+ premium agencies.</p>
        </div>
    </div>

    <!-- Right Pane: Form Area -->
    <div class="w-full lg:w-[55%] flex flex-col relative overflow-y-auto bg-[#09090b]">
        <!-- Subtle Grid Background -->
        <div
            class="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,0.02)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.02)_1px,transparent_1px)] bg-[size:40px_40px] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_50%,#000_70%,transparent_100%)] pointer-events-none">
        </div>

        <!-- Subtle Stepper -->
        <div class="px-8 pt-8 sm:px-16 sm:pt-12 max-w-2xl mx-auto w-full relative z-10">
            <div class="flex items-center gap-2">
                @for ($i = 1; $i <= 6; $i++)
                    <div
                        class="h-1.5 flex-1 rounded-full transition-all duration-500 {{ $step >= $i ? 'bg-gradient-to-r from-blue-600 to-indigo-600 shadow-[0_0_10px_rgba(59,130,246,0.5)]' : 'bg-white/5' }}">
                    </div>
                @endfor
            </div>
            <div class="mt-4 text-[10px] font-bold text-zinc-500 uppercase tracking-[0.2em]">
                Step {{ $step }} of 6
            </div>
        </div>

        <!-- Form Container -->
        <div class="flex-1 flex flex-col justify-center px-8 py-12 sm:px-16 max-w-2xl mx-auto w-full relative z-10" x-data="{ currentStep: $wire.entangle('step') }">

            <div class="relative w-full">
                <!-- Step 1 -->
                <div x-show="currentStep === 1" x-transition:enter="transition ease-out duration-500"
                    x-transition:enter-start="opacity-0 translate-x-8"
                    x-transition:enter-end="opacity-100 translate-x-0" style="display: none;">
                        <h3 class="text-3xl font-bold tracking-tight text-white mb-2">Create your account</h3>
                        <p class="text-sm text-zinc-400 mb-8">Enter your agency details to get started.</p>

                        <div class="space-y-6">
                            <div>
                                <label for="agency_name"
                                    class="block text-xs font-semibold uppercase tracking-wider text-zinc-400 mb-2">Agency
                                    Name</label>
                                <input wire:model.blur="agency_name" id="agency_name"
                                    type="text" placeholder="Acme Corp"
                                    class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-zinc-600 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-transparent transition-all shadow-inner focus-visible:ring-2">
                                @error('agency_name')
                                    <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>
                            <div>
                                <label for="slug"
                                    class="block text-xs font-semibold uppercase tracking-wider text-zinc-400 mb-2">Workspace
                                    Slug</label>
                                <div
                                    class="flex rounded-xl bg-white/5 border border-white/10 focus-within:ring-2 focus-within:ring-blue-500/50 focus-within:border-transparent transition-all shadow-inner overflow-hidden">
                                    <input wire:model.live.debounce.500ms="slug" id="slug" type="text" placeholder="acme"
                                        class="w-full bg-transparent border-0 px-4 py-3 text-white placeholder-zinc-600 focus:ring-0">
                                    <span
                                        class="flex items-center px-4 border-l border-white/5 text-zinc-500 bg-white/5 select-none text-sm">.okyai.io</span>
                                </div>
                                @error('slug')
                                    <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>
                            <div>
                                <label for="email"
                                    class="block text-xs font-semibold uppercase tracking-wider text-zinc-400 mb-2">Admin
                                    Email</label>
                                <input wire:model="email" id="email" type="email"
                                    placeholder="admin@acmecorp.com"
                                    class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-zinc-600 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-transparent transition-all shadow-inner">
                                @error('email')
                                    <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>
                            <div>
                                <label for="password"
                                    class="block text-xs font-semibold uppercase tracking-wider text-zinc-400 mb-2">Password</label>
                                <input wire:model="password" id="password" type="password"
                                    class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-zinc-600 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-transparent transition-all shadow-inner">
                                @error('password')
                                    <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>
                            <div>
                                <label for="password_confirmation"
                                    class="block text-xs font-semibold uppercase tracking-wider text-zinc-400 mb-2">Confirm
                                    Password</label>
                                <input wire:model="password_confirmation" id="password_confirmation" type="password"
                                    class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-zinc-600 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-transparent transition-all shadow-inner">
                            </div>
                        </div>
                    </div>

                <!-- Step 2 -->
                <div x-show="currentStep === 2" x-transition:enter="transition ease-out duration-500"
                    x-transition:enter-start="opacity-0 translate-x-8"
                    x-transition:enter-end="opacity-100 translate-x-0" style="display: none;">
                        <h3 class="text-3xl font-bold tracking-tight text-white mb-2">Select a plan</h3>
                        <p class="text-sm text-zinc-400 mb-8">You can upgrade or downgrade at any time.</p>

                        <div class="space-y-4">
                            <!-- Starter -->
                            <label
                                class="relative flex cursor-pointer rounded-2xl border p-5 focus:outline-none transition-all duration-300 {{ $plan === 'starter' ? 'border-blue-500/50 bg-blue-500/10 shadow-[0_0_20px_rgba(59,130,246,0.15)] ring-1 ring-blue-500/50' : 'border-white/10 hover:border-white/20 bg-white/5' }}">
                                <input type="radio" wire:model="plan" value="starter" class="sr-only">
                                <span class="flex flex-1">
                                    <span class="flex flex-col">
                                        <span class="block text-lg font-semibold text-white">Starter</span>
                                        <span class="mt-1 flex items-center text-sm text-zinc-400">Perfect for
                                            exploring the platform.</span>
                                        <span class="mt-4 text-xs font-medium text-zinc-500 space-y-1">
                                            <span class="block text-zinc-300">✓ 500 Conversations / mo</span>
                                            <span class="block">✓ 5 Source URLs</span>
                                        </span>
                                    </span>
                                </span>
                                <span class="text-xl font-bold text-white">$49<span
                                        class="text-sm font-normal text-zinc-500">/mo</span></span>
                            </label>

                            <!-- Growth -->
                            <label
                                class="relative flex cursor-pointer rounded-2xl border p-5 focus:outline-none transition-all duration-300 {{ $plan === 'growth' ? 'border-blue-500/50 bg-blue-500/10 shadow-[0_0_20px_rgba(59,130,246,0.15)] ring-1 ring-blue-500/50' : 'border-white/10 hover:border-white/20 bg-white/5' }}">
                                <input type="radio" wire:model="plan" value="growth" class="sr-only">
                                <span class="flex flex-1">
                                    <span class="flex flex-col">
                                        <span
                                            class="block text-lg font-semibold text-white flex items-center gap-3">Growth
                                            <span
                                                class="text-[9px] uppercase tracking-wider font-bold bg-blue-500 text-white px-2 py-0.5 rounded-full shadow-[0_0_10px_rgba(59,130,246,0.5)]">Popular</span></span>
                                        <span class="mt-1 flex items-center text-sm text-zinc-400">For scaling
                                            agencies.</span>
                                        <span class="mt-4 text-xs font-medium text-zinc-500 space-y-1">
                                            <span class="block text-zinc-300">✓ 2,000 Conversations / mo</span>
                                            <span class="block text-zinc-300">✓ 20 Source URLs</span>
                                            <span class="block text-zinc-300">✓ Custom Domain Mapping</span>
                                        </span>
                                    </span>
                                </span>
                                <span class="text-xl font-bold text-white">$99<span
                                        class="text-sm font-normal text-zinc-500">/mo</span></span>
                            </label>

                            <!-- Pro -->
                            <label
                                class="relative flex cursor-pointer rounded-2xl border p-5 focus:outline-none transition-all duration-300 {{ $plan === 'pro' ? 'border-blue-500/50 bg-blue-500/10 shadow-[0_0_20px_rgba(59,130,246,0.15)] ring-1 ring-blue-500/50' : 'border-white/10 hover:border-white/20 bg-white/5' }}">
                                <input type="radio" wire:model="plan" value="pro" class="sr-only">
                                <span class="flex flex-1">
                                    <span class="flex flex-col">
                                        <span class="block text-lg font-semibold text-white">Enterprise</span>
                                        <span class="mt-1 flex items-center text-sm text-zinc-400">No limits. Maximum
                                            power.</span>
                                        <span class="mt-4 text-xs font-medium text-zinc-500 space-y-1">
                                            <span class="block text-zinc-300">✓ Unlimited Conversations</span>
                                            <span class="block text-zinc-300">✓ Unlimited Source URLs</span>
                                            <span class="block text-zinc-300">✓ White-glove Onboarding</span>
                                        </span>
                                    </span>
                                </span>
                                <span class="text-xl font-bold text-white">$199<span
                                        class="text-sm font-normal text-zinc-500">/mo</span></span>
                            </label>
                        </div>
                    </div>

                <!-- Step 3 -->
                <div x-show="currentStep === 3" x-transition:enter="transition ease-out duration-500"
                    x-transition:enter-start="opacity-0 translate-x-8"
                    x-transition:enter-end="opacity-100 translate-x-0" style="display: none;">
                        <h3 class="text-3xl font-bold tracking-tight text-white mb-2">Chatbot branding</h3>
                        <p class="text-sm text-zinc-400 mb-8">Customize the look and feel of your AI assistant.</p>

                        <div class="space-y-6" x-data="{ primary: $wire.entangle('primary_color'), accent: $wire.entangle('accent_color') }">
                            <div class="grid grid-cols-2 gap-6">
                                <div>
                                    <label
                                        class="block text-xs font-semibold uppercase tracking-wider text-zinc-400 mb-2">Primary
                                        Color</label>
                                    <div class="flex items-center gap-3">
                                        <input type="color" x-model="primary" aria-label="Primary Color"
                                            class="h-12 w-12 p-0 border-0 rounded-xl cursor-pointer shrink-0 shadow-inner bg-transparent">
                                        <input x-model="primary" type="text"
                                            class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:ring-2 focus:ring-blue-500/50 focus:border-transparent font-mono text-sm shadow-inner">
                                    </div>
                                    <p class="text-[11px] text-zinc-500 mt-2">Used for headers and primary buttons.</p>
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-semibold uppercase tracking-wider text-zinc-400 mb-2">Accent
                                        Color</label>
                                    <div class="flex items-center gap-3">
                                        <input type="color" x-model="accent" aria-label="Accent Color"
                                            class="h-12 w-12 p-0 border-0 rounded-xl cursor-pointer shrink-0 shadow-inner bg-transparent">
                                        <input x-model="accent" type="text"
                                            class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:ring-2 focus:ring-blue-500/50 focus:border-transparent font-mono text-sm shadow-inner">
                                    </div>
                                    <p class="text-[11px] text-zinc-500 mt-2">Used for AI chat bubbles.</p>
                                </div>
                            </div>

                            <div
                                class="mt-8 p-6 rounded-3xl border border-white/5 bg-white/5 backdrop-blur-md relative overflow-hidden">
                                <div
                                    class="absolute -top-10 -right-10 w-40 h-40 bg-blue-500/10 blur-[50px] rounded-full pointer-events-none">
                                </div>
                                <p
                                    class="text-xs text-zinc-400 mb-4 uppercase tracking-widest font-semibold flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span> Live Preview
                                </p>
                                <div
                                    class="w-full bg-[#121214] rounded-2xl shadow-2xl border border-white/10 overflow-hidden transform transition-all hover:scale-[1.02]">
                                    <div class="p-4 text-white font-medium flex items-center justify-between transition-colors duration-200"
                                        :style="`background-color: ${primary}`">
                                        <div class="flex items-center gap-2">
                                            <div class="w-2 h-2 rounded-full bg-white animate-pulse"></div>
                                            {{ $agency_name ?: 'AI Assistant' }}
                                        </div>
                                        <svg class="w-5 h-5 opacity-70" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </div>
                                    <div class="p-5 space-y-5 h-48 bg-[#09090b]/50 relative">
                                        <div class="flex items-start gap-3 max-w-[85%]">
                                            <div
                                                class="w-8 h-8 rounded-full bg-white/10 flex-shrink-0 mt-1 flex items-center justify-center border border-white/5">
                                                <svg class="w-4 h-4 text-zinc-300" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                            <div
                                                class="bg-white/10 border border-white/5 text-zinc-200 rounded-2xl rounded-tl-sm p-3 text-sm shadow-sm backdrop-blur-sm">
                                                Hello! How can I help you today?
                                            </div>
                                        </div>
                                        <div class="flex flex-col items-end gap-1">
                                            <div class="text-white rounded-2xl rounded-tr-sm p-3 max-w-[85%] text-sm shadow-sm backdrop-blur-sm transition-colors duration-200"
                                                :style="`background-color: ${accent}`">
                                                I'd like to know more about your services.
                                            </div>
                                            <span class="text-[10px] text-zinc-500 mr-1">Just now</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                <!-- Step 4 -->
                <div x-show="currentStep === 4" x-transition:enter="transition ease-out duration-500"
                    x-transition:enter-start="opacity-0 translate-x-8"
                    x-transition:enter-end="opacity-100 translate-x-0" style="display: none;">
                        <h3 class="text-3xl font-bold tracking-tight text-white mb-2">System Instructions</h3>
                        <p class="text-sm text-zinc-400 mb-8">Define how the AI should behave and what it knows.</p>

                        <div class="relative group">
                            <div
                                class="absolute -inset-0.5 bg-gradient-to-r from-blue-500 to-purple-600 rounded-2xl blur opacity-20 group-hover:opacity-40 transition duration-1000 group-hover:duration-200">
                            </div>
                            <textarea wire:model="system_prompt" rows="12"
                                class="relative block w-full rounded-2xl border border-white/10 p-5 text-sm text-zinc-200 bg-[#121214] shadow-inner focus:ring-0 focus:border-white/20 font-mono resize-y"></textarea>
                            <div
                                class="absolute bottom-4 right-4 text-[10px] font-bold uppercase tracking-wider text-zinc-500 bg-white/5 px-2 py-1 rounded border border-white/5">
                                Markdown Supported</div>
                        </div>
                        @error('system_prompt') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                <!-- Step 5 -->
                <div x-show="currentStep === 5" x-transition:enter="transition ease-out duration-500"
                    x-transition:enter-start="opacity-0 translate-x-8"
                    x-transition:enter-end="opacity-100 translate-x-0" style="display: none;">
                        <h3 class="text-3xl font-bold tracking-tight text-white mb-2">Knowledge Base</h3>
                        <p class="text-sm text-zinc-400 mb-8">Add URLs for your website or documentation to train your
                            AI.</p>

                        <div class="flex gap-3 mb-6 items-center">
                            <div class="flex-1 relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-zinc-500" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                    </svg>
                                </div>
                                <input wire:model="url_input" wire:keydown.enter.prevent="addUrl" type="url"
                                    placeholder="https://example.com/pricing"
                                    class="w-full bg-white/5 border border-white/10 rounded-xl pl-11 pr-4 py-3 text-white placeholder-zinc-600 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-transparent transition-all shadow-inner">
                            </div>
                            <x-ui.glow-button type="button" wire:click="addUrl" variant="secondary"
                                class="h-12 px-6 shrink-0 text-sm">Add URL</x-ui.glow-button>
                        </div>
                        @error('url_input') <span class="text-red-400 text-xs mt-1 block mb-4 animate-pulse">{{ $message }}</span> @enderror

                        <div class="mt-4 min-h-[200px]">
                            @if (count($urls) > 0)
                                <ul
                                    class="divide-y divide-white/5 border border-white/10 rounded-2xl overflow-hidden bg-white/5 backdrop-blur-md shadow-inner">
                                    @foreach ($urls as $index => $url)
                                        <li
                                            class="flex items-center justify-between gap-x-6 py-4 px-6 hover:bg-white/5 transition-colors group">
                                            <div class="flex items-center gap-3 min-w-0">
                                                <div
                                                    class="w-8 h-8 rounded-full bg-blue-500/10 flex items-center justify-center shrink-0">
                                                    <svg class="w-4 h-4 text-blue-400" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                                    </svg>
                                                </div>
                                                <p class="text-sm font-medium text-zinc-200 truncate">
                                                    {{ $url }}</p>
                                            </div>
                                            <button wire:click="removeUrl({{ $index }})"
                                                class="text-zinc-500 hover:text-red-400 opacity-0 group-hover:opacity-100 transition-all p-2 rounded-lg hover:bg-red-500/10 border border-transparent hover:border-red-500/20">
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <div
                                    class="text-center py-16 bg-white/[0.02] border border-white/10 border-dashed rounded-3xl">
                                    <div
                                        class="mx-auto w-16 h-16 bg-white/5 border border-white/10 rounded-2xl flex items-center justify-center mb-4 shadow-inner">
                                        <svg class="w-8 h-8 text-zinc-600" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                        </svg>
                                    </div>
                                    <p class="text-base font-medium text-white mb-1">No sources added yet</p>
                                    <p class="text-sm text-zinc-500">Add links to your docs or site to train the bot.
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>

                <!-- Step 6 -->
                <div x-show="currentStep === 6" x-transition:enter="transition ease-out duration-1000"
                    x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                    class="text-center relative" style="display: none;">

                        <div
                            class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-80 h-80 bg-blue-500/20 blur-[100px] rounded-full -z-10 mix-blend-screen animate-pulse">
                        </div>

                        <div
                            class="mx-auto flex h-24 w-24 items-center justify-center rounded-full bg-gradient-to-br from-blue-400 to-indigo-600 text-white mb-8 shadow-[0_0_40px_rgba(59,130,246,0.5)] border border-white/20">
                            <svg class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke-width="2.5"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>

                        <h3 class="text-4xl font-extrabold tracking-tight text-white mb-4">You're All Set!</h3>
                        <p class="text-lg text-zinc-400 mb-12 max-w-md mx-auto">Your platform is ready. We are
                            processing your knowledge base URLs in the background.</p>

                        <div
                            class="bg-[#121214] rounded-3xl p-8 mb-8 text-left border border-white/10 shadow-2xl relative overflow-hidden group">
                            <div class="absolute inset-y-0 left-0 w-1 bg-gradient-to-b from-blue-400 to-purple-500">
                            </div>
                            <div class="flex items-center justify-between mb-6 pl-4">
                                <div>
                                    <h4 class="text-lg font-semibold text-white">Widget Embed Code</h4>
                                    <p class="text-sm text-zinc-500">Place this before the closing &lt;/body&gt; tag.
                                    </p>
                                </div>
                                <button
                                    onclick="navigator.clipboard.writeText(document.querySelector('code').innerText); alert('Copied to clipboard!')"
                                    class="text-xs font-bold uppercase tracking-wider text-white bg-white/10 hover:bg-white/20 border border-white/10 px-4 py-2 rounded-lg transition-all backdrop-blur-md">Copy
                                    Code</button>
                            </div>
                            <div class="relative pl-4">
                                <pre
                                    class="bg-[#09090b] text-zinc-300 p-5 rounded-2xl text-sm overflow-x-auto font-mono border border-white/5 shadow-inner"><code>&lt;script
  src="https://yoursaas.io/widget.js"
  data-tenant="{{ $slug }}"
  data-theme="{{ $primary_color }}"
  async&gt;
&lt;/script&gt;</code></pre>
                            </div>
                        </div>
                    </div>
            </div>

            <!-- Navigation Buttons -->
            <div class="mt-12 flex items-center justify-between border-t border-white/5 pt-8 z-20 relative">
                @if ($step > 1 && $step < 6)
                    <x-ui.glow-button wire:click="prevStep" variant="outline" class="px-8">Back</x-ui.glow-button>
                @else
                    <div></div>
                @endif

                @if ($step < 5)
                    <x-ui.glow-button wire:click="nextStep" variant="primary" class="px-10">Continue <span
                            aria-hidden="true" class="ml-2">&rarr;</span></x-ui.glow-button>
                @elseif($step === 5)
                    <x-ui.glow-button wire:click="nextStep" wire:target="nextStep" wire:loading.attr="disabled"
                        variant="primary" class="px-10">
                        <span wire:loading.remove wire:target="nextStep">Create Platform</span>
                        <span wire:loading wire:target="nextStep" class="flex items-center gap-2">
                            <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            Building...
                        </span>
                    </x-ui.glow-button>
                @elseif($step === 6)
                    <x-ui.glow-button wire:click="finishOnboarding" variant="primary"
                        class="w-full sm:w-auto px-12">Enter Dashboard <span aria-hidden="true"
                            class="ml-2">&rarr;</span></x-ui.glow-button>
                @endif
            </div>

        </div>
    </div>
</div>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    class="dark h-full bg-[#09090b] text-zinc-100 selection:bg-blue-500/30 selection:text-blue-100 scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OkyAi - Enterprise AI Chatbot Platform</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="h-full font-sans antialiased bg-[#09090b] text-zinc-100 overflow-x-hidden">

    <!-- Glowing Background Mesh -->
    <div class="fixed inset-0 z-0 pointer-events-none overflow-hidden">
        <div
            class="absolute -top-[40%] -left-[10%] w-[70%] h-[70%] rounded-full bg-blue-900/20 blur-[120px] mix-blend-screen animate-blob">
        </div>
        <div
            class="absolute top-[20%] -right-[20%] w-[60%] h-[60%] rounded-full bg-indigo-900/20 blur-[120px] mix-blend-screen animate-blob animation-delay-2000">
        </div>
        <div
            class="absolute -bottom-[20%] left-[20%] w-[80%] h-[80%] rounded-full bg-purple-900/20 blur-[150px] mix-blend-screen animate-blob animation-delay-4000">
        </div>
        <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/stardust.png')] opacity-20">
        </div>
    </div>

    <!-- Navbar -->
    <nav x-data="{ scrolled: false }" @scroll.window="scrolled = (window.pageYOffset > 20)"
        :class="{ 'bg-[#09090b]/80 backdrop-blur-xl border-b border-white/5 shadow-lg': scrolled, 'bg-transparent': !scrolled }"
        class="fixed w-full z-50 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <div class="flex items-center gap-3">
                    <div
                        class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center shadow-lg shadow-blue-500/20">
                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <span class="text-xl font-bold tracking-tight text-white">OkyAi</span>
                </div>
                <div class="hidden md:flex space-x-8">
                    <a href="#features"
                        class="text-sm font-medium text-zinc-400 hover:text-white transition-colors">Features</a>
                    <a href="#features"
                        class="text-sm font-medium text-zinc-400 hover:text-white transition-colors">Capabilities</a>
                    <a href="#pricing"
                        class="text-sm font-medium text-zinc-400 hover:text-white transition-colors">Pricing</a>
                    <a href="#pricing"
                        class="text-sm font-medium text-zinc-400 hover:text-white transition-colors">FAQ</a>
                </div>
                <div class="flex items-center gap-4">
                    @auth
                        <a href="{{ url('/dashboard') }}"
                            class="text-sm font-medium text-zinc-300 hover:text-white transition-colors">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}"
                            class="hidden md:block text-sm font-medium text-zinc-300 hover:text-white transition-colors">Sign
                            in</a>
                        <a href="{{ url('/onboarding') }}">
                            <x-ui.glow-button variant="primary" class="h-10 px-5 text-xs">Get Started <span
                                    aria-hidden="true">&rarr;</span></x-ui.glow-button>
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <main class="relative z-10 pt-32 pb-16">
        <!-- Hero Section -->
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-20 pb-32 text-center">
            <div
                class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/5 border border-white/10 mb-8 backdrop-blur-md">
                <span class="flex w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                <span class="text-xs font-medium text-zinc-300">OkyAi 2.0 is now live — Build smarter bots.</span>
            </div>

            <h1 class="text-5xl md:text-7xl font-extrabold tracking-tight mb-6 leading-tight">
                The Next Generation <br />
                <x-ui.gradient-text>AI Chatbot Platform</x-ui.gradient-text>
            </h1>

            <p class="mt-4 text-lg md:text-xl text-zinc-400 max-w-3xl mx-auto mb-10 leading-relaxed">
                Empower your agency with white-labeled, autonomous AI agents. Train them on your own data, deploy them
                across multiple channels, and watch your conversions soar.
            </p>

            <div class="flex flex-col sm:flex-row justify-center items-center gap-4">
                <a href="{{ url('/onboarding') }}">
                    <x-ui.glow-button variant="primary" class="h-12 px-8 text-base">Start Free Trial</x-ui.glow-button>
                </a>
                <a href="#features">
                    <x-ui.glow-button variant="secondary" class="h-12 px-8 text-base group">
                        <svg class="w-5 h-5 text-zinc-400 group-hover:text-white transition-colors" fill="currentColor"
                            viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"
                                clip-rule="evenodd" />
                        </svg>
                        View Demo
                    </x-ui.glow-button>
                </a>
            </div>

            <!-- Dashboard Mockup Float -->
            <div class="mt-20 relative max-w-5xl mx-auto animate-float">
                <div
                    class="absolute inset-0 bg-gradient-to-t from-[#09090b] via-transparent to-transparent z-10 bottom-0 top-1/2">
                </div>
                <div
                    class="rounded-2xl border border-white/10 bg-[#121214] shadow-2xl overflow-hidden shadow-blue-500/10">
                    <div class="h-8 border-b border-white/5 bg-[#18181b] flex items-center px-4 gap-2">
                        <div class="w-2.5 h-2.5 rounded-full bg-red-500/80"></div>
                        <div class="w-2.5 h-2.5 rounded-full bg-yellow-500/80"></div>
                        <div class="w-2.5 h-2.5 rounded-full bg-green-500/80"></div>
                    </div>
                    <img src="https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&fit=crop&w=2000&q=80"
                        alt="App Preview" class="w-full h-auto opacity-80 mix-blend-luminosity">
                </div>
            </div>
        </section>

        <!-- Features Grid -->
        <section id="features" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 border-t border-white/5">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold mb-4">Enterprise-grade capabilities out of the box</h2>
                <p class="text-zinc-400 max-w-2xl mx-auto">Everything you need to launch a world-class AI chatbot
                    service for your clients, without writing a single line of code.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <x-ui.glass-card hover="true">
                    <div
                        class="w-12 h-12 rounded-xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-white mb-3">Multi-Tenant Architecture</h3>
                    <p class="text-zinc-400 text-sm leading-relaxed">Perfect for agencies. Manage thousands of clients
                        from a single, high-performance super admin dashboard.</p>
                </x-ui.glass-card>

                <x-ui.glass-card hover="true">
                    <div
                        class="w-12 h-12 rounded-xl bg-purple-500/10 border border-purple-500/20 flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-white mb-3">Advanced NLP Models</h3>
                    <p class="text-zinc-400 text-sm leading-relaxed">Powered by state-of-the-art LLMs. The bots
                        understand context, sentiment, and complex multi-turn conversations.</p>
                </x-ui.glass-card>

                <x-ui.glass-card hover="true">
                    <div
                        class="w-12 h-12 rounded-xl bg-cyan-500/10 border border-cyan-500/20 flex items-center justify-center mb-6">
                        <svg class="w-6 h-6 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-white mb-3">Instant Knowledge Sync</h3>
                    <p class="text-zinc-400 text-sm leading-relaxed">Just paste a URL. Our vector engine automatically
                        crawls, chunks, and embeddings your data in seconds.</p>
                </x-ui.glass-card>
            </div>
        </section>

        <!-- Pricing Section -->
        <section id="pricing" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 border-t border-white/5 relative">
            <!-- Glow effect behind pricing -->
            <div
                class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[500px] bg-blue-600/10 rounded-full blur-[120px] pointer-events-none">
            </div>

            <div class="text-center mb-16 relative z-10">
                <h2 class="text-3xl font-bold mb-4">Simple, transparent pricing</h2>
                <p class="text-zinc-400 max-w-2xl mx-auto">Start for free, then scale as your agency grows.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-5xl mx-auto relative z-10">
                <!-- Starter -->
                <div
                    class="rounded-3xl bg-white/[0.02] border border-white/10 p-8 hover:bg-white/[0.04] transition-colors">
                    <h3 class="text-lg font-semibold text-white mb-2">Starter</h3>
                    <p class="text-sm text-zinc-400 mb-6">Perfect for small agencies.</p>
                    <div class="mb-6">
                        <span class="text-4xl font-bold text-white">$49</span><span class="text-zinc-400">/mo</span>
                    </div>
                    <ul class="space-y-4 mb-8 text-sm text-zinc-300">
                        <li class="flex items-center gap-3"><svg class="w-5 h-5 text-blue-500 shrink-0"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg> Up to 5 AI Bots</li>
                        <li class="flex items-center gap-3"><svg class="w-5 h-5 text-blue-500 shrink-0"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg> 10,000 Messages/mo</li>
                        <li class="flex items-center gap-3"><svg class="w-5 h-5 text-blue-500 shrink-0"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg> Standard Support</li>
                    </ul>
                    <x-ui.glow-button variant="secondary" class="w-full">Get Started</x-ui.glow-button>
                </div>

                <!-- Growth (Popular) -->
                <div
                    class="rounded-3xl bg-gradient-to-b from-blue-900/20 to-transparent border border-blue-500/30 p-8 relative transform scale-105 shadow-2xl shadow-blue-900/20">
                    <div
                        class="absolute top-0 inset-x-0 h-px bg-gradient-to-r from-transparent via-blue-400 to-transparent">
                    </div>
                    <div
                        class="absolute -top-4 left-1/2 -translate-x-1/2 px-3 py-1 bg-blue-500 text-white text-[10px] font-bold uppercase tracking-wider rounded-full shadow-lg">
                        Most Popular</div>
                    <h3 class="text-lg font-semibold text-white mb-2">Growth</h3>
                    <p class="text-sm text-blue-200/60 mb-6">Scale your operations.</p>
                    <div class="mb-6">
                        <span class="text-4xl font-bold text-white">$99</span><span
                            class="text-blue-200/60">/mo</span>
                    </div>
                    <ul class="space-y-4 mb-8 text-sm text-zinc-200">
                        <li class="flex items-center gap-3"><svg class="w-5 h-5 text-blue-400 shrink-0"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg> Up to 20 AI Bots</li>
                        <li class="flex items-center gap-3"><svg class="w-5 h-5 text-blue-400 shrink-0"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg> 50,000 Messages/mo</li>
                        <li class="flex items-center gap-3"><svg class="w-5 h-5 text-blue-400 shrink-0"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg> Priority Support</li>
                        <li class="flex items-center gap-3"><svg class="w-5 h-5 text-blue-400 shrink-0"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg> White-label Dashboard</li>
                    </ul>
                    <x-ui.glow-button variant="primary" class="w-full">Start Free Trial</x-ui.glow-button>
                </div>

                <!-- Pro -->
                <div
                    class="rounded-3xl bg-white/[0.02] border border-white/10 p-8 hover:bg-white/[0.04] transition-colors">
                    <h3 class="text-lg font-semibold text-white mb-2">Enterprise</h3>
                    <p class="text-sm text-zinc-400 mb-6">For large scale operations.</p>
                    <div class="mb-6">
                        <span class="text-4xl font-bold text-white">$199</span><span class="text-zinc-400">/mo</span>
                    </div>
                    <ul class="space-y-4 mb-8 text-sm text-zinc-300">
                        <li class="flex items-center gap-3"><svg class="w-5 h-5 text-blue-500 shrink-0"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg> Unlimited Bots</li>
                        <li class="flex items-center gap-3"><svg class="w-5 h-5 text-blue-500 shrink-0"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg> Unlimited Messages</li>
                        <li class="flex items-center gap-3"><svg class="w-5 h-5 text-blue-500 shrink-0"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg> 24/7 Dedicated Support</li>
                    </ul>
                    <x-ui.glow-button variant="secondary" class="w-full">Contact Sales</x-ui.glow-button>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="border-t border-white/5 bg-[#09090b] pt-16 pb-8 relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 mb-12">
                <div class="col-span-2 md:col-span-1">
                    <div class="flex items-center gap-2 mb-4">
                        <div
                            class="w-6 h-6 rounded-md bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <span class="text-lg font-bold text-white">OkyAi</span>
                    </div>
                    <p class="text-sm text-zinc-500 leading-relaxed">Building the future of autonomous conversational
                        agents for businesses worldwide.</p>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-white mb-4">Product</h4>
                    <ul class="space-y-2 text-sm text-zinc-500">
                        <li><a href="#features" class="hover:text-blue-400 transition-colors">Features</a></li>
                        <li><a href="#" onclick="event.preventDefault()" class="text-zinc-600 cursor-not-allowed">Integrations</a></li>
                        <li><a href="#pricing" class="hover:text-blue-400 transition-colors">Pricing</a></li>
                        <li><a href="#" onclick="event.preventDefault()" class="text-zinc-600 cursor-not-allowed">Changelog</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-white mb-4">Company</h4>
                    <ul class="space-y-2 text-sm text-zinc-500">
                        <li><a href="#" onclick="event.preventDefault()" class="text-zinc-600 cursor-not-allowed">About Us</a></li>
                        <li><a href="#" onclick="event.preventDefault()" class="text-zinc-600 cursor-not-allowed">Careers</a></li>
                        <li><a href="#" onclick="event.preventDefault()" class="text-zinc-600 cursor-not-allowed">Blog</a></li>
                        <li><a href="#" onclick="event.preventDefault()" class="text-zinc-600 cursor-not-allowed">Contact</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-white mb-4">Legal</h4>
                    <ul class="space-y-2 text-sm text-zinc-500">
                        <li><a href="#" onclick="event.preventDefault()" class="text-zinc-600 cursor-not-allowed">Privacy Policy</a></li>
                        <li><a href="#" onclick="event.preventDefault()" class="text-zinc-600 cursor-not-allowed">Terms of Service</a></li>
                    </ul>
                </div>
            </div>
            <div class="pt-8 border-t border-white/5 text-center text-sm text-zinc-600">
                &copy; {{ date('Y') }} OkyAi Inc. All rights reserved.
            </div>
        </div>
    </footer>

</body>

</html>

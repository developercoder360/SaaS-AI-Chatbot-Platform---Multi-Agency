<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark h-full bg-[#09090b] text-zinc-100 selection:bg-blue-500/30 selection:text-blue-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'SaaS Chatbot Platform') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased bg-[#09090b] text-zinc-100 selection:bg-blue-500/30">
    <div class="min-h-full flex flex-col">
        <main class="flex-1 flex flex-col">
            {{ $slot }}
        </main>
    </div>
</body>
</html>

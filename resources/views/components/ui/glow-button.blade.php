@props(['variant' => 'primary', 'type' => 'button'])

@php
    $baseClasses = 'relative inline-flex items-center justify-center px-6 py-3 font-semibold text-sm transition-all duration-300 rounded-xl overflow-hidden group';
    
    $variants = [
        'primary' => 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white hover:from-blue-500 hover:to-indigo-500 animate-glow shadow-lg shadow-blue-500/30',
        'secondary' => 'bg-white/10 text-white hover:bg-white/20 border border-white/10 backdrop-blur-md',
        'outline' => 'bg-transparent border border-blue-500/50 text-blue-400 hover:bg-blue-500/10 hover:border-blue-400 hover:text-blue-300'
    ];
@endphp

<button type="{{ $type }}" {{ $attributes->merge(['class' => $baseClasses . ' ' . $variants[$variant]]) }}>
    @if($variant === 'primary')
        <div class="absolute inset-0 bg-white/20 opacity-0 group-hover:opacity-100 transition-opacity"></div>
    @endif
    <span class="relative z-10 flex items-center gap-2">{{ $slot }}</span>
</button>

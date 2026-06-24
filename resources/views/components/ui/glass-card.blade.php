@props(['hover' => false])

<div {{ $attributes->merge(['class' => 'glass-card rounded-2xl p-6 relative overflow-hidden transition-all duration-300 ' . ($hover ? 'hover:-translate-y-1 hover:shadow-[0_8px_30px_rgb(0,0,0,0.12)] hover:border-white/20 hover:bg-white/10' : '')]) }}>
    {{ $slot }}
</div>

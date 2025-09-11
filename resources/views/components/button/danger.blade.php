@props([
  'href',
])

@php
  $base = 'inline-flex items-center justify-center gap-2 relative aria-pressed:z-10 font-medium whitespace-nowrap disabled:pointer-events-none disabled:cursor-default disabled:opacity-75 dark:disabled:opacity-75 h-8 rounded-lg text-sm [:where(&)]:px-3 transition duration-150 ease-out active:scale-[0.97] active:translate-y-[1px] active:shadow-inner active:ease-in transform-gpu focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500/40';
  $style = 'bg-red-600 hover:bg-red-500 text-white border border-red-600/90 border-b-red-700 shadow-xs dark:bg-red-500 dark:hover:bg-red-400 dark:text-white dark:border-red-500 dark:border-b-red-600';
  $classes = $base . ' ' . $style;
@endphp

@isset($href)
  <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
  <button type="submit" {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
  </button>
@endif

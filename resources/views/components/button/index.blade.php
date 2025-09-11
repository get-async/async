@props([
  'href',
])

@isset($href)
  <a href="{{ $href }}" {{ $attributes->merge(['class' => 'inline-flex items-center justify-center gap-2 relative aria-pressed:z-10 cursor-pointer font-medium whitespace-nowrap disabled:pointer-events-none disabled:cursor-default disabled:opacity-75 dark:disabled:opacity-75 h-8 rounded-lg text-sm [:where(&)]:px-3 bg-[var(--color-accent)] hover:bg-[color-mix(in_oklab,_var(--color-accent),_transparent_10%)] text-[var(--color-accent-foreground)] border border-black/10 dark:border-0 shadow-[inset_0px_1px_--theme(--color-white/.2)] transition-[transform,box-shadow] duration-150 active:scale-[0.97] active:shadow-[inset_0_2px_4px_0_rgba(0,0,0,0.35),inset_0_0_0_1px_rgba(0,0,0,0.25)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-accent)]/50']) }}>
    {{ $slot }}
  </a>
@else
  <button type="submit" {{ $attributes->merge(['class' => 'inline-flex items-center justify-center gap-2 relative aria-pressed:z-10 font-medium cursor-pointer whitespace-nowrap disabled:pointer-events-none disabled:cursor-default disabled:opacity-75 dark:disabled:opacity-75 h-8 rounded-lg text-sm [:where(&)]:px-3 bg-[var(--color-accent)] hover:bg-[color-mix(in_oklab,_var(--color-accent),_transparent_10%)] text-[var(--color-accent-foreground)] border border-black/10 dark:border-0 shadow-[inset_0px_1px_--theme(--color-white/.2)] transition-[transform,box-shadow] duration-150 active:scale-[0.97] active:shadow-[inset_0_2px_4px_0_rgba(0,0,0,0.35),inset_0_0_0_1px_rgba(0,0,0,0.25)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-accent)]/50']) }}>
    {{ $slot }}
  </button>
@endif

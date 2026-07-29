@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-semibold text-sm text-gray-700 dark:text-slate-300']) }}>
    {{ $value ?? $slot }}
</label>

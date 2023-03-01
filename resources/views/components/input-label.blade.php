@props(['value'])
@props(['required'])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-gray-700 dark:text-gray-300']) }}>
    {{ $value ?? $slot }}
    @isset($required)
        <span class='text-red-500 font-bold dark:font-normal'>*</span>
    @endisset
</label>

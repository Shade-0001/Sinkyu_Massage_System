@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'form-control border-secondary focus:border-primary focus:ring-primary rounded shadow-sm']) }}>

@props(['value'])

<label {{ $attributes->merge(['class' => 'form-label fw-medium fs-6 text-secondary']) }}>
  {{ $value ?? $slot }}
</label>

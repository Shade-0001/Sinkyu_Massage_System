<button {{ $attributes->merge(['type' => 'submit', 'class' => 'd-inline-flex align-items-center px-3 py-2 bg-primary border border-0 rounded fs-6 text-white text-uppercase fw-semibold hover:bg-primary-subtle active:bg-primary focus:outline-none focus:ring focus:ring-primary focus:ring-offset-2 transition ease-in-out duration-150']) }}>
  {{ $slot }}
</button>

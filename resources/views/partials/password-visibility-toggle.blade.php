{{--
  Reusable password visibility toggle for password inputs.
  Usage:
  @include('partials.password-visibility-toggle', ['targetId' => 'password'])
--}}
<button type="button" class="absolute inset-y-0 right-3 flex items-center text-text-secondary hover:text-text-primary"
    data-password-toggle data-target-id="{{ $targetId }}" aria-label="Tampilkan sembunyikan kata sandi">
    <svg data-password-toggle-icon xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20"
        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
        aria-hidden="true">
        <path id="eye-open" d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" />
        <circle id="eye-open-circle" cx="12" cy="12" r="3" />
        <path id="eye-closed" d="M3 3l18 18" style="display:none" />
    </svg>
</button>

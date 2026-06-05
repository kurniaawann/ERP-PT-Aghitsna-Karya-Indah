{{--
  Reusable loading submit button.

  Usage:
  @include('partials.loading-submit-button', [
      'id' => 'loginBtn',
      'textId' => 'loginBtnText',
      'spinnerId' => 'loginBtnSpinner',
      'buttonText' => 'Masuk',
      'buttonType' => 'submit',
      'buttonClass' => 'w-full bg-primary ...'
  ])

  Requirements:
  Place this partial inside a <form>.
  The script will bind submit handler to the closest form.
--}}

@php
    $buttonType = $buttonType ?? 'submit';
    $buttonClass =
        $buttonClass ??
        'w-full bg-primary text-white font-medium py-2 rounded-lg hover:bg-primary-hover transition-all';
    $id = $id ?? 'submitBtn';
    $textId = $textId ?? $id . 'Text';
    $spinnerId = $spinnerId ?? $id . 'Spinner';
    $buttonText = $buttonText ?? 'Simpan';
@endphp

<button type="{{ $buttonType }}" id="{{ $id }}"
    class="{{ $buttonClass }} inline-flex items-center justify-center gap-2">

    <span id="{{ $textId }}">{{ $buttonText }}</span>
    <svg id="{{ $spinnerId }}" class="hidden w-4 h-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none"
        viewBox="0 0 24 24" aria-hidden="true">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
    </svg>
</button>

<script>
    (function() {
        const btn = document.getElementById(@json($id));
        if (!btn) return;

        const text = document.getElementById(@json($textId));
        const spinner = document.getElementById(@json($spinnerId));
        if (!text || !spinner) return;

        const form = btn.closest('form');
        if (!form) return;

        // Avoid double-binding when partial rendered multiple times.
        if (btn.dataset.loadingBound === '1') return;
        btn.dataset.loadingBound = '1';

        form.addEventListener('submit', function() {
            btn.disabled = true;
            text.classList.add('hidden');
            spinner.classList.remove('hidden');
        });
    })();
</script>

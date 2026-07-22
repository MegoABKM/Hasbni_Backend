@php
    $currentLocale = session()->get('locale', app()->getLocale());
    $targetLocale = $currentLocale === 'ar' ? 'en' : 'ar';
@endphp

<a
    href="{{ url('switch-language/' . $targetLocale) }}"
    class="me-4 flex h-10 w-10 items-center justify-center rounded-full border border-gray-200 bg-gray-50/50 transition-colors hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800/50 dark:hover:bg-white/5"
    title="{{ $currentLocale === 'ar' ? __('Switch to English') : __('Switch to Arabic') }}"
    aria-label="{{ $currentLocale === 'ar' ? __('Switch to English') : __('Switch to Arabic') }}"
>
    <span class="font-bold text-sm text-primary-600 dark:text-primary-400">
        {{ $currentLocale === 'ar' ? 'EN' : 'AR' }}
    </span>
</a>

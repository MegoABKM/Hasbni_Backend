@php
    $currentLocale = session()->get('locale', app()->getLocale());
    $targetLocale = $currentLocale === 'ar' ? 'en' : 'ar';
@endphp

<a href="{{ url('switch-language/' . $targetLocale) }}" 
   class="me-4 flex items-center justify-center w-10 h-10 rounded-full border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50 transition-colors hover:bg-gray-100 dark:hover:bg-white/5"
   title="{{ $currentLocale === 'ar' ? 'Switch to English' : 'تغيير للغة العربية' }}">
    <span class="font-bold text-sm text-primary-600 dark:text-primary-400">
        {{ $currentLocale === 'ar' ? 'EN' : 'AR' }}
    </span>
</a>
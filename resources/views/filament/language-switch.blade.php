<!-- resources/views/filament/language-switch.blade.php -->
@php
    // جلب اللغة الحالية (سواء من السشن أو الافتراضية)
    $currentLocale = session()->get('locale', app()->getLocale());
    $targetLocale = $currentLocale === 'ar' ? 'en' : 'ar';
@endphp

<a href="{{ url('switch-language/' . $targetLocale) }}" 
   style="margin-inline-end: 1rem; display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 50%; background-color: rgba(128, 128, 128, 0.1); text-decoration: none; border: 1px solid rgba(128,128,128,0.2);"
   title="تغيير اللغة">
    <span style="font-weight: bold; font-size: 14px; color: #a1a1aa;">
        {{ $currentLocale === 'ar' ? 'EN' : 'AR' }}
    </span>
</a>
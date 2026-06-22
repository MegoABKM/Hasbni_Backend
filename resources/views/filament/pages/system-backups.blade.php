<x-filament-panels::page>
    <div class="flex justify-between items-center mb-4">
        <div>
            <h3 class="text-lg font-bold text-gray-400">حماية البيانات والنسخ الاحتياطي لقاعدة البيانات الرئيسية</h3>
            <p class="text-sm text-gray-500">ينصح بأخذ نسخة احتياطية بشكل دوري وتحميلها خارج السيرفر لحماية بيانات متاجرك.</p>
        </div>
        <x-filament::button wire:click="generateBackup" color="success" icon="heroicon-o-plus-circle" size="lg">
            Create Full Backup Now (إنشاء نسخة جديدة)
        </x-filament::button>
    </div>

    <x-filament::card>
        @if(empty($backupFiles))
            <div class="text-center py-12">
                <x-filament::icon alias="panels::pages.dashboard.stats" icon="heroicon-o-circle-stack" class="mx-auto h-12 w-12 text-gray-400" />
                <h4 class="mt-4 text-lg font-medium text-gray-400">لا توجد أي نسخ احتياطية محفوظة حالياً.</h4>
                <p class="mt-2 text-sm text-gray-500">اضغط على زر الإنشاء بالأعلى لأخذ أول نسخة احتياطية كاملة.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-right border-collapse">
                    <thead>
                        <tr class="border-b border-gray-700 bg-gray-900 text-gray-300">
                            <th class="p-3 text-sm font-semibold">تاريخ الإنشاء</th>
                            <th class="p-3 text-sm font-semibold">اسم الملف</th>
                            <th class="p-3 text-sm font-semibold">الحجم</th>
                            <th class="p-3 text-sm font-semibold text-center">التحكم</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($backupFiles as $file)
                            <tr class="border-b border-gray-800 hover:bg-gray-800/50 transition duration-150">
                                <td class="p-3 text-sm text-gray-400 font-mono">{{ $file['created_at'] }}</td>
                                <td class="p-3 text-sm font-semibold text-primary-500 font-mono">{{ $file['name'] }}</td>
                                <td class="p-3 text-sm text-gray-400 font-mono">{{ $file['size'] }}</td>
                                <td class="p-3 text-sm text-center">
                                    <div class="flex justify-center gap-3">
                                        <x-filament::button 
                                            wire:click="downloadBackup('{{ $file['name'] }}')" 
                                            color="info" 
                                            size="sm" 
                                            icon="heroicon-m-arrow-down-tray">
                                            تحميل
                                        </x-filament::button>

                                        <x-filament::button 
                                            wire:click="deleteBackup('{{ $file['name'] }}')" 
                                            color="danger" 
                                            size="sm" 
                                            icon="heroicon-m-trash"
                                            requires-confirmation>
                                            حذف
                                        </x-filament::button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::card>
</x-filament-panels::page>
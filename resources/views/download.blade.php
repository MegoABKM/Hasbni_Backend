<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تحميل نظام حاسبني POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Tajawal', sans-serif; background-color: #f8fafc; }
    </style>
</head>
<body class="antialiased text-gray-800">

    <!-- Header -->
    <div class="bg-teal-600 text-white text-center py-12 shadow-lg">
        <h1 class="text-4xl font-black mb-4">نظام حاسبني المكتبي (POS)</h1>
        <p class="text-lg text-teal-100">قم بتحميل وتثبيت النظام على جهازك بخطوتين فقط</p>
    </div>

    <div class="max-w-4xl mx-auto mt-10 px-4 pb-20">
        
        <!-- Step 1: Certificate -->
        <div class="bg-white rounded-2xl shadow-md p-8 mb-8 border border-gray-100">
            <div class="flex items-center mb-4">
                <div class="bg-orange-100 text-orange-600 font-bold w-10 h-10 rounded-full flex items-center justify-center text-xl ml-4">1</div>
                <h2 class="text-2xl font-bold text-gray-800">تحميل وتثبيت شهادة الأمان</h2>
            </div>
            <p class="text-gray-600 mb-6 leading-relaxed">لأن النظام يتم تحميله من سيرفراتنا الخاصة، يرجى تحميل شهادة الأمان أولاً ليتمكن ويندوز من الوثوق بالتطبيق.</p>
            
            <div class="bg-gray-50 p-6 rounded-lg mb-6 text-sm text-gray-700 border border-gray-200">
                <p class="font-bold mb-4 text-lg">طريقة التثبيت خطوة بخطوة:</p>
                <ol class="list-decimal list-inside space-y-3">
                    <li>افتح الملف المحمل <b>hasbni.pfx</b>.</li>
                    <li>اختر <b>Local Machine</b> (الجهاز المحلي) ثم اضغط Next (قد يطلب صلاحية مسؤول، وافق عليها).</li>
                    <!-- 🚨 التعديل هنا: إضافة كلمة المرور بشكل بارز جداً 🚨 -->
                    <li>عندما يطلب منك كلمة المرور، أدخل الرقم <b class="bg-red-100 text-red-700 px-3 py-1 rounded text-lg mx-1 shadow-sm select-all">123456</b> ثم اضغط Next.</li>
                    <li>اختر <b>Place all certificates in the following store</b> واضغط Browse.</li>
                    <li>من القائمة اختر <b>Trusted Root Certification Authorities</b> ثم OK ثم Next ثم Finish.</li>
                    <li>ستظهر لك رسالة تفيد بنجاح التثبيت (The import was successful).</li>
                </ol>
            </div>

            <a href="/get-cert" download class="inline-block bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 px-6 rounded-lg transition duration-300 shadow-md">
                📥 تحميل شهادة الأمان (Certificate)
            </a>
        </div>

        <!-- Step 2: The App -->
        <div class="bg-white rounded-2xl shadow-md p-8 border border-gray-100">
            <div class="flex items-center mb-4">
                <div class="bg-teal-100 text-teal-600 font-bold w-10 h-10 rounded-full flex items-center justify-center text-xl ml-4">2</div>
                <h2 class="text-2xl font-bold text-gray-800">تحميل وتثبيت التطبيق</h2>
            </div>
            <p class="text-gray-600 mb-6 leading-relaxed">بعد تثبيت شهادة الأمان بنجاح، قم بتحميل التطبيق وتثبيته. سيقوم التطبيق بتحديث نفسه تلقائياً في المستقبل ولن تحتاج لتكرار هذه الخطوات.</p>

            <a href="/get-app" download class="inline-block bg-teal-600 hover:bg-teal-700 text-white font-bold py-4 px-8 rounded-lg text-lg shadow-md transition duration-300">
                💻 تحميل نظام حاسبني (نسخة الويندوز)
            </a>
            
            <p class="mt-4 text-xs text-gray-400">الإصدار: 1.0.0 | يعمل على Windows 10 و Windows 11</p>
        </div>

    </div>

</body>
</html>
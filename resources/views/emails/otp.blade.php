<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إعادة تعيين كلمة المرور</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; text-align: center;">
    <div style="max-width: 500px; margin: 0 auto; background: #ffffff; padding: 30px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
        <h2 style="color: #333333;">إعادة تعيين كلمة المرور</h2>
        <p style="color: #666666; font-size: 16px;">لقد طلبت إعادة تعيين كلمة المرور الخاصة بحسابك. يرجى استخدام رمز التحقق (OTP) التالي:</p>
        <div style="margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 8px; font-size: 28px; font-weight: bold; letter-spacing: 5px; color: #0d9488;">
            {{ $otp }}
        </div>
        <p style="color: #999999; font-size: 12px;">هذا الرمز صالح لمدة 15 دقيقة فقط. إذا لم تطلب هذا، يرجى تجاهل هذه الرسالة.</p>
        <hr style="border: none; border-top: 1px solid #eeeeee; margin: 20px 0;">
        <p style="color: #bbbbbb; font-size: 12px;">&copy; {{ date('Y') }} Hasbni App. جميع الحقوق محفوظة.</p>
    </div>
</body>
</html>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تحميل Hasbni POS</title>
    <style>
        :root {
            color-scheme: light;
            --primary: #0f766e;
            --primary-dark: #115e59;
            --surface: #ffffff;
            --muted: #64748b;
            --border: #dbe3ea;
            --background: #f7fafc;
            --warning-bg: #fff7ed;
            --warning-border: #fed7aa;
            --warning-text: #9a3412;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--background);
            color: #0f172a;
            font-family: Tahoma, Arial, sans-serif;
            line-height: 1.7;
        }

        .hero {
            background: var(--primary);
            color: white;
            padding: 48px 20px;
            text-align: center;
        }

        .hero h1 {
            margin: 0;
            font-size: clamp(28px, 5vw, 44px);
            font-weight: 800;
        }

        .hero p {
            margin: 12px auto 0;
            max-width: 680px;
            color: #ccfbf1;
            font-size: 17px;
        }

        .container {
            width: min(100%, 880px);
            margin: 0 auto;
            padding: 32px 16px 64px;
        }

        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
        }

        .card + .card {
            margin-top: 16px;
        }

        .eyebrow {
            margin: 0 0 6px;
            color: var(--primary);
            font-size: 14px;
            font-weight: 700;
        }

        h2 {
            margin: 0 0 12px;
            font-size: 24px;
            line-height: 1.3;
        }

        p {
            margin: 0;
            color: var(--muted);
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 24px;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 48px;
            border-radius: 8px;
            padding: 12px 18px;
            background: var(--primary);
            color: white;
            font-weight: 700;
            text-decoration: none;
            transition: background 150ms ease, box-shadow 150ms ease;
        }

        .button:hover,
        .button:focus {
            background: var(--primary-dark);
            box-shadow: 0 0 0 4px rgba(15, 118, 110, 0.18);
            outline: none;
        }

        .notice {
            margin-top: 18px;
            border: 1px solid var(--warning-border);
            border-radius: 8px;
            background: var(--warning-bg);
            padding: 14px 16px;
            color: var(--warning-text);
            font-size: 14px;
        }

        .meta {
            margin-top: 12px;
            color: #94a3b8;
            font-size: 13px;
        }

        @media (max-width: 520px) {
            .hero {
                padding: 36px 16px;
            }

            .card {
                padding: 18px;
            }

            .button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <header class="hero">
        <h1>Hasbni POS</h1>
        <p>تحميل نسخة ويندوز الرسمية لنظام نقاط البيع.</p>
    </header>

    <main class="container">
        <section class="card" aria-labelledby="download-title">
            <p class="eyebrow">Windows installer</p>
            <h2 id="download-title">تحميل التطبيق</h2>
            <p>
                استخدم هذا الرابط لتحميل حزمة التثبيت الرسمية. إذا ظهرت رسالة ثقة أو شهادة أثناء التثبيت،
                تواصل مع فريق الدعم للحصول على تعليمات التثبيت المعتمدة.
            </p>

            <div class="actions">
                <a href="/get-app" download class="button">تحميل Hasbni لنظام ويندوز</a>
            </div>

            <div class="notice">
                لا يتم نشر ملفات الشهادات الخاصة أو مفاتيح التوقيع عبر صفحة عامة.
            </div>

            <p class="meta">الإصدار: 1.0.0 | يدعم Windows 10 و Windows 11</p>
        </section>
    </main>
</body>
</html>

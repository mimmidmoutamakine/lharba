<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'الهربة') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="auth-shell text-slate-900 antialiased">
        <div class="auth-shell__noise" aria-hidden="true"></div>

        <main class="auth-shell__content">
            <section class="auth-card auth-card--brand">
                <div class="auth-brand-badge">منصة تدريب ألمانية</div>
                <a href="{{ url('/') }}" class="auth-brand-logo" aria-label="الهربة">
                    <img src="{{ asset('images/lharba-logo.svg') }}" alt="الهربة">
                </a>
                <!-- <h1 class="auth-brand-title">الهربة</h1> -->
                <p class="auth-brand-text">
                    تمرّن بوضوح، تابع تقدّمك، وادخل مباشرة للنماذج اللي فعلاً تحتاجها.
                </p>

                <div class="auth-brand-points">
                    <div class="auth-brand-point">
                        <span class="auth-brand-point__dot"></span>
                        <span>تدريب سريع ومنظم</span>
                    </div>
                    <div class="auth-brand-point">
                        <span class="auth-brand-point__dot"></span>
                        <span>محاكاة + مراجعة الأخطاء</span>
                    </div>
                    <div class="auth-brand-point">
                        <span class="auth-brand-point__dot"></span>
                        <span>خطة واضحة للوصول للإتقان</span>
                    </div>
                </div>
            </section>

            <section class="auth-card auth-card--form">
                {{ $slot }}
            </section>
        </main>
    </body>
</html>

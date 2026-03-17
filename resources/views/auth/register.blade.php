<x-guest-layout>
    <div class="auth-form-head">
        <div>
            <p class="auth-form-kicker">حساب جديد</p>
            <h2 class="auth-form-title">ابدأ معنا منظم</h2>
            <p class="auth-form-text">افتح حسابك باش تدير setup وتمشي مباشرة للتدريب المناسب لك.</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="auth-alert auth-alert--error">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}" class="auth-form-grid">
        @csrf

        <div>
            <label for="name" class="auth-label">الاسم</label>
            <input id="name" class="auth-input" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" placeholder="اسمك الكامل" />
        </div>

        <div>
            <label for="email" class="auth-label">البريد الإلكتروني</label>
            <input id="email" class="auth-input" type="email" name="email" value="{{ old('email') }}" required autocomplete="username" placeholder="name@example.com" />
        </div>

        <div>
            <label for="password" class="auth-label">كلمة المرور</label>
            <input id="password" class="auth-input" type="password" name="password" required autocomplete="new-password" placeholder="8 حروف على الأقل" />
        </div>

        <div>
            <label for="password_confirmation" class="auth-label">تأكيد كلمة المرور</label>
            <input id="password_confirmation" class="auth-input" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="أعد كتابة كلمة المرور" />
        </div>

        <button type="submit" class="auth-button auth-button--primary">إنشاء الحساب</button>
    </form>

    <div class="auth-footer-links">
        <span>عندك حساب من قبل؟</span>
        <a href="{{ route('login') }}">سجّل الدخول</a>
    </div>
</x-guest-layout>

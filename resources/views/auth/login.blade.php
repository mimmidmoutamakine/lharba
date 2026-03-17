<x-guest-layout>
    <div class="auth-form-head">
        <div>
            <p class="auth-form-kicker">تسجيل الدخول</p>
            <h2 class="auth-form-title">مرحبا بعودتك</h2>
            <p class="auth-form-text">دخل لحسابك وكمّل التدريب منين وقفتي.</p>
        </div>
    </div>

    @if (session('status'))
        <div class="auth-alert auth-alert--success">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="auth-alert auth-alert--error">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="auth-form-grid">
        @csrf

        <div>
            <label for="email" class="auth-label">البريد الإلكتروني</label>
            <input id="email" class="auth-input" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="name@example.com" />
        </div>

        <div>
            <div class="auth-label-row">
                <label for="password" class="auth-label">كلمة المرور</label>
                @if (Route::has('password.request'))
                    <a class="auth-inline-link" href="{{ route('password.request') }}">نسيت كلمة المرور؟</a>
                @endif
            </div>
            <input id="password" class="auth-input" type="password" name="password" required autocomplete="current-password" placeholder="••••••••" />
        </div>

        <label class="auth-check">
            <input id="remember_me" type="checkbox" name="remember">
            <span>خليه متذكّرني</span>
        </label>

        <button type="submit" class="auth-button auth-button--primary">دخول</button>
    </form>

    <div class="auth-footer-links">
        <span>ما عندكش حساب؟</span>
        <a href="{{ route('register') }}">فتح حساب جديد</a>
    </div>
</x-guest-layout>

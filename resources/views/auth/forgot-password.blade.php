<x-guest-layout>
    <div class="auth-form-head">
        <div>
            <p class="auth-form-kicker">استرجاع الحساب</p>
            <h2 class="auth-form-title">نسيت كلمة المرور؟</h2>
            <p class="auth-form-text">دخل البريد الإلكتروني ديالك وغنصيفطو لك رابط إعادة التعيين.</p>
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

    <form method="POST" action="{{ route('password.email') }}" class="auth-form-grid">
        @csrf

        <div>
            <label for="email" class="auth-label">البريد الإلكتروني</label>
            <input id="email" class="auth-input" type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="name@example.com" />
        </div>

        <button type="submit" class="auth-button auth-button--primary">إرسال رابط الاسترجاع</button>
    </form>

    <div class="auth-footer-links">
        <span>تذكّرت كلمة المرور؟</span>
        <a href="{{ route('login') }}">العودة لتسجيل الدخول</a>
    </div>
</x-guest-layout>

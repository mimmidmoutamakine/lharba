@php
    $user = auth()->user();
    $showStudentLinks = $user && ! $user->isAdmin() && ! $user->needsOnboarding();
@endphp

<nav x-data="{ open: false }" class="app-nav">
    <div class="app-nav-inner">
        <div class="flex min-h-[4.5rem] items-center justify-between gap-4">
            <div class="flex items-center gap-4 sm:gap-8">
                <a href="{{ route('dashboard') }}" class="app-logo-lockup" aria-label="{{ config('app.name', 'الهربة') }}">
                    <img src="{{ asset('images/lharba-logo.svg') }}" alt="{{ config('app.name', 'الهربة') }}" class="app-logo-image">
                </a>

                <div class="hidden items-center gap-6 sm:flex">
                    @if ($showStudentLinks)
                        <x-nav-link :href="route('hub.home')" :active="request()->routeIs('dashboard', 'hub.home')">
                            <span class="inline-flex items-center gap-2">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M3 10.5 12 3l9 7.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M5.25 9.75V20h13.5V9.75" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <span>الرئيسية</span>
                            </span>
                        </x-nav-link>

                        <x-nav-link :href="route('training.index')" :active="request()->routeIs('training.*', 'attempts.*', 'exams.*')">
                            <span class="inline-flex items-center gap-2">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M12 2a10 10 0 1 0 0 20A10 10 0 0 0 12 2Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                    <path d="M10 8l6 4-6 4V8Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" fill="currentColor" opacity=".25"/>
                                    <path d="M10 8l6 4-6 4V8Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                </svg>
                                <span>تدريب</span>
                            </span>
                        </x-nav-link>

                        <x-nav-link :href="route('hub.lessons')" :active="request()->routeIs('hub.lessons')">
                            <span class="inline-flex items-center gap-2">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M5 6.5A2.5 2.5 0 0 1 7.5 4H20v14H7.5A2.5 2.5 0 0 0 5 20.5v-14Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                    <path d="M5 6.5A2.5 2.5 0 0 1 7.5 4H20v14H7.5A2.5 2.5 0 0 0 5 20.5m0-14v14m0 0H18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <span>دروس</span>
                            </span>
                        </x-nav-link>

                        <x-nav-link :href="route('hub.packages')" :active="request()->routeIs('hub.packages')">
                            <span class="inline-flex items-center gap-2">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M12 3l8 4.5v9L12 21 4 16.5v-9L12 3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                    <path d="M12 12 20 7.5M12 12 4 7.5M12 12v9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <span>باقاتنا</span>
                            </span>
                        </x-nav-link>

                        <x-nav-link :href="route('hub.news')" :active="request()->routeIs('hub.news')">
                            <span class="inline-flex items-center gap-2">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M5 6h11a3 3 0 0 1 3 3v8H8a3 3 0 0 1-3-3V6Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                    <path d="M8 18a3 3 0 0 0 3 3h8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                    <path d="M8.5 10h7M8.5 13h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                </svg>
                                <span>أخبار</span>
                            </span>
                        </x-nav-link>
                    @endif

                    @if ($user?->isAdmin())
                        <x-nav-link :href="route('admin.exams.index')" :active="request()->routeIs('admin.*')">
                            <span class="inline-flex items-center gap-2">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M12 8.5a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7Z" stroke="currentColor" stroke-width="1.8"/>
                                    <path d="M19.4 15a1 1 0 0 0 .2 1.1l.1.1a2 2 0 0 1 0 2.8 2 2 0 0 1-2.8 0l-.1-.1a1 1 0 0 0-1.1-.2 1 1 0 0 0-.6.9V20a2 2 0 0 1-4 0v-.2a1 1 0 0 0-.6-.9 1 1 0 0 0-1.1.2l-.1.1a2 2 0 0 1-2.8 0 2 2 0 0 1 0-2.8l.1-.1a1 1 0 0 0 .2-1.1 1 1 0 0 0-.9-.6H4a2 2 0 0 1 0-4h.2a1 1 0 0 0 .9-.6 1 1 0 0 0-.2-1.1l-.1-.1a2 2 0 0 1 0-2.8 2 2 0 0 1 2.8 0l.1.1a1 1 0 0 0 1.1.2 1 1 0 0 0 .6-.9V4a2 2 0 0 1 4 0v.2a1 1 0 0 0 .6.9 1 1 0 0 0 1.1-.2l.1-.1a2 2 0 0 1 2.8 0 2 2 0 0 1 0 2.8l-.1.1a1 1 0 0 0-.2 1.1 1 1 0 0 0 .9.6H20a2 2 0 0 1 0 4h-.2a1 1 0 0 0-.9.6Z" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <span>الإدارة</span>
                            </span>
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <div class="hidden items-center gap-3 sm:flex">
                <!-- Dark mode toggle (desktop) -->
                <button
                    id="darkModeToggleDesktop"
                    onclick="window.toggleDarkMode()"
                    class="dark-mode-toggle-btn"
                    title="تبديل الوضع الليلي"
                    aria-label="تبديل الوضع الليلي"
                >
                    <svg id="darkIconDesktop" class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <svg id="lightIconDesktop" class="h-4 w-4 hidden" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="1.8"/>
                        <path d="M12 2v2M12 20v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M2 12h2M20 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                </button>

                <x-dropdown align="right" width="56">
                    <x-slot name="trigger">
                        <button class="app-user-button">
                            <span class="app-user-icon" aria-hidden="true">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                                    <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" stroke="currentColor" stroke-width="1.8"/>
                                    <path d="M5 20a7 7 0 0 1 14 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                </svg>
                            </span>
                            <span class="text-sm font-semibold text-slate-900">{{ Auth::user()->name }}</span>
                            <svg class="h-4 w-4 text-slate-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.51a.75.75 0 01-1.08 0l-4.25-4.51a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="px-4 py-3 text-xs uppercase tracking-[0.2em] text-slate-400">
                            {{ Auth::user()->email }}
                        </div>
                        @if ($showStudentLinks)
                            <x-dropdown-link :href="route('hub.home')">
                                الرئيسية
                            </x-dropdown-link>
                            <x-dropdown-link :href="route('training.index')">
                                تدريب
                            </x-dropdown-link>
                            <div class="my-1 border-t border-slate-100 dark:border-white/10"></div>
                        @endif
                        <x-dropdown-link :href="route('profile.edit')">
                            تعديل الملف الشخصي
                        </x-dropdown-link>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                                تسجيل الخروج
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="flex items-center sm:hidden">
                <button @click="open = ! open" class="app-nav-hamburger-btn rounded-2xl border border-black/10 bg-white/80 p-2 text-slate-700 transition hover:bg-white hover:text-slate-950">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="app-nav-mobile-panel hidden border-t border-black/5 bg-white/90 sm:hidden">
        <div class="app-nav-inner space-y-3 py-4">
            @if ($showStudentLinks)
                <x-responsive-nav-link :href="route('hub.home')" :active="request()->routeIs('dashboard', 'hub.home')">
                    الرئيسية
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('training.index')" :active="request()->routeIs('training.*', 'attempts.*', 'exams.*')">
                    تدريب
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('hub.lessons')" :active="request()->routeIs('hub.lessons')">
                    دروس
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('hub.packages')" :active="request()->routeIs('hub.packages')">
                    باقاتنا
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('hub.news')" :active="request()->routeIs('hub.news')">
                    أخبار
                </x-responsive-nav-link>
            @endif

            @if ($user?->isAdmin())
                <x-responsive-nav-link :href="route('admin.exams.index')" :active="request()->routeIs('admin.*')">
                    الإدارة
                </x-responsive-nav-link>
            @endif

            <div class="rounded-2xl border border-black/5 bg-white dark:border-white/10 dark:bg-slate-800 px-4 py-3 flex items-center justify-between">
                <div>
                    <div class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ Auth::user()->name }}</div>
                    <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ Auth::user()->email }}</div>
                </div>
                <!-- Dark mode toggle (mobile) -->
                <button
                    id="darkModeToggleMobile"
                    onclick="window.toggleDarkMode()"
                    class="dark-mode-toggle-btn"
                    title="تبديل الوضع الليلي"
                    aria-label="تبديل الوضع الليلي"
                >
                    <svg id="darkIconMobile" class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <svg id="lightIconMobile" class="h-4 w-4 hidden" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="1.8"/>
                        <path d="M12 2v2M12 20v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M2 12h2M20 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>

            <div class="space-y-2">
                <x-responsive-nav-link :href="route('profile.edit')">
                    تعديل الملف الشخصي
                </x-responsive-nav-link>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                        تسجيل الخروج
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>

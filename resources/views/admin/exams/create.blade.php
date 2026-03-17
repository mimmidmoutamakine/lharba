<x-admin.layouts.app>
    <h2 class="text-2xl font-bold">Create Exam</h2>

    <form method="POST" action="{{ route('admin.exams.store') }}" class="mt-6 rounded-lg bg-white p-6 shadow">
        @include('admin.exams._form', [
            'exam' => null,
            'method' => 'POST',
            'button' => 'Create Exam',
        ])
    </form>
</x-admin.layouts.app>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $item->title }} - Print</title>
    <style>
        @page { margin: 18mm; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #111827;
            background: #ffffff;
            margin: 0;
            line-height: 1.45;
            font-size: 14px;
        }
        .no-print {
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
        }
        .meta {
            color: #6b7280;
            font-size: 12px;
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
        }
        .actions {
            display: flex;
            gap: 10px;
        }
        .btn {
            display: inline-block;
            border: 1px solid #cbd5e1;
            border-radius: 999px;
            padding: 10px 16px;
            color: #111827;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            background: #fff;
        }
        .btn-primary {
            background: #111827;
            color: #fff;
            border-color: #111827;
        }
        h1 {
            margin: 0 0 8px;
            font-size: 28px;
        }
        h2 {
            margin: 24px 0 12px;
            font-size: 18px;
        }
        .intro {
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 18px 20px;
            margin-bottom: 22px;
            background: #fafafa;
        }
        .instructions {
            margin: 10px 0 0;
            white-space: pre-line;
        }
        .option-list {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px 20px;
            margin-bottom: 26px;
        }
        .option-item {
            display: flex;
            gap: 10px;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 10px 12px;
            break-inside: avoid;
        }
        .option-key {
            font-weight: 700;
            min-width: 22px;
        }
        .text-card {
            border: 1px solid #d1d5db;
            border-radius: 14px;
            padding: 14px 16px;
            margin-bottom: 16px;
            break-inside: avoid;
        }
        .text-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            gap: 16px;
        }
        .text-label {
            font-size: 18px;
            font-weight: 700;
        }
        .answer-box {
            min-width: 160px;
            border: 1px dashed #9ca3af;
            border-radius: 10px;
            padding: 8px 12px;
            text-align: center;
            color: #6b7280;
            font-size: 13px;
            font-weight: 600;
        }
        .answer-box.correct {
            border-style: solid;
            border-color: #16a34a;
            background: #f0fdf4;
            color: #166534;
        }
        .answer-grid {
            margin-top: 26px;
            width: 100%;
            border-collapse: collapse;
        }
        .answer-grid th,
        .answer-grid td {
            border: 1px solid #d1d5db;
            padding: 10px 12px;
            text-align: center;
        }
        .answer-grid th {
            background: #f8fafc;
        }
        .footer-note {
            margin-top: 18px;
            color: #6b7280;
            font-size: 12px;
        }
        @media print {
            .no-print { display: none !important; }
            body { font-size: 12px; }
            .intro { background: #fff; }
            .text-card, .option-item { break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <div>
            <h1>{{ $item->title }}</h1>
            <div class="meta">
                <span>Level: {{ strtoupper((string) $item->level) }}</span>
                <span>Section: Lesen</span>
                <span>Part: {{ $item->part_title }}</span>
                <span>Points: {{ $item->points }}</span>
            </div>
        </div>
        <div class="actions">
            <a href="{{ route('admin.part-bank.print', ['item' => $item, 'answers' => $showAnswers ? 0 : 1]) }}" class="btn">{{ $showAnswers ? 'Hide answers' : 'Show answers' }}</a>
            <button type="button" class="btn btn-primary" onclick="window.print()">Print</button>
        </div>
    </div>

    <div class="intro">
        <strong>{{ $item->part_title }} - Leseverstehen</strong>
        <p class="instructions">{{ $item->instruction_text }}</p>
    </div>

    <h2>Uberschriften</h2>
    <div class="option-list">
        @foreach ($options as $option)
            <div class="option-item">
                <div class="option-key">{{ $option['option_key'] ?? '?' }}.</div>
                <div>{{ $option['option_text'] ?? '' }}</div>
            </div>
        @endforeach
    </div>

    <h2>Texte</h2>
    @foreach ($texts as $text)
        @php
            $answer = $answers->get((string) ($text['label'] ?? ''));
            $correctKey = $answer['option_key'] ?? null;
            $correctOption = $correctKey ? $options->firstWhere('option_key', $correctKey) : null;
        @endphp
        <div class="text-card">
            <div class="text-header">
                <div class="text-label">Text {{ $text['label'] ?? '?' }}</div>
                <div class="answer-box {{ $showAnswers && $correctOption ? 'correct' : '' }}">
                    @if ($showAnswers && $correctOption)
                        {{ $correctOption['option_key'] }}. {{ $correctOption['option_text'] }}
                    @else
                        Antwortfeld
                    @endif
                </div>
            </div>
            <div>{{ $text['body_text'] ?? '' }}</div>
        </div>
    @endforeach

    <h2>Antwortbogen</h2>
    <table class="answer-grid">
        <thead>
            <tr>
                @foreach ($texts as $text)
                    <th>{{ $text['label'] ?? '?' }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            <tr>
                @foreach ($texts as $text)
                    @php
                        $answer = $answers->get((string) ($text['label'] ?? ''));
                    @endphp
                    <td>{{ $showAnswers ? ($answer['option_key'] ?? '') : '' }}</td>
                @endforeach
            </tr>
        </tbody>
    </table>

    <div class="footer-note">
        {{ $showAnswers ? 'Korrekturversion mit Losungen.' : 'Schulerversion ohne Losungen.' }}
    </div>
</body>
</html>

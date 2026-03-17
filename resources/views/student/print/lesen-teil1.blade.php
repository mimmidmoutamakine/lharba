<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $item->part_title ?? 'Teil 1' }} - {{ $item->title }}</title>
    <style>
        body {
            margin: 0;
            padding: 32px;
            font-family: Arial, sans-serif;
            background: #f5f6f8;
            color: #111827;
        }
        .page {
            max-width: 1080px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #d9dee8;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.08);
        }
        .header {
            padding: 28px 32px 20px;
            border-bottom: 1px solid #e5e7eb;
            background: linear-gradient(135deg, #fff8dd, #ffffff);
        }
        .meta {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: center;
            flex-wrap: wrap;
        }
        .meta h1 {
            margin: 0;
            font-size: 30px;
        }
        .meta p {
            margin: 8px 0 0;
            color: #6b7280;
            font-size: 14px;
        }
        .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 16px;
            border-radius: 999px;
            border: 1px solid #d1d5db;
            color: #111827;
            text-decoration: none;
            background: #fff;
            font-size: 14px;
            font-weight: 700;
        }
        .button-primary {
            background: #111827;
            border-color: #111827;
            color: #fff;
        }
        .content {
            padding: 24px 32px 32px;
        }
        .instruction-box {
            margin-bottom: 24px;
            padding: 16px 18px;
            border-radius: 18px;
            background: #fffbe8;
            border: 1px solid #fde68a;
            line-height: 1.6;
        }
        .options-list {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 24px;
        }
        .option-card {
            border: 1px solid #d8def0;
            background: #eef0ff;
            border-radius: 16px;
            padding: 12px 14px;
            font-size: 15px;
        }
        .text-card {
            border: 1px solid #d9dee8;
            border-radius: 22px;
            margin-bottom: 18px;
            overflow: hidden;
        }
        .text-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            padding: 14px 18px;
            background: #e9edff;
            border-bottom: 1px solid #d7ddff;
        }
        .text-label {
            font-weight: 800;
            font-size: 18px;
        }
        .answer-box {
            min-width: 240px;
            padding: 10px 14px;
            border-radius: 999px;
            border: 1px dashed #9ca3af;
            color: #6b7280;
            background: #fff;
            font-size: 14px;
            text-align: center;
        }
        .answer-box.answer-box-filled {
            border-style: solid;
            border-color: #16a34a;
            color: #166534;
            background: #dcfce7;
            font-weight: 700;
        }
        .text-body {
            padding: 18px;
            line-height: 1.7;
            font-size: 15px;
            white-space: pre-line;
        }
        .answer-grid {
            margin-top: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #d9dee8;
            padding: 10px 12px;
            text-align: left;
        }
        th {
            background: #f8fafc;
        }
        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .page {
                box-shadow: none;
                border: 0;
                border-radius: 0;
                max-width: none;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="header">
            <div class="meta">
                <div>
                    <h1>{{ $item->part_title ?? 'Teil 1' }} - {{ $item->title }}</h1>
                    <p>{{ $item->level }} · {{ $item->points }} Punkte</p>
                </div>
                <div class="actions no-print">
                    <button type="button" class="button button-primary" onclick="window.print()">Print</button>
                    @if ($showAnswers)
                        <a href="{{ route('training.models.print', $item) }}" class="button">إزالة الأجوبة</a>
                    @else
                        <a href="{{ route('training.models.print', ['model' => $item->id, 'answers' => 1]) }}" class="button">الأجوبة</a>
                    @endif
                </div>
            </div>
        </div>

        <div class="content">
            <div class="instruction-box">
                {{ $item->instruction_text ?: 'Lesen Sie die Überschriften a-j und die Texte 1-5 und entscheiden Sie, welche Überschrift am besten zu welchem Text passt.' }}
            </div>

            <div class="options-list">
                @foreach ($options as $option)
                    <div class="option-card">
                        <strong>{{ $option['option_key'] ?? '?' }}.</strong>
                        {{ $option['option_text'] ?? '' }}
                    </div>
                @endforeach
            </div>

            @foreach ($texts as $text)
                @php
                    $label = (string) ($text['label'] ?? '');
                    $answer = $answers->get($label);
                    $answerText = null;

                    if ($answer) {
                        $matchingOption = $options->firstWhere('id', $answer['correct_option_id'] ?? null);
                        $answerText = $matchingOption
                            ? (($matchingOption['option_key'] ?? '').'. '.($matchingOption['option_text'] ?? ''))
                            : ($answer['correct_option_label'] ?? null);
                    }
                @endphp

                <div class="text-card">
                    <div class="text-header">
                        <div class="text-label">Text {{ $label }}</div>
                        <div class="answer-box {{ $showAnswers && $answerText ? 'answer-box-filled' : '' }}">
                            {{ $showAnswers && $answerText ? $answerText : '........................................' }}
                        </div>
                    </div>
                    <div class="text-body">{{ $text['body_text'] ?? '' }}</div>
                </div>
            @endforeach

            <div class="answer-grid">
                <h2>Answer Grid</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Text</th>
                            <th>Your Answer</th>
                            @if ($showAnswers)
                                <th>Correct</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($texts as $text)
                            @php
                                $label = (string) ($text['label'] ?? '');
                                $answer = $answers->get($label);
                                $matchingOption = $answer
                                    ? $options->firstWhere('id', $answer['correct_option_id'] ?? null)
                                    : null;
                                $correctValue = $matchingOption
                                    ? (($matchingOption['option_key'] ?? '').'. '.($matchingOption['option_text'] ?? ''))
                                    : '';
                            @endphp
                            <tr>
                                <td>Text {{ $label }}</td>
                                <td>________________________</td>
                                @if ($showAnswers)
                                    <td>{{ $correctValue }}</td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>

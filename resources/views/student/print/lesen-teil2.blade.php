<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>{{ $item->title ?? 'Lesen Teil 2' }}</title>
    <style>
        @page {
            size: A4;
            margin: 10mm;
        }

        * {
            box-sizing: border-box;
        }

        html, body {
            margin: 0;
            padding: 0;
            background: #f5f5f5;
            color: #0f172a;
            font-family: Arial, Helvetica, sans-serif;
        }

        body {
            font-size: 14px;
            line-height: 1.45;
        }

        .screen-toolbar {
            display: flex;
            justify-content: flex-end;
            width: 100%;
            max-width: 1100px;
            margin: 10px auto 0;
            padding: 0 8px;
        }

        .print-btn {
            appearance: none;
            border: 1px solid #0f172a;
            background: #0f172a;
            color: #fff;
            font-size: 14px;
            font-weight: 700;
            border-radius: 999px;
            padding: 10px 18px;
            cursor: pointer;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.12);
        }

        .print-btn:hover {
            opacity: 0.96;
        }

        .sheet {
            width: 100%;
            max-width: 1100px;
            margin: 14px auto 28px;
            background: #fdfbf2;
            border: 1px solid #d9dde7;
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.07);
        }

        .page {
            padding: 26px 30px 30px;
        }

        .page + .page {
            border-top: 1px solid #e5e7eb;
        }

        .hero {
            display: flex;
            align-items: center;
            gap: 18px;
            margin-bottom: 20px;
        }

        .hero-left {
            flex: 0 0 74px;
        }

        .hero-left img {
            display: block;
            width: 58px;
            height: auto;
        }

        .hero-right {
            flex: 1;
            border: 4px solid #111;
            min-height: 96px;
            display: flex;
            align-items: center;
            padding: 18px 22px;
            background: #fff;
        }

        .hero-title {
            margin: 0;
            font-size: 19px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.2px;
        }

        .instruction {
            margin: 0 0 16px;
            font-size: 14px;
            background: #fff9e8;
            border: 1px solid #efd56a;
            border-radius: 18px;
            padding: 14px 18px;
        }

        .passage-box {
            background: #fff;
            border: 1px solid #d8deea;
            border-radius: 22px;
            padding: 18px 20px;
            overflow: hidden;
        }

        .passage-text {
            font-size: 12pt;
            line-height: 1.2;
            color: #111827;
            white-space: pre-line;
        }

        .page-title {
            margin: 0 0 16px;
            font-size: 17px;
            font-weight: 800;
            color: #0f172a;
        }

        .question-block {
            margin-bottom: 16px;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .question-line {
            font-size: 18px;
            font-weight: 800;
            margin-bottom: 8px;
            color: #0f172a;
        }

        .question-line .question-text {
            font-weight: 400;
        }

        .options-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            overflow: hidden;
            border: 1px solid #d5daf0;
            border-radius: 18px;
            background: #fff;
        }

        .options-table tr + tr td {
            border-top: 1px solid #d9dfef;
        }

        .options-table td {
            padding: 12px 14px;
            vertical-align: top;
        }

        .option-key {
            width: 54px;
            text-align: center;
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            background: #eef1ff;
            border-right: 1px solid #d9dfef;
        }

        .option-text {
            font-size: 16px;
            color: #111827;
        }

        .correct-answer td {
            background: #f6f7fb;
        }

        .answer-mark {
            font-weight: 800;
            margin-left: 8px;
        }

        @media print {
            html, body {
                background: #fff;
            }

            .screen-toolbar {
                display: none !important;
            }

            .sheet {
                max-width: none;
                margin: 0;
                border: none;
                border-radius: 0;
                box-shadow: none;
                background: #fff;
            }

            .page {
                padding: 0;
            }

            .page + .page {
                border-top: none;
                page-break-before: always;
            }

            .hero-right {
                background: #fff;
            }

            .instruction,
            .passage-box,
            .options-table {
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="screen-toolbar">
        <button type="button" class="print-btn" onclick="window.print()">Print</button>
    </div>

    <div class="sheet">
        @php
            $baseNumber = 6;
        @endphp

        {{-- PAGE 1: HEADER + TEXT --}}
        <div class="page">
            <div class="hero">
                <div class="hero-left">
                    <img src="{{ asset('images/hub/ninja_reading.png') }}" alt="Ninja Reading">
                </div>

                <div class="hero-right">
                    <p class="hero-title">
                        {{ $passage['title'] ?? $item->title ?? 'Lesen Teil 2' }}
                    </p>
                </div>
            </div>

            <p class="instruction">
                {{ $item->instruction_text ?? 'Lesen Sie den Text und die Aufgaben. Entscheiden Sie anhand des Textes, welche Lösung richtig ist.' }}
            </p>

            <div class="passage-box">
                <div class="passage-text">{{ $passage['body_text'] ?? '' }}</div>
            </div>
        </div>

        {{-- PAGE 2: QUESTIONS --}}
        <div class="page">
            <p class="page-title">Aufgaben</p>

            @foreach($questions as $index => $question)
                @php
                    $number = $baseNumber + $index;
                    $options = $question['options'] ?? [];
                @endphp

                <div class="question-block">
                    <div class="question-line">
                        ({{ $number }})
                        <span class="question-text">{{ $question['question_text'] ?? '' }}</span>
                    </div>

                    <table class="options-table">
                        <tbody>
                            @foreach($options as $option)
                                <tr class="{{ $showAnswers && !empty($option['is_correct']) ? 'correct-answer' : '' }}">
                                    <td class="option-key">{{ $option['option_key'] ?? '' }}</td>
                                    <td class="option-text">
                                        {{ $option['option_text'] ?? '' }}

                                        @if($showAnswers && !empty($option['is_correct']))
                                            <span class="answer-mark">✓</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach
        </div>
    </div>
</body>
</html>
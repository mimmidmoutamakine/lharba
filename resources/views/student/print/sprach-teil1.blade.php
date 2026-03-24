<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>{{ $item->title ?? 'Sprachbausteine Teil 1' }}</title>
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
            color: #111827;
            font-family: Arial, Helvetica, sans-serif;
        }

        body {
            font-size: 14px;
            line-height: 1.42;
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

        .sheet {
            width: 100%;
            max-width: 1100px;
            margin: 14px auto 28px;
            background: #fffdf5;
            border: 1px solid #d9dde7;
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.07);
        }

        .page {
            padding: 26px 30px 30px;
        }

        .instruction {
            margin: 0 0 16px;
            font-size: 14px;
            line-height: 1.45;
        }

        .letter-box {
            border: 4px solid #111;
            background: #fff;
            padding: 12px 16px;
        }

        .letter-text {
            font-size: 12.5pt;
            line-height: 1.32;
            white-space: pre-line;
        }

        .gap {
            display: inline-block;
            min-width: 72px;
            text-align: center;
            color: #1d4ed8;
            font-weight: 800;
            border-bottom: 2px solid #1d4ed8;
            line-height: 1.1;
            padding: 0 4px 1px;
            margin: 0 2px;
        }

        .gap.has-answer {
            color: #047857;
            border-bottom-color: #047857;
        }

        .options-box {
            border: 1px solid #bfc7d8;
            background: #fff;
            padding: 14px 16px;
        }

        .options-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px 28px;
        }

        .option-group {
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .option-number {
            font-size: 18px;
            font-weight: 800;
            margin-bottom: 6px;
        }

        .option-row {
            display: flex;
            gap: 8px;
            margin-bottom: 10px;
            align-items: flex-start;
            font-size: 15px;
        }

        .option-key {
            min-width: 22px;
            font-weight: 800;
        }

        .correct {
            color: #065f46;
            font-weight: 800;
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
        }
    </style>
</head>
<body>
    <div class="screen-toolbar">
        <button type="button" class="print-btn" onclick="window.print()">Print</button>
    </div>

    @php
        $gapMap = $questions->keyBy(fn ($q) => (string) ($q['gap_number'] ?? $q['sort_order'] ?? ''));
        $bodyText = $passage['body_text'] ?? '';

        $renderedText = preg_replace_callback('/\[\[(\d+)\]\]/', function ($matches) use ($gapMap, $showAnswers) {
            $gapNumber = (string) $matches[1];
            $question = $gapMap->get($gapNumber);
            $answerText = $gapNumber;

            if ($showAnswers && $question) {
                $correct = collect($question['options'] ?? [])->first(fn ($opt) => !empty($opt['is_correct']));
                if ($correct) {
                    $answerText = $gapNumber . ' ' . ($correct['option_key'] ?? '');
                }
            }

            $class = $showAnswers ? 'gap has-answer' : 'gap';

            return '<span class="' . $class . '">___' . e($answerText) . '___</span>';
        }, e($bodyText));
    @endphp

    <div class="sheet">
        <div class="page">
            <p class="instruction">
                {{ $item->instruction_text ?? 'Lesen Sie den folgenden Text und entscheiden Sie, welches Wort (a, b oder c) in die jeweilige Lücke passt. Markieren Sie ihre Lösungen auf dem Antwortbogen bei den Aufgaben 21-30.' }}
            </p>

            <div class="letter-box">
                <div class="letter-text">{!! $renderedText !!}</div>
            </div>

            <div class="options-box">
                <div class="options-grid">
                    @foreach($questions as $index => $question)
                        @php
                            $number = 21 + $index;
                            $options = $question['options'] ?? [];
                        @endphp

                        <div class="option-group">
                            <div class="option-number">{{ $number }}</div>

                            @foreach($options as $option)
                                <div class="option-row">
                                    <div class="option-key">{{ $option['option_key'] ?? '' }}</div>
                                    <div class="{{ $showAnswers && !empty($option['is_correct']) ? 'correct' : '' }}">
                                        {{ $option['option_text'] ?? '' }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>{{ $item->title ?? 'Sprachbausteine Teil 2' }}</title>
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

        .text-box {
            border: 4px solid #111;
            background: #fff;
            padding: 12px 16px;
        }

        .passage-title {
            text-align: center;
            font-size: 19px;
            font-weight: 800;
            margin: 0 0 12px;
        }

        .text-body {
            font-size: 12.5pt;
            line-height: 1.28;
            white-space: pre-line;
        }

        .gap {
            display: inline-block;
            min-width: 74px;
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
            /* margin-top: 12px; */
        }

        .options-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px 28px;
        }

        .option-row {
            display: flex;
            align-items: baseline;
            gap: 10px;
            font-size: 17px;
            line-height: 1.45;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .option-key {
            min-width: 26px;
            font-weight: 800;
        }

        .option-text {
            flex: 1;
        }

        .answers-summary {
            margin-top: 18px;
            border: 1px dashed #b8c1d6;
            border-radius: 18px;
            padding: 12px 14px;
            background: #fafbff;
        }

        .answers-summary-title {
            font-size: 14px;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .answers-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 8px 14px;
            font-size: 14px;
        }

        .answers-grid span {
            display: inline-block;
            min-width: 62px;
            font-weight: 700;
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
        $gapMap = $gaps->keyBy(fn ($gap) => (string) ($gap['label'] ?? ''));
        $bodyText = $passage['body_text'] ?? '';

        $renderedText = preg_replace_callback('/\[\[(\d+)\]\]/', function ($matches) use ($gapMap, $answers, $showAnswers) {
            $gapLabel = (string) $matches[1];
            $answer = $answers->get($gapLabel);
            $display = $gapLabel;

            if ($showAnswers && $answer) {
                $display = $gapLabel . ' ' . ($answer['option_key'] ?? '');
            }

            $class = $showAnswers ? 'gap has-answer' : 'gap';

            return '<span class="' . $class . '">___' . e($display) . '___</span>';
        }, e($bodyText));
    @endphp

    <div class="sheet">
        <div class="page">
            <p class="instruction">
                {{ $item->instruction_text ?? 'Lesen Sie den Text und entscheiden Sie, welches Wort in welche Lücke passt. Sie können jedes Wort nur einmal verwenden. Nicht alle Wörter passen in den Text.' }}
            </p>

            <div class="text-box">
                @if(!empty($passage['title']))
                    <div class="passage-title">{{ $passage['title'] }}</div>
                @endif

                <div class="text-body">{!! $renderedText !!}</div>
            </div>

            <div class="options-box">
                <div class="options-grid">
                    @foreach($options as $option)
                        <div class="option-row">
                            <div class="option-key">{{ $option['option_key'] ?? '' }}</div>
                            <div class="option-text">{{ $option['option_text'] ?? '' }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            @if($showAnswers)
                <div class="answers-summary">
                    <div class="answers-summary-title">Lösungen</div>
                    <div class="answers-grid">
                        @foreach($gaps as $gap)
                            @php
                                $label = (string) ($gap['label'] ?? '');
                                $answer = $answers->get($label);
                                $correctKey = $answer['option_key'] ?? '—';
                            @endphp
                            <span>{{ $label }} = {{ $correctKey }}</span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</body>
</html>
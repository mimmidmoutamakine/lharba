<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>{{ $item->title ?? 'Lesen Teil 3' }}</title>
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
            line-height: 1.4;
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

        .page + .page {
            border-top: 1px solid #e5e7eb;
            page-break-before: always;
        }

        .top-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 18px;
        }

        .title-box {
            border: 3px solid #111;
            background: #fff7a8;
            box-shadow: inset 0 0 0 2px rgba(17, 17, 17, 0.18);
            padding: 10px 26px;
            font-size: 18px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .instruction {
            margin: 0 0 18px 0;
            font-size: 14px;
            line-height: 1.45;
        }

        .situations-list {
            margin-top: 8px;
        }

        .situation-row {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 14px;
            page-break-inside: avoid;
        }

        .answer-line {
            min-width: 54px;
            font-size: 18px;
            line-height: 1;
            padding-top: 3px;
            color: #111;
            white-space: nowrap;
        }

        .situation-text {
            flex: 1;
            font-size: 15px;
            line-height: 1.35;
        }

        .ad-block {
            margin-bottom: 16px;
            page-break-inside: avoid;
        }

        .ad-label {
            font-size: 24px;
            font-weight: 800;
            margin: 0 0 6px 0;
            color: #111;
        }

        .ad-card {
            border: 1.5px solid #efcf8b;
            background: #fff;
            padding: 8px 10px;
        }

        .ad-title {
            font-size: 15px;
            font-weight: 800;
            margin: 0 0 4px 0;
        }

        .ad-body {
            font-size: 14px;
            line-height: 1.34;
            white-space: pre-line;
        }

        .correct-badge {
            display: inline-block;
            margin-left: 8px;
            padding: 2px 8px;
            border-radius: 999px;
            background: #eef2ff;
            border: 1px solid #c7d2fe;
            font-size: 12px;
            font-weight: 700;
            color: #1e3a8a;
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
            min-width: 58px;
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

            .page + .page {
                border-top: none;
            }
        }
    </style>
</head>
<body>
    <div class="screen-toolbar">
        <button type="button" class="print-btn" onclick="window.print()">Print</button>
    </div>

    <div class="sheet">
        <div class="page">
            <div class="top-row">
                <div style="flex:1"></div>
                <div class="title-box">{{ $item->source_label ?? 'Teil 3' }}</div>
            </div>

            <p class="instruction">
                {{ $item->instruction_text ?? 'Lesen Sie zuerst die zehn Situationen (11-20) und dann die 12 Infotexte (A-L). Welche Infotext passt zu welcher Situation? Sie können jeden Infotext nur einmal verwenden. Manchmal gibt es keine Lösung. Markieren Sie dann X.' }}
            </p>

            <div class="situations-list">
                @foreach($situations as $index => $situation)
                    @php
                        $number = 11 + $index;
                        $label = $situation['label'] ?? (string) $number;
                        $answer = $answers->get($label);
                        $correctLabel = $answer['correct_ad_label'] ?? null;
                    @endphp

                    <div class="situation-row">
                        <div class="answer-line">
                            ___ {{ $number }})
                            @if($showAnswers && $correctLabel)
                                <span class="correct-badge">{{ $correctLabel }}</span>
                            @endif
                        </div>

                        <div class="situation-text">
                            {{ $situation['situation_text'] ?? '' }}
                        </div>
                    </div>
                @endforeach
            </div>

            @if($showAnswers)
                <div class="answers-summary">
                    <div class="answers-summary-title">Lösungen</div>
                    <div class="answers-grid">
                        @foreach($situations as $index => $situation)
                            @php
                                $number = 11 + $index;
                                $label = $situation['label'] ?? (string) $number;
                                $answer = $answers->get($label);
                                $correctLabel = $answer['correct_ad_label'] ?? '—';
                            @endphp
                            <span>{{ $number }} = {{ $correctLabel }}</span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <div class="page">
            @foreach($ads as $ad)
                <div class="ad-block">
                    <div class="ad-label">({{ $ad['label'] ?? '?' }})</div>

                    <div class="ad-card">
                        <div class="ad-title">{{ $ad['title'] ?? '' }}</div>
                        <div class="ad-body">{{ $ad['body_text'] ?? '' }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</body>
</html>
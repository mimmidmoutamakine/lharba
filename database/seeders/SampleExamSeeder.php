<?php

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\ExamPart;
use App\Models\ExamSection;
use App\Models\LesenMcqOption;
use App\Models\LesenSituationAnswer;
use App\Models\SprachGapOption;
use Illuminate\Database\Seeder;

class SampleExamSeeder extends Seeder
{
    public function run(): void
    {
        $exam = Exam::query()->updateOrCreate(
            ['title' => 'Deutsch B2 Probeprufung 1'],
            [
                'level' => 'B2',
                'total_duration_minutes' => 90,
                'is_published' => true,
            ]
        );

        $lesenSection = $exam->sections()->updateOrCreate(
            ['type' => ExamSection::TYPE_LESEN, 'sort_order' => 1],
            ['title' => 'Leseverstehen']
        );

        $sprachSection = $exam->sections()->updateOrCreate(
            ['type' => ExamSection::TYPE_SPRACHBAUSTEINE, 'sort_order' => 2],
            ['title' => 'Sprachbausteine']
        );

        $hoerenSection = $exam->sections()->updateOrCreate(
            ['type' => ExamSection::TYPE_HOEREN, 'sort_order' => 3],
            ['title' => 'Horen']
        );

        $schreibenSection = $exam->sections()->updateOrCreate(
            ['type' => ExamSection::TYPE_SCHREIBEN, 'sort_order' => 4],
            ['title' => 'Schreiben']
        );

        $part = $lesenSection->parts()->updateOrCreate(
            ['sort_order' => 1],
            [
                'title' => 'Teil 1',
                'instruction_text' => 'Lesen Sie die Uberschriften a-j und die Texte 1-5 und entscheiden Sie, welche Uberschrift am besten zu welchem Text passt.',
                'part_type' => ExamPart::TYPE_MATCHING_TITLES_TO_TEXTS,
                'points' => 25,
            ]
        );

        $lesenTeil2 = $lesenSection->parts()->updateOrCreate(
            ['sort_order' => 2],
            [
                'title' => 'Teil 2',
                'instruction_text' => 'Lesen Sie den Text und die Aufgaben. Entscheiden Sie anhand des Textes, welche Losung richtig ist.',
                'part_type' => ExamPart::TYPE_READING_TEXT_MCQ,
                'points' => 25,
            ]
        );

        $lesenTeil3 = $lesenSection->parts()->updateOrCreate(
            ['sort_order' => 3],
            [
                'title' => 'Teil 3',
                'instruction_text' => 'Lesen Sie die zehn Situationen (1-10) und die zwolf Texte (a-l). Welcher Text passt zu welcher Situation? Sie konnen jeden Text nur einmal verwenden. Manchmal passt kein Text. Wahlen Sie dann X.',
                'part_type' => ExamPart::TYPE_SITUATIONS_TO_ADS_WITH_X,
                'points' => 25,
            ]
        );

        $sprachTeil1 = $sprachSection->parts()->updateOrCreate(
            ['sort_order' => 1],
            [
                'title' => 'Teil 1',
                'instruction_text' => 'Lesen Sie den Text und entscheiden Sie, welches Wort in die jeweilige Lucke passt.',
                'part_type' => ExamPart::TYPE_SPRACHBAUSTEINE_EMAIL_GAP_MCQ,
                'points' => 15,
            ]
        );
        $sprachTeil2 = $sprachSection->parts()->updateOrCreate(
            ['sort_order' => 2],
            [
                'title' => 'Teil 2',
                'instruction_text' => 'Lesen Sie den Text und entscheiden Sie, welches Wort in welche Lucke passt. Sie konnen jedes Wort nur einmal verwenden. Nicht alle Worter passen in den Text.',
                'part_type' => ExamPart::TYPE_SPRACHBAUSTEINE_POOL_GAP_MATCH,
                'points' => 15,
            ]
        );

        $hoerenTeil1 = $hoerenSection->parts()->updateOrCreate(
            ['sort_order' => 1],
            [
                'title' => 'Teil 1',
                'instruction_text' => 'Sie horen die Nachrichten. Entscheiden Sie beim Horen, ob die Aussagen richtig oder falsch sind. Sie horen die Nachrichten nur einmal.',
                'part_type' => ExamPart::TYPE_HOEREN_TRUE_FALSE,
                'points' => 25,
                'config_json' => [
                    'audio_url' => 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3',
                    'audio_duration_seconds' => 1013,
                ],
            ]
        );
        $hoerenTeil2 = $hoerenSection->parts()->updateOrCreate(
            ['sort_order' => 2],
            [
                'title' => 'Teil 2',
                'instruction_text' => 'Sie horen kurze Texte. Entscheiden Sie, ob die Aussagen richtig oder falsch sind.',
                'part_type' => ExamPart::TYPE_HOEREN_TRUE_FALSE,
                'points' => 25,
                'config_json' => [
                    'audio_url' => 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-2.mp3',
                    'audio_duration_seconds' => 990,
                ],
            ]
        );
        $hoerenTeil3 = $hoerenSection->parts()->updateOrCreate(
            ['sort_order' => 3],
            [
                'title' => 'Teil 3',
                'instruction_text' => 'Sie horen jetzt funf kurze Texte. Entscheiden Sie beim Horen, ob die Aussagen richtig oder falsch sind. Sie horen diese Texte nur einmal.',
                'part_type' => ExamPart::TYPE_HOEREN_TRUE_FALSE,
                'points' => 25,
                'config_json' => [
                    'audio_url' => 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-3.mp3',
                    'audio_duration_seconds' => 1000,
                ],
            ]
        );

        $schreibenSection->parts()->updateOrCreate(
            ['sort_order' => 1],
            [
                'title' => 'Teil 1',
                'instruction_text' => 'Entscheiden Sie schnell, denn die zur Verfugung stehende Zeit ist begrenzt auf 30 Minuten.',
                'part_type' => ExamPart::TYPE_WRITING_TASK,
                'points' => 45,
                'config_json' => [
                    'duration_minutes' => 30,
                    'tasks' => [
                        [
                            'key' => 'A',
                            'label' => 'Bitte um Informationen',
                            'title' => 'Aufgabe A: Schriftlicher Ausdruck',
                            'prompt' => "In der Zeitung lesen Sie folgende Anzeige:\n\nSecuria Versicherungen AG\n\nAlle 4 Sekunden passiert in Deutschland ein Unfall - davon 71 % in der Freizeit und im Haushalt. Sollte Ihnen etwas zustoBen, bietet Ihnen unsere Unfallversicherung Schutz vor finanziellen Risiken.\n\nSie interessieren sich fur dieses Angebot und mochten weitere Informationen erhalten.\nSchreiben Sie eine E-Mail, in der Sie mindestens 3 gezielte Fragen stellen.",
                        ],
                        [
                            'key' => 'B',
                            'label' => 'Beschwerde',
                            'title' => 'Aufgabe B: Schriftlicher Ausdruck',
                            'prompt' => "Sie lesen folgende Werbeanzeige:\n\nJugendcamp Silberstrand\n\nSie haben im Jugendcamp Silberstrand zwei Wochen Urlaub gemacht. Leider waren Sie uberhaupt nicht zufrieden.\nSchreiben Sie eine Beschwerde an das Camp.\n\nBehandeln Sie darin konkrete Probleme und nennen Sie Ihre Erwartungen an eine Losung.",
                        ],
                    ],
                ],
            ]
        );

        $texts = [
            ['label' => '1', 'body_text' => 'Entdecken Sie interessante Stadte und Regionen im Herzen Deutschlands mit mildem Klima, Seen, Waldern und historischen Orten.'],
            ['label' => '2', 'body_text' => 'Schulerinnen und Schuler arbeiten in einem sozialen Projekt und spenden ihren Verdienst fur Jugendinitiativen in Krisenregionen.'],
            ['label' => '3', 'body_text' => 'Niedrigwasser auf dem Fluss zwingt ein Kreuzfahrtschiff zur Plananderung: Die Reise geht fur viele Gaste per Bus weiter.'],
            ['label' => '4', 'body_text' => 'An Badeseen steigt die Zahl riskanter Situationen. Rettungskrafte fordern mehr Aufklarung und Vorsicht.'],
            ['label' => '5', 'body_text' => 'Nach sechs Wochen harter Arbeit genieBt ein Team die freie Zeit in der Natur bei Wanderungen und Seen.'],
        ];

        $options = [
            ['key' => 'A', 'text' => 'Am Strand im Dienst - mehr Sicherheit fur Urlauber'],
            ['key' => 'B', 'text' => 'Bader, Seen und Natur - im hessischen Paradies'],
            ['key' => 'C', 'text' => 'Freiheit und Natur - nach sechs Wochen harter Arbeit'],
            ['key' => 'D', 'text' => 'Jugendliche arbeiten fur Jugendliche'],
            ['key' => 'E', 'text' => 'Kinderarbeit in Deutschland: Jugendliche werden zur Arbeit gezwungen'],
            ['key' => 'F', 'text' => 'Nach harter Arbeit durch nordische Gewasser'],
            ['key' => 'G', 'text' => 'Schaden an Kreuzfahrtschiff verhindert Weiterfahrt'],
            ['key' => 'H', 'text' => 'Urlaub an deutschen Seen immer gefahrlicher'],
            ['key' => 'I', 'text' => 'Wegen Niedrigwasser: vom Fluss auf die StraBe'],
            ['key' => 'J', 'text' => 'Zu Gast bei den Fursten'],
        ];

        $textModels = [];
        foreach ($texts as $index => $text) {
            $textModels[$text['label']] = $part->lesenMatchingTexts()->updateOrCreate(
                ['label' => $text['label']],
                [
                    'body_text' => $text['body_text'],
                    'sort_order' => $index + 1,
                ]
            );
        }

        $optionModels = [];
        foreach ($options as $index => $option) {
            $optionModels[$option['key']] = $part->lesenMatchingOptions()->updateOrCreate(
                ['option_key' => $option['key']],
                [
                    'option_text' => $option['text'],
                    'sort_order' => $index + 1,
                ]
            );
        }

        $correctMap = [
            '1' => 'B',
            '2' => 'D',
            '3' => 'I',
            '4' => 'H',
            '5' => 'C',
        ];

        foreach ($correctMap as $label => $optionKey) {
            $part->lesenMatchingAnswers()->updateOrCreate(
                ['lesen_matching_text_id' => $textModels[$label]->id],
                ['correct_option_id' => $optionModels[$optionKey]->id]
            );
        }

        $lesenTeil2->lesenMcqPassages()->updateOrCreate(
            ['sort_order' => 1],
            [
                'title' => 'Freizeitbegriff',
                'body_text' => "Das Freizeitverstandnis hat sich in den letzten Jahrzehnten deutlich verandert. Fruher sahen viele Menschen Freizeit vor allem als Erholungszeit nach der Arbeit. Heute verbinden die meisten damit einen eigenstandigen Lebensbereich, den sie aktiv gestalten wollen.\n\nUntersuchungen zeigen, dass ein groBer Teil der Bevolkerung Freizeit als Zeit fur personliche Interessen, Familie und soziale Kontakte betrachtet. Besonders wichtig sind regelmaBige Rituale: feste Termine fur Sport, Treffen mit Freunden oder gemeinsame Familienaktivitaten am Wochenende.\n\nGleichzeitig unterscheiden sich die Gewohnheiten je nach Alter und beruflicher Situation. Wahrend einige ihre freie Zeit spontan planen, organisieren andere ihre Aktivitaten lange im Voraus. Trotz dieser Unterschiede bleibt ein Punkt zentral: Freizeit dient nicht nur der Erholung, sondern auch der Lebensqualitat und dem sozialen Ausgleich.\n\nInsgesamt zeigt sich, dass Freizeit in Deutschland heute als positiver Bestandteil des Alltags gilt. Sie wird bewusst genutzt, strukturiert geplant und immer starker mit individuellen Zielen verbunden.",
            ]
        );

        $qData = [
            [
                'question_text' => 'Siebzig Prozent der Bevolkerung meinen, dass Freizeit',
                'options' => [
                    ['key' => 'A', 'text' => 'nach den eigenen Vorlieben gestaltet werden soll.'],
                    ['key' => 'B', 'text' => 'nicht unbedingt positiv besetzt ist.'],
                    ['key' => 'C', 'text' => 'nur dem Ausruhen und Schlafen dienen sollte.'],
                ],
                'correct' => 'A',
            ],
            [
                'question_text' => 'Die Mehrheit der Leute nutzt ihre Freizeit',
                'options' => [
                    ['key' => 'A', 'text' => 'fur die eigenen Interessen.'],
                    ['key' => 'B', 'text' => 'zur Aufbesserung des Einkommens.'],
                    ['key' => 'C', 'text' => 'nur zur Regeneration fur den nachsten Arbeitstag.'],
                ],
                'correct' => 'A',
            ],
            [
                'question_text' => 'Die Deutschen',
                'options' => [
                    ['key' => 'A', 'text' => 'gehen nur an Wochenenden ihren Hobbys nach.'],
                    ['key' => 'B', 'text' => 'organisieren ihre Freizeit gar nicht.'],
                    ['key' => 'C', 'text' => 'organisieren ihre Freizeit oft bewusst und planvoll.'],
                ],
                'correct' => 'C',
            ],
            [
                'question_text' => 'Der Sonntag ist bei vielen reserviert fur',
                'options' => [
                    ['key' => 'A', 'text' => 'zusatzliche Erwerbsarbeit.'],
                    ['key' => 'B', 'text' => 'Familie und Entspannung.'],
                    ['key' => 'C', 'text' => 'ausschlieBlich berufliche Weiterbildung.'],
                ],
                'correct' => 'B',
            ],
            [
                'question_text' => 'Freizeitrituale',
                'options' => [
                    ['key' => 'A', 'text' => 'sind fur viele Menschen ein fester Teil des Alltags.'],
                    ['key' => 'B', 'text' => 'haben in modernen Gesellschaften keine Bedeutung mehr.'],
                    ['key' => 'C', 'text' => 'wurden bisher kaum beobachtet oder beschrieben.'],
                ],
                'correct' => 'A',
            ],
        ];

        $questionIds = [];
        foreach ($qData as $index => $item) {
            $question = $lesenTeil2->lesenMcqQuestions()->updateOrCreate(
                ['sort_order' => $index + 1],
                ['question_text' => $item['question_text']]
            );
            $questionIds[] = $question->id;

            LesenMcqOption::query()->where('lesen_mcq_question_id', $question->id)->delete();
            foreach ($item['options'] as $optionIndex => $option) {
                $question->options()->create([
                    'option_key' => $option['key'],
                    'option_text' => $option['text'],
                    'sort_order' => $optionIndex + 1,
                    'is_correct' => $option['key'] === $item['correct'],
                ]);
            }
        }
        $lesenTeil2->lesenMcqQuestions()->whereNotIn('id', $questionIds)->delete();

        $ads3 = [
            ['label' => 'A', 'title' => 'Schweden per Schiff', 'body' => 'Historische Schiffsreisen von Goteborg bis Stockholm mit Unterbringung an Bord.'],
            ['label' => 'B', 'title' => 'Skating fur Fortgeschrittene', 'body' => 'Wochenendkurs fur erfahrene Inline-Skater mit Techniktraining.'],
            ['label' => 'C', 'title' => 'Gesund in Agypten', 'body' => 'Infoblatt zu Impfungen, Klima und medizinischer Versorgung fur Agypten-Reisen.'],
            ['label' => 'D', 'title' => 'Naturkosmetik Workshop', 'body' => 'Einsteigerkurs mit praktischen Rezepten fur naturliche Pflegeprodukte.'],
            ['label' => 'E', 'title' => 'Jugend hilft weltweit', 'body' => 'Freiwilligenprogramm fur Jugendliche ab 15 Jahren in sozialen Projekten.'],
            ['label' => 'F', 'title' => 'Sportlich im Sommer', 'body' => 'Kursprogramm mit Wandern, Klettern und Wassersport fur Erwachsene.'],
            ['label' => 'G', 'title' => 'Skate Event Helfer gesucht', 'body' => 'Organisationsteam sucht Unterstutzung fur Planung und Ablauf eines Skate-Events.'],
            ['label' => 'H', 'title' => 'Inline-Skaten lernen', 'body' => 'Grundkurs fur Anfanger mit Bremsen, Balance und sicherem Fahren.'],
            ['label' => 'I', 'title' => 'Skate Veranstaltungen Deutschland', 'body' => 'Jahreskalender mit Wettbewerben, Treffen und regionalen Touren.'],
            ['label' => 'J', 'title' => 'Reisepapiere Express', 'body' => 'Visum- und Dokumentenservice mit kurzfristiger Bearbeitung.'],
            ['label' => 'K', 'title' => 'Sprachcafe Franzosisch', 'body' => 'Wochentliches Treffen zum freien Sprechen auf Franzosisch.'],
            ['label' => 'L', 'title' => 'Tanzabend Salsa', 'body' => 'Offene Tanzgruppe fur Salsa mit Anfanger- und Aufbaukurs.'],
        ];
        $adMap3 = [];
        foreach ($ads3 as $index => $ad) {
            $model = $lesenTeil3->lesenSituationAds()->updateOrCreate(
                ['sort_order' => $index + 1],
                ['label' => $ad['label'], 'title' => $ad['title'], 'body_text' => $ad['body']]
            );
            $adMap3[$ad['label']] = $model->id;
        }

        $situations3 = [
            ['label' => '1', 'text' => 'Ein Bekannter mochte Schweden per Schiff kennenlernen.'],
            ['label' => '2', 'text' => 'Ein Freund mochte sich im Inline-Skaten perfektionieren.'],
            ['label' => '3', 'text' => 'Ein Kollege mochte sich uber Gesundheitsrisiken in Agypten informieren.'],
            ['label' => '4', 'text' => 'Eine Bekannte mochte einen Kurs uber Naturkosmetik besuchen.'],
            ['label' => '5', 'text' => 'Eine 17-jahrige Freundin wurde gerne armen Menschen in anderen Landern helfen.'],
            ['label' => '6', 'text' => 'Ihr Nachbar mochte sich im Sommerurlaub sportlich betatigen.'],
            ['label' => '7', 'text' => 'Ihre Freundin mochte gerne bei der Organisation einer Inline-Skate-Veranstaltung mitwirken.'],
            ['label' => '8', 'text' => 'Sie mochten das Inline-Skaten erlernen und suchen Informationen.'],
            ['label' => '9', 'text' => 'Sie mochten herausfinden, wo es in Deutschland Skate-Veranstaltungen gibt.'],
            ['label' => '10', 'text' => 'Sie mussen kurzfristig fur Ihren Chef Reisepapiere fur Agypten besorgen.'],
        ];
        $situationMap3 = [];
        foreach ($situations3 as $index => $situation) {
            $model = $lesenTeil3->lesenSituations()->updateOrCreate(
                ['sort_order' => $index + 1],
                ['label' => $situation['label'], 'situation_text' => $situation['text']]
            );
            $situationMap3[$situation['label']] = $model->id;
        }

        $correct3 = [
            '1' => 'A',
            '2' => 'B',
            '3' => 'C',
            '4' => 'X',
            '5' => 'E',
            '6' => 'F',
            '7' => 'G',
            '8' => 'H',
            '9' => 'X',
            '10' => 'J',
        ];
        $rows3 = [];
        foreach ($correct3 as $situationLabel => $answerLabel) {
            $rows3[] = [
                'exam_part_id' => $lesenTeil3->id,
                'lesen_situation_id' => $situationMap3[$situationLabel],
                'correct_ad_id' => $answerLabel === 'X' ? null : $adMap3[$answerLabel],
                'is_no_match' => $answerLabel === 'X',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        $lesenTeil3->lesenSituationAnswers()->delete();
        LesenSituationAnswer::query()->insert($rows3);

        $sprachTeil1->sprachGapPassages()->updateOrCreate(
            ['sort_order' => 1],
            [
                'title' => null,
                'body_text' => "Liebe Daniela,\n\nich habe schon ein ganz schlechtes Gewissen, denn [[1]] wollte ich dir schon vor zwei Monaten schreiben. Aber du weiBt ja, wie das ist: Wenn man sich auf eine Prufung vorbereitet, hat [[2]] uberhaupt keine Zeit mehr fur seine Hobbys - alles dreht sich nur noch ums Lernen.\n\nNun habe ich es aber geschafft: Gestern war die Prufung und ich bin zuversichtlich, dass ich sie bestanden habe. Mein Freund, mit [[3]] Hilfe es mir uberhaupt nur moglich war, diese ganze Zeit zu [[4]], hat mich fur heute Abend in ein tolles Restaurant eingeladen. Danach gehen wir auch noch tanzen.\n\nIn deinem letzten Brief hast du mich gefragt, [[5]] ich Lust hatte, mit dir zusammen ein Wochenende in London zu verbringen. Naturlich habe ich Lust! Nach dem ganzen Stress der letzten Wochen fande ich es super, mal ein paar Tage lang mit einer Freundin etwas Tolles zu [[6]].\n\nLondon ist eine wunderbare Stadt, ich habe schon viele Berichte daruber gelesen. Ich wurde mich [[7]] besonders [[8]] die Tate Gallery und das Filmmuseum interessieren.\n\nMach [[9]] einfach ein paar Vorschlage, wann du Zeit hast. Ich bin sicher, dass wir [[10]] auf ein Wochenende einigen konnen. In der Zwischenzeit drucke ich schon mal ein paar Angebote aus dem Internet aus.\n\nHerzliche GruBe",
            ]
        );

        $gapData = [
            ['gap' => 1, 'options' => [['A', 'auBerdem'], ['B', 'eigentlich'], ['C', 'uberhaupt']], 'correct' => 'B'],
            ['gap' => 2, 'options' => [['A', 'er'], ['B', 'es'], ['C', 'man']], 'correct' => 'C'],
            ['gap' => 3, 'options' => [['A', 'der'], ['B', 'dessen'], ['C', 'seiner']], 'correct' => 'A'],
            ['gap' => 4, 'options' => [['A', 'ubersetzen'], ['B', 'uberstehen'], ['C', 'ubertragen']], 'correct' => 'B'],
            ['gap' => 5, 'options' => [['A', 'dass'], ['B', 'falls'], ['C', 'ob']], 'correct' => 'C'],
            ['gap' => 6, 'options' => [['A', 'unternehmen'], ['B', 'verbringen'], ['C', 'verplanen']], 'correct' => 'A'],
            ['gap' => 7, 'options' => [['A', 'ganz'], ['B', 'recht'], ['C', 'zwar']], 'correct' => 'B'],
            ['gap' => 8, 'options' => [['A', 'auf'], ['B', 'fur'], ['C', 'in']], 'correct' => 'A'],
            ['gap' => 9, 'options' => [['A', 'bestimmt'], ['B', 'doch'], ['C', 'sicher']], 'correct' => 'B'],
            ['gap' => 10, 'options' => [['A', 'euch'], ['B', 'sich'], ['C', 'uns']], 'correct' => 'C'],
        ];

        $sprachQuestionIds = [];
        foreach ($gapData as $index => $gap) {
            $question = $sprachTeil1->sprachGapQuestions()->updateOrCreate(
                ['sort_order' => $index + 1],
                ['gap_number' => $gap['gap']]
            );
            $sprachQuestionIds[] = $question->id;

            SprachGapOption::query()->where('sprach_gap_question_id', $question->id)->delete();
            foreach ($gap['options'] as $optionIndex => $option) {
                $question->options()->create([
                    'option_key' => $option[0],
                    'option_text' => $option[1],
                    'sort_order' => $optionIndex + 1,
                    'is_correct' => $option[0] === $gap['correct'],
                ]);
            }
        }
        $sprachTeil1->sprachGapQuestions()->whereNotIn('id', $sprachQuestionIds)->delete();

        $sprachTeil2->sprachPoolPassages()->updateOrCreate(
            ['sort_order' => 1],
            [
                'title' => 'Es gibt immer weniger Deutsche',
                'body_text' => "[[1]] Angaben des Statistischen Bundesamtes in Wiesbaden wird die Bevolkerungszahl in Deutschland in den nachsten Jahrzehnten [[2]] sinken.\n\nDie Statistiker [[3]] damit, dass die Zahl der Deutschen bis zum Jahr 2050 von jetzt 82 Millionen auf nur noch 65 Millionen zuruckgehen wird. Diese Entwicklung sei, so kommentieren die Statistiker, deswegen so dramatisch, weil sich gleichzeitig mit dem Ruckgang der Einwohnerzahl die Altersstruktur Deutschlands sehr stark verandern wird: Fast die Halfte der Bevolkerung wird dann im Rentenalter sein. Das Gesundheitssystem und die Altersversorgung werden [[4]] dieser Entwicklung vor groBen Problemen stehen und moglicherweise nicht mehr bezahlbar sein.\n\nDiese ungunstige Bevolkerungsentwicklung in Deutschland hat nach Auskunft der Statistiker mehrere Aspekte. Zum einen werden die Deutschen immer alter: Das durchschnittliche Lebensalter fur Frauen wird bis 2050 auf 84, das der Manner auf 78 Jahre [[5]]. Gleichzeitig werde zum anderen die Zahl der Geburten zuruckgehen: Im Jahr 2050 werden voraussichtlich nur noch 1400 Kinder pro 1000 Frauen geboren.\n\nDie Auswirkungen auf das politische und gesellschaftliche Leben in Deutschland im Jahr 2050 lassen sich [[6]] erahnen. Wenn nahezu funfzig Prozent der Bevolkerung Senioren sind, werden sich Politik und Geschaftswelt [[7]] diesen Personenkreis einstellen. Fur junge Leute wird sich dann das Problem ergeben, dass sich Politiker mehr [[8]] die alten Wahler interessieren werden. Die Produktivitat der Wirtschaft wird abnehmen, da Arbeitnehmer den groBten Teil ihres Einkommens in die Kranken- und Rentenversicherungen [[9]] in den Konsum stecken mussen. Diese Probleme konne man nur [[10]], so das Statistische Bundesamt, wenn ab sofort eine hohe Zahl von jungen Arbeitskraften aus dem Ausland zuwandere.",
            ]
        );

        $poolGaps = collect(range(1, 10))->map(fn ($i) => ['label' => (string) $i, 'sort_order' => $i])->all();
        $poolOptions = [
            ['key' => 'A', 'text' => 'AN'],
            ['key' => 'B', 'text' => 'AUF'],
            ['key' => 'C', 'text' => 'AUFGRUND'],
            ['key' => 'D', 'text' => 'BEHEBEN'],
            ['key' => 'E', 'text' => 'BESCHEIDEN'],
            ['key' => 'F', 'text' => 'DRASTISCH'],
            ['key' => 'G', 'text' => 'ERHOHEN'],
            ['key' => 'H', 'text' => 'FUR'],
            ['key' => 'I', 'text' => 'IM'],
            ['key' => 'J', 'text' => 'NACH'],
            ['key' => 'K', 'text' => 'RECHNEN'],
            ['key' => 'L', 'text' => 'STATT'],
            ['key' => 'M', 'text' => 'STEIGEN'],
            ['key' => 'N', 'text' => 'UBERHEBLICH'],
            ['key' => 'O', 'text' => 'UNSCHWER'],
        ];

        $poolGapMap = [];
        foreach ($poolGaps as $gap) {
            $model = $sprachTeil2->sprachPoolGaps()->updateOrCreate(
                ['label' => $gap['label']],
                ['sort_order' => $gap['sort_order']]
            );
            $poolGapMap[$gap['label']] = $model->id;
        }

        $poolOptionMap = [];
        foreach ($poolOptions as $index => $option) {
            $model = $sprachTeil2->sprachPoolOptions()->updateOrCreate(
                ['option_key' => $option['key']],
                ['option_text' => $option['text'], 'sort_order' => $index + 1]
            );
            $poolOptionMap[$option['key']] = $model->id;
        }

        $poolCorrectMap = [
            '1' => 'J',
            '2' => 'F',
            '3' => 'K',
            '4' => 'C',
            '5' => 'M',
            '6' => 'O',
            '7' => 'B',
            '8' => 'H',
            '9' => 'L',
            '10' => 'D',
        ];

        $sprachTeil2->sprachPoolAnswers()->delete();
        foreach ($poolCorrectMap as $gapLabel => $optionKey) {
            $sprachTeil2->sprachPoolAnswers()->create([
                'sprach_pool_gap_id' => $poolGapMap[$gapLabel],
                'correct_option_id' => $poolOptionMap[$optionKey],
            ]);
        }

        $hoerenData = [
            $hoerenTeil1->id => [
                ['text' => 'Laut BILD AM SONNTAG konnen in Zukunft nur Mieter, aber nicht Vermieter bestimmte Mietvertrage schneller kundigen.', 'true' => false],
                ['text' => 'In bestimmten Bundeslandern sollen Wohnhauser abgerissen werden, weil sie unbewohnt sind.', 'true' => true],
                ['text' => 'Sowohl die Waldbrande als auch die Hitzewelle in Griechenland sind zu Ende.', 'true' => false],
                ['text' => 'In Kanada mussten die Bergungsarbeiten nach einem Tornado wegen erneuter Unwetterwarnungen eingestellt werden.', 'true' => true],
                ['text' => 'Bei einem Fahrungluck in der Nahe von Gibraltar gab es nur Sachschaden.', 'true' => false],
            ],
            $hoerenTeil2->id => [
                ['text' => 'Im ersten Beitrag geht es um neue Regeln fur E-Scooter in Innenstadten.', 'true' => true],
                ['text' => 'Die Sprecherin sagt, dass das Museum montags geschlossen bleibt.', 'true' => false],
                ['text' => 'Ein Verein sucht Freiwillige fur Wochenendaktionen im Park.', 'true' => true],
                ['text' => 'Die Bahnstrecke wird fur zwei Monate komplett gesperrt.', 'true' => false],
                ['text' => 'Am Ende wird ein kostenloser Sprachkurs fur Erwachsene angekundigt.', 'true' => true],
            ],
            $hoerenTeil3->id => [
                ['text' => 'Der Software-Service von Macrohard steht rund um die Uhr zur Verfugung.', 'true' => true],
                ['text' => 'Fur das Konzert mit Romano Castelli gibt es noch Karten ab 200 Euro.', 'true' => false],
                ['text' => 'Uber den neuen Tarif von T-Upline konnen Sie sich im Internet informieren.', 'true' => true],
                ['text' => 'Im Park des Museums fur Volkerkunde treten in diesem Jahr nur japanische Musiker auf.', 'true' => false],
                ['text' => 'Beim Festival gibt es neben folkloristischer Unterhaltung auch kulinarische Spezialitaten.', 'true' => true],
            ],
        ];

        foreach ([$hoerenTeil1, $hoerenTeil2, $hoerenTeil3] as $hoerenPart) {
            $rows = $hoerenData[$hoerenPart->id] ?? [];
            $ids = [];
            foreach ($rows as $index => $row) {
                $model = $hoerenPart->hoerenTrueFalseQuestions()->updateOrCreate(
                    ['sort_order' => $index + 1],
                    ['statement_text' => $row['text'], 'is_true_correct' => $row['true']]
                );
                $ids[] = $model->id;
            }
            $hoerenPart->hoerenTrueFalseQuestions()->whereNotIn('id', $ids)->delete();
        }
    }
}

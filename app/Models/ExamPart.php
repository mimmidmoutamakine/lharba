<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class ExamPart extends Model
{
    public const TYPE_MATCHING_TITLES_TO_TEXTS = 'matching_titles_to_texts';
    public const TYPE_READING_TEXT_MCQ = 'reading_text_mcq';
    public const TYPE_SITUATIONS_TO_ADS_WITH_X = 'situations_to_ads_with_x';
    public const TYPE_SPRACHBAUSTEINE_EMAIL_GAP_MCQ = 'sprachbausteine_email_gap_mcq';
    public const TYPE_SPRACHBAUSTEINE_POOL_GAP_MATCH = 'sprachbausteine_pool_gap_match';
    public const TYPE_HOEREN_TRUE_FALSE = 'hoeren_true_false';
    public const TYPE_MULTIPLE_CHOICE = 'multiple_choice';
    public const TYPE_GAP_FILL = 'gap_fill';
    public const TYPE_WRITING_TASK = 'writing_task';
    public const TYPE_LISTENING_MCQ = 'listening_mcq';

    protected $fillable = [
        'exam_section_id',
        'part_bank_item_id',
        'title',
        'instruction_text',
        'part_type',
        'points',
        'sort_order',
        'config_json',
    ];

    protected function casts(): array
    {
        return [
            'config_json' => 'array',
        ];
    }

    public static function types(): array
    {
        return [
            self::TYPE_MATCHING_TITLES_TO_TEXTS => 'Matching titles to texts',
            self::TYPE_READING_TEXT_MCQ => 'Reading text MCQ',
            self::TYPE_SITUATIONS_TO_ADS_WITH_X => 'Situations to ads with X',
            self::TYPE_SPRACHBAUSTEINE_EMAIL_GAP_MCQ => 'Sprachbausteine email gap MCQ',
            self::TYPE_SPRACHBAUSTEINE_POOL_GAP_MATCH => 'Sprachbausteine pool gap match',
            self::TYPE_HOEREN_TRUE_FALSE => 'Horen true/false',
            self::TYPE_MULTIPLE_CHOICE => 'Multiple choice',
            self::TYPE_GAP_FILL => 'Gap fill',
            self::TYPE_WRITING_TASK => 'Writing task',
            self::TYPE_LISTENING_MCQ => 'Listening MCQ',
        ];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(ExamSection::class, 'exam_section_id');
    }

    public function bankItem(): BelongsTo
    {
        return $this->belongsTo(PartBankItem::class, 'part_bank_item_id');
    }

    public function lesenMatchingTexts(): HasMany
    {
        return $this->hasMany(LesenMatchingText::class)->orderBy('sort_order');
    }

    public function lesenMatchingOptions(): HasMany
    {
        return $this->hasMany(LesenMatchingOption::class)->orderBy('sort_order');
    }

    public function lesenMatchingAnswers(): HasMany
    {
        return $this->hasMany(LesenMatchingAnswer::class);
    }

    public function lesenMcqPassages(): HasMany
    {
        return $this->hasMany(LesenMcqPassage::class)->orderBy('sort_order');
    }

    public function lesenMcqQuestions(): HasMany
    {
        return $this->hasMany(LesenMcqQuestion::class)->orderBy('sort_order');
    }

    public function lesenSituationAds(): HasMany
    {
        return $this->hasMany(LesenSituationAd::class)->orderBy('sort_order');
    }

    public function lesenSituations(): HasMany
    {
        return $this->hasMany(LesenSituation::class)->orderBy('sort_order');
    }

    public function lesenSituationAnswers(): HasMany
    {
        return $this->hasMany(LesenSituationAnswer::class);
    }

    public function sprachGapPassages(): HasMany
    {
        return $this->hasMany(SprachGapPassage::class)->orderBy('sort_order');
    }

    public function sprachGapQuestions(): HasMany
    {
        return $this->hasMany(SprachGapQuestion::class)->orderBy('sort_order');
    }

    public function sprachPoolPassages(): HasMany
    {
        return $this->hasMany(SprachPoolPassage::class)->orderBy('sort_order');
    }

    public function sprachPoolGaps(): HasMany
    {
        return $this->hasMany(SprachPoolGap::class)->orderBy('sort_order');
    }

    public function sprachPoolOptions(): HasMany
    {
        return $this->hasMany(SprachPoolOption::class)->orderBy('sort_order');
    }

    public function sprachPoolAnswers(): HasMany
    {
        return $this->hasMany(SprachPoolAnswer::class);
    }

    public function hoerenTrueFalseQuestions(): HasMany
    {
        return $this->hasMany(HoerenTrueFalseQuestion::class)->orderBy('sort_order');
    }

    public function attemptAnswers(): HasMany
    {
        return $this->hasMany(AttemptAnswer::class);
    }
}

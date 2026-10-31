<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One difficult word / collocation / phrase of an IELTS test, with its meaning and translations. */
class VocabWord extends Model
{
    protected $fillable = ['exam', 'ielts_test_id', 'celpip_test_id', 'topic_id', 'module', 'word', 'type', 'meaning', 'synonyms', 'example', 'translations', 'approved', 'source'];

    protected $casts = ['synonyms' => 'array', 'translations' => 'array', 'approved' => 'boolean'];

    /** Languages the AI translates into (code => name). */
    public const LANGUAGES = ['pa' => 'Punjabi', 'ur' => 'Urdu', 'ar' => 'Arabic', 'es' => 'Spanish', 'hi' => 'Hindi', 'zh' => 'Chinese', 'ru' => 'Russian', 'tl' => 'Filipino', 'vi' => 'Vietnamese', 'tr' => 'Turkish'];
}

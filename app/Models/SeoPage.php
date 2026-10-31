<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Search settings and text of one exam list page (Admin → SEO Pages). */
class SeoPage extends Model
{
    protected $fillable = ['exam_group', 'page', 'meta_title', 'meta_description', 'h1', 'intro', 'content'];
}

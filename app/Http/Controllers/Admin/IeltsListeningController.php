<?php

namespace App\Http\Controllers\Admin;

use App\Support\IeltsListening;

/** IELTS Listening in the admin panel – the same pages and forms as Reading (see IeltsReadingController), with 4 parts and one audio file. */
class IeltsListeningController extends IeltsReadingController
{
    protected string $module = 'listening';

    protected string $cls = IeltsListening::class;
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Files added from inside the text editor (blogs, lessons, pages, SEO text, authors):
 * pictures, audio, video and documents. The editor puts the returned address into the text.
 */
class EditorUploadController extends Controller
{
    private const KINDS = [
        'image'    => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
        'audio'    => ['mp3', 'wav', 'ogg', 'm4a', 'aac'],
        'video'    => ['mp4', 'webm', 'mov'],
        'document' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'zip'],
    ];

    public function __invoke(Request $request)
    {
        $all = array_merge(...array_values(self::KINDS));
        $request->validate(
            ['file' => ['required', 'file', 'mimes:' . implode(',', $all), 'max:51200']],
            [
                'file.mimes'    => 'This file type is not allowed. Use a picture, audio, video, PDF or Office document.',
                'file.max'      => 'The file must be smaller than 50 MB.',
                'file.uploaded' => 'The file could not be uploaded. It is probably bigger than your server allows (see upload_max_filesize in php.ini).',
            ]
        );

        $file = $request->file('file');
        $ext = strtolower($file->extension() ?: $file->getClientOriginalExtension());
        $kind = collect(self::KINDS)->search(fn ($exts) => in_array($ext, $exts, true)) ?: 'document';
        $dir = 'uploads/editor/' . date('Y-m');
        $name = Str::random(20) . '.' . $ext;
        $file->move(public_path($dir), $name);

        return response()->json([
            'location' => asset($dir . '/' . $name),
            'kind'     => $kind,
            'ext'      => $ext,
            'name'     => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
        ]);
    }
}

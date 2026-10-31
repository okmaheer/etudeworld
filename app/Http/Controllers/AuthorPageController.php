<?php

namespace App\Http\Controllers;

use App\Models\Author;

class AuthorPageController extends Controller
{
    /** Author page on the live website: /author/{slug} */
    public function show(Author $author)
    {
        $blogs = $author->blogs()->published()->latest('published_at')->paginate(9);

        return view('authors.show', compact('author', 'blogs'));
    }
}

<?php

namespace App\Http\Controllers;

use App\Form;
use App\Models\Page;
use Corcel\Model\Post;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WordPressController extends Controller
{
    const CLASS_VIEW_MAP = [
        Page::class => 'main.wordpress.page',
        Form::class => 'main.wordpress.form',
        Post::class => 'main.wordpress.post',
        'default' => 'main.wordpress.page'
    ];

    /**
     * Renders the homepage
     *
     * @return Response
     */
    public function homepage()
    {
        return view(self::CLASS_VIEW_MAP[Page::class])->with([
            'page' => Page::home()->first()
        ]);
    }

    /**
     * Renders the Privacy Policy
     *
     * @return Response
     */
    public function privacy()
    {
        return view(self::CLASS_VIEW_MAP[Page::class])->with([
            'page' => Page::privacyPolicy()->first()
        ]);
    }

    /**
     * Handles fallback routes
     *
     * @return Response
     */
    public function fallback(Request $request)
    {
        $slug = trim($request->path(), '/\\');

        $page = Page::slug($slug)->first();
        if (!$page) {
            $page = Page::slug('404')->first();
        }

        if (!$page) {
            throw new NotFoundHttpException();
        }

        return view(self::CLASS_VIEW_MAP['default'])->with(['page' => $page]);
    }
}

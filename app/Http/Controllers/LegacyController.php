<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** The old site's /page.php?id=N addresses (bookmarked, in search results and in old emails). */
class LegacyController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $id = (int) input_string($request->query('id'));
        $route = ((array) ga('legacy_pages'))[$id] ?? 'home';

        return redirect()->route($route, status: 301);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Faq;

class FaqController extends Controller
{
    public function index()
    {
        $faqs       = Faq::ordered()->get();
        $categories = Faq::categories();

        return view('faq', compact('faqs', 'categories'));
    }
}

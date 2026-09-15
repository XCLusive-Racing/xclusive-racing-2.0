<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    public function index(Request $request)
    {
        $categories = Faq::categories();
        $category   = $request->input('category');

        $faqs = Faq::ordered()
            ->when($category, fn ($q) => $q->where('category', $category))
            ->get();

        return view('admin.faqs.index', compact('faqs', 'categories', 'category'));
    }

    public function create()
    {
        $categories = Faq::categories();

        return view('admin.faqs.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'question'   => 'required|string|max:255',
            'answer'     => 'required|string',
            'category'   => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        Faq::create([
            'question'   => $request->input('question'),
            'answer'     => $request->input('answer'),
            'category'   => $request->input('category') ?: 'General',
            'sort_order' => $request->input('sort_order', 0),
        ]);

        return redirect()->route('admin.faqs.index')->with('success', 'FAQ added.');
    }

    public function edit(Faq $faq)
    {
        $categories = Faq::categories();

        return view('admin.faqs.edit', compact('faq', 'categories'));
    }

    public function update(Request $request, Faq $faq)
    {
        $request->validate([
            'question'   => 'required|string|max:255',
            'answer'     => 'required|string',
            'category'   => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $faq->update([
            'question'   => $request->input('question'),
            'answer'     => $request->input('answer'),
            'category'   => $request->input('category') ?: 'General',
            'sort_order' => $request->input('sort_order', 0),
        ]);

        return redirect()->route('admin.faqs.index')->with('success', 'FAQ updated.');
    }

    public function destroy(Faq $faq)
    {
        $faq->delete();

        return redirect()->route('admin.faqs.index')->with('success', 'FAQ deleted.');
    }
}

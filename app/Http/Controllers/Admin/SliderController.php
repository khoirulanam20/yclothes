<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Slider;
use App\Support\ModelSerializer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SliderController extends Controller
{
    public function index()
    {
        $sliders = Slider::orderBy('sort_order')->paginate(15);

        return Inertia::render('Admin/Sliders/Index', [
            'sliders' => ModelSerializer::paginated($sliders, [ModelSerializer::class, 'slider']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Sliders/Form');
    }

    public function store(Request $request)
    {
        $validated = $this->validateSlider($request);
        $validated['image'] = $request->file('image')->store('sliders', 'public');
        $validated['is_active'] = $request->boolean('is_active', true);

        Slider::create($validated);

        return redirect()->route('admin.sliders.index')->with('success', 'Slider berhasil ditambahkan.');
    }

    public function edit(Slider $slider)
    {
        return Inertia::render('Admin/Sliders/Form', [
            'slider' => ModelSerializer::slider($slider),
        ]);
    }

    public function update(Request $request, Slider $slider)
    {
        $validated = $this->validateSlider($request, false);

        if ($request->hasFile('image')) {
            Storage::disk('public')->delete($slider->image);
            $validated['image'] = $request->file('image')->store('sliders', 'public');
        } else {
            unset($validated['image']);
        }

        $validated['is_active'] = $request->boolean('is_active');

        $slider->update($validated);

        return redirect()->route('admin.sliders.index')->with('success', 'Slider berhasil diperbarui.');
    }

    public function destroy(Slider $slider)
    {
        Storage::disk('public')->delete($slider->image);
        $slider->delete();

        return redirect()->route('admin.sliders.index')->with('success', 'Slider berhasil dihapus.');
    }

    private function validateSlider(Request $request, bool $requireImage = true): array
    {
        return $request->validate([
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:500',
            'image' => ($requireImage ? 'required' : 'nullable').'|image|mimes:jpeg,png,jpg,webp|max:4096',
            'link_url' => 'nullable|string|max:500',
            'cta_label' => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);
    }
}

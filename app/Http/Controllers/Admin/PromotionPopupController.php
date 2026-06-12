<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PromotionPopup;
use App\Services\PromotionPopupService;
use App\Support\ModelSerializer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class PromotionPopupController extends Controller
{
    public function index()
    {
        $popups = PromotionPopup::orderByDesc('priority')->latest()->paginate(15);

        return Inertia::render('Admin/PromotionPopups/Index', [
            'popups' => ModelSerializer::paginated($popups, fn (PromotionPopup $p) => $this->serialize($p)),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/PromotionPopups/Form', [
            'pageOptions' => PromotionPopupService::PAGE_OPTIONS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        PromotionPopup::create($this->validated($request));

        return redirect()->route('admin.promotion-popups.index')->with('success', 'Pop up promosi berhasil ditambahkan.');
    }

    public function edit(PromotionPopup $promotionPopup)
    {
        return Inertia::render('Admin/PromotionPopups/Form', [
            'popup' => $this->serialize($promotionPopup),
            'pageOptions' => PromotionPopupService::PAGE_OPTIONS,
        ]);
    }

    public function update(Request $request, PromotionPopup $promotionPopup): RedirectResponse
    {
        $promotionPopup->update($this->validated($request, $promotionPopup));

        return redirect()->route('admin.promotion-popups.index')->with('success', 'Pop up promosi berhasil diubah.');
    }

    public function destroy(PromotionPopup $promotionPopup): RedirectResponse
    {
        if ($promotionPopup->image) {
            Storage::disk('public')->delete($promotionPopup->image);
        }
        $promotionPopup->delete();

        return redirect()->route('admin.promotion-popups.index')->with('success', 'Pop up promosi berhasil dihapus.');
    }

    private function serialize(PromotionPopup $popup): array
    {
        return [
            'id' => $popup->id,
            'title' => $popup->title,
            'imageUrl' => storage_url($popup->image),
            'buttonLabel' => $popup->button_label,
            'buttonUrl' => $popup->button_url,
            'displayDurationSeconds' => $popup->display_duration_seconds,
            'startDate' => $popup->start_date?->format('Y-m-d\TH:i'),
            'endDate' => $popup->end_date?->format('Y-m-d\TH:i'),
            'showOnPages' => $popup->show_on_pages ?? [],
            'isActive' => (bool) $popup->is_active,
            'priority' => $popup->priority,
        ];
    }

    private function validated(Request $request, ?PromotionPopup $popup = null): array
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'image' => ($popup ? 'nullable' : 'required').'|image|mimes:jpeg,png,jpg,webp|max:4096',
            'remove_image' => 'nullable|boolean',
            'button_label' => 'nullable|string|max:100',
            'button_url' => 'nullable|string|max:500',
            'display_duration_seconds' => 'nullable|integer|min:0|max:300',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'show_on_pages' => 'required|array|min:1',
            'show_on_pages.*' => 'string|in:'.implode(',', array_keys(PromotionPopupService::PAGE_OPTIONS)),
            'is_active' => 'nullable|boolean',
            'priority' => 'nullable|integer',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['priority'] = $validated['priority'] ?? 0;
        $validated['display_duration_seconds'] = $validated['display_duration_seconds'] ?? 0;

        if ($request->hasFile('image')) {
            if ($popup?->image) {
                Storage::disk('public')->delete($popup->image);
            }
            $validated['image'] = $request->file('image')->store('promotion-popups', 'public');
        } elseif ($request->boolean('remove_image') && $popup?->image) {
            Storage::disk('public')->delete($popup->image);
            $validated['image'] = null;
        } else {
            unset($validated['image'], $validated['remove_image']);
        }

        return $validated;
    }
}

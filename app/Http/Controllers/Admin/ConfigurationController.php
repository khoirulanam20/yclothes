<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminConfigurationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class ConfigurationController extends Controller
{
    public function __construct(private AdminConfigurationService $configuration) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Configuration/Index', [
            'categories' => $this->configuration->getCategories(),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        return response()->json(
            $this->configuration->search($request->string('q')->toString()),
        );
    }

    public function edit(string $slug): Response|RedirectResponse
    {
        $key = str_replace('/', '.', $slug);
        $section = $this->configuration->getSection($key);

        if ($section['type'] === 'link') {
            return redirect($section['href']);
        }

        return Inertia::render('Admin/Configuration/Edit', [
            'section' => $section,
            'appUrl' => config('app.url'),
        ]);
    }

    public function update(Request $request, string $slug): RedirectResponse
    {
        $key = str_replace('/', '.', $slug);
        $this->configuration->validateAndSave($key, $request);

        return redirect()
            ->route('admin.configuration.edit', ['slug' => $slug])
            ->with('success', 'Konfigurasi berhasil disimpan.');
    }

    public function testEmail(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email|max:255',
        ]);

        app(\App\Services\MailSettingsService::class)->apply();

        try {
            $appName = site_app_name();
            Mail::raw("Email test dari konfigurasi admin {$appName}.", function ($message) use ($request, $appName) {
                $message->to($request->input('email'))
                    ->subject("Test Email — {$appName}");
            });
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Gagal mengirim email test: '.$e->getMessage());
        }

        return back()->with('success', 'Email test telah dikirim.');
    }
}

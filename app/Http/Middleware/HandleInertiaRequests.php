<?php

namespace App\Http\Middleware;

use App\Models\Item;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'currentLocale' => $request->session()->get('locale', 'fr'),
            'availableLocales' => $this->availableLocales(),
            'appVersion' => config('app.version'),
        ];
    }

    /**
     * Récupère dynamiquement les locales réellement présentes en base.
     *
     * @return array<string, string>
     */
    private function availableLocales(): array
    {
        $labels = [
            'en' => 'English',
            'es' => 'Español',
            'de' => 'Deutsch',
            'fr' => 'Français',
            'it' => 'Italiano',
            'ja' => '日本語',
            'pt-br' => 'Português (Brasil)',
        ];

        return Item::select('locale')
            ->distinct()
            ->pluck('locale')
            ->mapWithKeys(fn ($locale) => [$locale => $labels[$locale] ?? $locale])
            ->sort()
            ->toArray();
    }
}
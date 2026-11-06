<?php

namespace App\Support\Dashboard\Blocks;

use App\Models\User;
use App\Support\Dashboard\Block;

class ProfileCompletion implements Block
{
    private const ITEMS = [
        'personal_data' => ['label' => 'Fill in your personal data', 'anchor' => 'personal-data'],
        'avatar' => ['label' => 'Add a profile picture', 'anchor' => 'avatar'],
        'contact' => ['label' => 'Add a contact', 'anchor' => 'contacts'],
        'emergency_contact' => ['label' => 'Add an emergency contact', 'anchor' => 'contacts'],
        'main_address' => ['label' => 'Set a main address', 'anchor' => 'addresses'],
    ];

    public function key(): string
    {
        return 'ProfileCompletion';
    }

    public function title(): string
    {
        return __('Your profile');
    }

    public function permission(): ?string
    {
        return null;
    }

    public function available(): bool
    {
        return true;
    }

    public function data(User $user): array
    {
        $items = array_intersect_key(self::ITEMS, array_flip(config('template.dashboard.profile_items')));
        $filled = $this->filled($user);
        $missing = array_diff_key($items, array_filter($filled));

        return [
            'percent' => $items === [] ? 100 : (int) round((count($items) - count($missing)) / count($items) * 100),
            'missing' => array_map(fn (string $key) => [
                'key' => $key,
                'label' => __($missing[$key]['label']),
                'url' => route('profile.edit').'#'.$missing[$key]['anchor'],
            ], array_keys($missing)),
        ];
    }

    /** @return array<string, bool> */
    private function filled(User $user): array
    {
        $data = $user->personalData;

        return [
            'personal_data' => filled($data?->birth_date) && filled($data?->gender) && filled($data?->cpf),
            'avatar' => filled($data?->avatar_path),
            'contact' => $user->contacts()->exists(),
            'emergency_contact' => $user->contacts()->where('is_emergency', true)->exists(),
            'main_address' => $user->addresses()->where('is_main', true)->exists(),
        ];
    }
}

<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class CartStore
{
    /**
     * @return array<int, int>
     */
    public function items(User $user): array
    {
        return $this->normalize(Cache::get($this->key($user), []));
    }

    /**
     * @return array<int, int>
     */
    public function add(User $user, int $productId): array
    {
        $items = $this->items($user);
        $items[$productId] = min(99, ($items[$productId] ?? 0) + 1);
        $this->put($user, $items);

        return $items;
    }

    /**
     * @return array<int, int>
     */
    public function setQty(User $user, int $productId, int $qty): array
    {
        $items = $this->items($user);

        if ($qty <= 0) {
            unset($items[$productId]);
        } else {
            $items[$productId] = min(99, $qty);
        }

        $this->put($user, $items);

        return $this->normalize($items);
    }

    /**
     * @return array<int, int>
     */
    public function remove(User $user, int $productId): array
    {
        $items = $this->items($user);
        unset($items[$productId]);
        $this->put($user, $items);

        return $this->normalize($items);
    }

    public function clear(User $user): void
    {
        Cache::forget($this->key($user));
    }

    /**
     * @param  array<int|string, int>  $items
     */
    private function put(User $user, array $items): void
    {
        $items = $this->normalize($items);

        if ($items === []) {
            Cache::forget($this->key($user));

            return;
        }

        Cache::put($this->key($user), $items, now()->addDays(45));
    }

    private function key(User $user): string
    {
        return 'claim-cart:'.$user->id;
    }

    /**
     * @param  array<int|string, mixed>  $items
     * @return array<int, int>
     */
    private function normalize(array $items): array
    {
        $normalized = [];

        foreach ($items as $productId => $qty) {
            $productId = (int) $productId;
            $qty = (int) $qty;

            if ($productId > 0 && $qty > 0) {
                $normalized[$productId] = min(99, $qty);
            }
        }

        return $normalized;
    }
}

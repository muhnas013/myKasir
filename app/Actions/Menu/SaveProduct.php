<?php

namespace App\Actions\Menu;

use App\Models\AuditLog;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SaveProduct
{
    /**
     * @param  array{
     *     category_id: int, sku: string, name: string, price: int, cost_price?: int,
     *     is_active: bool, track_stock: bool, stock_qty?: int, image_path?: ?string,
     *     variant_groups?: list<array{
     *         id?: ?int, name: string, is_required: bool, max_select: int,
     *         options: list<array{id?: ?int, name: string, price_delta: int}>
     *     }>
     * }  $data
     */
    public function handle(?Product $product, array $data, User $actor): Product
    {
        return DB::transaction(function () use ($product, $data, $actor) {
            $product ??= new Product;
            $oldPrice = $product->exists ? $product->price : null;

            $product->fill([
                'category_id' => $data['category_id'],
                'sku' => $data['sku'],
                'name' => $data['name'],
                'price' => $data['price'],
                'cost_price' => $data['cost_price'] ?? $product->cost_price ?? 0,
                'is_active' => $data['is_active'],
                'track_stock' => $data['track_stock'],
                'stock_qty' => $data['stock_qty'] ?? $product->stock_qty ?? 0,
                'image_path' => $data['image_path'] ?? $product->image_path,
            ])->save();

            if ($oldPrice !== null && $oldPrice !== $product->price) {
                AuditLog::create([
                    'user_id' => $actor->id,
                    'action' => 'product.price_changed',
                    'subject_type' => 'product',
                    'subject_id' => $product->id,
                    'old_values' => ['price' => $oldPrice],
                    'new_values' => ['price' => $product->price],
                    'ip' => request()->ip(),
                ]);
            }

            if (array_key_exists('variant_groups', $data)) {
                $this->syncVariants($product, $data['variant_groups']);
            }

            return $product;
        });
    }

    /**
     * Sinkron berdasarkan id agar resep (F4) yang menunjuk opsi tidak ikut terhapus.
     */
    private function syncVariants(Product $product, array $groups): void
    {
        $keptGroupIds = [];

        foreach ($groups as $groupData) {
            $group = isset($groupData['id'])
                ? $product->variantGroups()->findOrFail($groupData['id'])
                : $product->variantGroups()->make();

            $group->fill([
                'name' => $groupData['name'],
                'is_required' => $groupData['is_required'],
                'max_select' => $groupData['max_select'],
            ])->save();
            $keptGroupIds[] = $group->id;

            $keptOptionIds = [];
            foreach ($groupData['options'] as $optionData) {
                $option = isset($optionData['id'])
                    ? $group->options()->findOrFail($optionData['id'])
                    : $group->options()->make();

                $option->fill([
                    'name' => $optionData['name'],
                    'price_delta' => $optionData['price_delta'],
                ])->save();
                $keptOptionIds[] = $option->id;
            }

            $group->options()->whereNotIn('id', $keptOptionIds)->delete();
        }

        $product->variantGroups()->whereNotIn('id', $keptGroupIds)->delete();
    }
}

<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\ClaimPeriod;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => config('claimly.employee_email')],
            [
                'name' => 'Aeris S.',
                'team' => 'Marketing Team',
                'initials' => 'AS',
                'employment_type' => 'tetap',
                'company' => 'PT Citra Pasifik Kosmetik',
                'is_admin' => true,
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'dina@claimly.test'],
            [
                'name' => 'Dina K.',
                'team' => 'Operations',
                'initials' => 'DK',
                'employment_type' => 'kontrak',
                'company' => 'PT Kinerja Inovasi Nusantara',
                'is_admin' => false,
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );

        foreach ($this->brands() as $brandIndex => $brandData) {
            $brand = Brand::query()->updateOrCreate(
                ['name' => $brandData['name']],
                [
                    'short' => $brandData['short'],
                    'subtitle' => $brandData['subtitle'],
                    'discount_percent' => $brandData['discount_percent'],
                    'shipping_fee' => 20000,
                    'company_name' => $brandData['company_name'],
                    'bank_account_name' => $brandData['company_name'],
                    'sort_order' => $brandIndex,
                ],
            );

            $categoryIds = [];

            foreach ($brandData['categories'] as $categoryIndex => $categoryName) {
                $category = Category::query()->updateOrCreate(
                    ['brand_id' => $brand->id, 'name' => $categoryName],
                    ['sort_order' => $categoryIndex],
                );
                $categoryIds[$categoryName] = $category->id;
            }

            foreach ($brandData['products'] as $productIndex => [$name, $categoryName, $price, $image]) {
                Product::query()->updateOrCreate(
                    ['brand_id' => $brand->id, 'name' => $name],
                    [
                        'category_id' => $categoryIds[$categoryName],
                        'retail_price' => $price,
                        'image_url' => $image,
                        'is_active' => true,
                        'sort_order' => $productIndex,
                    ],
                );
            }
        }

        ClaimPeriod::query()->updateOrCreate(
            ['name' => 'Periode Oktober 2026'],
            [
                'starts_on' => '2026-10-01',
                'ends_on' => '2026-10-31',
            ],
        );

        Cache::forget('catalog.v2');
    }

    /**
     * @return array<int, array{name: string, short: string, subtitle: string, discount_percent: int, company_name: string, categories: array<int, string>, products: array<int, array{0: string, 1: string, 2: int, 3: string}>}>
     */
    private function brands(): array
    {
        return [
            [
                'name' => 'Aeris Beauté',
                'short' => 'AB',
                'subtitle' => 'Beauty tools & makeup',
                'discount_percent' => 40,
                'company_name' => 'PT Citra Pasifik Kosmetik',
                'categories' => ['Puffs', 'Brush Series', 'Coal Series', 'Ombré Series', 'Brush Set', 'Cleansers'],
                'products' => [
                    ['Cloud Control Puff', 'Puffs', 69000, $this->image('photo-1596462502278-27bfdc403348')],
                    ['Velvet King Powder Puff', 'Puffs', 89000, $this->image('photo-1522335789203-aabd1fc54bc9')],
                    ['Aeris Beauté 8 Brush Set', 'Brush Set', 499000, $this->image('photo-1583241800698-e8ab01830a07')],
                    ['Aeris Beauté x Nanda Arsyinta Brush', 'Brush Series', 179000, $this->image('photo-1631214524020-7e18db9a8f92')],
                    ['The Coal Travel Brush Set', 'Coal Series', 349000, $this->image('photo-1596704017254-9b121068fb31')],
                    ['The Ombré 5-Piece Brush Set', 'Ombré Series', 279000, $this->image('photo-1526045478516-99145907023c')],
                    ['Brush & Sponge Solid Cleanser', 'Cleansers', 89000, $this->image('photo-1556228578-8c89e6adf883')],
                    ['The Signature Eye Brush Set', 'Brush Set', 299000, $this->image('photo-1515688594390-b649af70d282')],
                    ['Ash Black Brush No. 8', 'Brush Series', 139000, $this->image('photo-1619451334792-150fd785ee74')],
                ],
            ],
            [
                'name' => 'From This Island',
                'short' => 'FTI',
                'subtitle' => 'Skincare & makeup',
                'discount_percent' => 30,
                'company_name' => 'PT Inovasi Alam Nusantara',
                'categories' => ['Lip Care', 'Toner', 'Moisturizer', 'Serum', 'Cleanser'],
                'products' => [
                    ['Illipe Plumping Lip Butter — Classic Nude', 'Lip Care', 149000, $this->image('photo-1586495777744-4413f21062fa')],
                    ['Illipe Plumping Lip Butter — Pink Sugar', 'Lip Care', 149000, $this->image('photo-1625093742435-6fa192b6fb10')],
                    ['Sugarcane AHA Exfoliating Solution', 'Toner', 170000, $this->image('photo-1608248543803-ba4f8c70ae0b')],
                    ['Tropical Guava Barrier Moisture Gel', 'Moisturizer', 219000, $this->image('photo-1620916566398-39f1143ab7be')],
                    ['Mangosteen Brightening Serum', 'Serum', 195000, $this->image('photo-1624454002302-36b824d7bd0a')],
                    ['Java Black Tea Soothing Gel Cleanser', 'Cleanser', 159000, $this->image('photo-1556229010-6c3f2c9ca5f8')],
                    ['Papua Red Fruit Plumping Cream', 'Moisturizer', 259000, $this->image('photo-1571781926291-c477ebfd024b')],
                    ['Illipe Plumping Lip Butter — Sweet Peach', 'Lip Care', 149000, $this->image('photo-1631730486784-5456119f69c7')],
                ],
            ],
            [
                'name' => 'KIN + ALLY',
                'short' => 'K+A',
                'subtitle' => 'Activewear',
                'discount_percent' => 30,
                'company_name' => 'PT Kinerja Inovasi Nusantara',
                'categories' => ['Poppy', 'Azalea', 'Legging Full', 'Legging 7/8', 'Capri', 'Run Short'],
                'products' => [
                    ['Poppy Racerback Bra — Burgundy', 'Poppy', 299000, $this->image('photo-1517836357463-d25dfeac3438')],
                    ['Azalea Active Bra — Terracotta', 'Azalea', 259000, $this->image('photo-1506629082955-511b1aa562c8')],
                    ['Euphoric Compression Legging', 'Legging Full', 499000, $this->image('photo-1538805060514-97d9cc17730c')],
                    ['Defy 7/8 Performance Legging', 'Legging 7/8', 449000, $this->image('photo-1609899537878-88d5ba429bdb')],
                    ['Odette Compression Capri — Maroon', 'Capri', 389000, $this->image('photo-1599058917212-d750089bc07e')],
                    ['Move Compression Run Short', 'Run Short', 329000, $this->image('photo-1566241142559-40e1dab266c6')],
                    ['Poppy Racerback Bra — Black', 'Poppy', 299000, $this->image('photo-1571731956672-f2b94d7dd0cb')],
                    ['Azalea Active Bra — Olive', 'Azalea', 259000, $this->image('photo-1560089000-7433a4ebbd64')],
                ],
            ],
        ];
    }

    private function image(string $photo): string
    {
        return "https://images.unsplash.com/{$photo}?auto=format&fit=crop&w=700&q=80";
    }
}

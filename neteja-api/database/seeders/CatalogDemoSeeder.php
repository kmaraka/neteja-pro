<?php

namespace Database\Seeders;

use App\Modules\Ecommerce\Models\Category;
use App\Modules\Ecommerce\Models\Product;
use App\Modules\Ecommerce\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CatalogDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Catégorie => produit => [variante, prix FCFA, stock]
        $catalog = [
            'Entretien ménager' => [
                'Détergent multi-surfaces' => [['1 L', 1500, 40], ['5 L', 6500, 12]],
                'Eau de Javel' => [['1 L', 800, 60], ['5 L', 3500, 3]],
                'Liquide vaisselle' => [['500 mL', 1200, 25], ['1 L', 2000, 0]],
            ],
            'Matériel de nettoyage' => [
                'Balai-brosse' => [['Standard', 3000, 15]],
                'Seau avec essoreur' => [['Standard', 8500, 6]],
                'Lot de 5 microfibres' => [['Bleu', 4500, 20], ['Jaune', 4500, 8]],
            ],
            'Jardinage' => [
                'Sécateur' => [['Standard', 7500, 10]],
                'Arrosoir 10 L' => [['Standard', 4000, 14]],
            ],
        ];

        $position = 0;

        foreach ($catalog as $categoryName => $products) {
            $category = Category::updateOrCreate(
                ['slug' => Str::slug($categoryName)],
                ['name' => $categoryName, 'sort_order' => $position++, 'is_active' => true],
            );

            foreach ($products as $productName => $variants) {
                $product = Product::updateOrCreate(
                    ['slug' => Str::slug($productName)],
                    [
                        'category_id' => $category->id,
                        'name' => $productName,
                        'description' => "Données de démonstration : {$productName}.",
                        'is_active' => true,
                    ],
                );

                foreach ($variants as [$variantName, $price, $stock]) {
                    ProductVariant::updateOrCreate(
                        ['product_id' => $product->id, 'name' => $variantName],
                        ['price_fcfa' => $price, 'stock' => $stock, 'is_active' => true],
                    );
                }
            }
        }
    }
}

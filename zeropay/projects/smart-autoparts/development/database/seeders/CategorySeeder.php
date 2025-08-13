<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => ['ar' => 'المحرك', 'en' => 'Engine'],
                'icon' => 'fas fa-cogs',
                'children' => [
                    ['name' => ['ar' => 'فلاتر', 'en' => 'Filters']],
                    ['name' => ['ar' => 'زيوت', 'en' => 'Oils']],
                    ['name' => ['ar' => 'أحزمة', 'en' => 'Belts']],
                    ['name' => ['ar' => 'شمعات الإشعال', 'en' => 'Spark Plugs']],
                    ['name' => ['ar' => 'نظام التبريد', 'en' => 'Cooling System']],
                ]
            ],
            [
                'name' => ['ar' => 'الفرامل', 'en' => 'Brakes'],
                'icon' => 'fas fa-compact-disc',
                'children' => [
                    ['name' => ['ar' => 'تيل فرامل', 'en' => 'Brake Pads']],
                    ['name' => ['ar' => 'أقراص فرامل', 'en' => 'Brake Discs']],
                    ['name' => ['ar' => 'زيت فرامل', 'en' => 'Brake Fluid']],
                    ['name' => ['ar' => 'خراطيم فرامل', 'en' => 'Brake Hoses']],
                ]
            ],
            [
                'name' => ['ar' => 'التعليق والتوجيه', 'en' => 'Suspension & Steering'],
                'icon' => 'fas fa-car',
                'children' => [
                    ['name' => ['ar' => 'ممتصات الصدمات', 'en' => 'Shock Absorbers']],
                    ['name' => ['ar' => 'مقصات', 'en' => 'Control Arms']],
                    ['name' => ['ar' => 'روابط', 'en' => 'Links']],
                    ['name' => ['ar' => 'كراسي محور', 'en' => 'Bushings']],
                ]
            ],
            [
                'name' => ['ar' => 'الإطارات والجنوط', 'en' => 'Tires & Wheels'],
                'icon' => 'fas fa-circle',
                'children' => [
                    ['name' => ['ar' => 'إطارات', 'en' => 'Tires']],
                    ['name' => ['ar' => 'جنوط', 'en' => 'Rims']],
                    ['name' => ['ar' => 'صواميل', 'en' => 'Wheel Nuts']],
                    ['name' => ['ar' => 'أغطية صمامات', 'en' => 'Valve Caps']],
                ]
            ],
            [
                'name' => ['ar' => 'البطارية والكهرباء', 'en' => 'Battery & Electrical'],
                'icon' => 'fas fa-battery-full',
                'children' => [
                    ['name' => ['ar' => 'بطاريات', 'en' => 'Batteries']],
                    ['name' => ['ar' => 'مولدات', 'en' => 'Alternators']],
                    ['name' => ['ar' => 'بادئ الحركة', 'en' => 'Starters']],
                    ['name' => ['ar' => 'أسلاك', 'en' => 'Wires']],
                ]
            ],
            [
                'name' => ['ar' => 'نظام العادم', 'en' => 'Exhaust System'],
                'icon' => 'fas fa-cloud',
                'children' => [
                    ['name' => ['ar' => 'كاتم صوت', 'en' => 'Mufflers']],
                    ['name' => ['ar' => 'أنابيب عادم', 'en' => 'Exhaust Pipes']],
                    ['name' => ['ar' => 'محول حفاز', 'en' => 'Catalytic Converter']],
                ]
            ],
            [
                'name' => ['ar' => 'التكييف', 'en' => 'Air Conditioning'],
                'icon' => 'fas fa-snowflake',
                'children' => [
                    ['name' => ['ar' => 'ضاغط مكيف', 'en' => 'AC Compressor']],
                    ['name' => ['ar' => 'مبخر', 'en' => 'Evaporator']],
                    ['name' => ['ar' => 'مكثف', 'en' => 'Condenser']],
                    ['name' => ['ar' => 'غاز تبريد', 'en' => 'Refrigerant']],
                ]
            ],
            [
                'name' => ['ar' => 'الإضاءة', 'en' => 'Lighting'],
                'icon' => 'fas fa-lightbulb',
                'children' => [
                    ['name' => ['ar' => 'مصابيح أمامية', 'en' => 'Headlights']],
                    ['name' => ['ar' => 'مصابيح خلفية', 'en' => 'Tail Lights']],
                    ['name' => ['ar' => 'مصابيح ضباب', 'en' => 'Fog Lights']],
                    ['name' => ['ar' => 'لمبات', 'en' => 'Bulbs']],
                ]
            ],
        ];

        foreach ($categories as $index => $categoryData) {
            $category = Category::create([
                'name' => $categoryData['name'],
                'slug' => Str::slug($categoryData['name']['en']),
                'icon' => $categoryData['icon'],
                'sort_order' => $index + 1,
                'is_active' => true,
                'is_featured' => $index < 4, // First 4 categories are featured
            ]);

            // Create subcategories
            if (isset($categoryData['children'])) {
                foreach ($categoryData['children'] as $subIndex => $childData) {
                    Category::create([
                        'name' => $childData['name'],
                        'slug' => Str::slug($childData['name']['en']),
                        'parent_id' => $category->id,
                        'sort_order' => $subIndex + 1,
                        'is_active' => true,
                    ]);
                }
            }
        }
    }
}
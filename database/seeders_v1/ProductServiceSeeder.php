<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductServiceSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::parse('2026-05-25 08:00:00');

        DB::table('category_product')->insert([
            ['product_category_id' => 1, 'product_category_name' => 'Sữa tắm', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['product_category_id' => 2, 'product_category_name' => 'Vật tư vệ sinh', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['product_category_id' => 3, 'product_category_name' => 'Vật tư chăm sóc lông', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['product_category_id' => 4, 'product_category_name' => 'Vật tư y tế', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('product')->insert([
            ['product_id' => 1, 'product_category_id' => 1, 'product_image_url' => 'products/dog-shampoo.jpg', 'product_name' => 'Sữa tắm cho chó', 'unit' => 'ml', 'item_price' => 1200, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['product_id' => 2, 'product_category_id' => 3, 'product_image_url' => 'products/conditioner.jpg', 'product_name' => 'Dầu xả lông', 'unit' => 'ml', 'item_price' => 1500, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['product_id' => 3, 'product_category_id' => 2, 'product_image_url' => 'products/towel.jpg', 'product_name' => 'Khăn vệ sinh', 'unit' => 'gram', 'item_price' => 200, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['product_id' => 4, 'product_category_id' => 2, 'product_image_url' => 'products/ear-cleaner.jpg', 'product_name' => 'Dung dịch vệ sinh tai', 'unit' => 'ml', 'item_price' => 1800, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['product_id' => 5, 'product_category_id' => 3, 'product_image_url' => 'products/nail-kit.jpg', 'product_name' => 'Vật tư cắt móng', 'unit' => 'bộ', 'item_price' => 35000, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['product_id' => 6, 'product_category_id' => 4, 'product_image_url' => 'products/gloves.jpg', 'product_name' => 'Găng tay kiểm tra sức khỏe', 'unit' => 'cái', 'item_price' => 2500, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['product_id' => 7, 'product_category_id' => 2, 'product_image_url' => 'products/pet-food.jpg', 'product_name' => 'Thức ăn hạt thường', 'unit' => 'gram', 'item_price' => 300, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('category_services')->insert([
            ['service_category_id' => 1, 'service_category_name' => 'Tắm thú cưng', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['service_category_id' => 2, 'service_category_name' => 'Chăm sóc lông', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['service_category_id' => 3, 'service_category_name' => 'Kiểm tra sức khỏe', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['service_category_id' => 4, 'service_category_name' => 'Dịch vụ bổ sung', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('services')->insert([
            ['service_id' => 1, 'service_category_id' => 1, 'service_name' => 'Tắm thú cưng', 'species' => 'DOG', 'description_sv' => 'Tắm sấy theo cân nặng, gồm sữa tắm, dầu xả và khăn vệ sinh.', 'base_price' => 100000, 'duration_minutes' => 60, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['service_id' => 2, 'service_category_id' => 2, 'service_name' => 'Grooming toàn diện', 'species' => 'DOG', 'description_sv' => 'Cắt tỉa lông, vệ sinh tai và chăm sóc lông toàn diện.', 'base_price' => 250000, 'duration_minutes' => 120, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['service_id' => 3, 'service_category_id' => 3, 'service_name' => 'Kiểm tra sức khỏe cơ bản', 'species' => 'ALL', 'description_sv' => 'Kiểm tra nhanh tình trạng sức khỏe trước khi lưu trú.', 'base_price' => 80000, 'duration_minutes' => 30, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['service_id' => 4, 'service_category_id' => 4, 'service_name' => 'Cắt móng', 'species' => 'ALL', 'description_sv' => 'Cắt móng và chăm sóc bàn chân cho thú cưng.', 'base_price' => 50000, 'duration_minutes' => 25, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('service_product_detail')->insert([
            ['service_product_detail_id' => 1, 'service_id' => 1, 'product_id' => 1, 'amount' => 100.00, 'notes' => 'Sữa tắm mặc định cho một lần tắm.', 'created_at' => $now, 'updated_at' => $now],
            ['service_product_detail_id' => 2, 'service_id' => 1, 'product_id' => 2, 'amount' => 40.00, 'notes' => 'Dầu xả lông sau khi tắm.', 'created_at' => $now, 'updated_at' => $now],
            ['service_product_detail_id' => 3, 'service_id' => 1, 'product_id' => 3, 'amount' => 200.00, 'notes' => 'Khăn vệ sinh tính theo gram vật tư.', 'created_at' => $now, 'updated_at' => $now],
            ['service_product_detail_id' => 4, 'service_id' => 2, 'product_id' => 2, 'amount' => 80.00, 'notes' => 'Dầu xả dùng trong gói grooming.', 'created_at' => $now, 'updated_at' => $now],
            ['service_product_detail_id' => 5, 'service_id' => 2, 'product_id' => 4, 'amount' => 30.00, 'notes' => 'Dung dịch vệ sinh tai.', 'created_at' => $now, 'updated_at' => $now],
            ['service_product_detail_id' => 6, 'service_id' => 3, 'product_id' => 6, 'amount' => 2.00, 'notes' => 'Găng tay cho một lượt kiểm tra.', 'created_at' => $now, 'updated_at' => $now],
            ['service_product_detail_id' => 7, 'service_id' => 4, 'product_id' => 5, 'amount' => 1.00, 'notes' => 'Vật tư cắt móng cho một lượt dịch vụ.', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}
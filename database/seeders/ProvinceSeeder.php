<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Province;
use App\Models\Ward;
use Illuminate\Support\Facades\Http;

class ProvinceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Bắt đầu load dữ liệu từ API 34tinhthanh.com...');

        // Lấy danh sách tỉnh/thành phố
        try {
            $response = Http::get('https://34tinhthanh.com/api/provinces');
            
            if (!$response->successful()) {
                $this->command->error('Không thể lấy dữ liệu từ API. Status: ' . $response->status());
                return;
            }

            $provinces = $response->json();

            if (empty($provinces)) {
                $this->command->error('API trả về dữ liệu rỗng');
                return;
            }

            $this->command->info('Đã lấy được ' . count($provinces) . ' tỉnh/thành phố');

            // Lưu tỉnh/thành phố vào database
            foreach ($provinces as $provinceData) {
                Province::updateOrCreate(
                    ['province_code' => $provinceData['province_code']],
                    ['name' => $provinceData['name']]
                );
            }

            $this->command->info('Đã lưu ' . count($provinces) . ' tỉnh/thành phố vào database');

            // Lấy danh sách phường/xã cho từng tỉnh/thành phố
            $totalWards = 0;
            foreach ($provinces as $index => $provinceData) {
                $provinceCode = $provinceData['province_code'];
                $provinceName = $provinceData['name'];
                
                $currentIndex = $index + 1;
                $totalProvinces = count($provinces);
                $this->command->info("Đang load phường/xã cho {$provinceName} ({$currentIndex}/{$totalProvinces})...");

                try {
                    $wardsResponse = Http::get("https://34tinhthanh.com/api/wards", [
                        'province_code' => $provinceCode
                    ]);

                    if ($wardsResponse->successful()) {
                        $wards = $wardsResponse->json();
                        
                        if (!empty($wards)) {
                            foreach ($wards as $wardData) {
                                Ward::updateOrCreate(
                                    ['ward_code' => $wardData['ward_code']],
                                    [
                                        'ward_name' => $wardData['ward_name'],
                                        'province_code' => $wardData['province_code'],
                                    ]
                                );
                                $totalWards++;
                            }
                            $this->command->info("  → Đã lưu " . count($wards) . " phường/xã cho {$provinceName}");
                        }
                    } else {
                        $this->command->warn("  → Không thể lấy phường/xã cho {$provinceName}. Status: " . $wardsResponse->status());
                    }

                    // Nghỉ 0.5 giây giữa các request để tránh rate limit
                    usleep(500000); // 500ms

                } catch (\Exception $e) {
                    $this->command->error("  → Lỗi khi load phường/xã cho {$provinceName}: " . $e->getMessage());
                }
            }

            $this->command->info("Hoàn thành! Đã lưu {$totalWards} phường/xã vào database");

        } catch (\Exception $e) {
            $this->command->error('Lỗi khi load dữ liệu: ' . $e->getMessage());
        }
    }
}

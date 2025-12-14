<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use App\Models\Province;
use App\Models\Ward;
use Illuminate\Support\Facades\Log;

class ProvinceWardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Bắt đầu load dữ liệu từ API 34tinhthanh.com...');

        try {
            // Không xóa dữ liệu cũ, chỉ update hoặc create mới
            $this->command->info('Đang load dữ liệu từ API...');

            // 1. Load danh sách tỉnh/thành phố
            $this->command->info('Đang load danh sách tỉnh/thành phố...');
            $response = Http::timeout(30)->get('https://34tinhthanh.com/api/provinces');

            if (!$response->successful()) {
                throw new \Exception('Không thể kết nối đến API. Status: ' . $response->status());
            }

            $provinces = $response->json();

            if (empty($provinces)) {
                throw new \Exception('API không trả về dữ liệu tỉnh/thành phố.');
            }

            $this->command->info('Tìm thấy ' . count($provinces) . ' tỉnh/thành phố.');

            // 2. Lưu provinces vào database
            foreach ($provinces as $provinceData) {
                Province::updateOrCreate(
                    ['province_code' => $provinceData['province_code']],
                    ['name' => $provinceData['name']]
                );
            }

            $this->command->info('Đã lưu ' . count($provinces) . ' tỉnh/thành phố vào database.');

            // 3. Load danh sách phường/xã cho từng tỉnh/thành phố
            $totalWards = 0;
            $provinceCount = 0;

            foreach ($provinces as $provinceData) {
                $provinceCount++;
                $provinceCode = $provinceData['province_code'];
                $provinceName = $provinceData['name'];

                $this->command->info("[$provinceCount/" . count($provinces) . "] Đang load phường/xã cho: $provinceName ($provinceCode)...");

                try {
                    $wardsResponse = Http::timeout(30)->get('https://34tinhthanh.com/api/wards', [
                        'province_code' => $provinceCode
                    ]);

                    if ($wardsResponse->successful()) {
                        $wards = $wardsResponse->json();

                        if (!empty($wards)) {
                            $wardCount = 0;
                            foreach ($wards as $wardData) {
                                Ward::updateOrCreate(
                                    ['ward_code' => $wardData['ward_code']],
                                    [
                                        'ward_name' => $wardData['ward_name'] ?? $wardData['name'] ?? '',
                                        'province_code' => $provinceCode,
                                    ]
                                );
                                $wardCount++;
                                $totalWards++;
                            }
                            $this->command->info("  → Đã lưu $wardCount phường/xã cho $provinceName");
                        } else {
                            $this->command->warn("  → Không tìm thấy phường/xã cho $provinceName");
                        }
                    } else {
                        $this->command->warn("  → Lỗi khi load phường/xã cho $provinceName. Status: " . $wardsResponse->status());
                    }
                } catch (\Exception $e) {
                    $this->command->error("  → Lỗi khi load phường/xã cho $provinceName: " . $e->getMessage());
                    Log::error("Error loading wards for province $provinceCode: " . $e->getMessage());
                }

                // Nghỉ 0.5 giây giữa các request để tránh quá tải API
                usleep(500000);
            }

            $this->command->info('');
            $this->command->info('✅ Hoàn tất!');
            $this->command->info("  - Tỉnh/Thành phố: " . count($provinces));
            $this->command->info("  - Phường/Xã: $totalWards");

        } catch (\Exception $e) {
            $this->command->error('❌ Lỗi: ' . $e->getMessage());
            Log::error('ProvinceWardSeeder error: ' . $e->getMessage());
            throw $e;
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\ShippingCost;
use Illuminate\Database\Seeder;

class ShippingCostSeeder extends Seeder
{
    /** @var list<array{code: string, name: string}> */
    private array $couriers = [
        ['code' => 'jne', 'name' => 'JNE'],
        ['code' => 'jnt', 'name' => 'J&T Express'],
        ['code' => 'sicepat', 'name' => 'SiCepat'],
        ['code' => 'anteraja', 'name' => 'AnterAja'],
        ['code' => 'ninja', 'name' => 'Ninja Xpress'],
    ];

    /**
     * Kota besar Indonesia (kode Kemendagri 2025).
     *
     * @var list<array{province_code: string, province_name: string, regency_code: string, regency_name: string, base_cost: int}>
     */
    private array $cities = [
        ['province_code' => '31', 'province_name' => 'DKI Jakarta', 'regency_code' => '31.71', 'regency_name' => 'Kota Jakarta Pusat', 'base_cost' => 12000],
        ['province_code' => '31', 'province_name' => 'DKI Jakarta', 'regency_code' => '31.74', 'regency_name' => 'Kota Jakarta Selatan', 'base_cost' => 12000],
        ['province_code' => '31', 'province_name' => 'DKI Jakarta', 'regency_code' => '31.73', 'regency_name' => 'Kota Jakarta Barat', 'base_cost' => 12000],
        ['province_code' => '31', 'province_name' => 'DKI Jakarta', 'regency_code' => '31.75', 'regency_name' => 'Kota Jakarta Timur', 'base_cost' => 12000],
        ['province_code' => '31', 'province_name' => 'DKI Jakarta', 'regency_code' => '31.72', 'regency_name' => 'Kota Jakarta Utara', 'base_cost' => 12000],
        ['province_code' => '32', 'province_name' => 'Jawa Barat', 'regency_code' => '32.73', 'regency_name' => 'Kota Bandung', 'base_cost' => 15000],
        ['province_code' => '32', 'province_name' => 'Jawa Barat', 'regency_code' => '32.75', 'regency_name' => 'Kota Bekasi', 'base_cost' => 14000],
        ['province_code' => '32', 'province_name' => 'Jawa Barat', 'regency_code' => '32.76', 'regency_name' => 'Kota Depok', 'base_cost' => 14000],
        ['province_code' => '32', 'province_name' => 'Jawa Barat', 'regency_code' => '32.71', 'regency_name' => 'Kota Bogor', 'base_cost' => 14000],
        ['province_code' => '36', 'province_name' => 'Banten', 'regency_code' => '36.71', 'regency_name' => 'Kota Tangerang', 'base_cost' => 14000],
        ['province_code' => '36', 'province_name' => 'Banten', 'regency_code' => '36.72', 'regency_name' => 'Kota Tangerang Selatan', 'base_cost' => 14000],
        ['province_code' => '35', 'province_name' => 'Jawa Timur', 'regency_code' => '35.78', 'regency_name' => 'Kota Surabaya', 'base_cost' => 18000],
        ['province_code' => '35', 'province_name' => 'Jawa Timur', 'regency_code' => '35.77', 'regency_name' => 'Kota Malang', 'base_cost' => 19000],
        ['province_code' => '33', 'province_name' => 'Jawa Tengah', 'regency_code' => '33.74', 'regency_name' => 'Kota Semarang', 'base_cost' => 16000],
        ['province_code' => '33', 'province_name' => 'Jawa Tengah', 'regency_code' => '33.73', 'regency_name' => 'Kabupaten Temanggung', 'base_cost' => 15000],
        ['province_code' => '34', 'province_name' => 'DI Yogyakarta', 'regency_code' => '34.71', 'regency_name' => 'Kota Yogyakarta', 'base_cost' => 16000],
        ['province_code' => '12', 'province_name' => 'Sumatera Utara', 'regency_code' => '12.71', 'regency_name' => 'Kota Medan', 'base_cost' => 28000],
        ['province_code' => '16', 'province_name' => 'Sumatera Selatan', 'regency_code' => '16.71', 'regency_name' => 'Kota Palembang', 'base_cost' => 26000],
        ['province_code' => '73', 'province_name' => 'Sulawesi Selatan', 'regency_code' => '73.71', 'regency_name' => 'Kota Makassar', 'base_cost' => 32000],
        ['province_code' => '51', 'province_name' => 'Bali', 'regency_code' => '51.71', 'regency_name' => 'Kota Denpasar', 'base_cost' => 28000],
        ['province_code' => '64', 'province_name' => 'Kalimantan Timur', 'regency_code' => '64.71', 'regency_name' => 'Kota Balikpapan', 'base_cost' => 30000],
        ['province_code' => '61', 'province_name' => 'Kalimantan Barat', 'regency_code' => '61.71', 'regency_name' => 'Kota Pontianak', 'base_cost' => 30000],
    ];

    /** @var array<string, int> */
    private array $courierCostAdjust = [
        'jne' => 0,
        'jnt' => -1000,
        'sicepat' => -2000,
        'anteraja' => -1500,
        'ninja' => -500,
    ];

    public function run(): void
    {
        foreach ($this->cities as $city) {
            foreach ($this->couriers as $courier) {
                $adjust = $this->courierCostAdjust[$courier['code']] ?? 0;
                $cost = max(8000, $city['base_cost'] + $adjust);

                ShippingCost::updateOrCreate(
                    [
                        'courier_code' => $courier['code'],
                        'regency_code' => $city['regency_code'],
                    ],
                    [
                        'courier_name' => $courier['name'],
                        'province_code' => $city['province_code'],
                        'province_name' => $city['province_name'],
                        'regency_name' => $city['regency_name'],
                        'city_name' => $city['regency_name'],
                        'cost' => $cost,
                        'cost_per_kg' => (int) round($cost * 0.35),
                        'is_active' => true,
                    ],
                );
            }
        }
    }
}

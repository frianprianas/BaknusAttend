<?php

namespace Database\Seeders;

use App\Models\BluetoothDevice;
use Illuminate\Database\Seeder;

class BluetoothDeviceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        BluetoothDevice::firstOrCreate(
            ['device_id' => 'WEMOS_GERBANG_01'],
            [
                'device_name'   => 'Gerbang Depan',
                'secret_key'    => 'kunciRahasiaBaknus2026!#',
                'radius_meters' => 50,
                'is_active'     => true,
            ]
        );
    }
}

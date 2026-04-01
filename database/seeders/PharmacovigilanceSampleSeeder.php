<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Medication;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Seeder;

class PharmacovigilanceSampleSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            Customer::factory()->create([
                'name' => 'Ana Maria Costa',
                'email' => 'ana.costa@example.com',
                'phone' => '+351912345678',
            ]),
            Customer::factory()->create([
                'name' => 'Bruno Ferreira Silva',
                'email' => 'bruno.ferreira@example.com',
                'phone' => '+351923456789',
            ]),
            Customer::factory()->create([
                'name' => 'Carla Rodrigues',
                'email' => 'carla.rodrigues@example.com',
                'phone' => '+351934567890',
            ]),
            Customer::factory()->create([
                'name' => 'Diogo Almeida',
                'email' => 'diogo.almeida@example.com',
                'phone' => null,
            ]),
        ];

        $metforminLot951357 = Medication::factory()->create([
            'name' => 'Metformin Hydrochloride 500mg film-coated tablets',
            'lot_number' => '951357',
        ]);

        $atorvastatin = Medication::factory()->create([
            'name' => 'Atorvastatin 20mg tablets',
            'lot_number' => '882201',
        ]);

        $ordersSpec = [
            [
                'customer' => $customers[0],
                'purchase_date' => '2025-11-12',
                'medications' => [$metforminLot951357],
            ],
            [
                'customer' => $customers[1],
                'purchase_date' => '2025-10-28',
                'medications' => [$atorvastatin],
            ],
            [
                'customer' => $customers[2],
                'purchase_date' => '2025-12-03',
                'medications' => [$metforminLot951357, $atorvastatin],
            ],
            [
                'customer' => $customers[0],
                'purchase_date' => '2026-01-08',
                'medications' => [$atorvastatin],
            ],
            [
                'customer' => $customers[3],
                'purchase_date' => '2025-09-15',
                'medications' => [$metforminLot951357],
            ],
            [
                'customer' => $customers[1],
                'purchase_date' => '2026-02-19',
                'medications' => [$metforminLot951357, $atorvastatin],
            ],
        ];

        foreach ($ordersSpec as $spec) {
            $order = Order::factory()->create([
                'customer_id' => $spec['customer']->id,
                'purchase_date' => $spec['purchase_date'],
            ]);

            foreach ($spec['medications'] as $medication) {
                OrderItem::factory()->create([
                    'order_id' => $order->id,
                    'medication_id' => $medication->id,
                ]);
            }
        }
    }
}

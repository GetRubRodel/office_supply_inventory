<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Supplier;
use App\Models\Supply;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        User::create([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'password' => bcrypt('admin123'),
        ]);

        // Create categories
        $categories = ['Office Supplies', 'Paper Products', 'Writing Instruments', 'Cleaning Materials', 'IT Equipment', 'Furniture'];
        foreach ($categories as $cat) {
            Category::create(['name' => $cat, 'description' => "$cat category"]);
        }

        // Create suppliers
        $suppliers = [
            ['National Book Store', 'Juan Dela Cruz', 'nbs@email.com', '123-4567'],
            ['Office Warehouse', 'Maria Santos', 'ow@email.com', '234-5678'],
            ['Paper Direct', 'Pedro Reyes', 'pd@email.com', '345-6789'],
            ['Tech Supplies Inc.', 'Ana Gonzales', 'tsi@email.com', '456-7890'],
            ['CleanCo Supplies', 'Jose Rizal', 'cleanco@email.com', '567-8901'],
        ];
        foreach ($suppliers as $s) {
            Supplier::create([
                'name' => $s[0],
                'contact_person' => $s[1],
                'email' => $s[2],
                'phone' => $s[3],
            ]);
        }

        // Create supplies
        $items = [
            ['Bond Paper (Short)', 'STK-0001', 1, 1, 'ream', 5, 50],
            ['Bond Paper (Long)',  'STK-0002', 1, 1, 'ream', 5, 30],
            ['Ballpen (Black)',    'STK-0003', 3, 1, 'box', 10, 25],
            ['Ballpen (Blue)',     'STK-0004', 3, 1, 'box', 10, 20],
            ['Folder (Long)',      'STK-0005', 2, 2, 'pcs', 20, 100],
            ['Folder (Short)',     'STK-0006', 2, 2, 'pcs', 20, 80],
            ['Stapler',            'STK-0007', 3, 1, 'pcs', 5, 8],
            ['Staples',            'STK-0008', 3, 1, 'box', 10, 15],
            ['Clear Book',         'STK-0009', 2, 2, 'pcs', 15, 40],
            ['Whiteboard Marker',  'STK-0010', 3, 1, 'box', 10, 5],
            ['Paper Clips',        'STK-0011', 3, 1, 'box', 20, 12],
            ['Ink Cartridge',      'STK-0012', 5, 4, 'pcs', 3, 4],
            ['Mouse',              'STK-0013', 5, 4, 'pcs', 5, 3],
            ['Keyboard',           'STK-0014', 5, 4, 'pcs', 5, 6],
            ['Trash Bin',          'STK-0015', 4, 5, 'pcs', 10, 18],
            ['Broom',              'STK-0016', 4, 5, 'pcs', 5, 7],
        ];
        foreach ($items as $i) {
            Supply::create([
                'stock_no' => $i[1],
                'category_id' => $i[2],
                'supplier_id' => $i[3],
                'name' => $i[0],
                'unit' => $i[4],
                'reorder_level' => $i[5],
                'current_stock' => $i[6],
            ]);
        }

        echo "Seeded: "
            . Category::count() . " categories, "
            . Supplier::count() . " suppliers, "
            . Supply::count() . " supplies\n";
    }
}

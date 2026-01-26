<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Distribution;
use App\Models\OrderBooker;
use App\Models\Van;
use App\Models\Customer;

class InvoiceSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_push_transactions_creates_invoice_and_deducts_stock()
    {
        // 1. Setup Data
        $distribution = Distribution::create([
            'name' => 'Test Dist', 
            'status' => 'active', 
            'code' => 'DMS',
            'country_id' => 1, // Mock required fields if any
            'currency' => 'PKR'
        ]);

        $user = User::factory()->create([
            'distribution_id' => $distribution->id
        ]);

        $van = Van::create(['name' => 'Test Van', 'distribution_id' => $distribution->id, 'status' => 'active', 'code' => 'VAN001']);
        $booker = OrderBooker::create([
            'name' => 'Test Booker', 
            'distribution_id' => $distribution->id, 
            'van_id' => $van->id, 
            'status' => 'active', 
            'code' => 'OB001',
            'user_id' => $user->id // If needed
        ]);
        
        // Link user to booker if relation exists
        // $user->order_booker_id = $booker->id; 
        // $user->save();
        // Check Model OrderBooker logic, usually user has orderBooker?
        // SyncController: $user->orderBooker
        // User Model: hasOne OrderBooker?
        // Let's manually link them to be safe if Relation relies on ID
        $user->order_booker_id = $booker->id; 
        $user->save();
        // Or if OrderBooker has user_id
        $booker->user_id = $user->id;
        $booker->save();


        $customer = Customer::create([
            'shop_name' => 'Test Shop', 
            'distribution_id' => $distribution->id, 
            'status' => 'active',
            'customer_code' => 'CUST001',
            'channel_id' => 1,
            'sub_distribution_id' => 1,
            'day' => 'Monday'
        ]);

        $product = Product::create([
            'name' => 'Test Juice',
            'dms_code' => 'P001',
            'distribution_id' => $distribution->id,
            'status' => 'active',
            'pieces_per_packing' => 12,
            'list_price_before_tax' => 100, // Exclusive
            'invoice_price' => 100,
            'unit_price' => 118, // inclusive
            'sku' => 'SKU001',
            'brand_id' => 1,
            'category_id' => 1,
            'product_type_id' => 1,
        ]);

        $stock = Stock::create([
            'distribution_id' => $distribution->id,
            'product_id' => $product->id,
            'batch_number' => 'BATCH001',
            'expiry_date' => now()->addYear(),
            'quantity' => 50, // Initial Stock
            'unit_cost' => 80,
            'location' => 'WH1'
        ]);

        // 2. Prepare Payload
        $payload = [
            'invoices' => [
                [
                    'local_id' => 'LOC001',
                    'customer_id' => $customer->id,
                    'invoice_date' => now()->toDateString(),
                    'is_credit' => false,
                    'notes' => 'API Test Invoice',
                    'items' => [
                        [
                            'product_id' => $product->id,
                            'quantity' => 10, // Stock to deduct
                            'cartons' => 0,
                            'pieces' => 10,
                            'list_price' => 100,
                            'unit_price' => 118,
                            'exclusive_amount' => 1000,
                            'gross_amount' => 1000,
                            'gst_percent' => 18,
                            'tax_amount' => 180,
                            'line_total' => 1180,
                            'total' => 1180,
                        ]
                    ]
                ]
            ]
        ];

        // 3. Call API
        $response = $this->actingAs($user)
            ->postJson('/api/v1/sync/push', $payload);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // 4. Verify DB
        $this->assertDatabaseHas('invoices', [
             'order_booker_id' => $booker->id,
             'customer_id' => $customer->id,
             'notes' => 'API Test Invoice'
        ]);

        $invoice = \App\Models\Invoice::latest()->first();
        $this->assertEquals(1180, $invoice->subtotal);

        // 5. Verify Stock Deduction
        $stock->refresh();
        $this->assertEquals(40, $stock->quantity); // 50 - 10
        
        // Cleanup not needed with RefreshDatabase
    }
}

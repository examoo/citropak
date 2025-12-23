<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Rename 'name' to 'shop_name' if 'name' exists
            if (Schema::hasColumn('customers', 'name') && !Schema::hasColumn('customers', 'shop_name')) {
                $table->renameColumn('name', 'shop_name');
            }

            // If 'distribution_id' is not nullable, modify it to be nullable
            $table->unsignedBigInteger('distribution_id')->nullable()->change();

            // Add missing columns if they don't exist
            $columns = [
                'customer_code' => 'string',
                'van' => 'string',
                'address' => 'text',
                'sub_address' => 'string',
                'phone' => 'string',
                'category' => 'string',
                'channel' => 'string',
                'ntn_number' => 'string',
                'cnic' => 'string',
                'sales_tax_number' => 'string',
                'sub_distribution' => 'string',
                'day' => 'string',
                'atl' => ['enum', ['active', 'inactive']],
                'adv_tax_percent' => ['decimal', 8, 2],
                'percentage' => ['decimal', 8, 2],
            ];

            foreach ($columns as $name => $type) {
                if (!Schema::hasColumn('customers', $name)) {
                    if (is_array($type)) {
                        if ($type[0] === 'enum') {
                            $table->enum($name, $type[1])->nullable();
                        } elseif ($type[0] === 'decimal') {
                            $table->decimal($name, $type[1], $type[2])->nullable();
                        }
                    } else {
                        $table->$type($name)->nullable();
                    }
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            //
        });
    }
};

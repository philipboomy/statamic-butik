<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DeleteOrdersTable extends Migration
{
    public function up()
    {
        Schema::drop('butik_orders');
    }

    public function down()
    {
        Schema::create('butik_orders', function (Blueprint $table) {
            $table->string('id')->unique()->primary();
            $table->string('transaction_id');
            $table->string('status');
            $table->string('method');
            $table->json('items');
            $table->json('customer');
            $table->integer('total_amount');
            $table->timestamp('paid_at')->default(null)->nullable();
            $table->timestamp('failed_at')->default(null)->nullable();
            $table->timestamp('shipped_at')->default(null)->nullable();

            $table->timestamps();
        });
    }
}

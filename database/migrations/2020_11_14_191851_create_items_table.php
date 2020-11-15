<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('item', function (Blueprint $table) {
            $table->integer('id')->unsigned();
            $table->string('name');
            $table->string('quality');
            $table->integer('level')->unsigned();
            $table->integer('required_level')->unsigned();
            $table->integer('sell_price')->unsigned();
            $table->integer('purchase_price')->unsigned();
            $table->string('item_class');
            $table->integer('item_class_id')->unsigned();
            $table->string('item_subclass');
            $table->integer('item_subclass_id')->unsigned();
            $table->string('inventory_type');
            $table->string('image');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('item');
    }
}

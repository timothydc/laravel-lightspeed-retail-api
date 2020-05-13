<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLightspeedRetailResourcesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lightspeed_retail_resources', function (Blueprint $table) {
            $table->id();

            $table->string('resource_type');
            $table->unsignedInteger('resource_id');

            $table->string('lightspeed_type');
            $table->unsignedInteger('lightspeed_id');

            $table->timestamps();

            $table->index(['resource_type', 'resource_id']);
            $table->index(['lightspeed_type', 'lightspeed_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('lightspeed_retail_resources');
    }
}

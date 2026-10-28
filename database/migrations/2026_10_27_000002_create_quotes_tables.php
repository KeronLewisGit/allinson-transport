<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            // In the customer's signed link instead of the sequential id.
            $table->ulid('public_id')->unique();
            $table->string('status', 20)->default('new')->index();
            $table->string('service', 20);
            $table->string('depot', 30)->index();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('company', 160)->nullable();
            $table->string('contact_name', 120);
            $table->string('email');
            $table->string('phone', 30);
            $table->string('phone_digits', 20)->nullable()->index();
            $table->string('collection_postcode', 10);
            $table->string('collection_town', 80)->nullable();
            $table->date('collection_date');
            $table->string('delivery_postcode', 10);
            $table->string('delivery_town', 80)->nullable();
            $table->date('delivery_date')->nullable();
            $table->string('goods', 300);
            $table->unsignedSmallInteger('pallets')->nullable();
            $table->unsignedInteger('weight_kg');
            $table->decimal('length_m', 5, 2)->nullable();
            $table->decimal('width_m', 4, 2)->nullable();
            $table->decimal('height_m', 4, 2)->nullable();
            $table->boolean('adr')->default(false);
            $table->string('adr_details', 160)->nullable();
            $table->boolean('tail_lift')->default(false);
            $table->boolean('regular')->default(false);
            $table->text('message')->nullable();
            // Filled in by traffic.
            $table->decimal('price', 10, 2)->nullable();
            $table->text('price_notes')->nullable();
            $table->date('valid_until')->nullable();
            $table->timestamp('quoted_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->text('customer_note')->nullable();
            $table->timestamp('status_changed_at')->nullable();
            $table->string('source_url', 300)->nullable();
            $table->timestamps();
        });

        Schema::create('quote_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name', 200);
            $table->string('mime', 120)->nullable();
            $table->unsignedInteger('size')->default(0);
            $table->timestamps();
        });

        Schema::create('warehousing_enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->string('status', 20)->default('new')->index();
            $table->string('company', 160);
            $table->string('contact_name', 120);
            $table->string('email');
            $table->string('phone', 30);
            $table->string('phone_digits', 20)->nullable()->index();
            $table->unsignedInteger('pallet_spaces');
            $table->string('duration', 20);
            $table->date('start_on')->nullable();
            $table->string('goods_type', 20);
            $table->text('goods_description');
            $table->json('handling')->nullable();
            $table->boolean('hazardous')->default(false);
            $table->string('depot', 30)->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('message')->nullable();
            $table->timestamp('status_changed_at')->nullable();
            $table->string('source_url', 300)->nullable();
            $table->timestamps();
        });

        Schema::create('enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->string('status', 20)->default('new')->index();
            $table->string('topic', 20);
            $table->string('name', 120);
            $table->string('organisation', 160)->nullable();
            $table->string('email');
            $table->string('phone', 30)->nullable();
            $table->text('message');
            $table->string('source_url', 300)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enquiries');
        Schema::dropIfExists('warehousing_enquiries');
        Schema::dropIfExists('quote_files');
        Schema::dropIfExists('quotes');
    }
};

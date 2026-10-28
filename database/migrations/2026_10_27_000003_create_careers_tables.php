<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_postings', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 120)->unique();
            $table->string('title', 160);
            $table->string('role', 20);
            $table->string('depot', 30)->index();
            $table->string('employment_type', 20);
            $table->string('status', 20)->default('open')->index();
            $table->string('pay', 120)->nullable();
            $table->string('hours', 120)->nullable();
            $table->unsignedSmallInteger('openings')->default(1);
            $table->text('summary');
            $table->text('duties')->nullable();
            $table->text('requirements')->nullable();
            $table->date('closes_on')->nullable();
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });

        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            // In the applicant's signed documents link instead of the sequential id.
            $table->ulid('public_id')->unique();
            $table->foreignId('job_posting_id')->nullable()->constrained()->nullOnDelete();
            $table->string('role', 20);
            $table->string('depot', 30)->index();
            $table->string('status', 20)->default('new')->index();
            $table->string('first_name', 80);
            $table->string('last_name', 80);
            $table->string('phone', 30);
            $table->string('phone_digits', 20)->nullable()->index();
            $table->string('email')->nullable();
            $table->string('postcode', 10)->nullable();
            $table->string('experience', 10);
            // Self-declared by the applicant; checked against the DVLA record and the cards at assessment.
            $table->json('licence_categories')->nullable();
            $table->date('cpc_expires_on')->nullable();
            $table->date('tacho_card_expires_on')->nullable();
            $table->unsignedTinyInteger('licence_points')->nullable();
            $table->string('endorsements', 200)->nullable();
            $table->string('hiab_qualification', 160)->nullable();
            $table->json('forklift')->nullable();
            $table->boolean('right_to_work')->default(false);
            $table->date('available_from')->nullable();
            $table->text('message')->nullable();
            $table->string('cv_path')->nullable();
            $table->string('cv_name', 200)->nullable();
            $table->string('cv_mime', 120)->nullable();
            $table->unsignedInteger('cv_size')->nullable();
            $table->timestamp('consented_at')->nullable();
            // The pipeline.
            $table->timestamp('rtw_checked_at')->nullable();
            $table->foreignId('rtw_checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('rtw_method', 40)->nullable();
            $table->string('references_status', 20)->default('not_requested');
            $table->timestamp('references_updated_at')->nullable();
            $table->dateTime('assessment_at')->nullable();
            $table->string('assessment_result', 20)->nullable();
            $table->timestamp('documents_requested_at')->nullable();
            $table->timestamp('status_changed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('applicant_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_application_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 30);
            $table->string('path');
            $table->string('original_name', 200);
            $table->string('mime', 120)->nullable();
            $table->unsignedInteger('size')->default(0);
            $table->timestamp('superseded_at')->nullable();
            $table->timestamps();
        });

        // Drivers and staff whose licence, Driver CPC and tacho card expiries HR tracks.
        Schema::create('staff_members', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('role', 20);
            $table->string('depot', 30)->index();
            $table->string('employee_number', 30)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->json('licence_categories')->nullable();
            $table->date('licence_expires_on')->nullable();
            $table->date('cpc_expires_on')->nullable();
            $table->date('tacho_card_expires_on')->nullable();
            // Last reminder threshold (days) emailed to HR for each date; reset when the date changes.
            $table->smallInteger('licence_reminded_at_days')->nullable();
            $table->smallInteger('cpc_reminded_at_days')->nullable();
            $table->smallInteger('tacho_reminded_at_days')->nullable();
            $table->date('cards_checked_on')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->foreignId('job_application_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_members');
        Schema::dropIfExists('applicant_documents');
        Schema::dropIfExists('job_applications');
        Schema::dropIfExists('job_postings');
    }
};

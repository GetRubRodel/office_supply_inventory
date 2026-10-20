<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requisitions', function (Blueprint $table) {
            // Free-text reason captured when a Division Chief or Administrator
            // cancels the RIS. Shown to the original requester in the
            // cancellation notification (App\Notifications\RisCancelled).
            $table->text('cancellation_remarks')->nullable()->after('cancelled_by_date');
        });
    }

    public function down(): void
    {
        Schema::table('requisitions', function (Blueprint $table) {
            $table->dropColumn('cancellation_remarks');
        });
    }
};

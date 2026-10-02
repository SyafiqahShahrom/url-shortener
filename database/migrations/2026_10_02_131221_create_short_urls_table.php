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
        Schema::create('short_urls', function (Blueprint $table) {
            $table->id();
            // Matches the max length enforced by StoreShortUrlRequest.
            $table->string('original_url', 2048);

            // The unique index is the source of truth for uniqueness and also
            // serves every redirect lookup.
            $shortCode = $table->string('short_code', 8)->unique();

            // MySQL's default collation is case-insensitive, which would make
            // "Ab12X9" and "ab12x9" the same code. Codes are case-sensitive,
            // so compare them byte-for-byte. (SQLite is binary by default.)
            if (in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
                $shortCode->charset('utf8mb4')->collation('utf8mb4_bin');
            }

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('short_urls');
    }
};

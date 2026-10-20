<?php

use App\Support\BacResolutionText;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The BAC Resolution title was previously written in all caps and rendered
 * bold. It is now sentence case at regular weight:
 *
 *   "A resolution authorizing the procurement of office supplies for
 *    ______ through small value procurement as provided in the Implementing
 *    Rules and Regulations (IRR) of Republic Act No. 12009."
 *
 * Re-writes the stored title so the column never contradicts the printed
 * document. The blank itself stays tokenised — no Division is written here.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('bac_resolutions')->update([
            'title' => BacResolutionText::titleTemplate(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('bac_resolutions')->update([
            'title' => 'A RESOLUTION AUTHORIZING THE PROCUREMENT OF OFFICE SUPPLIES FOR '
                .BacResolutionText::DIVISION_TOKEN
                .' THROUGH SMALL VALUE PROCUREMENT AS PROVIDED IN THE IMPLEMENTING RULES AND '
                .'REGULATIONS (IRR) OF REPUBLIC ACT NO. 12009.',
            'updated_at' => now(),
        ]);
    }
};
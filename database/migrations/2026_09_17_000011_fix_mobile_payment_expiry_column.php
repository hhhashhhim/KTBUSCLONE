<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class FixMobilePaymentExpiryColumn extends Migration
{
    public function up()
    {
        if (DB::getDriverName() === 'mysql') {
            // Legacy MySQL/MariaDB gives the first non-null TIMESTAMP an implicit
            // DEFAULT/ON UPDATE CURRENT_TIMESTAMP. Status checks then overwrite
            // the checkout deadline. Keep the values Laravel currently reads,
            // but remove both automatic behaviors and timezone conversion.
            DB::statement('ALTER TABLE `mobile_payments` MODIFY COLUMN `expires_at` DATETIME NOT NULL');
        }
    }

    public function down()
    {
        // Forward-only schema repair: never restore automatic deadline updates.
        // Expired payments and released seats must not be revived by a rollback.
    }
}

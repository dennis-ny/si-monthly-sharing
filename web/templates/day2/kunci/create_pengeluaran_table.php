<?php
// =========================================================
// FILE: database/migrations/create_pengeluaran_table.php (Kunci)
// =========================================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pengeluaran')) {
            Schema::create('pengeluaran', function (Blueprint $table) {
                $table->id();
                $table->string('keterangan', 100);
                $table->integer('nominal');
                $table->date('tanggal');
            });
        }
    }
};

<?php
// FILE: database/migrations/create_pengeluaran_table.php (TAHAP 1 - Migration)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // tabel: pengeluaran | kolom: keterangan, nominal, tanggal
        if (!Schema::hasTable('____')) {
            Schema::create('____', function (Blueprint $table) {
                $table->id();
                $table->string('____', 100);
                $table->integer('____');
                $table->date('____');
            });
        }
    }
};

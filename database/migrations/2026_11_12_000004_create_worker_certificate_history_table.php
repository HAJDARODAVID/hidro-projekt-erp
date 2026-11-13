<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * History of the workers' certificates / checks: the old version of a row when it is renewed or deleted.
     * From now on worker_certificates holds only the current row of each worker + type + title.
     *
     * Rows of the old way (a renewal was a new row in worker_certificates) are moved here:
     * per worker + type + title the newest stays, the older ones become "renewed" history.
     */
    public function up(): void
    {
        Schema::create('worker_certificate_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('certificate_id')->nullable()->comment('worker_certificates.id it was (no FK: deleted rows are gone)');
            $table->unsignedBigInteger('worker_id');
            $table->string('type', 30);
            $table->string('title');
            $table->date('issued_at')->nullable();
            $table->date('valid_until')->nullable();
            $table->string('note')->nullable();
            $table->string('action', 20)->comment('renewed | deleted');
            $table->unsignedBigInteger('created_by')->nullable()->comment('who created the original row (users.id)');
            $table->unsignedBigInteger('archived_by')->nullable()->comment('who renewed / deleted it (users.id)');
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->foreign('worker_id')->references('id')->on('workers')->cascadeOnDelete();
            $table->index(['worker_id', 'archived_at']);
            $table->index('certificate_id');
        });

        /**Old way -> history: keep the newest row of each worker + type + title */
        $rows = DB::table('worker_certificates')
            ->orderBy('worker_id')->orderByDesc('issued_at')->orderByDesc('valid_until')->orderByDesc('id')
            ->get();
        $seen = [];
        foreach ($rows as $row) {
            $group = $row->worker_id . '|' . $row->type . '|' . mb_strtolower($row->title);
            if (!isset($seen[$group])) {
                $seen[$group] = $row->id;
                continue;
            }
            DB::table('worker_certificate_history')->insert([
                'certificate_id' => $seen[$group],
                'worker_id'      => $row->worker_id,
                'type'           => $row->type,
                'title'          => $row->title,
                'issued_at'      => $row->issued_at,
                'valid_until'    => $row->valid_until,
                'note'           => $row->note,
                'action'         => 'renewed',
                'created_by'     => $row->created_by,
                'archived_at'    => $row->updated_at ?? now(),
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
            DB::table('worker_certificates')->where('id', $row->id)->delete();
        }
    }

    /**
     * Back to the old way: the "renewed" history rows become rows of worker_certificates again
     * (deleted ones stay deleted).
     */
    public function down(): void
    {
        if (Schema::hasTable('worker_certificate_history')) {
            foreach (DB::table('worker_certificate_history')->where('action', 'renewed')->get() as $row) {
                DB::table('worker_certificates')->insert([
                    'worker_id' => $row->worker_id, 'type' => $row->type, 'title' => $row->title,
                    'issued_at' => $row->issued_at, 'valid_until' => $row->valid_until, 'note' => $row->note,
                    'created_by' => $row->created_by, 'created_at' => $row->created_at, 'updated_at' => $row->archived_at,
                ]);
            }
        }
        Schema::dropIfExists('worker_certificate_history');
    }
};

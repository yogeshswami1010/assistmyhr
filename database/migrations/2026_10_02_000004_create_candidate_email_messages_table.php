<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('candidate_email_messages')) {
            Schema::create('candidate_email_messages', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('job_application_id');
                $table->unsignedInteger('user_id')->nullable();
                $table->foreign('job_application_id')->references('id')->on('job_applications')->cascadeOnDelete();
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
                $table->enum('direction', ['outbound', 'inbound']);
                $table->string('from_address');
                $table->string('to_address');
                $table->string('subject')->nullable();
                $table->longText('body');
                $table->string('message_id')->nullable();
                $table->string('in_reply_to')->nullable();
                $table->timestamp('read_at')->nullable();
                $table->timestamp('received_at')->nullable();
                $table->timestamps();
            });
        }

        $foreignKeyColumns = array_column(Schema::getForeignKeys('candidate_email_messages'), 'columns');
        foreach (['job_application_id' => 'job_applications', 'user_id' => 'users'] as $column => $parentTable) {
            if (in_array([$column], $foreignKeyColumns, true)) {
                continue;
            }

            // A previous attempt created the columns but failed while adding the
            // constraints. Repair the legacy INT-compatible columns in place.
            $isUser = $column === 'user_id';
            if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
                Schema::table('candidate_email_messages', function (Blueprint $table) use ($column, $isUser) {
                    $table->unsignedInteger($column)->nullable($isUser)->change();
                });
            }
            Schema::table('candidate_email_messages', function (Blueprint $table) use ($column, $parentTable, $isUser) {
                $table->foreign($column)->references('id')->on($parentTable)->onDelete($isUser ? 'set null' : 'cascade');
            });
        }

        $indexes = [
            'candidate_email_messages_message_id_index' => ['message_id'],
            'candidate_email_messages_in_reply_to_index' => ['in_reply_to'],
            'cem_application_direction_read_idx' => ['job_application_id', 'direction', 'read_at'],
        ];
        foreach ($indexes as $name => $columns) {
            if (!Schema::hasIndex('candidate_email_messages', $columns)) {
                Schema::table('candidate_email_messages', function (Blueprint $table) use ($columns, $name) {
                    $table->index($columns, $name);
                });
            }
        }
    }
    public function down(): void { Schema::dropIfExists('candidate_email_messages'); }
};

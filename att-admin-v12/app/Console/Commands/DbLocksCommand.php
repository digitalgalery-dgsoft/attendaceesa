<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DbLocksCommand extends Command
{
    protected $signature = 'db:locks {--kill : Terminate hanging queries} {--vacuum : Run VACUUM ANALYZE on report tables}';
    protected $description = 'Check and manage active database locks/queries';

    public function handle(): int
    {
        if ($this->option('vacuum')) {
            $this->info("Running VACUUM ANALYZE on report tables...");
            $t = microtime(true);
            DB::statement('VACUUM ANALYZE report_submissions');
            DB::statement('VACUUM ANALYZE report_submission_values');
            $this->info("VACUUM ANALYZE completed in " . round(microtime(true) - $t, 2) . "s");
            return 0;
        }

        $this->info("Checking active PostgreSQL connections & queries...");

        $queries = DB::select("
            SELECT pid, usename, client_addr, state, wait_event_type, wait_event,
                   EXTRACT(EPOCH FROM (now() - query_start))::int as duration_sec,
                   query
            FROM pg_stat_activity
            WHERE pid <> pg_backend_pid()
              AND datname = current_database()
            ORDER BY query_start ASC
        ");

        if (empty($queries)) {
            $this->info("No other active connections found. Everything is quiet.");
            return 0;
        }

        $headers = ['PID', 'State', 'Wait Type', 'Wait Event', 'Duration (s)', 'Query'];
        $rows = [];

        foreach ($queries as $q) {
            $rows[] = [
                $q->pid,
                $q->state,
                $q->wait_event_type ?? '-',
                $q->wait_event ?? '-',
                $q->duration_sec ?? 0,
                substr(preg_replace('/\s+/', ' ', $q->query ?? ''), 0, 80),
            ];

            if ($this->option('kill') && ($q->duration_sec > 5 || $q->state === 'idle in transaction')) {
                DB::statement("SELECT pg_terminate_backend(?)", [$q->pid]);
                $this->warn("Killed PID {$q->pid}");
            }
        }

        $this->table($headers, $rows);
        return 0;
    }
}

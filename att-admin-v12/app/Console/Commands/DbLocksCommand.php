<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DbLocksCommand extends Command
{
    protected $signature = 'db:locks {--kill : Terminate hanging queries}';
    protected $description = 'Check and manage active database locks/queries';

    public function handle(): int
    {
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

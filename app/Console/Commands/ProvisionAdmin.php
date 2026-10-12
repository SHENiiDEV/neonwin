<?php

namespace App\Console\Commands;

use App\Models\AdminAudit;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProvisionAdmin extends Command
{
    protected $signature = 'neonwin:admin {email : Existing account email} {--revoke : Remove administrator access}';

    protected $description = 'Grant or revoke administrator access for an existing account without default credentials';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();
        if (! $user) {
            $this->error('Create the account through registration first.');

            return self::FAILURE;
        }
        DB::transaction(function () use ($user): void {
            $before = $user->is_admin;
            $user->is_admin = ! $this->option('revoke');
            $user->save();
            AdminAudit::create(['action' => 'admin_provision_cli', 'target' => (string) $user->id, 'before' => ['is_admin' => $before], 'after' => ['is_admin' => $user->is_admin]]);
        });
        $this->info('Administrator access updated.');

        return self::SUCCESS;
    }
}

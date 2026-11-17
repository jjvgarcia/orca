<?php

namespace App\Console\Commands;

use App\Models\Quotation;
use App\Support\PublicLink;
use Database\Seeders\DemoSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

#[Signature('demo:reset')]
#[Description('Recria o dataset de demonstração: 3 clientes, 8 serviços, 7 orçamentos, 4 pedidos e 9 parcelas.')]
class DemoReset extends Command
{
    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->error('demo:reset não roda em produção.');

            return self::FAILURE;
        }

        $started = microtime(true);

        if (Artisan::call('migrate:fresh', ['--force' => true]) !== 0) {
            $this->error('Falha ao recriar o banco.');

            return self::FAILURE;
        }

        Artisan::call('db:seed', ['--class' => DemoSeeder::class, '--force' => true]);

        $elapsed = number_format(microtime(true) - $started, 2);
        $pending = Quotation::where('status', 'sent')->where('valid_until', '>=', today())->latest('id')->first();

        $this->newLine();
        $this->info("Demo recriado em {$elapsed}s.");
        $this->line('  Login:   demo@orca.test / password');
        $this->line('  Operador: operador@orca.test / password');
        $this->line('  URL:     '.config('app.url'));

        if ($pending !== null) {
            $this->line('  Link público pendente: '.PublicLink::to($pending));
        }

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Jobs\InitBookRecsShortJob;
use Illuminate\Console\Command;

class InitBookRecsShortCommand extends Command
{
    protected $signature = 'recs:initShort {--start=} {--end=} {--debug}';

    protected $description = 'Заполнить базу быстрым списком рекоммендаций';

    public function handle()
    {
        dispatch(new InitBookRecsShortJob(
            $this->option('start'),
            $this->option('end'),
            $this->option('debug') ?? false
        ));
    }
}

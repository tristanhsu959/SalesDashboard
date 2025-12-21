<?php

namespace App\Console\Commands;

use App\Services\PosService;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitializeNewReleaseDataToLocal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'new-release:initialize-to-local';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch Pos Data to Local DB';

    /**
     * Execute the console command.
     */
    public function handle(PosService $posService)
    {
        try
		{
			$configKey = 'porkRibs';
			#新品目前似乎只有梁社漢有
			$posService->setConfig($configKey);
			
			#1. Get params fetch date
			$this->info('Get Params-----');
			$params = $posService->getParams();
			$this->info(json_encode($params));
						
			#2. Get POS DB data
			$this->info('Fetch data from POSDB -----');
			$posData = [];
			$posData = $posService->getDataFromPosDB($params);
			
			#3. Save data to local
			$this->info('Save Data to Local -----');
			$posService->saveToLocalDB();
			
			$this->info('initialize completed -----');
		}
		catch(Exception $e)
		{
			Log::channel('commandLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
			$this->error($e->getMessage());
		}
    }
}

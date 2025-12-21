<?php

namespace App\Console\Commands;

use App\Services\Commands\PosInitializeService;
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
    protected $signature = 'new-release:initialize-to-local {configKey?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize Pos Data to Local DB';

    /**
     * Execute the console command.
     */
    public function handle(PosInitializeService $posService)
    {
        try
		{
			$configKeys = [];
			
			#只執行指定table的參數
			$argument = $this->argument('configKey');
			
			if (empty($argument))
			{
				$list = config('web.new_release.products');
				$configKeys = array_keys($list);
			}
			else
				$configKeys[] = $argument;
			
			foreach($configKeys as $configKey)
			{
				Log::channel('commandLog')->info("Initialize Start : {$configKey}", [ __class__, __function__, __line__]);
			
				$this->info("Initialize Start : {$configKey} -----");
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
				$count = count($posData);
				$this->info("Data count : {$count} -----");
				
				#3. Save data to local
				$this->info('Save Data to Local -----');
				$posService->saveToLocalDB($posData);
				
				$this->info("Initialize {$configKey} completed -----");
				Log::channel('commandLog')->info("Initialize {$configKey} completed", [ __class__, __function__, __line__]);
			}
		}
		catch(Exception $e)
		{
			Log::channel('commandLog')->error('Initialize : ' . $e->getMessage(), [ __class__, __function__, __line__]);
			$this->fail($e->getMessage());
		}
    }
}

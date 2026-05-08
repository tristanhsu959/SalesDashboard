<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class BafangPosOrderToLocal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bafang:pos-order-to-local {argStDate?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Copy Bafang Pos Sale01 to Local';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $argStDate 	= $this->argument('argStDate');
		
		list($stDate, $endDate) = $this->_getParams($argStDate);
		
		try
		{
			$this->info("Fetch bafang data start -----" . now());
			Log::channel('commandLog')->info("Fetch bafang data start" . now(), [ __class__, __function__, __line__]);
			
			$this->_fetchOrder($stDate, $endDate);
					
			$this->info("Fetch bafang data completed -----" . now());
			Log::channel('commandLog')->info("Fetch bafang data completed" . now(), [ __class__, __function__, __line__]);
		}
		catch(Exception $e)
		{
			Log::channel('commandLog')->error('Fetch bafang data : ' . $e->getMessage(), [ __class__, __function__, __line__]);
			$this->fail($e->getMessage());
		}
    }
	
	private function _getParams($stDate)
	{
		#無stDate時, carbon default is now
		$result = DB::connection('PosOrder')
					->table('bf_sale01')
					->select('saleDate')
					->orderBy('saleDate', 'DESC')
					->limit(1)
					->get()
					->toArray();
		
		$saleData = data_get($result, 'saleData', NULL);
		
		if (! empty($saleData)) #取最後更新時間
			$stDate = Carbon::parse($saleData)->subMinutes(15)->format('Y-m-d H:i:s');
		else
			$stDate = Carbon::parse($stDate)->format('Y-m-d H:i:s'); #只有跑一次
		
		$endDate = Carbon::parse($stDate)->addMinutes(60)->format('Y-m-d H:i:s');
			
		return [$stDate, $endDate];
	}
	
	private function _fetchOrder($stDate, $endDate)
	{
		#無stDate時, carbon default is now
		#(saleId, saleSno, shopId, productId, price, qty, discount, taste, saleDate)
		$result = DB::connection('BFPosErp')
			->table('SALE00 as s0')
			->fromRaw('SALE00 as s0 WITH(NOLOCK)')
			->join(DB::RAW('SALE01 as s1 WITH(NOLOCK)'), function($query){
				$query->where('s1.SHOP_ID', 's0.SHOP_ID')
						->where('s1.SALE_ID', 's0.SALE_ID')
			})
			->where('s0.STATUS', '=', '2')
			->where('s0.SALE_DATE', '>=', $stDate)
			->where('s0.SALE_DATE', '<=', $endDate)
			->select('s1.SALE_ID', 's1.SALE_SNO', 's1.SHOP_ID', 's1.PROD_ID')
			->addSelect('s1.SALE_PRICE', 's1.QTY', 's1.ITEM_DISC', 's1.TASTE_MEMO', 's0.SALE_DATE')
			->toRawSql();
			
		dd($result);
	}
}

<?php

namespace App\Listeners;

use App\Events\ProductRemoved;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Repositories\NewReleaseSettingRepository;
use App\Repositories\SalesSettingRepository;
use Exception;
use Log;

class ProductRemovedNotification
{
    /**
     * Create the event listener.
     */
    public function __construct(
		protected NewReleaseSettingRepository $_newItemRepository, 
		protected SalesSettingRepository $_salesSettingRepository)
    {
       
    }

    /**
     * Handle the event.
     */
    public function handle(ProductRemoved $event): void
    {
		try
		{ 
			$productId = $event->productId;
			
			$this->_salesSettingRepository->updateStatus($productId);
			$this->_newItemRepository->updateStatus($productId);
			
		}
		catch(Exception $e)
		{
			Log::channel('appServiceLog')->error($e->getMessage(), [ __class__, __function__, __line__]);
		}
    }
}

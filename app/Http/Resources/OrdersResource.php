<?php
    /**
     * Created by PhpStorm.
     * User: anthonyrodrigues
     * Date: 8/29/18
     * Time: 5:06 PM
     */

    namespace App\Http\Resources;

    use Dsc\MercadoLivre\Resources\Order\OrderService;
    use Dsc\MercadoLivre\Resources\Order\OrdersList;

    class OrdersResource extends OrderService
    {
        public function findLastOrdersByBuyer($sellerId, $limit, $offset, $sort, $status)
        {
            return $this->getResponse(
                $this->get('/orders/search/recent', [
                    'seller' => $sellerId,
                    'limit'  => $limit,
                    'offset' => $offset,
                    'sort'   => $sort,
                    'order.status' => $status
                ]),
                OrdersList::class
            );
        }

    }
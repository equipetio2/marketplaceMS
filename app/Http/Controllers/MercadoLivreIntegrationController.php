<?php
    /**
     * Created by PhpStorm.
     * User: anthonyrodrigues
     * Date: 8/28/18
     * Time: 11:20 AM
     */

    namespace App\Http\Controllers;

    use App\Announcement\OrderStatus;
    use App\Http\Middleware\MeliAuthMiddleware;
    use GuzzleHttp\Client;
    use Illuminate\Http\Request;
    use Dsc\MercadoLivre\Requests\Category\CategoryService;
    use Dsc\MercadoLivre\Environments\Site;
    use Dsc\MercadoLivre\Announcement\Item;
    use Dsc\MercadoLivre\Announcement\Picture;
    use Dsc\MercadoLivre\Announcement;
    use Dsc\MercadoLivre\Announcement\Status;
    use Dsc\MercadoLivre\Resources\User\UserService;
    use App\Http\Resources\OrdersResource;

    class MercadoLivreIntegrationController extends Controller
    {
        /**
         * @param Request $request
         * @return false|string
         */
        public function createToken(Request $request)
        {
            return MeliAuthMiddleware::$token;
        }

        /**
         * @param Request $request
         */
        public function createProduct(Request $request)
        {
            $item = new Item();
            $item->setTitle($request->title)
                ->setCategoryId($request->categoryId)
                ->setPrice($request->price)
                ->setCurrencyId('BRL')
                ->setAvailableQuantity($request->quantity)
                ->setBuyingMode($request->buyingMode)
                ->setListingTypeId($request->listingType)
                ->setCondition($request->condition)
                ->setDescription($request->description) //corrigir isso plain_text
                ->setWarranty($request->warranty);

            if ($request->photos) {
                foreach ($request->photos as $photo) {
                    $item->addPicture($this->setImage($photo)); // collection de imagens
                }
            }
            $announcement = new Announcement(MeliAuthMiddleware::$meli);
            $response = $announcement->create($item);

            // Link do produto publicado
            return json_encode($response->getPermalink());
        }

        /**
         * @return false|string
         */
        public function getCategories()
        {
            $return = [];
            $service = new CategoryService();
            $data = $service->findCategories(Site::BRASIL);
            foreach ($data as $key => $datum) {
                $category = ($service->findCategory($datum->getId()))->getChildrenCategories();
                foreach ($category as $subKey => $item) {
                    $return[$datum->getName()][$subKey]['id'] = $item->getId();
                    $return[$datum->getName()][$subKey]['name'] = $item->getName();
                }
            }
            return json_encode($return);
        }
        /**
         * @param $categoryCod
         * @return array
         */
        public function getCategoryData($categoryCod)
        {
            $categoryData = [];
            $service = new CategoryService();
            $attributes = ($service->findCategory($categoryCod))->getChildrenCategories();
            foreach ($attributes as $key => $attribute) {
                $categories = $service->findCategory($attribute->getId());
                    $categoryData[$categories->getName()][$key]['id'] = $categories->getId();
                    $categoryData[$categories->getName()][$key]['name'] = $categories->getName();
                foreach ($categories->getChildrenCategories() as $keyCategory => $category) {
                    $categoryData[$categories->getName()][$keyCategory]['id'] = $category->getId();
                    $categoryData[$categories->getName()][$keyCategory]['name'] = $category->getName();
                }
            }
            return json_encode($categoryData);
        }

        /**
         * @param Request $request
         * @param $productId
         * @return false|string
         */
        public function changeProduct(Request $request, $productId)
        {
            $announcement = new Announcement(MeliAuthMiddleware::$meli);
            $response = $announcement->update($productId, $request->update);
            return json_encode($response->getPermalink());
        }

        public function changeStatus(Request $request, $productId, $status)
        {
            if (!Status::isValid(strtolower($status))) {
                return json_encode(['error' => 'Type not found']);
            }
            $announcement = new Announcement(MeliAuthMiddleware::$meli);
            $response = $announcement->changeStatus($productId, strtolower($status));
            return json_encode($response->getPermalink());
        }

        public function getStatus()
        {
            $data[Status::ACTIVE]['name'] = 'Ativo';
            $data[Status::CLOSED]['name'] = 'Fechado';
            $data[Status::PAUSED]['name'] = 'Pausado';
            return json_encode($data);
        }

        public function getStatusOrders()
        {
            $data[OrderStatus::CANCELLED]['name'] = 'Cancelado';
            $data[OrderStatus::CONFIRMED]['name'] = 'Confirmado';
            $data[OrderStatus::INVALID]['name'] = 'Invalido';
            $data[OrderStatus::PAID]['name'] = 'Pagamento Associado';
            $data[OrderStatus::PARTIALLY_PAID]['name'] = 'Pago Parcialmente';
            $data[OrderStatus::PAYMENT_REQUIRED]['name'] = 'Pagamento Requerido';
            $data[OrderStatus::PAYMENT_IN_PROCESS]['name'] = 'Pagamento em processamento';
            return json_encode($data);
        }

        public function deleteProduct(Request $request, $productId)
        {
            $announcement = new Announcement(MeliAuthMiddleware::$meli);
            $announcement->delete($productId);
            return json_encode(['status' => 'ok', 'message' => 'deleted']);
        }

        public function getOrders(Request $request, $limit = 20, $offset = 0, $sort = 'date_desc', $status = 'paid')
        {
            $service = new OrdersResource(MeliAuthMiddleware::$meli);
            return json_encode(
                $service->findOrdersBySeller(
                    $this->getUserId(), $limit, $offset, $sort
                )->getResults()
            );
        }

        /**
         * @param Request $request
         * @return \Psr\Http\Message\StreamInterface
         * @throws \GuzzleHttp\Exception\GuzzleException
         * @todo melhorar esse método
         */
        public function getLastOrders(Request $request)
        {
            $client = new Client();
            $response = $client->request('GET', 'https://api.mercadolibre.com/orders/search?seller=' . $this->getUserId() .
                '&access_token=' . MeliAuthMiddleware::$token);
            return $response->getBody();
        }

        /**
         * @param $itemId
         * @return \Psr\Http\Message\StreamInterface
         * @throws \GuzzleHttp\Exception\GuzzleException
         * @todo melhorar esse método.
         */
        public function getItem($itemId)
        {
            $client = new Client();
            $response = $client->request('GET', 'https://api.mercadolibre.com/items/' . $itemId);
            return $response->getBody();
        }

        /**
         * @return int
         */
        protected function getUserId()
        {
            $service = new UserService(MeliAuthMiddleware::$meli);
            return ($service->getInformationAuthenticatedUser())->getId();
        }

        /**
         * @param String $image
         * @return Picture
         */
        protected function setImage($image)
        {
            $picture = new Picture();
            $picture->setSource($image);
            return $picture;
        }
    }
<?php
    /**
     * Created by PhpStorm.
     * User: anthonyrodrigues
     * Date: 8/28/18
     * Time: 11:20 AM
     */

    namespace App\Http\Controllers;

    use Dsc\MercadoLivre\Meli;
    use Dsc\MercadoLivre\Resources\Authorization\AuthorizationService;
    use Illuminate\Http\Request;
    use Dsc\MercadoLivre\Requests\Category\CategoryService;
    use Dsc\MercadoLivre\Environments\Site;
    use Dsc\MercadoLivre\Announcement\Item;
    use Dsc\MercadoLivre\Announcement\Picture;
    use Dsc\MercadoLivre\Announcement;
    use Dsc\MercadoLivre\Announcement\Status;

    class MercadoLivreIntegrationController extends Controller
    {
        public $token;
        public $meli;

        /**
         * @param Request $request
         * @return false|string
         */
        public function __construct(Request $request)
        {
            $meli = new Meli($request->appId, $request->appSecretKey);
            $service = new AuthorizationService($meli);
            $this->token = $service->getAccessToken();
            $this->meli = $meli;
            return json_encode($this->token);
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
//                ->setDescription($request->description) corrigir isso plain_text
                ->setWarranty($request->warranty);

            if ($request->photos) {
                foreach ($request->photos as $photo) {
                    $item->addPicture($this->setImage($photo)); // collection de imagens
                }
            }

            $announcement = new Announcement($this->meli);
            $response = $announcement->create($item);

            // Link do produto publicado
            return json_encode($response->getPermalink());
        }

        /**
         * @return false|string
         */
        public function getCategories()
        {
            $service = new CategoryService();
            $data = $service->findCategories(Site::BRASIL);
            return json_encode($data->getValues());
        }

        /**
         * @param $categoryCod
         * @return array
         * @todo validar
         */
        public function getCategoryData($categoryCod)
        {
            $category = [];
            $service = new CategoryService();
            $category['category'] = $service->findCategory($categoryCod);
            $category['category']->attributes = $service->findCategoryAttributes($categoryCod);
            return $category;
        }

        /**
         * @param Request $request
         * @param $productId
         * @return false|string
         * @todo validar como podemos fazer isso, talvez os dados ja sendo mandados corretamente
         */
        public function changeProduct(Request $request, $productId)
        {
            $data = [
                'title' => 'New title',
                'available_quantity' => 10,
                'price' => 100
            ];

            $announcement = new Announcement($this->meli);
            $response = $announcement->update($productId, $data);
            return json_encode($response->getPermalink());
        }

        public function changeStatus(Request $request, $status)
        {
            if (!Status::isValid(strtoupper($status))) {
                return json_encode(['error' => 'Type not found']);
            }
            $announcement = new Announcement($this->meli);
            $response = $announcement->changeStatus('ITEM-CODE', Status::PAUSED);
            return json_encode($response->getPermalink());
        }

        /**
         * @param String $image
         * @return Picture
         * @todo validate
         */
        protected function setImage($image)
        {
            $picture = new Picture();
            $picture->setSource($image);
            return $picture;
        }
    }
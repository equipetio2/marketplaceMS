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

use Dsc\MercadoLivre\Meli;
use Dsc\MercadoLivre\Resources\Authorization\AuthorizationService;

class MercadoLivreIntegrationController extends Controller
{
    private function accessToken($appId, $secretId){
        $meli = new Meli($appId, $secretId);
        $service = new AuthorizationService($meli);

        return $service->getAccessToken();
    }


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
        $accessToken = $this->accessToken($request->appId, $request->appSecretKey);

        $data = [
            "title" => $request->title,
            "category_id" => $request->categoryId,
            "price" => $request->price,
            "currency_id" => 'BRL',
            "available_quantity" => $request->quantity,
            "buying_mode" => $request->buyingMode,
            "listing_type_id" => $request->listingType,
            "condition" => $request->condition,
            "description" => $request->description
        ];
        if ($request->photos) { // collection de imagens
            foreach ($request->photos as $photo) {
                $data['pictures'][]['source'] = $photo;
            }
        }
        //return json_encode($data);
        $url = 'https://api.mercadolibre.com/items?access_token='.$accessToken;
        $data = json_encode($data);
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $newProduct = curl_exec($curl);
        curl_close($curl);

        return json_encode($newProduct);
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
        $attributes = $service->findCategory($categoryCod);
        $categoryData[$attributes->getName()][0]['id'] = $attributes->getId();
        $categoryData[$attributes->getName()][0]['name'] = $attributes->getName();
        foreach ($attributes->getChildrenCategories() as $key => $attribute) {
            unset($categoryData);
            $categories = $service->findCategory($attribute->getId());
            $categoryData[$categories->getName()][$key]['id'] = $categories->getId();
            $categoryData[$categories->getName()][$key]['name'] = $categories->getName();
            foreach ($categories->getChildrenCategories() as $keyCategory => $category) {
                $categoryData[$categories->getName()][$keyCategory]['id'] = $category->getId();
                $categoryData[$categories->getName()][$keyCategory]['name'] = $category->getName();
                $childrenCategories = $service->findCategory($category->getId());
                foreach ($childrenCategories->getChildrenCategories() as $keyChildren => $childrenCategory) {
                    $categoryData[$categories->getName()][$category->getName()][$keyChildren]['id'] = $childrenCategory->getId();
                    $categoryData[$categories->getName()][$category->getName()][$keyChildren]['name'] = $childrenCategory->getName();
                    unset($categoryData[$categories->getName()][$keyCategory]);
                    $lastCategories = $service->findCategory($childrenCategory->getId());
                    foreach ($lastCategories->getChildrenCategories() as $keyLast => $lastCategory) {
                        $categoryData[$categories->getName()][$category->getName()][$childrenCategory->getName()][$keyLast]['id'] = $lastCategory->getId();
                        $categoryData[$categories->getName()][$category->getName()][$childrenCategory->getName()][$keyLast]['name'] = $lastCategory->getName();
                        unset($categoryData[$categories->getName()][$category->getName()][$keyChildren]);
                    }
                }
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
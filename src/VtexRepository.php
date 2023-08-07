<?php

namespace VtexIntegration;

use VtexIntegration\VtexApiHelper;

/**
 * Class VtexRepository
 * Wrapper class for interacting with the VTEX API.
 */
class VtexRepository
{
    private $vtexApi;
    private $sellerId;
    private $affiliateId;
    private $salesChannel;

    /**
     * VtexRepository constructor.
     *
     * @param string $url Base URL of the VTEX store.
     * @param array $credentials API credentials.
     * @param string $sellerId Seller ID.
     * @param string $affiliateId Affiliate ID.
     * @param string $salesChannel Sales channel.
     */
    public function __construct($url, $credentials, $sellerId, $affiliateId, $salesChannel)
    {
        $this->vtexApi = new VtexApiHelper($url, $credentials);
        $this->sellerId = $sellerId;
        $this->affiliateId = $affiliateId;
        $this->salesChannel = $salesChannel;
    }


    /**
     * Retrieves a list of products.
     *
     * @param bool $paginate Whether to paginate the results.
     * @param int $page Page number for pagination.
     * @param int $pageSize Page size for pagination.
     * @param array $filters Additional filters for the request.
     * @return array List of products.
     */
    public function getProducts($paginate = true, $page = 1, $pageSize = 100, $filters = [])
    {
        $products = [];

        do {
            $params = http_build_query([
                'page' => $page,
                'pageSize' => $pageSize,
                // Agregar otros parámetros de filtrado si es necesario
            ]);

            $response = $this->vtexApi->get("api/catalog_system/pvt/sku/stockkeepingunitidsbysaleschannel");
            $products = array_merge($products, $response);

            $page++;
        } while ($paginate && !empty($response));

        return $products;
    }

    /**
     * Search for products in the sales channel and optional filters.
     *
     * @param bool $paginate Whether to paginate the results.
     * @param int $from Starting point for the range of items.
     * @param int $to Ending point for the range of items.
     * @param int $step Step size for date range pagination.
     *
     * @return array Array containing the retrieved products.
     */
    public function searchProducts($paginate = true, $from = 0, $to = 49, $step = 50)
    {
        $products = [];

        do {
            $params = http_build_query([
                '_from' => $from,
                '_to' => $to,
                'fq' => "isAvailablePerSalesChannel_{$this->salesChannel}"
                // Agregar otros parámetros de filtrado si es necesario
            ]);

            $endpoint = "/api/catalog_system/pub/products/search?{$params}";
            $response = $this->vtexApi->get($endpoint);

            $products = array_merge($products, $response);

            $from += $step;
            $to += $step;
        } while ($paginate && !empty($response));

        return $products;
    }

    /**
     * Get price and stock information for multiple SKUs.
     *
     * @param array $skuIds List of SKU IDs to retrieve price and stock for.
     * @param string|null $postalCode Postal code for simulation (optional).
     * @param string|null $country Country code for simulation (optional).
     * @param int $batchSize Batch size for processing SKUs in chunks.
     * @return array Array containing price and stock information for SKUs.
     */
    public function getPriceAndStock($skuIds, $postalCode = null, $country = null, $batchSize = 10)
    {
        $skuBatches = array_chunk($skuIds, $batchSize);

        $pricesAndStock = [];
        foreach ($skuBatches as $skuBatch) {
            $items = array_map(function ($skuId) {
                return [
                    "id" => $skuId,
                    "quantity" => "1",
                    "seller" => $this->sellerId
                ];
            }, $skuBatch);

            $request = [
                "items" => $items,
                "sc" => $this->salesChannel
            ];

            if ($country !== null) {
                $request["country"] = $country;
            }

            if ($postalCode !== null) {
                $request["postalCode"] = $postalCode;
            }

            $endpoint = "/api/fulfillment/pvt/orderForms/simulation";
            $response = $this->vtexApi->post($endpoint, $request);
            $pricesAndStock = array_merge($pricesAndStock, $response);
        }
        return $pricesAndStock;
    }

    public function getProduct($productId)
    {
        $endpoint = "/api/catalog/pvt/product/{$productId}";
        $response = $this->vtexApi->get($endpoint);

        return $response;
    }

    /**
     * Retrieves information about a specific SKU.
     *
     * @param string $skuId SKU ID.
     * @return array SKU information.
     */
    public function getSku($skuId)
    {
        $endpoint = "/api/catalog_system/pvt/sku/stockkeepingunitbyid/{$skuId}";
        $response = $this->vtexApi->get($endpoint);

        return $response;
    }

    /**
     * Retrieves information about multiple SKUs by their IDs.
     *
     * @param array $skuIds List of SKU IDs.
     * @return array List of SKU information.
     */
    public function getSkusByIds($skuIds)
    {
        $skuInfo = [];

        foreach ($skuIds as $skuId) {
            $skuInfo[] = $this->getSku($skuId);
        }

        return $skuInfo;
    }

    public function sendOrder($orderData)
    {
        $endpoint = "/api/oms/pvt/orders";
        $response = $this->vtexApi->post($endpoint, $orderData);

        return $response;
    }

    public function authorizeOrder($orderId)
    {
        $endpoint = "/api/oms/pvt/orders/{$orderId}/authorize";
        $response = $this->vtexApi->post($endpoint);

        return $response;
    }

    public function searchOrders($filters = [])
    {
        $endpoint = "/api/oms/pvt/orders/search";
        $response = $this->vtexApi->get($endpoint, $filters);

        return $response;
    }

    public function cancelOrder($orderId)
    {
        $endpoint = "/api/oms/pvt/orders/{$orderId}/cancel";
        $response = $this->vtexApi->post($endpoint);

        return $response;
    }

    public function setVtexApi($vtexApi)
    {
        $this->vtexApi = $vtexApi;
    }
}

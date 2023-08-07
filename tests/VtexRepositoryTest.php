<?php
use PHPUnit\Framework\TestCase;
use VtexIntegration\VtexRepository;
use VtexIntegration\VtexApiHelper;

class VtexRepositoryTest extends TestCase
{
    public function testSearchProducts()
    {
        $vtexApiMock = $this->getMockBuilder(VtexApiHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $vtexApiMock->expects($this->once())
            ->method('get')
            ->willReturn(['product1', 'product2']);

        $vtexRepo = new VtexRepository('https://example.com', ['appkey'=>'rrrrr','apptoken'=>'rrrrr'], 'sellerId', 'affiliateId', 'salesChannel');
        $vtexRepo->setVtexApi($vtexApiMock);

        $result = $vtexRepo->searchProducts(false); // Disable pagination for testing

        $this->assertCount(2, $result);
        $this->assertEquals('product1', $result[0]);
        $this->assertEquals('product2', $result[1]);
    }

    public function testGetPriceAndStock()
    {
        $vtexApiMock = $this->getMockBuilder(VtexApiHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $vtexApiMock->expects($this->once())
            ->method('post')
            ->willReturn(['sku1Data', 'sku2Data']);

        $vtexRepo = new VtexRepository('https://example.com', ['appkey'=>'rrrrr','apptoken'=>'rrrrr'], 'sellerId', 'affiliateId', 'salesChannel');
        $vtexRepo->setVtexApi($vtexApiMock);

        $skuIds = ['sku1', 'sku2'];
        $result = $vtexRepo->getPriceAndStock($skuIds);

        $this->assertCount(2, $result);
        $this->assertEquals('sku1Data', $result[0]);
        $this->assertEquals('sku2Data', $result[1]);
    }

    // ... Other test methods for other functions
}
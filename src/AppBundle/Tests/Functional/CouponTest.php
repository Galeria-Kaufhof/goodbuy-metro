<?php

namespace AppBundle\Tests\Functional;

use AppBundle\Tests\TestHelpers;
use PHPQRCode\QRcode;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CouponTest extends WebTestCase
{
    public function testIndexWithCorrectHash()
    {
        $client = static::createClient();
        $kernel = $client->getKernel();
        $secret = $kernel->getContainer()->getParameter('secret');

        $crawler = $client->request('GET', '/customer/123/coupons?hash=' . sha1($secret . '123'));

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains('I am the coupon document for 123', $crawler->filter('body')->text());
    }

    public function testIndexWithWrongHash()
    {
        $client = static::createClient();
        $client->request('GET', '/customer/123/coupons?hash=' . sha1('foo' . '123'));
        $this->assertEquals(500, $client->getResponse()->getStatusCode());
    }

    public function testIndexWithoutHash()
    {
        $client = static::createClient();
        $client->request('GET', '/customer/123/coupons');
        $this->assertEquals(500, $client->getResponse()->getStatusCode());
    }

    /**
     * @runInSeparateProcess
     */
    public function testQrCodeWithCorrectHash()
    {
        $client = static::createClient();
        $kernel = $client->getKernel();
        $secret = $kernel->getContainer()->getParameter('secret');

        $client->request('GET', '/qrcode/12894389.png?hash=' . sha1($secret . '12894389'));

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals('image/png', $client->getResponse()->headers->get('Content-Type'));

        ob_start();
        QRcode::png('12894389');
        $image = ob_get_contents();
        ob_clean();

        $this->assertSame($image, $client->getResponse()->getContent());
    }
}

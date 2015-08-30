<?php

namespace AppBundle\Tests\Functional;

use AppBundle\Tests\TestHelpers;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CouponTest extends WebTestCase
{

    public function testWithCorrectHash()
    {
        $client = static::createClient();
        $kernel = $client->getKernel();
        $secret = $kernel->getContainer()->getParameter('secret');

        $crawler = $client->request('GET', '/customer/123/coupons?hash=' . sha1($secret . '123'));

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains('I am the coupon document for 123', $crawler->filter('body')->text());
    }

    public function testWithWrongHash()
    {
        $client = static::createClient();
        $client->request('GET', '/customer/123/coupons?hash=' . sha1('foo' . '123'));
        $this->assertEquals(500, $client->getResponse()->getStatusCode());
    }

    public function testWithoutHash()
    {
        $client = static::createClient();
        $client->request('GET', '/customer/123/coupons');
        $this->assertEquals(500, $client->getResponse()->getStatusCode());
    }
}

<?php

namespace AppBundle\Tests\Functional;

use AppBundle\Entity\Couponcode;
use AppBundle\Entity\Customer;
use AppBundle\Tests\TestHelpers;
use PHPQRCode\QRcode;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CouponControllerTest extends WebTestCase
{
    use TestHelpers;

    public function testIndexWithExistingAndActivatedCustomerAndCorrectHash()
    {
        $client = static::createClient();

        $this->resetDatabase();

        static::$kernel = static::createKernel();
        static::$kernel->boot();
        $em = static::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $customer = new Customer();
        $customer->setEmail('example@example.org');
        $customer->setActivationCode('abc');
        $customer->setEmployeeNumber('1234567890');
        $customer->setSalesdivision(Customer::SALESDIVISION_MEDIAMARKT_SATURN);
        $customer->setIsActivated(true);
        $em->persist($customer);

        $couponcode = new Couponcode();
        $couponcode->setCode('111');
        $couponcode->setCustomer($customer);
        $em->persist($couponcode);
        $couponcode = new Couponcode();
        $couponcode->setCode('222');
        $couponcode->setCustomer($customer);
        $em->persist($couponcode);
        $couponcode = new Couponcode();
        $couponcode->setCode('333');
        $couponcode->setCustomer($customer);
        $em->persist($couponcode);
        $couponcode = new Couponcode();
        $couponcode->setCode('444');
        $couponcode->setCustomer($customer);
        $em->persist($couponcode);
        $couponcode = new Couponcode();
        $couponcode->setCode('555');
        $couponcode->setCustomer($customer);
        $em->persist($couponcode);
        $couponcode = new Couponcode();
        $couponcode->setCode('666');
        $couponcode->setCustomer($customer);
        $em->persist($couponcode);

        $em->flush();

        $secret = static::$kernel->getContainer()->getParameter('secret');

        $crawler = $client->request(
            'GET',
            '/customer/' . $customer->getId() . '/coupons?hash=' . sha1($secret . $customer->getId())
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains('I am the coupon document for ' . $customer->getId(), $crawler->filter('body')->text());
    }

    public function testIndexWithNonExistantCustomer()
    {
        $client = static::createClient();
        $secret = static::$kernel->getContainer()->getParameter('secret');
        $client->request('GET', '/customer/123/coupons?hash=' . sha1($secret . '123'));
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    public function testIndexWithWrongHash()
    {
        $client = static::createClient();
        $client->request('GET', '/customer/123/coupons?hash=' . sha1('foo' . '123'));
        $this->assertEquals(403, $client->getResponse()->getStatusCode());
    }

    public function testIndexWithoutHash()
    {
        $client = static::createClient();
        $client->request('GET', '/customer/123/coupons');
        $this->assertEquals(403, $client->getResponse()->getStatusCode());
    }

    public function testQrCodeWithCorrectHash()
    {
        $client = static::createClient();
        $kernel = $client->getKernel();
        $secret = $kernel->getContainer()->getParameter('secret');

        $client->request('GET', '/qrcode/9850012501010470001010009160cZjike0TCb7hv0c__0000000000000000002.png?hash=' . sha1($secret . '9850012501010470001010009160cZjike0TCb7hv0c__0000000000000000002'));

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals('image/png', $client->getResponse()->headers->get('Content-Type'));

        ob_start();
        @QRcode::png('9850012501010470001010009160cZjike0TCb7hv0c__0000000000000000002');
        $image = ob_get_contents();
        ob_end_clean();

        $this->assertSame($image, $client->getResponse()->getContent());
    }

    public function testQrCodeWithWrongHash()
    {
        $client = static::createClient();
        $kernel = $client->getKernel();
        $secret = $kernel->getContainer()->getParameter('secret');

        $client->request('GET', '/qrcode/9850012501010470001010009160cZjike0TCb7hv0c__0000000000000000002.png?hash=' . sha1($secret . '12345'));

        $this->assertEquals(403, $client->getResponse()->getStatusCode());
    }

    public function testQrCodeWithOverride()
    {
        $client = static::createClient();

        $client->request('GET', '/qrcode/9850012501010470001010009160cZjike0TCb7hv0c__0000000000000000002.png?override=264d6d73ecde4b9e50ca654e8bf6b7978141dec5');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals('image/png', $client->getResponse()->headers->get('Content-Type'));

        ob_start();
        @QRcode::png('9850012501010470001010009160cZjike0TCb7hv0c__0000000000000000002');
        $image = ob_get_contents();
        ob_end_clean();

        $this->assertSame($image, $client->getResponse()->getContent());
    }
}

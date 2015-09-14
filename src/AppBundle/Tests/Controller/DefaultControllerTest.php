<?php

namespace AppBundle\Tests\Functional;

use AppBundle\Entity\Couponcode;
use AppBundle\Entity\Customer;
use AppBundle\Tests\TestHelpers;
use PHPQRCode\QRcode;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase
{
    public function testOptInInfos()
    {
        $client = static::createClient();
        $crawler = $client->request(
            'GET',
            '/optin-infos/'
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains('Die Einwilligung können Sie jederzeit mit Wirkung für die Zukunft widerrufen.', $crawler->filter('body')->text());
    }
}

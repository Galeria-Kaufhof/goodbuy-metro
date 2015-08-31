<?php

namespace AppBundle\Tests\Service;

use AppBundle\Entity\Couponcode;
use AppBundle\Entity\Customer;
use AppBundle\Service\CouponMapperService;
use AppBundle\Tests\TestHelpers;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CouponMapperServiceTest extends WebTestCase
{
    use TestHelpers;

    public function testMappingWorks()
    {
        $this->resetDatabase();

        $client = static::createClient();
        $container = $client->getContainer();
        $em = $container->get('doctrine.orm.entity_manager');

        $customer = new Customer();
        $customer->setIsActivated(true);
        $customer->setActivationCode('abc');
        $customer->setEmail('example@example.org');
        $customer->setEmployeeNumber('12345');
        $customer->setSalesdivision(Customer::SALESDIVISION_MEDIAMARKT_SATURN);
        $em->persist($customer);

        $couponcode = new Couponcode();
        $couponcode->setCode('111');
        $em->persist($couponcode);
        $couponcode = new Couponcode();
        $couponcode->setCode('222');
        $em->persist($couponcode);
        $couponcode = new Couponcode();
        $couponcode->setCode('333');
        $em->persist($couponcode);
        $couponcode = new Couponcode();
        $couponcode->setCode('444');
        $em->persist($couponcode);

        $em->flush();
        $em->clear();

        $cRepo = $em->getRepository('AppBundle\Entity\Customer');
        $customer = $cRepo->find(1);

        $m = new CouponMapperService($em);
        $m->mapNToCustomer(3, $customer);

        $em->clear();

        $cRepo = $em->getRepository('AppBundle\Entity\Customer');
        $customer = $cRepo->find(1);
        $codes = $customer->getCouponCodes()->getValues();
        $this->assertSame('333', $codes[0]->getCode());
        $this->assertSame('222', $codes[1]->getCode());
        $this->assertSame('111', $codes[2]->getCode());
        $this->assertSame(3, sizeof($codes));

        $m = new CouponMapperService($em);
        $m->mapNToCustomer(1, $customer);

        $em->clear();

        $cRepo = $em->getRepository('AppBundle\Entity\Customer');
        $customer = $cRepo->find(1);
        $codes = $customer->getCouponCodes()->getValues();
        $this->assertSame('444', $codes[0]->getCode());
        $this->assertSame('333', $codes[1]->getCode());
        $this->assertSame('222', $codes[2]->getCode());
        $this->assertSame('111', $codes[3]->getCode());
        $this->assertSame(4, sizeof($codes));
    }

    public function testMappingFailsIfNotEnoughFreeCodes()
    {
        $this->resetDatabase();

        $client = static::createClient();
        $container = $client->getContainer();
        $em = $container->get('doctrine.orm.entity_manager');

        $customer = new Customer();
        $customer->setIsActivated(true);
        $customer->setActivationCode('abc');
        $customer->setEmail('example@example.org');
        $customer->setEmployeeNumber('12345');
        $customer->setSalesdivision(Customer::SALESDIVISION_MEDIAMARKT_SATURN);
        $em->persist($customer);

        $couponcode = new Couponcode();
        $couponcode->setCode('111');

        $em->flush();
        $em->clear();

        $cRepo = $em->getRepository('AppBundle\Entity\Customer');
        $customer = $cRepo->find(1);

        $m = new CouponMapperService($em);
        $m->mapNToCustomer(3, $customer);

        $em->clear();

        $cRepo = $em->getRepository('AppBundle\Entity\Customer');
        $customer = $cRepo->find(1);
        $codes = $customer->getCouponCodes()->getValues();
        $this->assertSame(0, sizeof($codes));
    }
}

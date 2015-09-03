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
        $customer->setEmployeeNumber('12`345-é_ok#2©');
        $customer->setSalesdivision(Customer::SALESDIVISION_MEDIAMARKT_SATURN);
        $customer->setOptInAccepted(false);
        $em->persist($customer);

        $couponcode = new Couponcode();
        $couponcode->setCode('1850012501010470001010009160cZjike0TCb7hv0c__0000000000000000002');
        $em->persist($couponcode);
        $couponcode = new Couponcode();
        $couponcode->setCode('2850012501010470001010009160cZjike0TCb7hv0c__0000000000000000002');
        $em->persist($couponcode);
        $couponcode = new Couponcode();
        $couponcode->setCode('3850012501010470001010009160cZjike0TCb7hv0c__0000000000000000002');
        $em->persist($couponcode);
        $couponcode = new Couponcode();
        $couponcode->setCode('4850012501010470001010009160cZjike0TCb7hv0c__0000000000000000002');
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
        $this->assertSame('3850012501010470001010009160cZjike0TCb7hv0c__12345-ok2________12', $codes[0]->getCode());
        $this->assertSame('2850012501010470001010009160cZjike0TCb7hv0c__12345-ok2________12', $codes[1]->getCode());
        $this->assertSame('1850012501010470001010009160cZjike0TCb7hv0c__12345-ok2________12', $codes[2]->getCode());
        $this->assertSame(3, sizeof($codes));

        $customer->setEmployeeNumber('123`456ö7890abcdefghij*');
        $m = new CouponMapperService($em);
        $m->mapNToCustomer(1, $customer);

        $em->clear();

        $cRepo = $em->getRepository('AppBundle\Entity\Customer');
        $customer = $cRepo->find(1);
        $codes = $customer->getCouponCodes()->getValues();
        $this->assertSame('4850012501010470001010009160cZjike0TCb7hv0c__1234567890abcdefg12', $codes[0]->getCode());
        $this->assertSame('3850012501010470001010009160cZjike0TCb7hv0c__12345-ok2________12', $codes[1]->getCode());
        $this->assertSame('2850012501010470001010009160cZjike0TCb7hv0c__12345-ok2________12', $codes[2]->getCode());
        $this->assertSame('1850012501010470001010009160cZjike0TCb7hv0c__12345-ok2________12', $codes[3]->getCode());
        $this->assertSame(4, sizeof($codes));

        $ccRepo = $em->getRepository('AppBundle\Entity\Couponcode');
        $couponcode = $ccRepo->find(1);
        $this->assertSame('1850012501010470001010009160cZjike0TCb7hv0c__12345-ok2________12', $couponcode->getCode());
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
        $customer->setOptInAccepted(false);
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

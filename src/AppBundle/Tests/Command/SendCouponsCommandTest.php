<?php

use AppBundle\Command\SendCouponsCommand;
use AppBundle\Entity\Customer;
use AppBundle\Entity\Couponcode;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use \Symfony\Component\Console\Input\ArgvInput;

class SendCouponsCommandTest extends KernelTestCase
{
    public function testExecute()
    {
        $kernel = $this->createKernel();
        $kernel->boot();

        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input = new ArgvInput(['', 'doctrine:database:drop', '--no-interaction', '--force', '-q']);
        $application->run($input);
        $input = new ArgvInput(['', 'doctrine:database:create', '--no-interaction', '-q']);
        $application->run($input);
        $input = new ArgvInput(['', 'doctrine:migrations:migrate', '--no-interaction', '-q']);
        $application->run($input);

        $em = $kernel
            ->getContainer()
            ->get('doctrine')
            ->getManager();

        $customer = new Customer();
        $customer->setEmail('example@example.org');
        $customer->setActivationCode('abc');
        $customer->setEmployeeNumber('1234567890');
        $customer->setSalesdivision(Customer::SALESDIVISION_MEDIAMARKT_SATURN);
        $customer->setIsActivated(true);
        $customer->setCouponsHaveBeenSent(false);
        $customer->setOptInAccepted(false);
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
        $couponcode = new Couponcode();
        $couponcode->setCode('555');
        $em->persist($couponcode);
        $couponcode = new Couponcode();
        $couponcode->setCode('666');
        $em->persist($couponcode);

        $em->flush();

        $application->add(new SendCouponsCommand());

        $command = $application->find('app:sendcoupons');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName()));

        $this->assertSame("example@example.org\n", $commandTester->getDisplay());

        $this->assertTrue($customer->getCouponsHaveBeenSent());

        $repo = $em->getRepository('AppBundle\Entity\Couponcode');
        $codes = $repo->findBy(['customer' => 1]);

        $this->assertSame('111', $codes[0]->getCode());
        $this->assertSame('222', $codes[1]->getCode());
        $this->assertSame('333', $codes[2]->getCode());
        $this->assertSame('444', $codes[3]->getCode());
        $this->assertSame('555', $codes[4]->getCode());
        $this->assertSame('666', $codes[5]->getCode());
        $this->assertSame(6, sizeof($codes));
    }
}

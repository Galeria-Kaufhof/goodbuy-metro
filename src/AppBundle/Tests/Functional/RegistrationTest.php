<?php

namespace AppBundle\Tests\Functional;

use AppBundle\Entity\Couponcode;
use AppBundle\Entity\Customer;
use AppBundle\Tests\TestHelpers;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

ini_set('memory_limit', '512M'); // Looks like dataProviders are hungry

class RegistrationTest extends WebTestCase
{
    use TestHelpers;

    private function verifyUserNotCreatedAndNoMailSent(\Symfony\Bundle\FrameworkBundle\Client $client)
    {
        $container = $client->getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('AppBundle\Entity\Customer');
        $customers = $repo->findBy(['email' => 'example@example.org']);
        $this->assertSame(0, sizeof($customers));

        $mailCollector = $client->getProfile()->getCollector('swiftmailer');
        $this->assertEquals(0, $mailCollector->getMessageCount());
    }

    private function startRegistration(\Symfony\Bundle\FrameworkBundle\Client $client, array $fields, $reset = true)
    {
        if ($reset) {
            $this->resetDatabase();
        }

        $crawler = $client->request('GET', '/');
        $buttonNode = $crawler->selectButton('Absenden');
        $form = $buttonNode->form();
        $form->disableValidation();
        $client->enableProfiler();
        $crawler = $client->submit(
            $form,
            $fields
        );
        return $crawler;
    }

    public function testContents()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains('Willkommen zur Registrierung für die "Good Buy METRO" Sonderaktion', $crawler->filter('body')->text());
    }

    public function testFullUseCaseHappyPath()
    {
        $this->resetDatabase();
        $client = static::createClient();

        $container = $client->getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $customerRepo = $em->getRepository('AppBundle\Entity\Customer');

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

        $crawler = $client->request('GET', '/');

        $buttonNode = $crawler->selectButton('Absenden');
        $form = $buttonNode->form();

        $client->enableProfiler();

        $crawler = $client->submit(
            $form,
            [
                'registration[greeting]'           => Customer::GREETING_MRS,
                'registration[firstname]'          => 'René Freiherr',
                'registration[lastname]'           => 'zu Schüßlen-Sêine, Jr. III.',
                'registration[address]'            => 'Êherne Freiherrenstraße 32',
                'registration[zipcode]'            => '41515',
                'registration[city]'               => 'Grevenbroich-Minkeln 2',
                'registration[employeeNumber]'     => '789',
                'registration[email][first]'       => 'example@example.org',
                'registration[email][second]'      => 'example@example.org',
                'registration[salesdivision]'      => '2',
                'registration[optInAccepted]'      => '1',
                'registration[conditionsAccepted]' => '1'
            ]
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        # Auf der Dankesseite darf das Formular nicht mehr angezeigt werden
        $this->assertSame(
            0,
            sizeof($crawler->filter('#registration')->first())
        );

        $this->assertSame(
            'Vielen Dank. Sie erhalten nun eine Aktivierungsmail.',
            trim($crawler->filter('aside.messages div.alert.alert-success')->first()->text())
        );

        $customer = $customerRepo->findOneBy(['email' => 'example@example.org']);
        $this->assertSame('789', $customer->getEmployeeNumber());
        $this->assertSame(2, $customer->getSalesDivision());
        $this->assertSame(true, $customer->getOptInAccepted());
        $this->assertSame(false, $customer->getIsActivated());
        $this->assertSame(false, $customer->getCouponsHaveBeenSent());
        $this->assertSame(0, sizeof($customer->getCouponcodes()));

        $mailCollector = $client->getProfile()->getCollector('swiftmailer');
        $this->assertEquals(1, $mailCollector->getMessageCount());
        $collectedMessages = $mailCollector->getMessages();
        $message = $collectedMessages[0];
        $this->assertSame('Ihre Registrierung für die Good Buy METRO Sonderaktion', $message->getSubject());
        $this->assertEquals('example@example.org', key($message->getTo()));
        $this->assertEquals('goodbuy-metro@jumpmail.galeria-kaufhof.de', key($message->getFrom()));
        $expectedBody = '<html>
<body>
<p>
Guten Tag,
</p>
<p>
Sie erhalten diese E-Mail, da für die Adresse example@example.org eine Anmeldung zur Sonderaktion
"Good Buy METRO" durchgeführt wurde.
</p>
<p>
Um diese Anmeldung abzuschließen und Ihre persönlichen Gutscheine zu erhalten, klicken Sie bitte
auf den folgenden Link:
</p>
<p>
http://localhost:8000/customer/1/confirmation/' . $customer->getActivationCode() . '
</p>
<p>
Mit freundlichen Grüßen,
</p>
<p>
--&nbsp;<br>
GALERIA Kaufhof GmbH<br>
Leonhard-Tietz-Str. 1<br>
50676 Köln<br>
<br>
www.galeria-kaufhof.de<br>
Telefon: 0800-664870103<br>
Telefax: 01805-285028*<br>
Empfänger: GALERIA Kaufhof GmbH<br>
* (0,14 €/Min. aus dem dt. Festnetz, max. 0,42 €/Min. aus Mobilfunknetzen)<br>
<br>
Vorsitzender des Aufsichtsrats:<br>
Lovro Mandac<br>
Geschäftsführung:<br>
Olivier Van den Bossche (Vorsitzender)<br>
Edo Beukema<br>
Rolf Boje<br>
Thomas Fett<br>
Klaus Hellmich<br>
Volker Schlinge<br>
<br>
Sitz der Gesellschaft: Köln<br>
Eingetragen:<br>
Amtsgericht Köln<br>
HRB Nr. 64081<br>
UST-ID DE811142395<br>
WEEE-Reg.-Nr. DE80848693<br>
<br>
Member of METRO GROUP<br>
</p>
</body>
</html>
';
        $this->assertEquals($expectedBody, $message->getBody());

        $client->enableProfiler();
        $crawler = $client->request(
            'GET',
            '/customer/1/confirmation/' . $customer->getActivationCode(),
            [],
            [],
            ['HTTP_X_FORWARDED_FOR' => '123.456.789.0']
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertSame(
            'Vielen Dank, Ihre Freischaltung war erfolgreich. Sie erhalten in wenigen Minuten eine E-Mail mit Ihren persönlichen Rabattcodes.',
            trim($crawler->filter('aside.messages div.alert.alert-success')->first()->text())
        );

        $em->clear();
        $customer = $customerRepo->findOneBy(['email' => 'example@example.org']);
        $this->assertSame(true, $customer->getIsActivated());
        $this->assertSame(false, $customer->getCouponsHaveBeenSent());
        $this->assertNotNull($customer->getDatetimeActivation());
        $this->assertSame('123.456.789.0', $customer->getIpActivation());
        $this->assertSame(0, sizeof($customer->getCouponcodes()));

        $mailCollector = $client->getProfile()->getCollector('swiftmailer');
        $this->assertEquals(0, $mailCollector->getMessageCount());

        // Repeated Activation is not possible
        $crawler = $client->request('GET', '/customer/1/confirmation/' . $customer->getActivationCode());

        $this->assertEquals(410, $client->getResponse()->getStatusCode());

        $this->assertSame(
            'Eine erneute Freischaltung ist nicht möglich!',
            trim($crawler->filter('aside.messages div.alert.alert-danger')->first()->text())
        );
    }

    public function testRegistrationFailsIfConditionsNotAccepted()
    {
        $this->resetDatabase();
        $client = static::createClient();

        $crawler = $client->request('GET', '/');
        $buttonNode = $crawler->selectButton('Absenden');
        $form = $buttonNode->form();
        $client->enableProfiler();
        $crawler = $client->submit(
            $form,
            [
                'registration[employeeNumber]' => '456',
                'registration[email][first]'   => 'example@example.org',
                'registration[email][second]'  => 'example@example.org',
                'registration[salesdivision]'  => '1'
            ]
        );

        $this->assertEquals(422, $client->getResponse()->getStatusCode());

        $this->assertSame(
            'Bitte stimmen Sie den Teilnahmebedingungen zu.',
            trim($crawler->filter('aside.messages div.alert.alert-danger')->first()->text())
        );

        $this->verifyUserNotCreatedAndNoMailSent($client);
    }

    public function testRegistrationWorksIfOptInNotAccepted()
    {
        $this->resetDatabase();
        $client = static::createClient();

        $crawler = $client->request('GET', '/');
        $buttonNode = $crawler->selectButton('Absenden');
        $form = $buttonNode->form();
        $client->enableProfiler();
        $client->submit(
            $form,
            [
                'registration[employeeNumber]'     => '456',
                'registration[email][first]'       => 'example@example.org',
                'registration[email][second]'      => 'example@example.org',
                'registration[salesdivision]'      => '1',
                'registration[conditionsAccepted]' => '1'
            ]
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $container = $client->getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $customerRepo = $em->getRepository('AppBundle\Entity\Customer');

        $customer = $customerRepo->findOneBy(['email' => 'example@example.org']);
        $this->assertSame(false, $customer->getOptInAccepted());

        $mailCollector = $client->getProfile()->getCollector('swiftmailer');
        $this->assertEquals(1, $mailCollector->getMessageCount());
        $collectedMessages = $mailCollector->getMessages();
        $message = $collectedMessages[0];
        $this->assertSame('Ihre Registrierung für die Good Buy METRO Sonderaktion', $message->getSubject());
    }

    public function testRegistrationFailsIfMailaddressIsInvalid()
    {
        $client = static::createClient();
        $crawler = $this->startRegistration(
            $client,
            [
                'registration[employeeNumber]'     => '456',
                'registration[email][first]'       => 'example(at)example.org',
                'registration[email][second]'      => 'example(at)example.org',
                'registration[salesdivision]'      => '1',
                'registration[conditionsAccepted]' => '1',
            ]
        );

        $this->assertEquals(422, $client->getResponse()->getStatusCode());

        $this->assertSame(
            'Das Formular ist nicht korrekt ausgefüllt.',
            trim($crawler->filter('aside.messages div.alert.alert-danger')->first()->text())
        );

        $this->assertSame(
            'Diese E-Mailadresse ist ungültig.',
            trim($crawler->filter('#registration ul li')->first()->text())
        );

        $this->verifyUserNotCreatedAndNoMailSent($client);


        $crawler = $this->startRegistration(
            $client,
            [
                'registration[employeeNumber]'     => '456',
                'registration[email][first]'       => 'example@@example.org',
                'registration[email][second]'      => 'example@@example.org',
                'registration[salesdivision]'      => '1',
                'registration[conditionsAccepted]' => '1',
            ]
        );

        $this->assertEquals(422, $client->getResponse()->getStatusCode());

        $this->assertSame(
            'Das Formular ist nicht korrekt ausgefüllt.',
            trim($crawler->filter('aside.messages div.alert.alert-danger')->first()->text())
        );

        $this->assertSame(
            'Diese E-Mailadresse ist ungültig.',
            trim($crawler->filter('#registration ul li')->first()->text())
        );

        $this->verifyUserNotCreatedAndNoMailSent($client);


        $crawler = $this->startRegistration(
            $client,
            [
                'registration[employeeNumber]'     => '456',
                'registration[email][first]'       => '"example@example.org"',
                'registration[email][second]'      => '"example@example.org"',
                'registration[salesdivision]'      => '1',
                'registration[conditionsAccepted]' => '1',
            ]
        );

        $this->assertEquals(422, $client->getResponse()->getStatusCode());

        $this->assertSame(
            'Das Formular ist nicht korrekt ausgefüllt.',
            trim($crawler->filter('aside.messages div.alert.alert-danger')->first()->text())
        );

        $this->assertSame(
            'Diese E-Mailadresse ist ungültig.',
            trim($crawler->filter('#registration ul li')->first()->text())
        );

        $this->verifyUserNotCreatedAndNoMailSent($client);


        $crawler = $this->startRegistration(
            $client,
            [
                'registration[employeeNumber]'     => '456',
                'registration[email][first]'       => "example\u0000@example.org",
                'registration[email][second]'      => 'example\u0000@example.org',
                'registration[salesdivision]'      => '1',
                'registration[conditionsAccepted]' => '1',
            ]
        );

        $this->assertEquals(422, $client->getResponse()->getStatusCode());

        $this->assertSame(
            'Das Formular ist nicht korrekt ausgefüllt.',
            trim($crawler->filter('aside.messages div.alert.alert-danger')->first()->text())
        );

        $this->assertSame(
            'Diese E-Mailadresse ist ungültig.',
            trim($crawler->filter('#registration ul li')->first()->text())
        );

        $this->verifyUserNotCreatedAndNoMailSent($client);
    }

    public function testRegistrationFailsIfMailaddressIsEmpty()
    {
        $client = static::createClient();
        $crawler = $this->startRegistration(
            $client,
            [
                'registration[employeeNumber]'     => '456',
                'registration[salesdivision]'      => '1',
                'registration[conditionsAccepted]' => '0',
            ]
        );

        $this->assertEquals(422, $client->getResponse()->getStatusCode());

        $this->assertSame(
            'Das Formular ist nicht korrekt ausgefüllt.',
            trim($crawler->filter('aside.messages div.alert.alert-danger')->first()->text())
        );

        $this->assertSame(
            'Bitte geben Sie eine E-Mailadresse an.',
            trim($crawler->filter('#registration ul li')->first()->text())
        );

        $this->verifyUserNotCreatedAndNoMailSent($client);
    }

    /**
     * @dataProvider invalidEmployeesNumberProvider
     */
    public function testRegistrationFailsIfEmployeenumberIsInvalid($salesdivision, $employeeNumber)
    {
        $client = static::createClient();
        $crawler = $this->startRegistration(
            $client,
            [
                'registration[employeeNumber]'     => (string)$employeeNumber,
                'registration[email][first]'       => 'example@example.org',
                'registration[email][second]'      => 'example@example.org',
                'registration[salesdivision]'      => (string)$salesdivision,
                'registration[conditionsAccepted]' => '1'
            ]
        );

        $this->assertEquals(422, $client->getResponse()->getStatusCode());

        $this->assertSame(
            'Das Formular ist nicht korrekt ausgefüllt.',
            trim($crawler->filter('aside.messages div.alert.alert-danger')->first()->text())
        );

        $this->assertSame(
            'Diese Mitarbeiternummer ist ungültig.',
            trim($crawler->filter('#registration ul li')->first()->text())
        );

        $this->verifyUserNotCreatedAndNoMailSent($client);
        unset($crawler);
        unset($client);
    }

    public function invalidEmployeesNumberProvider()
    {
        return [
            [Customer::SALESDIVISION_CASH_CARRY, '234'],
            [Customer::SALESDIVISION_CASH_CARRY, '123.'],
            [Customer::SALESDIVISION_CASH_CARRY, '1 23'],
            [Customer::SALESDIVISION_CASH_CARRY, '1-23'],
            [Customer::SALESDIVISION_CASH_CARRY, '1/23'],
            [Customer::SALESDIVISION_CASH_CARRY, '1_23'],
            [Customer::SALESDIVISION_CASH_CARRY, '123´'],
            [Customer::SALESDIVISION_CASH_CARRY, '123"'],
            [Customer::SALESDIVISION_CASH_CARRY, '123`'],
            [Customer::SALESDIVISION_CASH_CARRY, '123^'],
            [Customer::SALESDIVISION_CASH_CARRY, "1\u23"],
            [Customer::SALESDIVISION_MEDIAMARKT_SATURN, '567'],
            [Customer::SALESDIVISION_MEDIAMARKT_SATURN, '456.'],
            [Customer::SALESDIVISION_MEDIAMARKT_SATURN, '4 56'],
            [Customer::SALESDIVISION_MEDIAMARKT_SATURN, '4-56'],
            [Customer::SALESDIVISION_MEDIAMARKT_SATURN, '4/56'],
            [Customer::SALESDIVISION_MEDIAMARKT_SATURN, '4_56'],
            [Customer::SALESDIVISION_MEDIAMARKT_SATURN, '456´'],
            [Customer::SALESDIVISION_MEDIAMARKT_SATURN, '456"'],
            [Customer::SALESDIVISION_MEDIAMARKT_SATURN, '456`'],
            [Customer::SALESDIVISION_MEDIAMARKT_SATURN, '456^'],
            [Customer::SALESDIVISION_MEDIAMARKT_SATURN, "4\u56"],
            [Customer::SALESDIVISION_METRO_GROUP_LOGISTIK, '890'],
            [Customer::SALESDIVISION_METRO_GROUP_LOGISTIK, '789.'],
            [Customer::SALESDIVISION_METRO_GROUP_LOGISTIK, '7 89'],
            [Customer::SALESDIVISION_METRO_GROUP_LOGISTIK, '7-89'],
            [Customer::SALESDIVISION_METRO_GROUP_LOGISTIK, '7/89'],
            [Customer::SALESDIVISION_METRO_GROUP_LOGISTIK, '7_89'],
            [Customer::SALESDIVISION_METRO_GROUP_LOGISTIK, '789´'],
            [Customer::SALESDIVISION_METRO_GROUP_LOGISTIK, '789"'],
            [Customer::SALESDIVISION_METRO_GROUP_LOGISTIK, '789`'],
            [Customer::SALESDIVISION_METRO_GROUP_LOGISTIK, '789^'],
            [Customer::SALESDIVISION_METRO_GROUP_LOGISTIK, "7\u89"]
        ];
    }

    /**
     * @dataProvider validEmployeesNumberProvider
     */
    public function testRegistrationWorksIfEmployeenumberIsValid($salesdivision, $employeeNumber)
    {
        $client = static::createClient();
        $this->startRegistration(
            $client,
            [
                'registration[employeeNumber]'     => (string)$employeeNumber,
                'registration[email][first]'       => 'example@example.org',
                'registration[email][second]'      => 'example@example.org',
                'registration[salesdivision]'      => (string)$salesdivision,
                'registration[conditionsAccepted]' => '1'
            ]
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        unset($client);
    }

    public function validEmployeesNumberProvider()
    {
        return [
            [Customer::SALESDIVISION_CASH_CARRY, ' 123 '],
            [Customer::SALESDIVISION_CASH_CARRY, '             123         '],
            [Customer::SALESDIVISION_CASH_CARRY, '123'],
            [Customer::SALESDIVISION_MEDIAMARKT_SATURN, ' 456 '],
            [Customer::SALESDIVISION_MEDIAMARKT_SATURN, '             456         '],
            [Customer::SALESDIVISION_MEDIAMARKT_SATURN, '456'],
            [Customer::SALESDIVISION_METRO_GROUP_LOGISTIK, ' 789 '],
            [Customer::SALESDIVISION_METRO_GROUP_LOGISTIK, '             789         '],
            [Customer::SALESDIVISION_METRO_GROUP_LOGISTIK, '789'],
        ];
    }

    public function testRegistrationFailsIfEmployeenumberIsEmpty()
    {
        $client = static::createClient();
        $crawler = $this->startRegistration(
            $client,
            [
                'registration[email][first]'       => 'example@example.org',
                'registration[email][second]'      => 'example@example.org',
                'registration[salesdivision]'      => '1',
                'registration[conditionsAccepted]' => '1'
            ]
        );

        $this->assertEquals(422, $client->getResponse()->getStatusCode());

        $this->assertSame(
            'Das Formular ist nicht korrekt ausgefüllt.',
            trim($crawler->filter('aside.messages div.alert.alert-danger')->first()->text())
        );

        $this->assertSame(
            'Diese Mitarbeiternummer ist ungültig.',
            trim($crawler->filter('#registration ul li')->first()->text())
        );

        $this->verifyUserNotCreatedAndNoMailSent($client);
    }

    public function testRegistrationFailsIfSalesdivisionAndEmployeenumberAreNotUnique()
    {
        $client = static::createClient();
        $crawler = $this->startRegistration(
            $client,
            [
                'registration[email][first]'       => 'example@example.org',
                'registration[email][second]'      => 'example@example.org',
                'registration[salesdivision]'      => '1',
                'registration[employeeNumber]'     => '456',
                'registration[conditionsAccepted]' => '1'
            ]
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $crawler = $this->startRegistration(
            $client,
            [
                'registration[email][first]'       => 'example2@example.org',
                'registration[email][second]'      => 'example2@example.org',
                'registration[salesdivision]'      => '1',
                'registration[employeeNumber]'     => '456',
                'registration[conditionsAccepted]' => '1'
            ],
            false
        );

        $this->assertEquals(403, $client->getResponse()->getStatusCode());

        $this->assertSame(
            'Diese Mitarbeiternummer kann nicht erneut für eine Registrierung verwendet werden.',
            trim($crawler->filter('aside.messages div.alert.alert-danger')->first()->text())
        );
    }

    public function testRegistrationWorksIfSalesdivisionAndEmployeenumberAreUnique()
    {
        $client = static::createClient();
        $this->startRegistration(
            $client,
            [
                'registration[email][first]'       => 'example@example.org',
                'registration[email][second]'      => 'example@example.org',
                'registration[salesdivision]'      => '0',
                'registration[employeeNumber]'     => '123',
                'registration[conditionsAccepted]' => '1'
            ]
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->startRegistration(
            $client,
            [
                'registration[email][first]'       => 'example2@example.org',
                'registration[email][second]'      => 'example2@example.org',
                'registration[salesdivision]'      => '1',
                'registration[employeeNumber]'     => '456',
                'registration[conditionsAccepted]' => '1'
            ],
            false
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function testRegistrationFailsIfSalesdivisionIsInvalid()
    {
        $client = static::createClient();
        $crawler = $this->startRegistration(
            $client,
            [
                'registration[email][first]'       => 'example@example.org',
                'registration[email][second]'      => 'example@example.org',
                'registration[employeeNumber]'     => '456',
                'registration[salesdivision]'      => '5',
                'registration[conditionsAccepted]' => '1'
            ]
        );

        $this->assertEquals(422, $client->getResponse()->getStatusCode());

        $this->assertSame(
            'Das Formular ist nicht korrekt ausgefüllt.',
            trim($crawler->filter('aside.messages div.alert.alert-danger')->first()->text())
        );

        $this->assertSame(
            'This value is not valid.',
            trim($crawler->filter('#registration ul li')->first()->text())
        );

        $this->verifyUserNotCreatedAndNoMailSent($client);
    }

    public function testRegistrationFailsIfGreetingIsInvalid()
    {
        $client = static::createClient();
        $crawler = $this->startRegistration(
            $client,
            [
                'registration[email][first]'       => 'example@example.org',
                'registration[email][second]'      => 'example@example.org',
                'registration[employeeNumber]'     => '456',
                'registration[salesdivision]'      => '2',
                'registration[greeting]'           => '2',
                'registration[conditionsAccepted]' => '1'
            ]
        );

        $this->assertEquals(422, $client->getResponse()->getStatusCode());

        $this->assertSame(
            'Das Formular ist nicht korrekt ausgefüllt.',
            trim($crawler->filter('aside.messages div.alert.alert-danger')->first()->text())
        );

        $this->assertSame(
            'This value is not valid.',
            trim($crawler->filter('#registration ul li')->first()->text())
        );

        $this->verifyUserNotCreatedAndNoMailSent($client);
    }

    public function testRegistrationFailsIfFirstnameContainsInvalidCharacters()
    {
        $client = static::createClient();
        $crawler = $this->startRegistration(
            $client,
            [
                'registration[firstname]'          => 'foo^bar',
                'registration[employeeNumber]'     => '456',
                'registration[email][first]'       => 'example@example.org',
                'registration[email][second]'      => 'example@example.org',
                'registration[salesdivision]'      => '1',
                'registration[conditionsAccepted]' => '1'
            ]
        );

        $this->assertEquals(422, $client->getResponse()->getStatusCode());

        $this->assertSame(
            'Das Formular ist nicht korrekt ausgefüllt.',
            trim($crawler->filter('aside.messages div.alert.alert-danger')->first()->text())
        );

        $this->assertSame(
            'Die Angabe des Vornamens enthält ungültige Zeichen.',
            trim($crawler->filter('#registration ul li')->first()->text())
        );

        $this->verifyUserNotCreatedAndNoMailSent($client);
    }

    public function testRegistrationFailsIfLastnameContainsInvalidCharacters()
    {
        $client = static::createClient();
        $crawler = $this->startRegistration(
            $client,
            [
                'registration[lastname]'           => 'foo^bar',
                'registration[employeeNumber]'     => '456',
                'registration[email][first]'       => 'example@example.org',
                'registration[email][second]'      => 'example@example.org',
                'registration[salesdivision]'      => '1',
                'registration[conditionsAccepted]' => '1'
            ]
        );

        $this->assertEquals(422, $client->getResponse()->getStatusCode());

        $this->assertSame(
            'Das Formular ist nicht korrekt ausgefüllt.',
            trim($crawler->filter('aside.messages div.alert.alert-danger')->first()->text())
        );

        $this->assertSame(
            'Die Angabe des Nachnamens enthält ungültige Zeichen.',
            trim($crawler->filter('#registration ul li')->first()->text())
        );

        $this->verifyUserNotCreatedAndNoMailSent($client);
    }

    public function testRegistrationFailsIfAddressContainsInvalidCharacters()
    {
        $client = static::createClient();
        $crawler = $this->startRegistration(
            $client,
            [
                'registration[address]'            => 'foo^bar',
                'registration[employeeNumber]'     => '456',
                'registration[email][first]'       => 'example@example.org',
                'registration[email][second]'      => 'example@example.org',
                'registration[salesdivision]'      => '1',
                'registration[conditionsAccepted]' => '1'
            ]
        );

        $this->assertEquals(422, $client->getResponse()->getStatusCode());

        $this->assertSame(
            'Das Formular ist nicht korrekt ausgefüllt.',
            trim($crawler->filter('aside.messages div.alert.alert-danger')->first()->text())
        );

        $this->assertSame(
            'Die Angabe zu Straße und Hausnummer enthält ungültige Zeichen.',
            trim($crawler->filter('#registration ul li')->first()->text())
        );

        $this->verifyUserNotCreatedAndNoMailSent($client);
    }

    public function testRegistrationFailsIfZipcodeContainsInvalidCharacters()
    {
        $client = static::createClient();
        $crawler = $this->startRegistration(
            $client,
            [
                'registration[zipcode]'            => '12_45',
                'registration[employeeNumber]'     => '456',
                'registration[email][first]'       => 'example@example.org',
                'registration[email][second]'      => 'example@example.org',
                'registration[salesdivision]'      => '1',
                'registration[conditionsAccepted]' => '1'
            ]
        );

        $this->assertEquals(422, $client->getResponse()->getStatusCode());

        $this->assertSame(
            'Das Formular ist nicht korrekt ausgefüllt.',
            trim($crawler->filter('aside.messages div.alert.alert-danger')->first()->text())
        );

        $this->assertSame(
            'Diese Postleitzahl ist ungültig.',
            trim($crawler->filter('#registration ul li')->first()->text())
        );

        $this->verifyUserNotCreatedAndNoMailSent($client);
    }

    public function testRegistrationFailsIfCityContainsInvalidCharacters()
    {
        $client = static::createClient();
        $crawler = $this->startRegistration(
            $client,
            [
                'registration[city]'               => 'Neuss`',
                'registration[employeeNumber]'     => '456',
                'registration[email][first]'       => 'example@example.org',
                'registration[email][second]'      => 'example@example.org',
                'registration[salesdivision]'      => '1',
                'registration[conditionsAccepted]' => '1'
            ]
        );

        $this->assertEquals(422, $client->getResponse()->getStatusCode());

        $this->assertSame(
            'Das Formular ist nicht korrekt ausgefüllt.',
            trim($crawler->filter('aside.messages div.alert.alert-danger')->first()->text())
        );

        $this->assertSame(
            'Die Angabe zur Stadt enthält ungültige Zeichen.',
            trim($crawler->filter('#registration ul li')->first()->text())
        );

        $this->verifyUserNotCreatedAndNoMailSent($client);
    }

    public function testRegistrationFailsIfMailAlreadyExists()
    {
        $this->resetDatabase();
        $client = static::createClient();

        $crawler = $client->request('GET', '/');
        $buttonNode = $crawler->selectButton('Absenden');
        $form = $buttonNode->form();
        $client->submit(
            $form,
            [
                'registration[employeeNumber]'     => '456',
                'registration[email][first]'       => 'example@example.org',
                'registration[email][second]'      => 'example@example.org',
                'registration[salesdivision]'      => '1',
                'registration[conditionsAccepted]' => '1'
            ]
        );

        $crawler = $client->request('GET', '/');
        $buttonNode = $crawler->selectButton('Absenden');
        $form = $buttonNode->form();
        $client->enableProfiler();
        $crawler = $client->submit(
            $form,
            [
                'registration[employeeNumber]'     => '123',
                'registration[email][first]'       => 'example@example.org',
                'registration[email][second]'      => 'example@example.org',
                'registration[salesdivision]'      => '0',
                'registration[conditionsAccepted]' => '1'
            ]
        );

        $this->assertEquals(403, $client->getResponse()->getStatusCode());

        $this->assertSame(
            'Diese E-Mail Adresse kann nicht erneut für eine Registrierung verwendet werden.',
            trim($crawler->filter('aside.messages div.alert.alert-danger')->first()->text())
        );

        $container = $client->getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('AppBundle\Entity\Customer');
        $customers = $repo->findBy(['email' => 'example@example.org']);
        $this->assertSame(1, sizeof($customers));

        $mailCollector = $client->getProfile()->getCollector('swiftmailer');
        $this->assertEquals(0, $mailCollector->getMessageCount());
    }

    public function testRegistrationFailsIfEmployeeNumberAlreadyExists()
    {
        $this->resetDatabase();
        $client = static::createClient();

        $crawler = $client->request('GET', '/');
        $buttonNode = $crawler->selectButton('Absenden');
        $form = $buttonNode->form();
        $client->submit(
            $form,
            [
                'registration[employeeNumber]'     => '456',
                'registration[email][first]'       => 'example@example.org',
                'registration[email][second]'      => 'example@example.org',
                'registration[salesdivision]'      => '1',
                'registration[conditionsAccepted]' => '1'
            ]
        );

        $crawler = $client->request('GET', '/');
        $buttonNode = $crawler->selectButton('Absenden');
        $form = $buttonNode->form();
        $client->enableProfiler();
        $crawler = $client->submit(
            $form,
            [
                'registration[employeeNumber]'     => '456',
                'registration[email][first]'       => 'other@example.org',
                'registration[email][second]'      => 'other@example.org',
                'registration[salesdivision]'      => '1',
                'registration[conditionsAccepted]' => '1'
            ]
        );

        $this->assertEquals(403, $client->getResponse()->getStatusCode());

        $this->assertSame(
            'Diese Mitarbeiternummer kann nicht erneut für eine Registrierung verwendet werden.',
            trim($crawler->filter('aside.messages div.alert.alert-danger')->first()->text())
        );

        $container = $client->getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('AppBundle\Entity\Customer');
        $customers = $repo->findBy(['employeeNumber' => '456']);
        $this->assertSame(1, sizeof($customers));

        $mailCollector = $client->getProfile()->getCollector('swiftmailer');
        $this->assertEquals(0, $mailCollector->getMessageCount());
    }

    public function testActivationFailsWithWrongCode()
    {
        $this->resetDatabase();
        $client = static::createClient();

        $crawler = $client->request('GET', '/');
        $buttonNode = $crawler->selectButton('Absenden');
        $form = $buttonNode->form();
        $client->submit(
            $form,
            [
                'registration[employeeNumber]'     => '456',
                'registration[email][first]'       => 'example@example.org',
                'registration[email][second]'      => 'example@example.org',
                'registration[salesdivision]'      => '1',
                'registration[conditionsAccepted]' => '1'
            ]
        );

        $client->enableProfiler();
        $crawler = $client->request('GET', '/customer/1/confirmation/3478347878');

        $this->assertEquals(403, $client->getResponse()->getStatusCode());

        $this->assertSame(
            'Dieser Aktivierungslink ist leider ungültig.',
            trim($crawler->filter('aside.messages div.alert.alert-danger')->first()->text())
        );

        $container = $client->getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('AppBundle\Entity\Customer');
        $customer = $repo->findOneBy(['email' => 'example@example.org']);
        $this->assertSame(false, $customer->getIsActivated());

        $mailCollector = $client->getProfile()->getCollector('swiftmailer');
        $this->assertEquals(0, $mailCollector->getMessageCount());
    }

    public function testActivationFailsWithWrongCustomerId()
    {
        $this->resetDatabase();
        $client = static::createClient();

        $client->enableProfiler();
        $crawler = $client->request('GET', '/customer/1/confirmation/3478347878');

        $this->assertEquals(403, $client->getResponse()->getStatusCode());

        $this->assertSame(
            'Dieser Aktivierungslink ist leider ungültig.',
            trim($crawler->filter('aside.messages div.alert.alert-danger')->first()->text())
        );

        $mailCollector = $client->getProfile()->getCollector('swiftmailer');
        $this->assertEquals(0, $mailCollector->getMessageCount());
    }
}

<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\LockHandler;
use PHPQRCode\QRcode;

class SendCouponsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('app:sendcoupons')
            ->setDescription('Send 100 unsent coupon mails');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $lock = new LockHandler('app:sendcoupons');
        if (!$lock->lock()) {
            $output->writeln('The command is already running in another process.');
            return 0;
        }

        $em = $this->getContainer()->get('doctrine')->getManager();
        $repo = $em->getRepository('AppBundle\Entity\Customer');

        $customers = $repo->findBy(
            ['isActivated' => true, 'couponsHaveBeenSent' => false],
            ['datetimeActivation' => 'ASC'],
            100
        );

        foreach ($customers as $customer) {
            $mapped = $this->getContainer()->get('couponmapper')->mapNToCustomer(6, $customer);
            if (!$mapped) {
                $output->writeln('Could not map coupon codes to customer ' . $customer->getId());
                $lock->release();
                return 1;
            }

            $couponcodesData = [];
            foreach ($customer->getCouponcodes() as $couponcode) {
                ob_start();
                @QRcode::png($couponcode->getCode());
                $imageData = ob_get_contents();
                ob_end_clean();
                $couponcodesData[] = base64_encode($imageData);
            }

            $useRemoteFont = true;
            if ($this->getContainer()->get('kernel')->getEnvironment() === 'test') {
                $useRemoteFont = false; // This decouples test runs from Internet connectivity
            }

            $pdfData = $this->getContainer()->get('knp_snappy.pdf')->getOutputFromHtml(
                $this->getContainer()->get('templating')->render(
                    'AppBundle:coupons:index.html.twig',
                    array(
                        'customer' => $customer,
                        'couponcodesData' => $couponcodesData,
                        'useRemoteFont' => $useRemoteFont
                    )
                )
            );

            if ($this->getContainer()->get('kernel')->getEnvironment() === 'dev') {
                file_put_contents('/var/tmp/coupon.pdf', $pdfData);
            }

            $fileLocator = $this->getContainer()->get('file_locator');
            $brandsPdfPath = $fileLocator->locate('@AppBundle/Resources/other/Marken_Selbst_Vertragspartner_2015_09_24.pdf');

            $message = \Swift_Message::newInstance()
                ->setSubject('Ihre Rabattcodes für die Goodbye Kaufhof Sonderaktion')
                ->setFrom('goodbye-metro@kaufhof.de')
                ->setTo($customer->getEmail())
                ->setBody(
                    $this->getContainer()->get('templating')->render(
                        'Emails/couponCodes.html.twig',
                        [
                            'customer' => $customer
                        ]
                    ),
                    'text/html'
                )
                ->attach(\Swift_Attachment::newInstance($pdfData, 'Goodbye-Metro-Rabattcodes.pdf', 'application/pdf'))
                ->attach(\Swift_Attachment::fromPath($brandsPdfPath, 'application/pdf'));

            $this->getContainer()->get('mailer')->send($message);

            $customer->setCouponsHaveBeenSent(true);
            $em->flush();

            $output->writeln($customer->getEmail());
        }
        $lock->release();
    }
}

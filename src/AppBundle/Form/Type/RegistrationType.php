<?php

namespace AppBundle\Form\Type;

use AppBundle\Entity\Customer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use EWZ\Bundle\RecaptchaBundle\Validator\Constraints\IsTrue as RecaptchaTrue;

class RegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'greeting',
                'choice',
                [
                    'choices' => [
                        Customer::GREETING_MRS => 'Frau',
                        Customer::GREETING_MR => 'Herr'
                    ],
                    'required' => false,
                    'label' => 'Anrede:'
                ])
            ->add('firstname',      'text',  ['label' => 'Vorname:', 'required' => false])
            ->add('lastname',       'text',  ['label' => 'Nachname:', 'required' => false])
            ->add('address',        'text',  ['label' => 'Straße und Hausnummer:', 'required' => false])
            ->add('zipcode',        'text',  ['label' => 'PLZ:', 'max_length' => 5, 'required' => false])
            ->add('city',           'text',  ['label' => 'Ort:', 'required' => false])
            ->add('email',          'email', ['label' => 'E-Mail Adresse: *', 'required' => true])
            ->add('employeeNumber', 'text',  ['label' => 'Ihre Mitarbeiternummer: *', 'required' => true])
            ->add(
                'salesdivision',
                'choice',
                [
                    'choices' => [
                        Customer::SALESDIVISION_CASH_CARRY => 'Cash & Carry',
                        Customer::SALESDIVISION_MEDIAMARKT_SATURN => 'MediaMarkt / Saturn',
                        Customer::SALESDIVISION_REAL => 'Real',
                        Customer::SALESDIVISION_REDCOON => 'redcoon'
                    ],
                    'required' => true,
                    'multiple' => false,
                    'label'    => 'Ihre Vertriebslinie: *'
                ])
            ->add(
                'conditionsAccepted',
                'checkbox',
                [
                    'label' => 'Ich willige ein, dass meine E-Maildaten für die Aktion "Goodbye Kaufhof" für den
Zeitraum vom 24.-30.09.2015 zum Zwecke der Abwicklung gespeichert und nach Abschluss der Aktion gelöscht werden: *',
                    'mapped' => false,
                    'required' => true,
                    'label_attr' => ['class' => 'non-bold display-inline']
                ]
            )
            ->add(
                'optInAccepted',
                'checkbox',
                [
                    'label' => 'Weiterhin willige ich ein, dass meine erhobene E-Mailadresse zu Marketingzwecken und Werbung nur von den folgenden Firmen GALERIA Kaufhof GmbH, real,-, Metro Cash & Carry Deutschland, Media Saturn und der Hudson Bay Company genutzt werden darf: ',
                    'required' => false,
                    'label_attr' => ['class' => 'non-bold display-inline']
                ]
            )
            ->add(
                'recaptcha',
                'ewz_recaptcha',
                [
                    'label' => 'Verifizierung: *',
                    'mapped' => false,
                    'constraints' => [
                        new RecaptchaTrue(['message' => 'Bitte aktivieren Sie das CAPTCHA.'])
                    ]
                ]
            )
            ->add('Save', 'submit', ['label' => 'Absenden', 'attr' => ['class' => 'btn btn-primary']]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class'         => '\AppBundle\Entity\Customer',
                'csrf_protection'    => false,
                'allow_extra_fields' => true
            ]
        );
    }

    public function getName()
    {
        return 'registration';
    }
}

<?php

namespace AppBundle\Form\Type;

use AppBundle\Entity\Customer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
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
            ->add('employeeNumber', 'text',  ['label' => 'Ihre Mitarbeiternummer (nur Ziffern ohne Leer- oder Trennzeichen!): *', 'required' => true])
            ->add(
                'salesdivision',
                'choice',
                [
                    'choices' => [
                        Customer::SALESDIVISION_CASH_CARRY => 'METRO Cash & Carry',
                        Customer::SALESDIVISION_MEDIAMARKT_SATURN => 'Media-Saturn',
                        Customer::SALESDIVISION_REAL => 'Real',
                        Customer::SALESDIVISION_METRO_GROUP_LOGISTIK => 'METRO Group Logistik'
                    ],
                    'required' => true,
                    'multiple' => false,
                    'label'    => 'Ihre Vertriebslinie: *'
                ])
            ->add(
                'conditionsAccepted',
                'checkbox',
                [
                    'label' => 'Ich willige ein, dass meine E-Maildaten für die Aktion "Good Buy METRO" für den
Zeitraum vom 24.-30.09.2015 zum Zwecke der Abwicklung gespeichert und nach Abschluss der Aktion gelöscht werden. *',
                    'mapped' => false,
                    'required' => true,
                    'label_attr' => ['class' => 'non-bold display-inline']
                ]
            )
            ->add(
                'optInAccepted',
                'checkbox',
                [
                    'label' => 'Ja, ich möchte auch weiterhin über spannende Aktionen und Gewinnspiele informiert werden und bin mit der Verwendung meiner erhobenen E-Mail-Adresse zu Marketingzwecken und für die Zusendung von Werbung per E-Mail der folgenden Firmen: GALERIA Kaufhof GmbH, real,- SB Warenhaus GmbH, Metro Cash & Carry Deutschland GmbH, Media Markt E-Business GmbH und der Hudson‘s Bay Company einverstanden. Das Einverständnis kann ich jederzeit widerrufen.',
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

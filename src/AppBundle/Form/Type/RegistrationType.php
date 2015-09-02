<?php

namespace AppBundle\Form\Type;

use AppBundle\Entity\Customer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
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
                        Customer::SALESDIVISION_REAL => 'Real'
                    ],
                    'required' => true,
                    'multiple' => false,
                    'label'    => 'Ihre Vertriebslinie: *'
                ])
            ->add(
                'optInAccepted',
                'checkbox',
                [
                    'label' => 'Ich stimme den Teilnahmebedingungen zu: *',
                    'mapped' => false,
                    'label_attr' => ['class' => 'display-inline']
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

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class'         => 'AppBundle\Entity\Customer',
                'csrf_protection'    => true,
                'allow_extra_fields' => true
            ]
        );
    }

    public function getName()
    {
        return 'registration';
    }
}

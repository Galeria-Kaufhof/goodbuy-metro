<?php

namespace AppBundle\Form\Type;

use AppBundle\Entity\Customer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

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
                        Customer::GREETING_MRS => 'Herr'
                    ],
                    'required' => false,
                ])
            ->add('firstname',      'text',  ['label' => 'Vorname:', 'required' => false])
            ->add('lastname',       'text',  ['label' => 'Nachname:', 'required' => false])
            ->add('address',        'text',  ['label' => 'Straße und Hausnummer:', 'required' => false])
            ->add('zipcode',        'text',  ['label' => 'PLZ:', 'required' => false])
            ->add('city',           'text',  ['label' => 'Ort:', 'required' => false])
            ->add('email',          'email', ['label' => 'E-Mail Adresse:', 'required' => true])
            ->add('employeeNumber', 'text',  ['label' => 'Ihre Mitarbeiternummer:', 'required' => true])
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
                    'label'    => 'Ihre Vertriebslinie:'
                ])
            ->add('Save', 'submit', ['label' => 'Absenden', 'attr' => ['class' => 'btn-primary']]);
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

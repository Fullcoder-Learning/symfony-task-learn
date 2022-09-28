<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;


class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, array(
                'required' => true, 
            ))
            ->add('password', PasswordType::class, array(
                'required' => false,
                'empty_data' => '' 
            ))
            ->add('name')
            ->add('lastname')
            ->add('avatar', FileType::class, [
                'required' => false, 
                'data_class' => null, 
                'constraints' => [ 
                    new File([
                        'maxSize' => '1024k',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/jpg',
                            'image/png'
                        ],
                        'mimeTypesMessage' => 'El formato de archivo no es correcto'
                    ])
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}

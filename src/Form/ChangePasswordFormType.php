<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Settings page: change your own password.
 * Not linked to an entity (no data_class): the controller reads the values.
 */
class ChangePasswordFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('currentPassword', PasswordType::class, [
                'attr' => ['autocomplete' => 'current-password'],
                'constraints' => [
                    new NotBlank(message: 'Enter your current password.'),
                    // checks it against the password of the logged-in admin
                    new UserPassword(message: 'Your current password is not correct.'),
                ],
            ])
            ->add('newPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'The two new passwords don’t match.',
                'first_options' => ['attr' => ['autocomplete' => 'new-password']],
                'second_options' => ['attr' => ['autocomplete' => 'new-password']],
                'constraints' => [
                    new NotBlank(message: 'Enter a new password.'),
                    new Length(min: 8, minMessage: 'The new password should be at least {{ limit }} characters', max: 4096),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}

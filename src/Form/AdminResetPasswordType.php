<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Admin: set a new password for another account (for example a staff member
 * who forgot theirs). The controller hashes it before saving.
 */
class AdminResetPasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('newPassword', RepeatedType::class, [
            'type' => PasswordType::class,
            'invalid_message' => 'The two passwords don’t match.',
            'first_options' => ['attr' => ['autocomplete' => 'new-password']],
            'second_options' => ['attr' => ['autocomplete' => 'new-password']],
            'constraints' => [
                new NotBlank(message: 'Enter a new password.'),
                new Length(min: 8, minMessage: 'The password should be at least {{ limit }} characters', max: 4096),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}

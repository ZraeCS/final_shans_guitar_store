<?php

namespace App\Form;

use App\Entity\User;
use App\Enum\UserRole;
use App\Enum\UserStatus;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Admin: edit an account (name, email, phone, role, status).
 * When admins edit THEMSELVES, role and status are locked (option is_self),
 * so nobody can accidentally lock themselves out.
 */
class UserEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['empty_data' => ''])
            ->add('email', EmailType::class, ['empty_data' => ''])
            ->add('phone', TelType::class, ['required' => false])
            ->add('role', EnumType::class, [
                'class' => UserRole::class,
                'choice_label' => fn (UserRole $r): string => $r->label(),
                'disabled' => $options['is_self'],
            ])
            ->add('status', EnumType::class, [
                'class' => UserStatus::class,
                'choice_label' => fn (UserStatus $s): string => $s->label(),
                'disabled' => $options['is_self'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_self' => false,
        ]);
        $resolver->setAllowedTypes('is_self', 'bool');
    }
}

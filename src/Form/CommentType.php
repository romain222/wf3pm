<?php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class CommentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('comment', TextareaType::class)
            ->add(
                'files',
                CollectionType::class,
                [
                    'entry_type' => CommentFileType::class,
                    'allow_add' => true
                ]
            );
        
        if ($options['stateless']) {
            $builder->add('submit', SubmitType::class);
        }
        
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('data_type', CommentType::class);
        $resolver->setDefault('stateless', false);
    }
}


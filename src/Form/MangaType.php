<?php
/**
 * Created by PhpStorm.
 * User: cje
 * Date: 23.10.2018
 * Time: 09:16
 */

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsFalse;

class MangaType extends AbstractType {


  public function buildForm(FormBuilderInterface $builder, array $options) {
    $builder
      ->add('website', ChoiceType::class, [
        'choices' => $options['listWebsites'],
      ])
      ->add('name', TextType::class, ['help' => 'mangadown.form.help.name'])
      ->add('multiple', CheckboxType::class, [
        'mapped' => FALSE,
        'required' => FALSE,
      ])
      ->add('numStartChapter', IntegerType::class)
      ->add('numEndChapter', IntegerType::class)
      ->add('output_format', ChoiceType::class, [
        'choices' => [
          'PDF' => '.pdf',
          'EPUB' => '.epub',
        ],
      ])
      ->add('submit', SubmitType::class);
  }

  public function configureOptions(OptionsResolver $resolver) {
    $resolver->setDefaults([
      'data_class' => 'App\Entity\Manga',
      'listWebsites' => NULL, //array of parameter from manga.yml
    ]);
  }
}
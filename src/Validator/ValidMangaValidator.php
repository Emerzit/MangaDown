<?php

namespace App\Validator;

use App\Repository\MangaRepository;
use phpDocumentor\Reflection\Types\Boolean;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ValidMangaValidator extends ConstraintValidator {

  private $mangaRepository;

  public function __construct(MangaRepository $mangaRepository) {
    $this->mangaRepository = $mangaRepository;
  }

  public function validate($value, Constraint $constraint) {
    /* @var $constraint App\Validator\ValidManga */
    $config = $this->mangaRepository->getConfigByWebsite($value->getWebsite());
    $url = $config['base_url'] . $value->getName();
    $error = get_headers($url)[0];
    $notFound = FALSE;

    //Manga is not on the site
    if (strpos($error, "302") !== FALSE || strpos($error, "404") !== FALSE) {
      $notFound = TRUE;
      $this->context->buildViolation($constraint->message)
        ->setParameter('{{ value }}', $value->getName())
        ->setParameter('{{ type }}', 'manga')
        ->addViolation();
    }

    //Chapter Start not exist
    $surl = $url . $config['configs']['chapter']['prepend'] .
      $this->mangaRepository->formatChapter($config, $value->getNumStartChapter()) .
      $config['configs']['page'] .
      '1' .
      $config['configs']['extension'];
    $error = get_headers($surl)[0];

    if (!$notFound && (strpos($error, "301") !== FALSE || strpos($error, "404") !== FALSE)) {
      $this->context->buildViolation($constraint->message)
        ->setParameter('{{ value }}', $value->getNumStartChapter())
        ->setParameter('{{ type }}', 'starting chapter')
        ->addViolation();
    }

    //Chapter End not exist
    $eurl = $url . $config['configs']['chapter']['prepend'] .
      $this->mangaRepository->formatChapter($config, $value->getNumEndChapter()) .
      $config['configs']['page'] .
      '1' .
      $config['configs']['extension'];
    $error = get_headers($eurl)[0];
    if (!$notFound && (strpos($error, "301") !== FALSE || strpos($error, "404") !== FALSE)) {
      $this->context->buildViolation($constraint->message)
        ->setParameter('{{ value }}', $value->getNumEndChapter())
        ->setParameter('{{ type }}', 'ending chapter')
        ->addViolation();
    }
  }

}

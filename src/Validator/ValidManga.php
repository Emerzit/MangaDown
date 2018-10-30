<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ValidManga extends Constraint
{
    /*
     * Any public properties become valid options for the annotation.
     * Then, use these in your validator class.
     */
    public $message = 'The {{ type }} "{{ value }}" seems to not exist on the website.';

    public function getTargets() {
      return Constraint::CLASS_CONSTRAINT;
    }
}

<?php
/**
 * Created by PhpStorm.
 * User: cje
 * Date: 23.10.2018
 * Time: 09:25
 */

namespace App\Entity;

use App\Validator\ValidManga;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Class Manga
 *
 * @package App\Entity
 * @Assert\GroupSequence({"Manga","strict","request"})
 * @ValidManga(groups={"request"})
 */
class Manga {

  /**
   * @var string
   */
  private $website;

  /**
   * @var string
   *
   * @Assert\Regex(pattern="/^[a-zA-Z0-9-_]+$/",message="Name invalid")
   */
  private $name;

  /**
   * @var int
   *
   * @Assert\GreaterThan(value="0",message="have to be more than zero")
   */
  private $numStartChapter;

  /**
   * @var int
   *
   * @Assert\GreaterThanOrEqual(propertyPath="numStartChapter",groups={"strict"})
   * @Assert\GreaterThan(value="0",message="have to be more than zero")
   */
  private $numEndChapter;

  /**
   * @var string
   */
  private $outputFormat;

  /**
   * @return string
   */
  public function getWebsite() {
    return $this->website;
  }

  /**
   * @param string $website
   */
  public function setWebsite(string $website) {
    $this->website = $website;
  }

  /**
   * @return string
   */
  public function getName() {
    return $this->name;
  }

  /**
   * @param string $name
   */
  public function setName(string $name) {
    $this->name = $name;
  }

  /**
   * @return string
   */
  public function getOutputFormat() {
    return $this->outputFormat;
  }

  /**
   * @param string $output
   */
  public function setOutputFormat(string $outputFormat) {
    $this->outputFormat = $outputFormat;
  }

  /**
   * @return int
   */
  public function getNumStartChapter() {
    return $this->numStartChapter;
  }

  /**
   * @param int $numStartChapter
   */
  public function setNumStartChapter(int $numStartChapter) {
    $this->numStartChapter = $numStartChapter;
  }

  /**
   * @return int
   */
  public function getNumEndChapter() {
    return $this->numEndChapter;
  }

  /**
   * @param int $numEndChapter
   */
  public function setNumEndChapter(int $numEndChapter) {
    $this->numEndChapter = $numEndChapter;
  }

}
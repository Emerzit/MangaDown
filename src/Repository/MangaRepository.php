<?php
/**
 * Created by PhpStorm.
 * User: cje
 * Date: 23.10.2018
 * Time: 12:14
 */

namespace App\Repository;


use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Panther\Client;
use Symfony\Component\Translation\TranslatorInterface;


/**
 * Class MangaRepository
 *
 * @package App\Repository
 */
class MangaRepository {

  private $translator;

  private $params;

  public function __construct(TranslatorInterface $translator, ParameterBagInterface $params) {
    $this->translator = $translator;
    $this->params = $params;
  }

  public function getConfigByWebsite($website) {
    $config = $this->params->get('websites')[$website];
    return $config;
  }

  public function formatChapter($config, int $nb) {
    return sprintf($config['configs']['chapter']['format'], $nb);
  }

  private function getConfigUrl($config, string $pageType) {
    switch ($pageType) {
      case "multi":
        return $config['base_url'] . '%name%' . $config['configs']['chapter']['prepend'] . '%nbChapter%' . $config['configs']['page'] . '%nbPage%' . $config['configs']['extension'];
      case "one":
        return $config['base_url'] . '%name%' . $config['configs']['chapter']['prepend'] . '%nbChapter%';
      default:
        throw new \Exception("unhandled type of page.");
    }
  }

  public function getStrWebsites() {
    $result = [];
    $listWebsites = $this->params->get('websites');
    foreach ($listWebsites as $key => $site) {
      $result[$key] = $key;
    };
    return $result;
  }

  public function getChapterImg($website, $name, $numeroChapter) {
    $config = $this->getConfigByWebsite($website);
    //format the chapter number in format for the specified website
    $chap = $this->formatChapter($config, $numeroChapter);

    //we get the generic url for the specified website
    $downUrl = $this->getConfigUrl($config, "one");
    //specify the manga's name in th generic URL
    $downUrl = str_replace('%name%', $name, $downUrl);
    $downUrl = str_replace('%nbChapter%', $chap, $downUrl);

    //client to crawl html
    $client = Client::createChromeClient();
    try {
      $crawler = $client->request('GET', $downUrl);
    }
    catch (\Exception $exception) {
      echo 'incapacité à accéder au site ' . $exception->getMessage();
      $client->quit();
      throw $exception;
    }

    $images = $crawler->filter($config['configs']['img_tag'])
      ->extract(["data-src"]);

    //without JS
    if ($images == NULL) {
      $images[] = $crawler->filter($config['configs']['img_tag'])
        ->extract(["src"]);
    }

    //test if on or multipage
    if (!(count($images) > 1)) {
      //multi Pages
      $downUrl = $this->getConfigUrl($config, "multi");
      $downUrl = str_replace('%name%', $name, $downUrl);
      $downUrl = str_replace('%nbChapter%', $chap, $downUrl);
      $images = array_merge($images, $this->getChapterImgMultiPages($downUrl, $config['configs']['img_tag'], $client));

    }
    $client->quit();
    return $images;
  }

  // TO DELETE //recursive function to get all last nodes of HTMLDOM
  private function getAllLastNodes(\simple_html_dom_node $root) {
    $array = [];
    if ($root->hasChildNodes()) {
      foreach ($root->children as $node) {
        $array = array_merge($array, $this->getAllLastNodes($node));
      }
    }
    else {
      return [$root];
    }
    return $array;

  }

  /**
   * @param string $downUrl
   * @param string $imgTag use for search the img in the html
   *
   */
  private function getChapterImgMultiPages($downUrl, $imgTag, $client) {
    $images = [];

    //we already have the first image
    $i = 2;


    while (TRUE) {

      $fullUrl = str_replace('%nbPage%', $i, $downUrl);

      try {
        $crawler = $client->request('GET', $fullUrl);
      }
      catch
      (\Exception $exception) {
        //other an error occured
        echo 'incapacité à accéder au site ' . $exception->getMessage();
        $client->quit();
        throw $exception;
      }

      //with js
      $images[] = $crawler->filter($imgTag)->extract(["data-src"]);

      //without js
      if ($images[$i - 2] == NULL) {
        $images[] = $crawler->filter($imgTag)->extract(["src"]);
      }

      //If error 404 then the we are at the end of the chapter and we return the images
      if ($images[$i - 2] == NULL/*(strpos($exception->getMessage(), '404') !== FALSE)*/) {
        return $images;
      }

      $i++;
    }
  }

  public function getPdf($images) {
    // create new PDF document from images
    $pdf = new \FPDF();
    $dpi = 300;
    foreach ($images as $i => $image) {
      $src = $image;
      dump($image);
      //take away whatever is after the .png, .jpeg
      if (($pos = strpos($src, '?')) !== FALSE) {
        $src = substr($src, 0, $pos);
      }
      //src doesn't have http:
      if (!isset(parse_url($src)["scheme"])) {
        $src = "http:" . $src;
      }
      //mm=(pixel*1inch)/DPI
      $dimension = getimagesize($src);

      dump($dimension);
      $width = ($dimension[0] * 25.4) / $dpi;
      $height = ($dimension[1] * 25.4) / $dpi;
      if ($width > $height) {
        $pdf->AddPage('L', [$width, $height]);
      }
      else {
        $pdf->AddPage('P', [$width, $height]);
      }


      $pdf->Image($src, 0, 0, -$dpi);
    }
    return $pdf;
  }
}
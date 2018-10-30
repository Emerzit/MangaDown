<?php
/**
 * Created by PhpStorm.
 * User: cje
 * Date: 23.10.2018
 * Time: 12:14
 */

namespace App\Repository;


use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Translation\TranslatorInterface;


/**
 * Class MangaRepository
 *
 * @package App\Repository
 */
class MangaRepository {

  private $translator;

  private $parser;

  private $params;

  public function __construct(TranslatorInterface $translator, ParameterBagInterface $params, \simple_html_dom $parser) {
    $this->parser = $parser;
    $this->translator = $translator;
    $this->params = $params;
  }

  public function getConfigByWebsite($website) {
    $config = $this->params->get('websites')[$website];
    return $config;
  }

  public function getConfigUrl($config) {
    return $config['base_url'] . '%name%' . $config['configs']['chapter']['prepend'] . '%nbChapter%' . $config['configs']['page'] . '%nbPage%' . $config['configs']['extension'];
    /*switch ($website) {
      case 'FanFox':
        return $config['base_url'] . '%name%' . $config['configs']['chapter']['prepend'] . '%nbChapter%' . $config['configs']['page'] . '%nbPage%' . $config['configs']['extension'];
        break;
      case 'mangaReader':
        return $config['base_url'] . '%name%' . $config['configs']['chapter']['prepend'] . '%nbChapter%' . $config['configs']['page'] . '%nbPage%' . $config['configs']['extension'];
        break;
      case 'Mangas-Lel':
        return $config['base_url'] . '%name%' . $config['configs']['chapter']['prepend'] . '%nbChapter%' . $config['configs']['page'] . '%nbPage%' . $config['configs']['extension'];
      default:
        echo 'default case';
    }*/
  }

  public function getStrWebsites() {
    $result = [];
    $listWebsites = $this->params->get('websites');
    foreach ($listWebsites as $key => $site) {
      $result[$key] = $key;
    };
    return $result;
  }

  /**
   * @param string $downUrl
   * @param string $numeroChapter
   * @param string $imgTag use for search the img in the html
   *
   */
  public function getChapterImg($downUrl, $numeroChapter, $imgTag) {
    $downUrl = str_replace('%nbChapter%', $numeroChapter, $downUrl);
    $imagesCollection = [];
    $i = 1;
    while (TRUE) {
      try {
        $fullUrl = str_replace('%nbPage%', $i, $downUrl);
        $html = file_get_contents($fullUrl);
        $html = str_get_html($html);

        //get image
        $image = $html->find('[id=' . $imgTag . ']')[0];

        $imagesCollection[] = getimagesize($image->attr['src']);
        $imagesCollection[$i - 1]['src'] = $image->attr['src'];
        $i++;
      } catch
      (\Exception $exception) {
        //If error 404 then the we are at the end of the chapter and we return the images
        if ((strpos($exception->getMessage(), '404') !== FALSE)) {
          return $imagesCollection;
        }
        //other an error occured
        echo 'incapacité à accéder au site ' . $exception->getMessage();
        throw $exception;
      }
    }
  }

  public function getPdf($images) {
    // create new PDF document from images
    $pdf = new \FPDF();
    foreach ($images as $image) {
      //mm=(pixel*1inch)/DPI
      $width = ($image[0] * 25.4) / 96;
      $height = ($image[1] * 25.4) / 96;
      if ($width > $height) {
        $pdf->AddPage('L', [$width, $height]);
      }
      else {
        $pdf->AddPage('P', [$width, $height]);
      }
      $src = $image['src'];
      //take away whatever is after the .png, .jpeg
      if (($pos = strpos($src, '?')) !== FALSE) {
        $src = substr($src, 0, $pos);
      }
      $pdf->Image($src, 0, 0, $width, $height);
    }
    return $pdf;
  }

  /**
   * Function to convert a volume in Number of chapter;
   */
  public function getVolume() {

  }

  public function getVolumes() {

  }
}
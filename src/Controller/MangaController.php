<?php
/**
 * Created by PhpStorm.
 * User: cje
 * Date: 23.10.2018
 * Time: 09:04
 */

namespace App\Controller;

use App\Entity\Manga;
use App\Form\MangaType;
use App\Repository\MangaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use ZipArchive;

class MangaController extends Controller {

  private $mangaRepository;

  public function __construct(MangaRepository $mangaRepository) {
    $this->mangaRepository = $mangaRepository;
  }

  public function index(Request $request) {
    $session = new Session();
    $manga = new Manga();
    $form = $this->createForm(MangaType::class, $manga, ['listWebsites' => $this->mangaRepository->getStrWebsites()]);
    //processing Form
    if ($request->isMethod('POST') && $form->handleRequest($request)
        ->isValid()) {
      $config = $this->mangaRepository->getConfigByWebsite($manga->getWebsite());
      $i = $manga->getNumStartChapter();
      //get data not mapped
      $multiple = $form->get("multiple")->getData();
      $pdfs = NULL;
      //we get the generic url for the specified website
      $downUrl = $this->mangaRepository->getConfigUrl($config);
      //specify the manga's name in th generic URL
      $downUrl = str_replace('%name%', $manga->getName(), $downUrl);
      do {
        $chapterImgs = $this->mangaRepository->getChapterImg(
          $downUrl,
          //format the chapter number in format for the specified website
          sprintf($config['configs']['chapter']['format'], $i),
          $config['configs']['img_tag']
        );
        $pdfs[$i] = $this->mangaRepository->getPdf($chapterImgs);
      } while ($multiple && $i++ < $manga->getNumEndChapter());
      //return;
      if (count($pdfs) > 1) {
        $zip = new ZipArchive;
        dump($session->getId());

        $path = 'tmp/' . $session->getId();
        if (!file_exists($path)) {
          mkdir($path, 0777, TRUE);
        }

        $zip_file = 'tmp/' . $session->getId() . '/' . $manga->getName() . '_' . sprintf("%03d", $manga->getNumStartChapter()) . '-' . sprintf("%03d", $manga->getNumEndChapter()) . '.zip';
        if ($zip->open($zip_file, ZipArchive::CREATE) === TRUE) {
          foreach ($pdfs as $key => $pdf) {
            $zip->addFromString($manga->getName() . "_ch" . sprintf("%03d", $key) . ".pdf", $pdf->Output('S'));
          }
          dump($zip_file);
          $zip->close();
          $filename = $zip_file;
          /*header('Content-type: application/zip');
          header('Content-Disposition: attachment; filename="' . basename($zip_file) . '"');
          header("Content-length: " . filesize($zip_file));
          header("Pragma: no-cache");
          header("Expires: 0");
          *///clean output buffer
          ob_clean();
          flush();
          //download
          // readfile($zip_file);
          // unlink($zip_file);

        }
        else {
          throw new \Exception("Un problème est servenu lors de la création du fichier Zip");
        }
      }
      else {
        $path = 'tmp/' . $session->getId();
        if (!file_exists($path)) {
          mkdir($path, 0777, TRUE);
        }
        $filename = $path . "/" . $manga->getName() . "_ch" . sprintf("%03d", $i) . ".pdf";
        $pdfs[$i]->Output('F', $filename);

      }
      return $this->render('download.html.twig', [
        'filename' => $filename,
        'path' => $path,
      ]);
    }
    return $this->render("index.html.twig", ['form' => $form->createView(),]);
  }


}
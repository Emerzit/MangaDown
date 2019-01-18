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
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use ZipArchive;

class MangaController extends AbstractController {

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
      $i = $manga->getNumStartChapter();
      //get data not mapped
      $multiple = $form->get("multiple")->getData();
      $pdfs = NULL;

      do {
        $chapterImgs = $this->mangaRepository->getChapterImg(
          $manga->getWebsite(),
          $manga->getName(),
          $i
        );
        $pdfs[$i] = $this->mangaRepository->getPdf($chapterImgs);
      } while ($multiple && $i++ < $manga->getNumEndChapter());

      if (count($pdfs) > 1) {
        $zip = new ZipArchive;

        //creation of directories
        $path = 'tmp/' . $session->getId();
        if (!file_exists($path)) {
          mkdir($path, 0777, TRUE);
        }

        $zip_file = 'tmp/' . $session->getId() . '/' . $manga->getName() . '_' . sprintf("%03d", $manga->getNumStartChapter()) . '-' . sprintf("%03d", $manga->getNumEndChapter()) . '.zip';
        if ($zip->open($zip_file, ZipArchive::CREATE) === TRUE) {
          foreach ($pdfs as $key => $pdf) {
            $zip->addFromString($manga->getName() . "_ch" . sprintf("%03d", $key) . ".pdf", $pdf->Output('S'));
          }
          $zip->close();
          $filename = $zip_file;
          ob_clean();
          flush();

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
      return $this->redirectToRoute('download', [
        'filename' => $filename,
        'type' => "PDF",
      ]);
      /*return $this->render('download.html.twig', [
        'filename' => $filename,
        'path' => $path,
      ]);*/
    }
    return $this->render("index.html.twig", ['form' => $form->createView(),]);
  }

  public function download(Request $request) {
    dump($request);
    $type = $request->query->get('type');
    $filename = $request->query->get('filename');
    switch ($type) {
      case "ZIP":
        /*header('Content-type: application/zip');
        header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
        header("Content-length: " . filesize($filename));
        header("Pragma: no-cache");
        header("Expires: 0");*/
        break;
      case "PDF":

        /*header('Content-type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
        header("Content-length: " . filesize($filename));
        header("Pragma: no-cache");
        header("Expires: 0");*/
        break;
    }
    return $this->file($filename);
    /*return $this->render('download.html.twig', [
      'filename' => $filename,
      'type' => $type,
    ]);*/
  }

}
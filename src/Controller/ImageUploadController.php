<?php

// src/Controller/ImageUploadController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use thiagoalessio\TesseractOCR\TesseractOCR;
use App\Service\TextValidator;

class ImageUploadController extends AbstractController
{
    #[Route('/api/upload-image', methods: ['POST'])]
    public function upload(Request $request, TextValidator $validator): JsonResponse
    {
        $file = $request->files->get('image');
        if (!$file || !$file->isValid()) {
            return new JsonResponse(['success' => false, 'error' => 'Invalid image'], 400);
        }

        $path = $file->getRealPath();
        $language = $_ENV['TESSERACT_LANG'] ?? $_SERVER['TESSERACT_LANG'] ?? 'eng';
        $tessdataPrefix = $this->resolveTessdataDir($language);

        try {
            $ocr = (new TesseractOCR($path))->lang($language);
            if ($tessdataPrefix !== null) {
                $ocr->tessdataDir($tessdataPrefix);
            }

            $text = $ocr->run();
        } catch (\Throwable $exception) {
            return new JsonResponse([
                'success' => false,
                'error' => 'OCR processing failed',
                'details' => $exception->getMessage(),
            ], 500);
        }

        $result = $validator->validate($text);

        return new JsonResponse([
            'success' => true,
            'extracted_text' => $text,
            'matches_format' => $result['valid'],
            'errors' => $result['errors'],
        ]);
    }

    private function resolveTessdataDir(string $language): ?string
    {
        $normalizedLanguage = trim($language) !== '' ? trim($language) : 'eng';
        $candidates = array_filter([
            $_ENV['TESSDATA_PREFIX'] ?? null,
            $_SERVER['TESSDATA_PREFIX'] ?? null,
            '/usr/share/tessdata',
            '/usr/share/tesseract-ocr/5/tessdata',
            '/usr/share/tesseract-ocr/tessdata',
            '/usr/local/share/tessdata',
            '/tmp/tessdata',
        ]);

        foreach ($candidates as $dir) {
            if (is_file(rtrim($dir, '/').'/'.$normalizedLanguage.'.traineddata')) {
                return $dir;
            }
        }

        $downloadDir = '/tmp/tessdata';
        if ($this->downloadTrainedData($normalizedLanguage, $downloadDir)) {
            return $downloadDir;
        }

        return null;
    }

    private function downloadTrainedData(string $language, string $dir): bool
    {
        if (!is_dir($dir) && !@mkdir($dir, 0777, true) && !is_dir($dir)) {
            return false;
        }

        $filePath = rtrim($dir, '/').'/'.$language.'.traineddata';
        if (is_file($filePath)) {
            return true;
        }

        $url = 'https://github.com/tesseract-ocr/tessdata_fast/raw/main/'.$language.'.traineddata';
        $context = stream_context_create([
            'http' => [
                'timeout' => 20,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $content = @file_get_contents($url, false, $context);
        if ($content === false || $content === '') {
            return false;
        }

        return @file_put_contents($filePath, $content) !== false;
    }
}

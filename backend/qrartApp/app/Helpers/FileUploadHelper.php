<?php

namespace App\Helpers;

use CodeIgniter\HTTP\Files\UploadedFile;

/**
 * File Upload Helper
 *
 * Centralizes file upload logic and validation
 */
class FileUploadHelper
{
    // Configurazione tipi MIME permessi
    private const ALLOWED_MIMES = [
        'image' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
        'audio' => ['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/ogg'],
        'video' => ['video/mp4', 'video/mpeg', 'video/quicktime', 'video/webm'],
    ];

    // Dimensioni massime per tipo (in bytes)
    private const MAX_SIZES = [
        'image' => 5 * 1024 * 1024,   // 5MB
        'audio' => 50 * 1024 * 1024,  // 50MB
        'video' => 100 * 1024 * 1024, // 100MB
    ];

    /**
     * Upload a file with validation
     *
     * @param UploadedFile $file File to upload
     * @param int $contentId Content ID for folder organization
     * @param string|null $language Language code (optional)
     * @param string $type File type (image, audio, video)
     * @param string|null $customName Custom filename (optional)
     * @return array ['success' => bool, 'path' => string|null, 'error' => string|null]
     */
    public static function upload(
        UploadedFile $file,
        int $contentId,
        ?string $language = null,
        string $type = 'image',
        ?string $customName = null
    ): array {
        // Validazione file
        if (!$file->isValid()) {
            return [
                'success' => false,
                'path' => null,
                'error' => 'File non valido: ' . $file->getErrorString()
            ];
        }

        // Validazione dimensione
        if (!self::validateSize($file, $type)) {
            $maxSizeMB = self::MAX_SIZES[$type] / (1024 * 1024);
            return [
                'success' => false,
                'path' => null,
                'error' => "File troppo grande. Dimensione massima: {$maxSizeMB}MB"
            ];
        }

        // Validazione tipo MIME
        if (!self::validateMimeType($file, $type)) {
            return [
                'success' => false,
                'path' => null,
                'error' => 'Tipo di file non permesso'
            ];
        }

        // Controlla spazio su disco
        if (!self::hasEnoughDiskSpace($file->getSize())) {
            return [
                'success' => false,
                'path' => null,
                'error' => 'Spazio su disco insufficiente'
            ];
        }

        // Costruisci path
        $targetPath = FCPATH . 'media/' . $contentId;
        if ($language) {
            $targetPath .= '/' . $language;
        }

        // Crea directory se non esiste
        if (!is_dir($targetPath)) {
            if (!mkdir($targetPath, 0755, true)) {
                return [
                    'success' => false,
                    'path' => null,
                    'error' => 'Impossibile creare la directory'
                ];
            }
        }

        // Genera nome file
        $filename = $customName ?? $contentId . '_' . ($language ?? 'common') . '_' . uniqid();
        $filename .= '.' . $file->getExtension();

        // Muovi file
        try {
            $file->move($targetPath, $filename);

            // Path relativo per database
            $relativePath = $contentId . '/' . ($language ? $language . '/' : '') . $filename;

            log_message('info', "File uploaded successfully: {$relativePath}");

            return [
                'success' => true,
                'path' => $relativePath,
                'error' => null
            ];
        } catch (\Exception $e) {
            log_message('error', "File upload failed: " . $e->getMessage());

            return [
                'success' => false,
                'path' => null,
                'error' => 'Errore durante l\'upload: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete a file from storage
     *
     * @param string $relativePath Relative path from media folder
     * @return bool
     */
    public static function delete(string $relativePath): bool
    {
        $fullPath = FCPATH . 'media/' . $relativePath;

        if (file_exists($fullPath)) {
            $result = unlink($fullPath);

            if ($result) {
                log_message('info', "File deleted: {$relativePath}");
            }

            return $result;
        }

        return false;
    }

    /**
     * Replace existing file
     *
     * @param string $oldPath Old file path to delete
     * @param UploadedFile $newFile New file to upload
     * @param int $contentId Content ID
     * @param string|null $language Language code
     * @param string $type File type
     * @return array Upload result
     */
    public static function replace(
        string $oldPath,
        UploadedFile $newFile,
        int $contentId,
        ?string $language = null,
        string $type = 'image'
    ): array {
        // Delete old file
        self::delete($oldPath);

        // Upload new file
        return self::upload($newFile, $contentId, $language, $type);
    }

    /**
     * Validate file size
     *
     * @param UploadedFile $file
     * @param string $type
     * @return bool
     */
    private static function validateSize(UploadedFile $file, string $type): bool
    {
        $maxSize = self::MAX_SIZES[$type] ?? self::MAX_SIZES['image'];
        return $file->getSize() <= $maxSize;
    }

    /**
     * Validate MIME type
     *
     * @param UploadedFile $file
     * @param string $type
     * @return bool
     */
    private static function validateMimeType(UploadedFile $file, string $type): bool
    {
        $allowedMimes = self::ALLOWED_MIMES[$type] ?? self::ALLOWED_MIMES['image'];
        return in_array($file->getMimeType(), $allowedMimes, true);
    }

    /**
     * Check if there's enough disk space
     *
     * @param int $requiredSize
     * @return bool
     */
    private static function hasEnoughDiskSpace(int $requiredSize): bool
    {
        $freeSpace = disk_free_space(FCPATH . 'media');
        $bufferSize = $requiredSize * 2; // 2x buffer for safety

        return $freeSpace >= $bufferSize;
    }

    /**
     * Get file type from content type
     *
     * @param string $contentType
     * @return string
     */
    public static function getFileTypeFromContentType(string $contentType): string
    {
        if (str_contains($contentType, 'audio')) {
            return 'audio';
        }

        if (str_contains($contentType, 'video')) {
            return 'video';
        }

        return 'image';
    }
}

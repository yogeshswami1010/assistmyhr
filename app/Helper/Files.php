<?php

namespace App\Helper;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;

/**
 * Class Reply
 * @package App\Classes
 */
class Files
{
    private static function imageManager(): ImageManager
    {
        static $manager = null;

        if ($manager === null) {
            $manager = extension_loaded('imagick')
                ? ImageManager::imagick()
                : ImageManager::gd();
        }

        return $manager;
    }

    /**
     * @param  UploadedFile  $image
     * @param  string  $dir
     * @param  int|false|null  $width
     * @param  int|false  $height
     * @param  array|false  $crop
     * @return string
     *
     * @throws \Exception
     */
    public static function upload($image, $dir, $width = null, $height = 800, $crop = false)
    {
        /** @var UploadedFile $uploadedFile */
        $uploadedFile = $image;
        $folder = $dir.'/';

        if (! $uploadedFile->isValid()) {
            throw new \Exception('File was not uploaded correctly');
        }

        if (config('saas.enabled') && app(\App\Saas\TenantContext::class)->current()) {
            app(\App\Saas\QuotaService::class)->assertUploadFits((int) $uploadedFile->getSize());
        }

        $newName = self::generateNewFileName($uploadedFile->getClientOriginalName());

        $tempPath = tenant_upload_path('temp/'.$newName);
        /** Check if folder exits or not. If not then create the folder */
        if (! \File::exists(tenant_upload_path($folder))) {
            \File::makeDirectory(tenant_upload_path($folder), 0775, true);
        }

        $newPath = $folder.'/'.$newName;

        /** @var UploadedFile $uploadedFile */
        $temporaryFiles = [$tempPath];
        try {
            $uploadedFile->move(tenant_upload_path('temp'), $newName);

            if (! empty($crop)) {
                // Crop image
                if (isset($crop[0])) {
                    // To store the multiple images for the copped ones
                    foreach ($crop as $cropped) {
                        $img = self::imageManager()->read($tempPath);

                        if (isset($cropped['resize']['width']) && isset($cropped['resize']['height'])) {

                            $img->crop(
                                (int) floor($cropped['width']),
                                (int) floor($cropped['height']),
                                (int) floor($cropped['x']),
                                (int) floor($cropped['y'])
                            );

                            $fileName = str_replace('.', '_'.$cropped['resize']['width'].'x'.$cropped['resize']['height'].'.', $newName);
                            $tempPathCropped = tenant_upload_path('temp').'/'.$fileName;
                            $temporaryFiles[] = $tempPathCropped;
                            $newPathCropped = $folder.'/'.$fileName;

                            $img->resize(
                                (int) $cropped['resize']['width'],
                                (int) $cropped['resize']['height']
                            );

                            $img->save($tempPathCropped);

                            self::putUpload($newPathCropped, \File::get($tempPathCropped));

                            // Deleting cropped temp file
                            \File::delete($tempPathCropped);
                        }

                    }
                } else {
                    $img = self::imageManager()->read($tempPath);
                    $img->crop(
                        (int) floor($crop['width']),
                        (int) floor($crop['height']),
                        (int) floor($crop['x']),
                        (int) floor($crop['y'])
                    );
                    $img->save($tempPath);
                }

            }

            if (($width || $height)) {
                $img = self::imageManager()->read($tempPath);
                $w = $width ? (int) $width : null;
                $h = $height ? (int) $height : null;
                $img->scaleDown($w, $h);
                $img->save($tempPath);
            }

            self::putUpload($newPath, \File::get($tempPath));

            // Deleting temp file
            \File::delete($tempPath);

            return $newName;
        } finally { foreach ($temporaryFiles as $temporaryFile) { \File::delete($temporaryFile); } }
    }

    private static function putUpload(string $path, string $contents): void
    {
        if (config('saas.enabled') && app(\App\Saas\TenantContext::class)->current() && config('filesystems.default') === 'local') {
            app(\App\Saas\QuotaService::class)->assertUploadFits(strlen($contents));
        }
        \Storage::put($path, $contents);
    }

    public static function generateNewFileName($currentFileName)
    {
        $ext = strtolower(\File::extension($currentFileName));
        $newName = md5(microtime());

        if ($ext === '') {
            return $newName;
        }

        return $newName.'.'.$ext;
    }

    public static function deleteFile($image, $folder)
    {
        $dir = trim($folder, '/');
        $path = $dir.'/'.$image;
        if (! \File::exists(public_path($path))) {
            \Storage::delete($path);
        }

        return true;
    }

    public static function uploadLocalOrS3($uploadedFile, $dir)
    {
        if (! $uploadedFile->isValid()) {
            throw new \Exception('File was not uploaded correctly');
        }
        if (config('filesystems.default') === 'local') {

            $fileName = self::upload($uploadedFile, $dir, false, false, false);

            // self::storeSize($uploadedFile, $dir, $fileName);

            return $fileName;
        }

        $newName = self::generateNewFileName($uploadedFile->getClientOriginalName());

        // self::storeSize($uploadedFile, $dir, $newName);

        // We have given 2 options of upload for now s3 and local
        // Storage::disk('s3')->putFileAs($dir, $uploadedFile, $newName, 'public');
        // dd($dir);

        Storage::disk('s3')->putFileAs($dir, $uploadedFile, $newName);

        return $newName;
    }
}

<?php
declare(strict_types=1);

namespace C1\AdaptiveImages\Utility\Placeholder;

use C1\AdaptiveImages\Utility\CropVariantUtility;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Extbase\Service\ImageService;

/**
 * ImagePlaceholderUtility
 * Create placeholder images to show while the final image is lazyloaded
 *
 */
class ImagePlaceholderUtility
{
    /**
     * @var \TYPO3\CMS\Extbase\Service\ImageService
     */
    protected $imageService;

    /**
     * @param ImageService $imageService
     * @return void
     */
    public function injectImageService(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * @var \C1\AdaptiveImages\Utility\CropVariantUtility
     */
    protected $cropVariantUtility;

    /**
     * @param CropVariantUtility $cropVariantUtility
     * @return void
     */
    public function injectCropVariantUtility(CropVariantUtility $cropVariantUtility)
    {
        $this->cropVariantUtility = $cropVariantUtility;
    }

    /**
     * getBase64EncodedImage
     *
     * @param FileInterface $file
     * @param bool $base64
     * @param string $cropVariant
     * @param int $width
     * @param bool $absolute
     * @return string|null
     */
    public function getPlaceholderImage($file, $base64, $cropVariant, $width, $absolute = false)
    {
        $imageUri = null;
        $this->cropVariantUtility->setCropVariantCollection($file);

        $processingInstructions = [
            'width' => $width,
            'crop' => $this->cropVariantUtility->getCropAreaForVariant($cropVariant),
            'additionalParameters' =>
                '-quality 50 -sampling-factor 4:2:0 -strip -colorspace sRGB ' .
                '-unsharp 0.25x0.25+8+0.065 -despeckle -noise 5'
        ];
        $processedImage = $this->imageService->applyProcessingInstructions($file, $processingInstructions);

        if ($processedImage->getProperties()['width'] != $width) {
            $pixel = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+ip1sAAAAASUVORK5CYII=';

            if ($base64 === false) {
                $imageUri = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('c1_adaptive_images') . 'Resources/Public/Images/placeholder.png';
                return $imageUri;
            } else {
                return sprintf(
                    'data:%s;base64,%s',
                    'image/png',
                    $pixel
                );
            }
        } else {
            if ($processedImage->exists()) {
                if ($base64 === false) {
                    $imageUri = $this->imageService->getImageUri($processedImage, $absolute);
                    return $imageUri;
                } else {
                    return sprintf(
                        'data:%s;base64,%s',
                        $file->getProperty('mime_type'),
                        base64_encode($processedImage->getContents())
                    );
                }
            } else {
                return $processedImage->getPublicUrl();
            }
        }
    }
}

<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\controllers;

use Craft;
use craft\helpers\Assets;
use craft\helpers\FileHelper;
use craft\helpers\Image;
use craft\web\Controller;
use craft\web\UploadedFile;
use yii\web\BadRequestHttpException;
use yii\web\Response;

Craft::$app->requireEdition(Craft::Client);

/**
 * The RebrandController class is a controller that handles various control panel re-branding tasks such as uploading,
 * cropping and deleting site logos and icons.
 *
 * Note that all actions in the controller require an authenticated Craft session via [[allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class RebrandController extends Controller
{
    /**
     * Allowed types of site images.
     *
     * @var array
     */
    private $_allowedTypes = ['logo', 'icon'];

    // Public Methods
    // =========================================================================

    /**
     * Handles Control Panel logo and site icon uploads.
     *
     * @return Response
     */
    public function actionUploadSiteImage(): Response
    {
        $this->requireAcceptsJson();
        $this->requireAdmin();
        $type = Craft::$app->getRequest()->getRequiredBodyParam('type');

        if (!in_array($type, $this->_allowedTypes, true)) {
            return $this->asErrorJson(Craft::t('app', 'That is not an allowed image type.'));
        }

        // Upload the file and drop it in the temporary folder
        $file = UploadedFile::getInstanceByName('image');

        try {
            // Make sure a file was uploaded
            if ($file) {
                $filename = Assets::prepareAssetName($file->name, true, true);

                if (!Image::canManipulateAsImage($file->getExtension())) {
                    throw new BadRequestHttpException('The uploaded file is not an image');
                }

                $targetPath = Craft::$app->getPath()->getRebrandPath().'/'.$type.'/';

                if (!is_dir($targetPath)) {
                    FileHelper::createDirectory($targetPath);
                } else {
                    FileHelper::clearDirectory($targetPath);
                }

                $fileDestination = $targetPath.'/'.$filename;

                move_uploaded_file($file->tempName, $fileDestination);

                Craft::$app->getImages()->loadImage($fileDestination)->scaleToFit(300, 300)->saveAs($fileDestination);
                $html = $this->getView()->renderTemplate('settings/general/_images/'.$type);

                return $this->asJson(['html' => $html]);
            }
        } catch (BadRequestHttpException $exception) {
            return $this->asErrorJson(Craft::t('app', 'The uploaded file is not an image.'));
        }

        return $this->asErrorJson(Craft::t('app',
            'There was an error uploading your photo'));
    }

    /**
     * Deletes Control Panel logo and site icon images.
     *
     * @return Response
     */
    public function actionDeleteSiteImage(): Response
    {
        $this->requireAdmin();
        $type = Craft::$app->getRequest()->getRequiredBodyParam('type');

        if (!in_array($type, $this->_allowedTypes, true)) {
            $this->asErrorJson(Craft::t('app', 'That is not an allowed image type.'));
        }

        FileHelper::clearDirectory(Craft::$app->getPath()->getRebrandPath().'/'.$type);

        $html = $this->getView()->renderTemplate('settings/general/_images/'.$type);

        return $this->asJson(['html' => $html]);
    }
}

<?php
/**
 * @author Anton Tuyakhov <atuyakhov@gmail.com>
 */

namespace tuyakhov\jsonapi\actions;

use Yii;
use yii\db\BaseActiveRecord;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

/**
 * Implements the API endpoint for deleting resources.
 * @link http://jsonapi.org/format/#crud-deleting
 */
class DeleteAction extends Action
{
    /**
     * Deletes a resource.
     * @param mixed $id id of the resource to be deleted.
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException on failure.
     */
    public function run($id)
    {
        /** @var BaseActiveRecord $model */
        $model = $this->findModel($id);

        if ($this->checkAccess) {
            call_user_func($this->checkAccess, $this->id, $model);
        }

        if ($model->delete() === false) {
            throw new ServerErrorHttpException('Failed to delete the resource for unknown reason.');
        }

        Yii::$app->getResponse()->setStatusCode(204);
    }
}
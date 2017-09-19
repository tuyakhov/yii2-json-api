<?php
/**
 * @author Anton Tuyakhov <atuyakhov@gmail.com>
 */

namespace tuyakhov\jsonapi\actions;


use tuyakhov\jsonapi\ResourceInterface;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;

class ViewRelatedAction extends Action
{
    /**
     * Prepares the data provider that should return the requested collection of the models.
     * @var callable
     */
    public $prepareDataProvider;

    /**
     * @param $id
     * @param $name
     * @return ActiveDataProvider|\yii\db\ActiveRecordInterface
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     */
    public function run($id, $name)
    {
        $model = $this->findModel($id);

        if (!$model instanceof ResourceInterface) {
            throw new BadRequestHttpException('Impossible to fetch related resource');
        }

        /** @var ActiveQuery $related */
        if (!$related = $model->getRelation($name, false)) {
            throw new NotFoundHttpException('Resource does not exist');
        }

        if ($this->checkAccess) {
            call_user_func($this->checkAccess, $this->id, $model, $name);
        }

        if ($this->prepareDataProvider !== null) {
            return call_user_func($this->prepareDataProvider, $this, $related, $name);
        }

        if ($related->multiple) {
            return new ActiveDataProvider([
                'query' => $related
            ]);
        } else {
            return $related->one();
        }
    }
}

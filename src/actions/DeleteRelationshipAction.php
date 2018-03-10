<?php
/**
 * @author Anton Tuyakhov <atuyakhov@gmail.com>
 */

namespace tuyakhov\jsonapi\actions;

use Yii;
use yii\data\ActiveDataProvider;
use yii\db\ActiveRecordInterface;
use yii\db\ActiveRelationTrait;
use yii\db\BaseActiveRecord;
use yii\helpers\ArrayHelper;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Deletes the specified members from a relationship
 * @link http://jsonapi.org/format/#crud-updating-relationships
 */
class DeleteRelationshipAction extends Action
{
    /**
     * Removes the relationships from primary model.
     * @var callable
     */
    public $unlinkRelationships;

    /**
     * @param string $id an ID of the primary resource
     * @param string $name a name of the related resource
     * @return ActiveDataProvider
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws \yii\base\InvalidConfigException
     */
    public function run($id, $name)
    {
        /** @var BaseActiveRecord $model */
        $model = $this->findModel($id);

        if (!$related = $model->getRelation($name, false)) {
            throw new NotFoundHttpException('Relationship does not exist');
        }

        if (!$related->multiple) {
            throw new ForbiddenHttpException('Unsupported request to update relationship');
        }

        if ($this->checkAccess) {
            call_user_func($this->checkAccess, $this->id, $model, $name);
        }

        $this->unlinkRelationships($model, [$name => Yii::$app->getRequest()->getBodyParams()]);


        return new ActiveDataProvider([
            'query' => $related
        ]);
    }

    /**
     * Removes the relationships from primary model.
     * @param $model ActiveRecordInterface
     * @param array $data relationship links
     */
    protected function unlinkRelationships($model, array $data = [])
    {
        if ($this->unlinkRelationships !== null) {
            call_user_func($this->unlinkRelationships, $this, $model, $data);
            return;
        }

        foreach ($data as $name => $relationship) {
            /** @var $related ActiveRelationTrait */
            if (!$related = $model->getRelation($name, false)) {
                continue;
            }
            /** @var BaseActiveRecord $relatedClass */
            $relatedClass = new $related->modelClass;
            $relationships = ArrayHelper::keyExists($relatedClass->formName(), $relationship) ? $relationship[$relatedClass->formName()] : [];

            $ids = [];
            foreach ($relationships as $index => $relObject) {
                if (!isset($relObject['id'])) {
                    continue;
                }
                $ids[] = $relObject['id'];
            }

            $records = $relatedClass::find()->andWhere(['in', $relatedClass::primaryKey(), $ids])->all();
            foreach ($records as $record) {
                $model->unlink($name, $record);
            }
        }
    }
}
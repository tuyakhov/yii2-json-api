<?php
/**
 * @author Anton Tuyakhov <atuyakhov@gmail.com>
 */

namespace tuyakhov\jsonapi\actions;

use tuyakhov\jsonapi\ResourceInterface;
use tuyakhov\jsonapi\ResourceTrait;
use yii\db\ActiveRecordInterface;
use yii\db\BaseActiveRecord;
use yii\helpers\ArrayHelper;

class Action extends \yii\rest\Action
{
    /**
     * Links the relationships with primary model.
     * @var callable
     */
    public $linkRelationships;

    /**
     * @var bool Weather allow to do a full replacement of a to-many relationship
     */
    public $allowFullReplacement = true;

    /**
     * @var bool Weather allow to delete the underlying resource if a relationship is deleted (as a garbage collection measure)
     */
    public $enableResourceDeleting = false;

    /**
     * Links the relationships with primary model.
     * @param $model ActiveRecordInterface
     * @param array $data relationship links
     */
    protected function linkRelationships($model, array $data = [])
    {
        if ($this->linkRelationships !== null) {
            call_user_func($this->linkRelationships, $this, $model, $data);
            return;
        }

        if (!$model instanceof ResourceInterface) {
            return;
        }

        foreach ($data as $name => $relationship) {
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

            if ($related->multiple && !$this->allowFullReplacement) {
                continue;
            }
            $records = $relatedClass::find()->andWhere(['in', $relatedClass::primaryKey(), $ids])->all();

            /** @see ResourceTrait::$allowDeletingResources */
            if (property_exists($model, 'allowDeletingResources')) {
                $model->allowDeletingResources = $this->enableResourceDeleting;
            }

            $model->setResourceRelationship($name, $records);
        }
    }
}
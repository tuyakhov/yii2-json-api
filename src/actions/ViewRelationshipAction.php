<?php

namespace tuyakhov\jsonapi\actions;

use Yii;
use yii\base\Model;
use yii\web\NotFoundHttpException;
use tuyakhov\jsonapi\Inflector;
use tuyakhov\jsonapi\Relationship;


class ViewRelationshipAction extends Action
{
    /**
     * @var string the scenario to be assigned to the new model before it is validated and saved.
     */
    public $scenario = Model::SCENARIO_DEFAULT;

    /**
     * @var string the name of the view action. This property is need to create the URL when the model is successfully created.
     */
    public $viewAction = 'view-relationship';

    /**
     * Gets a relationship between resources
     * @return array a relationship definition between resources
     * @throws NotFoundHttpException if the relationship doesn't exist
     */
    public function run($id, $name)
    {
        /* @var $model \yii\db\ActiveRecord */
        $model = $this->findModel($id);

        if (!$related = $model->getRelation($name, false)) {
            throw new NotFoundHttpException('Relationship does not exist');
        }

        if ($this->checkAccess) {
            call_user_func($this->checkAccess, $this->id, $model, $name);
        }

        $relationship = new Relationship([
            'multiple' => $related->multiple
        ]);
        
        if ($related->multiple) {
            return $relationship->addRelated($related->all());
        }

        return $relationship->addRelated($related->one());
    }
}
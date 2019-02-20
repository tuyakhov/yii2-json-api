<?php
/**
 * @author Anton Tuyakhov <atuyakhov@gmail.com>
 */

namespace tuyakhov\jsonapi\tests\actions;

use tuyakhov\jsonapi\actions\ViewRelationshipAction;
use tuyakhov\jsonapi\tests\data\ActiveQuery;
use tuyakhov\jsonapi\tests\data\ResourceModel;
use tuyakhov\jsonapi\tests\TestCase;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use tuyakhov\jsonapi\Relationship;

class ViewRelationshipActionTest extends TestCase
{
    public function testMultipleSuccess()
    {
        $model = new ResourceModel();
        $action = new ViewRelationshipAction('test', new Controller('test', \Yii::$app), [
            'modelClass' => ResourceModel::className()
        ]);
        ResourceModel::$related = [
            'extraField1' => new ActiveQuery(ResourceModel::className(), ['multiple' => true]),
        ];
        $action->findModel = function ($id, $action) use($model) {
            return $model;
        };
        $model->extraField1 = [new ResourceModel()];
        $this->assertInstanceOf(Relationship::className(), $relationship = $action->run(1, 'extraField1'));
        $this->assertTrue($relationship->multiple);
        $this->assertCount(2, $relationship->relations);
        $this->assertInstanceOf(ResourceModel::className(), $relationship->relations[0]);
    }

    public function testSingleSuccess()
    {
        $model = new ResourceModel();
        $action = new ViewRelationshipAction('test', new Controller('test', \Yii::$app), [
            'modelClass' => ResourceModel::className()
        ]);
        ResourceModel::$related = [
            'extraField1' => new ActiveQuery(ResourceModel::className()),
        ];
        $action->findModel = function ($id, $action) use($model) {
            return $model;
        };
        $model->extraField1 = new ResourceModel();
        $this->assertInstanceOf(Relationship::className(), $relationship = $action->run(1, 'extraField1'));
        $this->assertCount(1, $relationship->relations);
        $this->assertInstanceOf(ResourceModel::className(), $relationship->relations[0]);
    }

}
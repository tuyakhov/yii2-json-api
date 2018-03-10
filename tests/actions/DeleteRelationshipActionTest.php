<?php
/**
 * @author Anton Tuyakhov <atuyakhov@gmail.com>
 */

namespace tuyakhov\jsonapi\tests\actions;

use tuyakhov\jsonapi\actions\DeleteRelationshipAction;
use tuyakhov\jsonapi\tests\data\ActiveQuery;
use tuyakhov\jsonapi\tests\data\ResourceModel;
use tuyakhov\jsonapi\tests\TestCase;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;

class DeleteRelationshipActionTest extends TestCase
{
    public function testSuccess()
    {
        $model = new ResourceModel();
        $action = new DeleteRelationshipAction('test', new Controller('test', \Yii::$app), [
            'modelClass' => ResourceModel::className()
        ]);
        ResourceModel::$related = [
            'extraField1' => new ActiveQuery(ResourceModel::className(), ['multiple' => true]),
            'extraField2' => new ActiveQuery(ResourceModel::className())
        ];
        $action->findModel = function ($id, $action) use($model) {
            return $model;
        };
        $model->extraField1 = [new ResourceModel()];
        \Yii::$app->request->setBodyParams(['ResourceModel' => ['type' => 'resource-models', 'id' => 123]]);
        $this->assertInstanceOf(ActiveDataProvider::className(), $dataProvider = $action->run(1, 'extraField1'));
        $this->expectException(ForbiddenHttpException::class);
        $this->assertInstanceOf(ResourceModel::className(), $action->run(1, 'extraField2'));
    }
}
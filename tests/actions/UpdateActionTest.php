<?php
/**
 * Created by PhpStorm.
 * User: anton
 * Date: 08/03/2017
 * Time: 19:07
 */

namespace tuyakhov\jsonapi\tests\actions;

use tuyakhov\jsonapi\actions\UpdateAction;
use tuyakhov\jsonapi\tests\data\ActiveQuery;
use tuyakhov\jsonapi\tests\data\ResourceModel;
use tuyakhov\jsonapi\tests\TestCase;
use yii\base\Controller;

class UpdateActionTest extends TestCase
{
    public function testSuccess()
    {
        ResourceModel::$extraFields = ['extraField1'];
        ResourceModel::$related = [
            'extraField1' => new ActiveQuery(ResourceModel::className())
        ];

        \Yii::$app->controller = new Controller('test', \Yii::$app);
        $action = new UpdateAction('test', \Yii::$app->controller, [
            'modelClass' => ResourceModel::className(),
        ]);

        ResourceModel::$id = 124;
        $model = new ResourceModel();
        $model->field1 = '41231';
        ActiveQuery::$models = $model;
        \Yii::$app->request->setBodyParams([
            'ResourceModel' => [
                'field1' => 'test',
                'field2' => 2,
            ],
            'relationships' => [
                'extraField1' => [
                    'ResourceModel' => [
                        'id' => 124
                    ],
                ]
            ]
        ]);

        $this->assertInstanceOf(ResourceModel::className(), $model = $action->run(1));
        $this->assertFalse($model->hasErrors());
        $this->assertEquals('test', $model->field1);
        $relationships = $model->getResourceRelationships(['extra_field1']);
        $this->assertArrayHasKey('extra_field1', $relationships);
        $this->assertInstanceOf(ResourceModel::className(), $relationships['extra_field1']);
        $this->assertEquals(124, $relationships['extra_field1']->id);
    }
}
<?php
/**
 * @author Anton Tuyakhov <atuyakhov@gmail.com>
 */

namespace tuyakhov\jsonapi\tests\actions;


use tuyakhov\jsonapi\actions\CreateAction;
use tuyakhov\jsonapi\tests\data\ActiveQuery;
use tuyakhov\jsonapi\tests\data\ResourceModel;
use tuyakhov\jsonapi\tests\TestCase;
use yii\base\Controller;

class CreateActionTest extends TestCase
{
    public function testSuccess()
    {
        ResourceModel::$extraFields = ['extraField1'];
        ResourceModel::$related = [
            'extraField1' => new ActiveQuery(ResourceModel::className())
        ];

        \Yii::$app->controller = new Controller('test', \Yii::$app);
        $action = new CreateAction('test', \Yii::$app->controller, [
            'modelClass' => ResourceModel::className(),
        ]);

        ResourceModel::$id = 124;
        ActiveQuery::$models = new ResourceModel();
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

        $this->assertInstanceOf(ResourceModel::className(), $model = $action->run());
        $this->assertFalse($model->hasErrors());
        $relationships = $model->getResourceRelationships(['extra_field1']);
        $this->assertArrayHasKey('extra_field1', $relationships);
        $this->assertInstanceOf(ResourceModel::className(), $relationships['extra_field1']);
        $this->assertEquals(124, $relationships['extra_field1']->id);
    }
}
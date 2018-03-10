<?php
/**
 * @author Anton Tuyakhov <atuyakhov@gmail.com>
 */

namespace tuyakhov\jsonapi\tests\actions;

use tuyakhov\jsonapi\actions\DeleteAction;
use tuyakhov\jsonapi\tests\data\ActiveQuery;
use tuyakhov\jsonapi\tests\data\ResourceModel;
use tuyakhov\jsonapi\tests\TestCase;
use yii\web\Controller;

class DeleteActionTest extends TestCase
{
    public function testSuccess()
    {
        \Yii::$app->controller = new Controller('test', \Yii::$app);
        $action = new DeleteAction('test', \Yii::$app->controller, [
            'modelClass' => ResourceModel::className(),
        ]);

        ResourceModel::$id = 124;
        ActiveQuery::$models = new ResourceModel();

        $action->run(124);
        $this->assertTrue(\Yii::$app->getResponse()->getIsEmpty());
    }
}
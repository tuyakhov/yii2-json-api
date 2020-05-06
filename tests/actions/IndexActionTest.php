<?php
/**
 * @author Anton Tuyakhov <atuyakhov@gmail.com>
 */

namespace tuyakhov\jsonapi\tests\actions;


use tuyakhov\jsonapi\actions\IndexAction;
use tuyakhov\jsonapi\tests\data\ActiveQuery;
use tuyakhov\jsonapi\tests\data\ResourceModel;
use tuyakhov\jsonapi\tests\TestCase;
use yii\base\Controller;
use yii\data\ActiveDataFilter;
use yii\data\ActiveDataProvider;
use yii\db\Query;

class IndexActionTest extends TestCase
{
    public function testSuccess()
    {
        $action = new IndexAction('test', new Controller('test', \Yii::$app), [
            'modelClass' => ResourceModel::className(),
            'dataFilter' => [
                'class' => ActiveDataFilter::className(),
                'searchModel' => ResourceModel::className()
            ]
        ]);
        $filter = [
            'filter' => ['field1' => 'test,qwe'],
            'sort' => 'field1,-field2'
        ];
        \Yii::$app->getRequest()->setQueryParams($filter);

        $this->assertInstanceOf(ActiveDataProvider::className(), $dataProvider = $action->run());
        $this->assertInstanceOf(Query::className(), $dataProvider->query);
        $this->assertSame([
            'IN',
            'field1',
            ['test', 'qwe']
        ], $dataProvider->query->where);
        $this->assertSame(['field1' => SORT_ASC, 'field2' => SORT_DESC], $dataProvider->getSort()->orders);
    }

    public function testValidation()
    {
        $action = new IndexAction('test', new Controller('test', \Yii::$app), [
            'modelClass' => ResourceModel::className(),
            'dataFilter' => [
                'class' => ActiveDataFilter::className(),
                'searchModel' => ResourceModel::className()
            ]
        ]);
        \Yii::$app->getRequest()->setQueryParams(['filter' => ['field1' => 1]]);

        $this->assertInstanceOf(ActiveDataProvider::className(), $dataProvider = $action->run());
        $this->assertInstanceOf(Query::className(), $dataProvider->query);
        $this->assertNull($dataProvider->query->where);
    }

    public function testPagination()
    {
        $action = new IndexAction('test', new Controller('test', \Yii::$app), [
            'modelClass' => ResourceModel::className(),
        ]);
        ActiveQuery::$models = [new ResourceModel(), new ResourceModel()];
        $params = ['page' => 1];
        \Yii::$app->getRequest()->setQueryParams($params);

        $this->assertInstanceOf(ActiveDataProvider::className(), $dataProvider = $action->run());
        $this->assertSame(0, $dataProvider->getPagination()->page);

        $params = ['page' => ['number' => 2, 'size' => 1]];
        \Yii::$app->getRequest()->setQueryParams($params);

        $this->assertInstanceOf(ActiveDataProvider::className(), $dataProvider = $action->run());

        $this->assertSame(2, $dataProvider->getCount());
        $this->assertSame(2, $dataProvider->pagination->getPageCount());
        $this->assertSame(1, $dataProvider->pagination->getPageSize());
        $this->assertSame(1, $dataProvider->pagination->getOffset());

        // test invalid value
        $params = ['page' => 1];
        \Yii::$app->getRequest()->setQueryParams($params);

        $this->assertInstanceOf(ActiveDataProvider::className(), $dataProvider = $action->run());

        $this->assertSame(2, $dataProvider->getCount());
        $this->assertSame(1, $dataProvider->pagination->getPageCount());
        $this->assertSame($dataProvider->pagination->defaultPageSize, $dataProvider->pagination->getPageSize());
        $this->assertSame(0, $dataProvider->pagination->getOffset());
    }
}
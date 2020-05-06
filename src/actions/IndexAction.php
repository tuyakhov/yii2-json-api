<?php
/**
 * @author Anton Tuyakhov <atuyakhov@gmail.com>
 */

namespace tuyakhov\jsonapi\actions;


use tuyakhov\jsonapi\Inflector;
use tuyakhov\jsonapi\Pagination;
use yii\data\ActiveDataProvider;
use yii\data\DataFilter;
use Yii;

class IndexAction extends Action
{
    /**
     * @var callable a PHP callable that will be called to prepare a data provider that
     * should return a collection of the models. If not set, [[prepareDataProvider()]] will be used instead.
     * The signature of the callable should be:
     *
     * ```php
     * function (IndexAction $action) {
     *     // $action is the action object currently running
     * }
     * ```
     *
     * The callable should return an instance of [[ActiveDataProvider]].
     *
     * If [[dataFilter]] is set the result of [[DataFilter::build()]] will be passed to the callable as a second parameter.
     * In this case the signature of the callable should be the following:
     *
     * ```php
     * function (IndexAction $action, mixed $filter) {
     *     // $action is the action object currently running
     *     // $filter the built filter condition
     * }
     * ```
     */
    public $prepareDataProvider;
    /**
     * @var DataFilter|null data filter to be used for the search filter composition.
     * You must setup this field explicitly in order to enable filter processing.
     * For example:
     *
     * ```php
     * [
     *     'class' => 'yii\data\ActiveDataFilter',
     *     'searchModel' => function () {
     *         return (new \yii\base\DynamicModel(['id' => null, 'name' => null, 'price' => null]))
     *             ->addRule('id', 'integer')
     *             ->addRule('name', 'trim')
     *             ->addRule('name', 'string')
     *             ->addRule('price', 'number');
     *     },
     * ]
     * ```
     *
     * @see DataFilter
     *
     * @since 2.0.13
     */
    public $dataFilter;


    /**
     * @return ActiveDataProvider
     * @throws \yii\base\InvalidConfigException
     */
    public function run()
    {
        if ($this->checkAccess) {
            call_user_func($this->checkAccess, $this->id);
        }

        return $this->prepareDataProvider();
    }

    /**
     * Prepares the data provider that should return the requested collection of the models.
     * @return mixed|null|object|DataFilter|ActiveDataProvider
     * @throws \yii\base\InvalidConfigException
     */
    protected function prepareDataProvider()
    {
        $filter = $this->getFilter();

        if ($this->prepareDataProvider !== null) {
            return call_user_func($this->prepareDataProvider, $this, $filter);
        }

        /* @var $modelClass \yii\db\BaseActiveRecord */
        $modelClass = $this->modelClass;

        $query = $modelClass::find();
        if (!empty($filter)) {
            $query->andWhere($filter);
        }

        return Yii::createObject([
            'class' => ActiveDataProvider::className(),
            'query' => $query,
            'pagination' => [
                'class' => Pagination::className(),
            ],
            'sort' => [
                'enableMultiSort' => true
            ]
        ]);
    }

    protected function getFilter()
    {
        if ($this->dataFilter === null) {
            return null;
        }
        $requestParams = Yii::$app->getRequest()->getQueryParam('filter', []);
        $attributeMap = [];
        foreach ($requestParams as $attribute => $value) {
            $attributeMap[$attribute] = Inflector::camel2id(Inflector::variablize($attribute), '_');
            if (is_string($value) && strpos($value, ',') !== false) {
                $requestParams[$attribute] = ['in' => explode(',', $value)];
            }
        }
        $config = array_merge(['attributeMap' => $attributeMap], $this->dataFilter);
        /** @var DataFilter $dataFilter */
        $dataFilter = Yii::createObject($config);
        if ($dataFilter->load(['filter' => $requestParams])) {
            return $dataFilter->build();
        }
        return null;
    }
}
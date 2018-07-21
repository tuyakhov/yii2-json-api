<?php
/**
 * @author Anton Tuyakhov <atuyakhov@gmail.com>
 */

namespace tuyakhov\jsonapi\data;


use Yii;
use yii\base\InvalidParamException;
use yii\data\ActiveDataProvider;

class JsonApiDataProvider extends ActiveDataProvider
{
    private $_filter;

    protected function prepareModels()
    {
        $this->query->andFilterWhere([]);
        return parent::prepareModels();
    }

    /**
     * Returns the filtering object used by this data provider.
     * @return Filter|boolean the filtering object. If this is false, it means the filtering is disabled.
     */
    public function getFilter()
    {
        if ($this->_filter === null) {
            $this->setFilter([]);
        }

        return $this->_filter;
    }

    /**
     * Sets the filter definition for this data provider.
     * @param array|Filter|boolean $value the filter definition to be used by this data provider.
     * This can be one of the following:
     *
     * - a configuration array for creating the filter definition object. The "class" element defaults
     *   to 'tuyakhov\jsonapi\data\Filter'
     * - an instance of [[Filter]] or its subclass
     * - false, if sorting needs to be disabled.
     *
     * @throws InvalidParamException
     */
    public function setFilter($value)
    {
        if (is_array($value)) {
            $config = ['class' => Filter::className()];
            if ($this->id !== null) {
                $config['filterParam'] = $this->id . '-filter';
            }
            $this->_filter = Yii::createObject(array_merge($config, $value));
        } elseif ($value instanceof Filter || $value === false) {
            $this->_filter = $value;
        } else {
            throw new InvalidParamException('Only Filter instance, configuration array or false is allowed.');
        }
    }

}
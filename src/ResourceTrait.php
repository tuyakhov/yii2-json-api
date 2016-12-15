<?php
/**
 * @author Anton Tuyakhov <atuyakhov@gmail.com>
 */

namespace tuyakhov\jsonapi;

use yii\base\Arrayable;
use yii\db\ActiveRecordInterface;
use yii\db\BaseActiveRecord;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;

trait ResourceTrait
{
    /**
     * @return null|string
     */
    public function getId()
    {
        if ($this instanceof ActiveRecordInterface) {
            return (string) $this->getPrimaryKey();
        }
        return null;
    }

    /**
     * @return string
     */
    public function getType()
    {
        $reflect = new \ReflectionClass($this);
        $className = $reflect->getShortName();
        return Inflector::pluralize(Inflector::camel2id($className));
    }

    /**
     * @param array $fields
     * @return array
     */
    public function getResourceAttributes(array $fields = [])
    {
        $attributes = [];

        foreach ($this->resolveFields($this->fields(), $fields) as $name => $definition) {
            $attributes[$name] = is_string($definition) ? $this->$definition : call_user_func($definition, $this, $name);
        }
        return $attributes;
    }

    /**
     * @return array
     */
    public function getResourceRelationships()
    {
        $relationships = [];

        foreach ($this->resolveFields($this->extraFields()) as $name => $definition) {
            $relationships[$name] = is_string($definition) ? $this->$definition : call_user_func($definition, $this, $name);
        }
        return $relationships;
    }

    /**
     * @param string $name the case sensitive name of the relationship.
     * @param $relationship
     */
    public function setResourceRelationship($name, $relationship)
    {
        if (!is_array($relationship)) {
            $relationship = [$relationship];
        }
        foreach ($relationship as $key => $value) {
            if ($value instanceof ActiveRecordInterface) {
                $this->link($name, $value);
            }
        }
    }    

    /**
     * @return array
     */
    public function extraFields()
    {
        return [];
    }

    /**
     * @param array $fields
     * @param array $fieldSet
     * @return array
     */
    protected function resolveFields(array $fields, array $fieldSet = [])
    {
        $result = [];

        foreach ($fields as $field => $definition) {
            if (is_int($field)) {
                $field = $definition;
            }
            if (empty($fieldSet) || in_array($field, $fieldSet, true)) {
                $result[$field] = $definition;
            }
        }

        return $result;
    }
}

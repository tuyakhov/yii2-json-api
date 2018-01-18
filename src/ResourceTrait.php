<?php
/**
 * @author Anton Tuyakhov <atuyakhov@gmail.com>
 */

namespace tuyakhov\jsonapi;

use yii\base\Arrayable;
use yii\db\ActiveRecordInterface;
use yii\web\Link;
use yii\web\Linkable;

trait ResourceTrait
{
    /**
     * @return string
     */
    public function getId()
    {
        return (string) ($this instanceof ActiveRecordInterface ? $this->getPrimaryKey() : null);
    }

    /**
     * @return string
     */
    public function getType()
    {
        $reflect = new \ReflectionClass($this);
        $className = $reflect->getShortName();
        return Inflector::camel2id($className);
    }

    /**
     * @param array $fields
     * @return array
     */
    public function getResourceAttributes(array $fields = [])
    {
        $attributes = [];
        if ($this instanceof Arrayable) {
            $fieldDefinitions = $this->fields();
        } else {
            $vars = array_keys(\Yii::getObjectVars($this));
            $fieldDefinitions = array_combine($vars, $vars);
        }

        foreach ($this->resolveFields($fieldDefinitions, $fields) as $name => $definition) {
            $attributes[$name] = is_string($definition) ? $this->$definition : call_user_func($definition, $this, $name);
        }
        return $attributes;
    }

    /**
     * @param array $linked
     * @return array
     */
    public function getResourceRelationships(array $linked = [])
    {
        $fields = [];
        if ($this instanceof Arrayable) {
            $fields = $this->extraFields();
        }
        $resolvedFields = $this->resolveFields($fields);
        $keys = array_keys($resolvedFields);

        $relationships = array_fill_keys($keys, null);
        $linkedFields = array_intersect($keys, $linked);

        foreach ($linkedFields as $name) {
            $definition = $resolvedFields[$name];
            $relationships[$name] = is_string($definition) ? $this->$definition : call_user_func($definition, $this, $name);
        }

        return $relationships;
    }

    /**
     * @param string $name the case sensitive name of the relationship.
     * @param array|ActiveRecordInterface $relationship
     */
    public function setResourceRelationship($name, $relationship)
    {
        /** @var $this ActiveRecordInterface */
        if (!$this instanceof ActiveRecordInterface) {
            return;
        }
        if (!is_array($relationship)) {
            $relationship = [$relationship];
        }
        $this->unlinkAll($name);
        foreach ($relationship as $key => $value) {
            if ($value instanceof ActiveRecordInterface) {
                $this->link($name, $value);
            }
        }
    }

    /**
     * @param string $name the case sensitive name of the relationship.
     * @return array
     */
    public function getRelationshipLinks($name)
    {
        if (!$this instanceof Linkable) {
            return [];
        }
        $primaryLinks = $this->getLinks();
        if (!array_key_exists(Link::REL_SELF, $primaryLinks)) {
            return [];
        }
        $resourceLink = is_string($primaryLinks[Link::REL_SELF]) ? rtrim($primaryLinks[Link::REL_SELF], '/') : null;
        if (!$resourceLink) {
            return [];
        }
        return [
            Link::REL_SELF => "{$resourceLink}/relationships/{$name}",
            'related' => "{$resourceLink}/{$name}",
        ];
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
            $field = Inflector::camel2id(Inflector::variablize($field), '_');
            if (empty($fieldSet) || in_array($field, $fieldSet, true)) {
                $result[$field] = $definition;
            }
        }

        return $result;
    }
}

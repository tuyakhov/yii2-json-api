<?php
/**
 * @author Anton Tuyakhov <atuyakhov@gmail.com>
 */

namespace tuyakhov\jsonapi\tests\data;

use tuyakhov\jsonapi\LinksInterface;
use tuyakhov\jsonapi\ResourceInterface;
use tuyakhov\jsonapi\ResourceTrait;
use yii\base\Model;
use yii\helpers\Url;
use yii\web\Link;

class ResourceModel extends Model implements ResourceInterface, LinksInterface
{
    use ResourceTrait;

    public static $id = '123';
    public static $fields = ['field1', 'field2'];
    public static $extraFields = [];
    public static $related = [];
    public $field1 = 'test';
    public $field2 = 2;
    public $first_name = 'Bob';
    public $username = '';
    public $extraField1 = 'testExtra';
    public $extraField2 = 42;
    private $_id;

    public function getId()
    {
        if ($this->_id === null) {
            $this->_id = static::$id;
        }
        return $this->_id;
    }

    public function setId($value)
    {
        $this->_id = $value;
    }

    public function fields()
    {
        return static::$fields;
    }

    public function extraFields()
    {
        return static::$extraFields;
    }

    public function getRelation($name)
    {
        return isset(static::$related[$name]) ? static::$related[$name] : null;
    }

    public function setResourceRelationship($name, $relationship)
    {
        $this->$name = $relationship;
    }

    public static function find()
    {
        return new ActiveQuery(self::className());
    }

    public static function findOne()
    {
        return self::find()->one();
    }

    public static function primaryKey()
    {
        return ['id'];
    }

    public function getPrimaryKey($asArray = false)
    {
        return $asArray ? [$this->getId()] : $this->getId();
    }

    public function unlinkAll($name)
    {
        $this->$name = null;
    }

    public function unlink($name, $model)
    {
        array_pop($this->$name);
    }

    public function save()
    {
        return true;
    }

    public function delete()
    {
        return true;
    }

    public function getLinks()
    {
        return [
            Link::REL_SELF => Url::to('http://example.com/resource/' . $this->getId())
        ];
    }
}

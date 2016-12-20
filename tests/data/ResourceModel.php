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

    public static $id = 123;
    public static $fields = ['field1', 'field2'];
    public static $extraFields = [];
    public $field1 = 'test';
    public $field2 = 2;
    public $username = '';
    public $extraField1 = 'testExtra';
    public $extraField2 = 42;

    private $_related = [];

    public function getId()
    {
        return static::$id;
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
        return isset($this->_related[$name]) ? $this->_related[$name] : null;
    }

    public function setRelation($name, $value)
    {
        $this->_related[$name] = $value;
    }

    public function getLinks()
    {
        return [
            Link::REL_SELF => Url::to('http://example.com/resource/' . $this->getId())
        ];
    }
}

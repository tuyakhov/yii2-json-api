<?php

namespace tuyakhov\jsonapi;

use yii\base\Model;

class Relationship extends Model
{
    public $multiple = false;
    public $relations = [];

    public function addRelated($related)
    {
        if (!$related) {
            return $this;
        }

        if (!is_array($related)) {
            return $this->addRelated([$related]);
        }

        $this->relations = array_merge($this->relations, $related);
        return $this;
    }
}
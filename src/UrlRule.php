<?php
/**
 * @author Anton Tuyakhov <atuyakhov@gmail.com>
 */

namespace tuyakhov\jsonapi;


/**
 * UrlRule is provided to simplify the creation of URL rules for JSON API support.
 * @package tuyakhov\jsonapi
 */
class UrlRule extends \yii\rest\UrlRule
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->tokens = array_merge($this->tokens, array_merge([
            '{relationship}' => '<name:\w+>'
        ]));
        $this->patterns = array_merge($this->patterns, [
            'POST' => 'create',
            'DELETE {id}/relationships/{relationship}' => 'delete-relationship',
            'POST,PATCH {id}/relationships/{relationship}' => 'update-relationship',
            'HEAD,GET {id}/{relationship}' => 'view-related',
            '{id}/{relationship}' => 'options',
            'HEAD,GET {id}/relationships/{relationship}' => 'view-relationship',
        ]);
        parent::init();
    }

}
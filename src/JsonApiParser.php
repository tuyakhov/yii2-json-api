<?php
/**
 * @author Anton Tuyakhov <atuyakhov@gmail.com>
 */

namespace tuyakhov\jsonapi;

use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;
use \yii\web\JsonParser;

class JsonApiParser extends JsonParser
{
    /**
     * @var array|callable|null
     */
    public $formNameCallback;

    public function __construct($formNameCallback = null)
    {
        if ($formNameCallback === null) {
            $formNameCallback = [$this, 'typeToFormName'];
        }
        if (!is_callable($formNameCallback, true)) {
            throw new InvalidConfigException('JsonApiParser::formNameCallback should be callable');
        }

        $this->formNameCallback = $formNameCallback;
    }


    /**
     * Parse resource object into the input data to populates the model
     * @inheritdoc
     */
    public function parse($rawBody, $contentType)
    {
        $array = parent::parse($rawBody, $contentType);
        if ($type = ArrayHelper::getValue($array, 'data.type')) {
            $formName = call_user_func($this->formNameCallback, $type);
            if ($attributes = ArrayHelper::getValue($array, 'data.attributes')) {
                $result[$formName] = $attributes;
            } elseif ($id = ArrayHelper::getValue($array, 'data.id')) {
                $result[$formName] = ['id' => $id, 'type' => $type];
            }
            if ($relationships = ArrayHelper::getValue($array, 'data.relationships')) {
                foreach ($relationships as $name => $relationship) {
                    if (isset($relationship[0])) {
                        foreach ($relationship as $item) {
                            if (isset($item['type']) && isset($item['id'])) {
                                $formName = call_user_func($this->formNameCallback, $item['type']);
                                $result[$name][$formName][] = $item;
                            }
                        }
                    } elseif (isset($relationship['type']) && isset($relationship['id'])) {
                        $formName = call_user_func($this->formNameCallback, $relationship['type']);
                        $result[$name][$formName] = $relationship;
                    }
                }
            }
        } else {
            $data = ArrayHelper::getValue($array, 'data', []);
            foreach ($data as $relationLink) {
                if (isset($relationLink['type']) && isset($relationLink['id'])) {
                    $formName = call_user_func($this->formNameCallback, $relationLink['type']);
                    $result[$formName][] = $relationLink;
                }
            }
        }
        return isset($result) ? $result : $array;
    }

    protected function typeToFormName($type)
    {
        return Inflector::id2camel(Inflector::singularize($type));
    }
}

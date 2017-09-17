<?php
/**
 * @author Anton Tuyakhov <atuyakhov@gmail.com>
 */

namespace tuyakhov\jsonapi;

use yii\helpers\BaseInflector;

class Inflector extends BaseInflector
{
    /**
     * Format member names according to recommendations for JSON API implementations.
     * For example, both 'firstName' and 'first_name' will be converted to 'first-name'.
     * @link http://jsonapi.org/format/#document-member-names
     * @param $var string
     * @return string
     */
    public static function var2member($var)
    {
        return self::camel2id(self::variablize($var));
    }

    /**
     * Converts member names to variable names
     * All special characters will be replaced by underscore
     * For example, 'first-name' will be converted to 'first_name'
     * @param $member string
     * @return mixed
     */
    public static function member2var($member)
    {
        return str_replace(' ', '_', preg_replace('/[^A-Za-z0-9\.]+/', ' ', $member));
    }

    /**
     * Converts 'type' member to form name
     * Will be converted to singular form.
     * For example, 'articles' will be converted to 'Article'
     * @param $type string 'type' member of the document
     * @return string
     */
    public static function type2form($type)
    {
        return self::id2camel(self::singularize($type));
    }
}
<?php


namespace tuyakhov\jsonapi;

use Yii;
use yii\web\Request;

/**
 * This class enables the `page` query parameter family.
 * Query parameters such as page[number] and page[size] might be used.
 * @link https://jsonapi.org/format/1.1/#fetching-pagination
 */
class Pagination extends \yii\data\Pagination
{
    /** @var string default page size parameter */
    public $pageSizeParam = 'size';

    /** @var string default page number parameter  */
    public $pageParam = 'number';

    /**
     * Support `page` query parameter family
    */
    public function init()
    {
        if ($this->params === null) {
            $request = Yii::$app->getRequest();
            $params = $request instanceof Request ? $request->getQueryParam('page') : [];
            if (!is_array($params)) {
                $params = [];
            }
            $this->params = $params;
        }
    }

}
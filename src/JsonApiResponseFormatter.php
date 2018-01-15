<?php
/**
 * @author Anton Tuyakhov <atuyakhov@gmail.com>
 */

namespace tuyakhov\jsonapi;

use yii\base\Component;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\web\ErrorHandler;
use yii\web\Response;
use yii\web\ResponseFormatterInterface;

class JsonApiResponseFormatter extends Component implements ResponseFormatterInterface
{
    /**
     * Mapping between the error handler component and JSON API error object
     * @see ErrorHandler::convertExceptionToArray()
     */
    const ERROR_EXCEPTION_MAPPING = [
        'title' => 'name',
        'detail' => 'message',
        'code' => 'code',
        'status' => 'status'
    ];
    /**
     * An error object MAY have the following members
     * @link http://jsonapi.org/format/#error-objects
     */
    const ERROR_ALLOWED_MEMBERS = [
        'id', 'links', 'status', 'code', 'title', 'detail', 'source', 'meta'
    ];
    /**
     * @var integer the encoding options passed to [[Json::encode()]]. For more details please refer to
     * <http://www.php.net/manual/en/function.json-encode.php>.
     * Default is `JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE`.
     */
    public $encodeOptions = 320;
    /**
     * @var bool whether to format the output in a readable "pretty" format. This can be useful for debugging purpose.
     * If this is true, `JSON_PRETTY_PRINT` will be added to [[encodeOptions]].
     * Defaults to `false`.
     */
    public $prettyPrint = false;

    /**
     * Formats response data in JSON format.
     * @link http://jsonapi.org/format/upcoming/#document-structure
     * @param Response $response
     */
    public function format($response)
    {
        $response->getHeaders()->set('Content-Type', 'application/vnd.api+json; charset=UTF-8');
        if ($response->data !== null) {
            $options = $this->encodeOptions;
            if ($this->prettyPrint) {
                $options |= JSON_PRETTY_PRINT;
            }
            $apiDocument = $response->data;
            if ($response->isClientError || $response->isServerError) {
                if (ArrayHelper::isAssociative($response->data)) {
                    $response->data = [$response->data];
                }
                $formattedErrors = [];
                foreach ($response->data as $error) {
                    $formattedError = array_intersect_key($error, array_flip(static::ERROR_ALLOWED_MEMBERS));
                    foreach (static::ERROR_EXCEPTION_MAPPING as $member => $key) {
                        if (isset($error[$key])) {
                            $formattedError[$member] = (string) $error[$key];
                        }
                    }
                    if (!empty($formattedError)) {
                        $formattedErrors[] = $formattedError;
                    }
                }
                $apiDocument = ['errors' => $formattedErrors];
            }

            $response->content = Json::encode($apiDocument, $options);
        }
    }
}

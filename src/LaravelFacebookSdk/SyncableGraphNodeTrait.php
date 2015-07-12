<?php namespace SammyK\LaravelFacebookSdk;

use Illuminate\Database\Eloquent\Model;
use Facebook\GraphNodes\GraphObject;
use Facebook\GraphNodes\GraphNode;

trait SyncableGraphNodeTrait
{
    /*
     * List of Facebook field names and their corresponding
     * column names as they exist in the local database.
     *
     * protected static $graph_node_field_aliases = [];
     */

    /**
     * Inserts or updates the Graph node to the local database
     *
     * @param array|GraphObject|GraphNode $data
     *
     * @return Model
     *
     * @throws \InvalidArgumentException
     */
    public static function createOrUpdateGraphNode($data)
    {
        // @todo this will be GraphNode soon
        if ($data instanceof GraphObject || $data instanceof GraphNode) {
            $data = $data->asArray();
        }
        
        $data = static::removeIgnoreKeysFromFacebookData($data);
        $data = static::flattenFacebookDataArray($data);

        if (! isset($data['id'])) {
            throw new \InvalidArgumentException('Graph node id is missing');
        }

        $attributes = [static::getGraphNodeKeyName() => $data['id']];

        $graph_node = static::firstOrNewGraphNode($attributes);

        static::mapGraphNodeFieldNamesToDatabaseColumnNames($graph_node, $data);

        $graph_node->save();

        return $graph_node;
    }

    /**
     * Like static::firstOrNew() but without mass assignment
     *
     * @param array $attributes
     *
     * @return Model
     */
    public static function firstOrNewGraphNode(array $attributes)
    {
        if (is_null($facebook_object = static::firstOrNew($attributes))) {
            $facebook_object = new static();
        }

        return $facebook_object;
    }

    /**
     * Convert a Graph node field name to a database column name
     *
     * @param string $field
     *
     * @return string
     */
    public static function fieldToColumnName($field)
    {
        $model_name = get_class(new static());
        if (property_exists($model_name, 'graph_node_field_aliases')
            && isset(static::$graph_node_field_aliases[$field])) {
            return static::$graph_node_field_aliases[$field];
        }

        return $field;
    }

    /**
     * Get db column name of primary Graph node key
     *
     * @return string
     */
    public static function getGraphNodeKeyName()
    {
        return static::fieldToColumnName('id');
    }

    /**
     * Map Graph-node field names to local database column name
     *
     * @param Model $object
     * @param array $fields
     */
    public static function mapGraphNodeFieldNamesToDatabaseColumnNames(Model $object, array $fields)
    {
        foreach ($fields as $field => $value) {
            $object->{static::fieldToColumnName($field)} = $value;
        }
    }
    
    /**
     * Flattens an array of data from Graph with the path as the key
     *
     * @param array $data
     *
     * @return array
     */
    private static function flattenFacebookDataArray(array $data)
    {
        $query = http_build_query($data, null, '&');
        $params = explode('&', $query);
        $result = [];
        foreach ($params as $param) {
            list($key, $value) = explode('=', $param, 2);
            $result[urldecode($key)] = urldecode($value);
        }
        return $result;
    }



    /**
     * Removes any keys from Facebook that we want to ignore
     *
     * @param array $data
     *
     * @return array
     */
    private static function removeIgnoreKeysFromFacebookData(array $data)
    {
        $model_name = get_class(new static());
        if (property_exists($model_name, 'facebook_ignore_fields') && isset(static::$facebook_ignore_fields))
        {
            foreach (static::$facebook_ignore_fields as $key)
            {
                unset($data[$key]);
            }
        }
        return $data;
    }
    
    
}

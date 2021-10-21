<?php

namespace mix8872\yiiFiles\classes;

use yii\helpers\ArrayHelper;

class FilesCollection implements \ArrayAccess, \Iterator, \JsonSerializable, \Countable
{
    public $items;
    private $position = 0;

    /**
     * {@inheritdoc}
     */
    public function __construct($items)
    {
        $this->position = 0;
        $this->items = $items;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        $ids = ArrayHelper::getColumn($this->items, 'id');
        return implode(',', $ids);
    }

    /*
     * ArrayAccess functions definitions
     * */

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return isset($this->items[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        unset($this->items[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return $this->items[$offset] ?? null;
    }

    /*
     * Iterator functions definitions
     * */

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return $this->items[$this->position];
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        ++$this->position;
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return isset($this->items[$this->position]);
    }

    /*
     * JsonSerializable functions definitions
     * */

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return json_encode($this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->items);
    }
}

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
    public function offsetSet(mixed $offset, mixed $value): void
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
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset] ?? null;
    }

    /*
     * Iterator functions definitions
     * */

    /**
     * {@inheritdoc}
     */
    public function rewind(): void
    {
        $this->position = 0;
    }

    /**
     * {@inheritdoc}
     */
    public function current(): mixed
    {
        return $this->items[$this->position];
    }

    /**
     * {@inheritdoc}
     */
    public function key(): mixed
    {
        return $this->position;
    }

    /**
     * {@inheritdoc}
     */
    public function next(): void
    {
        ++$this->position;
    }

    /**
     * {@inheritdoc}
     */
    public function valid(): bool
    {
        return isset($this->items[$this->position]);
    }

    /*
     * JsonSerializable functions definitions
     * */

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): mixed
    {
        return json_encode($this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return count($this->items);
    }
}

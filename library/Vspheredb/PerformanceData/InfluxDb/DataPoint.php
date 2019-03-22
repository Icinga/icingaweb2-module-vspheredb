<?php

namespace Icinga\Module\Vspheredb\PerformanceData\InfluxDb;

class DataPoint
{
    const ESCAPE_TAG_CHARACTERS = ' ,=';

    const NULL = 'null';

    const TRUE = 'true';

    const FALSE = 'false';

    protected $timestamp;

    protected $measurement;

    protected $tags = [];

    protected $fields;

    public function __construct($measurement, $tags = [], $fields = [], $timestamp = null)
    {
        $this->measurement = (string) $measurement;
        if ($timestamp !== null) {
            $this->timestamp = $timestamp;
        }

        if (! empty($tags)) {
            $this->tags = (array) $tags;
        }

        if (\is_array($fields) || \is_object($fields)) {
            $this->fields = (array) $fields;
        } else {
            $this->fields = ['value' => $fields];
        }

        if (empty($this->fields)) {
            throw new \InvalidArgumentException('At least one field/value is required');
        }
    }

    protected function renderTags()
    {
        $tags = '';
        foreach ($this->tags as $key => $value) {
            $tags .= ','
                . \addcslashes($key, self::ESCAPE_TAG_CHARACTERS)
                . '='
                . \addcslashes($value, self::ESCAPE_TAG_CHARACTERS);
        }

        return $tags;
    }

    protected function renderFields()
    {
        $fields = '';
        foreach ($this->fields as $key => $value) {
            $fields .= ",$key="; // TODO: escape key

            if (\is_int($value) || \ctype_digit($value)) {
                $fields .= "${value}i";
            } elseif (\is_bool($value)) {
                $fields .= $value ? self::TRUE : self::FALSE;
            } elseif (\is_null($value)) {
                $fields .= self::NULL;
            } else {
                $fields .= '"' . \addcslashes($value, '"') . '"'; // TODO: escapeFieldValue
            }
        }
        $fields[0] = ' ';

        return $fields;
    }

    protected function renderTimeStamp()
    {
        if ($this->timestamp === null) {
            return '';
        } else {
            return ' ' . $this->timestamp;
        }
    }

    public function render()
    {
        return $this->measurement
            . $this->renderTags()
            . $this->renderFields()
            . $this->renderTimeStamp()
            . "\n";
    }

    public function __toString()
    {
        return $this->render();
    }
}

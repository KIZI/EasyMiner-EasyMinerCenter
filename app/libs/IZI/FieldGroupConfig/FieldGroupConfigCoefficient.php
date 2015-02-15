<?php

namespace IZI\FieldGroupConfig;

use IZI\Exception\InvalidCoefficientException;

class FieldGroupConfigCoefficient
{
    private $fieldRef;
    private $type;
    private $minimalLength;
    private $maximalLength;
    private $category;

    public function __construct($fieldRef, CoefficientType $type, $minimalLength, $maximalLength, $category, &$attributes, &$coefficients)
    {
        $this->fieldRef = $fieldRef;
        $this->type = $type;

        if ($this->type->getName() === 'One category') { // One category
            // category
            if (array_search($category, $attributes[$this->fieldRef]['choices']) === false) {
                throw new InvalidCoefficientException('Invalid coefficient category.');
            }
            $this->category = $category;
        } else { // other coefficients
            // min length - min value
            if ($coefficients[$this->type->getName()]['fields']['minLength']['minValueInclusive'] === true) { // inclusive
                if ($minimalLength < $coefficients[$this->type->getName()]['fields']['minLength']['minValue']) {
                    throw new InvalidCoefficientException('Invalid coefficient min length.');
                }
            } else { // not inclusive
                if ($minimalLength <= $coefficients[$this->type->getName()]['fields']['minLength']['minValue']) {
                    throw new InvalidCoefficientException('Invalid coefficient min length.');
                }
            }
            $this->minimalLength = $minimalLength;

            // min length - max value
            if ($coefficients[$this->type->getName()]['fields']['minLength']['maxValueInclusive'] === true) { // inclusive
                if ($minimalLength > $coefficients[$this->type->getName()]['fields']['minLength']['maxValue']) {
                    throw new InvalidCoefficientException('Invalid coefficient min length.');
                }
            } else { // not inclusive
                if ($minimalLength >= $coefficients[$this->type->getName()]['fields']['minLength']['maxValue']) {
                    throw new InvalidCoefficientException('Invalid coefficient min length.');
                }
            }

            //  max length - min value
            if ($coefficients[$this->type->getName()]['fields']['maxLength']['maxValueInclusive'] === true) { // inclusive
                if ($maximalLength < $coefficients[$this->type->getName()]['fields']['maxLength']['minValue']) {
                    throw new InvalidCoefficientException('Invalid coefficient max length.');
                }
            } else { // not inclusive
                if ($maximalLength <= $coefficients[$this->type->getName()]['fields']['maxLength']['minValue']) {
                    throw new InvalidCoefficientException('Invalid coefficient max length.');
                }
            }

            //  max length - max value
            if ($coefficients[$this->type->getName()]['fields']['maxLength']['maxValueInclusive'] === true) { // inclusive
                if ($maximalLength > $coefficients[$this->type->getName()]['fields']['maxLength']['maxValue']) {
                    throw new InvalidCoefficientException('Invalid coefficient max length.');
                }
            } else { // not inclusive
                if ($maximalLength >= $coefficients[$this->type->getName()]['fields']['maxLength']['maxValue']) {
                    throw new InvalidCoefficientException('Invalid coefficient max length.');
                }
            }
            $this->maximalLength = $maximalLength;
        }
    }

    public function toArray()
    {
        if ($this->type->getName() === 'One category') {
            $array = array($this->fieldRef => array('coefficient' => array('category' => $this->category)));
        } else {
            $array = array($this->fieldRef => array('coefficient' => array('minimalLength' => $this->minimalLength,
                                                                       'maximalLength' => $this->maximalLength)));
        }
        $array[$this->fieldRef]['coefficient'] = array_merge_recursive($array[$this->fieldRef]['coefficient'], $this->type->toArray());

        return $array;
    }

}
<?php

namespace Validator;

use DateTime;

class Validator
{
    /**
     * @var array
     */
    private $params;

    /**
     * @var ValidationError[]
     */
    private $errors = [];

    public function __construct($params)
    {
        $this->params = $params;
    }

    /**
     * Verify if many fields exists
     * Note, since version 1.4 a null field is considered as present and thus pass this test.
     * BREAKING CHANGE:
     * For version < 1.4 A 'null' field which was, before 1.4 considered as a error is now accepted
     * For version >= 1.4: a 'null' field is present, to require this field as non null use the notNull() method
     *
     * @param array $keys
     * @return $this
     */
    public function required(string ...$keys): self
    {
        foreach ($keys as $key) {
            try {
                $this->getValue($key, true);
            } catch (\Exception $e) {
                $this->addError($key, 'required');
            }
        }

        return $this;
    }

    /**
     * Verify if a present field has a non null value
     *
     * @param array $keys
     * @return $this
     */
    public function notNull(string ...$keys): self
    {
        foreach ($keys as $key) {
            $value = $this->getValue($key);
            if (is_null($value)) {
                $this->addError($key, 'notNull');
            }
        }

        return $this;
    }

    /**
     * Verify if many fields are not empty
     *
     * @param string ...$keys
     * @return $this
     */
    public function notEmpty(string ...$keys): self
    {
        foreach ($keys as $key) {
            $value = $this->getValue($key);
            if (!is_null($value)) {
                if (empty($value)) {
                    $this->addError($key, 'empty');
                }
            }
        }

        return $this;
    }

    /**
     * Verify if many fields exists and are not empty
     *
     * @param string ...$keys
     * @return $this
     */
    public function requiredAndNotEmpty(string...$keys): self
    {
        $this->required(...$keys);
        $this->notEmpty(...$keys);

        return $this;
    }

    /**
     * Verify if a field has the expected length
     *
     * @param $key
     * @param $min
     * @param null $max
     * @return $this
     */
    public function length(string $key, $min, $max = NULL): self
    {
        $value = $this->getValue($key);

        if (!empty($value)) {
            $length = mb_strlen($value);
            if (!is_null($min) && !is_null($max) && ($length < $min || $length > $max)) {
                $this->addError($key, 'betweenLength', [$min, $max]);
            }
            if (!is_null($min) && $length < $min) {
                $this->addError($key, 'minLength', [$min]);
            }
            if (!is_null($max) && $length > $max) {
                $this->addError($key, 'maxLength', [$max]);
            }
        }

        return $this;
    }

    /**
     * Verify if a field is a datetime
     *
     * @param $key
     * @param string $format
     * @return $this
     */
    public function dateTime($key, $format = 'Y-m-d H:i:s'): self
    {
        $value = $this->getValue($key);

        if (!empty($value)) {
            $date = DateTime::createFromFormat($format, $value);
            $errors = DateTime::getLastErrors();
            if ($errors['error_count'] > 0 || $errors['warning_count'] > 0 || $date == false) {
                $this->addError($key, 'datetime', [$format]);
            }
        }

        return $this;
    }

    /**
     * Verify if a field is a slug
     *
     * @param $key
     * @return $this
     */
    public function slug($key): self
    {
        $value = $this->getValue($key);

        if (!empty($value)) {
            $pattern = '/^([a-z0-9]+-?)+$/';
            if (is_null($value) || !preg_match($pattern, $this->params[$key])) {
                $this->addError($key, 'slug');
            }
        }
        return $this;
    }

    /**
     * Verify if an url field is valid (only http urls)
     *
     * @param $key
     * @return $this
     */
    public function url($key): self
    {
        $value = $this->getValue($key);

        if (!empty($value)) {
            $pattern = '|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i';
            if (is_null($value) || !preg_match($pattern, $this->params[$key])) {
                $this->addError($key, 'url');
            }
        }

        return $this;
    }

    /**
     * Verify if a key match to a expected value
     *
     * @param $key
     * @param $expected
     * @return $this
     */
    public function match($key, $expected): self
    {
        $value = $this->getValue($key);
        if (!empty($value) && !empty($expected)) {
            if ($value != $expected) {
                $this->addError($key, 'match', [$expected]);
            }
        }

        return $this;
    }

    /**
     * Verify if a key equal to a second key
     *
     * @param $key
     * @param $secondKey
     * @return $this
     */
    public function equal(string $key, string $secondKey): self
    {
        $value = $this->getValue($key);
        $value1 = $this->getValue($secondKey);
        if (!empty($value) && !empty($value1)) {
            if ($value != $value1) {
                $this->addError($key, 'notEqual', [$secondKey]);
            }
        }

        return $this;
    }

    /**
     * Verify if a key is an email
     *
     * @param $key
     * @return $this
     */
    public function email(string $key): self
    {
        $value = $this->getValue($key);
        if (!empty($value)) {
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $this->addError($key, 'email');
            }
        }

        return $this;
    }

    /**
     * Verify if a key is an array
     *
     * @param string[] $keys
     * @return $this
     */
    public function array(string ...$keys): self
    {
        foreach ($keys as $key) {
            $value = $this->getValue($key);
            if (!empty($value)) {
                if (!is_array($value)) {
                    $this->addError($key, 'array');
                }
            }
        }

        return $this;
    }

    /**
     * Verify if a key is a valid integer
     *
     * @param mixed ...$keys
     * @return $this
     */
    public function integer(...$keys): self
    {
        foreach ($keys as $key) {
            $value = $this->getValue($key);
            $pattern = '/^([0-9]+-?)+$/';

            if (!empty($value) && !is_null($value)) {
                if (!preg_match($pattern, $this->params[$key])) {
                    $this->addError($key, 'integer');
                }
            }
        }

        return $this;
    }

    /**
     * Verify if a key is a valid float
     *
     * @param mixed ...$keys
     * @return $this
     */
    public function float(...$keys): self
    {
        foreach ($keys as $key) {
            $value = $this->getValue($key);
            $pattern = '/^[0-9]{0,64}+.[0-9]{0,64}$/';

            if (!empty($value) && !is_null($value))
                if (!preg_match($pattern, strval($value)))
                    $this->addError($key, 'float');
        }

        return $this;
    }

    /**
     * Verify if a key is a valid boolean
     *
     * @param mixed ...$keys
     * @return $this
     */
    public function boolean(...$keys): self
    {
        foreach ($keys as $key) {
            $value = $this->getValue($key);
            if ($value !== '' && $value !== NULL)
                if (!(
                    ($value === false)
                    || ($value === true)
                    || ($value === 'false')
                    || ($value === 'true')
                    || ($value === 0)
                    || ($value === 1)
                    || ($value === '0')
                    || ($value === '1')
                ))
                    $this->addError($key, 'boolean');
        }

        return $this;
    }

    /**
     * Verify if a integer key is between a minimum and maximum value
     *
     * @param $key
     * @param $min
     * @param $max
     * @param bool $strict
     * @return $this
     */
    public function between(string $key, int $min, int $max, bool $strict = false): self
    {
        $value = $this->getValue($key);
        if (!empty($value) && is_int($value)) {
            if ($strict == true) {
                if ($value <= $min || $value >= $max) {
                    $this->addError($key, 'betweenStrict', [
                        $min,
                        $max
                    ]);
                }
            } else {
                if ($value < $min || $value > $max) {
                    $this->addError($key, 'between', [
                        $min,
                        $max
                    ]);
                }
            }
        }

        return $this;
    }

    /**
     * Verify if a string match a regex pattern
     *
     * @param string $key
     * @param string $pattern
     * @return $this
     */
    public function patternMatch(string $key, string $pattern): self
    {
        if (!empty($value = $this->getValue($key))) {
            if (is_null($value) || !preg_match($pattern, $value)) {
                $this->addError($key, 'patternMatch');
            }
        }
        return $this;
    }

    /**
     * Verify if a key is alpha numerical
     *
     * @param string[] $keys
     * @return $this
     */
    public function alphaNumerical(string ...$keys): self
    {
        foreach ($keys as $key) {
            if (!empty($value = $this->getValue($key))) {
                if (!ctype_alnum($value)) {
                    $this->addError($key, 'alphaNumerical');
                }
            }
        }
        return $this;
    }

    /**
     * Get errors
     *
     * @param string $format
     * @return ValidationError[]
     */
    public function getErrors(string $format = null): array
    {
        $errors = [];
        if ($format == null) {
            $format = ValidationError::getDefaultFormat();
        }
        foreach ($this->errors as $error) {
            if ($format === ValidationError::FORMAT_KEYS_WITH_MESSAGES) {
                $errors[$error->getKey() . '.' . $error->getRule()] = $error->__toString();
            } else if ($format === ValidationError::FORMAT_ARRAY) {
                $errors[] = ['code' => $error->getKey() . '.' . $error->getRule(), 'message' => $error->__toString()];
            } else {
                $errors[] = $error->__toString();
            }
        }
        return $errors;
    }

    /**
     * Return true if there are no errors and false if there are at least one error
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return empty($this->errors);
    }

    /**
     * Get the value of a given key in the init params
     *
     * @param string $key
     * @return mixed|null
     */
    public function getValue(string $key, bool $throwExceptionIfNotExists = false)
    {
        // if (empty($this->params)) {
        //     return null;
        // }
        $keys = array_map(
            function($str) {
                return substr($str, -1) === "]" ? substr($str, 0, strlen($str) - 1) : $str;
            },
            explode('[', $key)
        );
        $explored = $this->params;
        foreach ($keys as $nestedKey) {
            if (array_key_exists($nestedKey, $explored)) {
                $explored = $explored[$nestedKey];
            } else {
                if ($throwExceptionIfNotExists) {
                    throw new \Exception("Key not found");
                }
                return null;
            }
        }
        return $explored;
    }

    /**
     * Return if true or false a given key is defined
     *
     * @param string $key
     * @return bool
     */
    public function exists(string $key): bool
    {
        return !empty($this->params) && array_key_exists($key, $this->params);
    }

    /**
     * Add an error
     *
     * @param $key
     * @param $rule
     *
     * @param array $attributes
     * @return void
     */
    private function addError(string $key, string $rule, array $attributes = []): void
    {
        $this->errors[$key] = new ValidationError($key, $rule, $attributes);
    }

}

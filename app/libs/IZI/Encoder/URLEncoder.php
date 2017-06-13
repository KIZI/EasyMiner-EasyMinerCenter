<?php

namespace IZI\Encoder;

/**
 * Class URLEncoder
 * @package IZI\Encoder
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class URLEncoder
{

    public function encode($data) {
        $string = '';
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $string .= "{$key}=".urlencode($value).'&';
            }
        } else {
            $string .= urlencode($data);
        }

        return $string;
    }

}

<?php

namespace IZI\Encoder;

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

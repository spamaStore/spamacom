<?php
/**
 *   This file is part of Mobile Assistant Connector.
 *
 *   Mobile Assistant Connector is free software: you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation, either version 3 of the License, or
 *   (at your option) any later version.
 *
 *   Mobile Assistant Connector is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with Mobile Assistant Connector. If not, see <http://www.gnu.org/licenses/>.
 */

class BaseModelMobileassistantHelper extends Model
{
    public function nice_count($n, $format = false)
    {
        return $this->nice_price($n, '', false, true, $format);
    }

    public function nice_price($n, $code, $currency_value = false, $is_count = false, $format = true)
    {
        if ($is_count) {
            $n = (int)$n;
        } else {
            $n = (float)$n;
        }

        $final_number = str_replace(' ', '', trim($n));
        $suf          = '';

        if ($code == '') {
            $code = $this->config->get('config_currency');
        }

        $symbol_left   = $this->currency->getSymbolLeft($code);
        $symbol_right  = $this->currency->getSymbolRight($code);
        $decimal_place = $this->currency->getDecimalPlace($code);

        $thousand_point = ' ';
        if (is_object($this->language)) {
            $thousand_point = $this->language->get('thousand_point');
        }

        if (!$currency_value) {
            $currency_value = $this->currency->getValue($code);
        }

        if (!$is_count && isset($currency_value)) {
            $final_number = (float)$final_number * $currency_value;
        }

        if ($format) {
            if ($n >= 1000000000000000) {
                $decimal_place = 2;

                $final_number = ($n / 1000000000000000);
                $final_number = $this->my_currency_format($final_number);

                $suf = 'P';

            } elseif ($n >= 1000000000000) {
                $decimal_place = 2;

                $final_number = ($n / 1000000000000);
                $final_number = $this->my_currency_format($final_number);

                $suf = 'T';

            } elseif ($n >= 1000000000) {
                $decimal_place = 2;

                $final_number = ($n / 1000000000);
                $final_number = $this->my_currency_format($final_number);

                $suf = 'G';

            } elseif ($n >= 1000000) {
                $decimal_place = 2;

                $final_number = ($n / 1000000);
                $final_number = $this->my_currency_format($final_number);

                $suf = 'M';

            } elseif ($n >= 1000 && $is_count) {
                $final_number = number_format($n, 0, '', ' ');
            }
        }

        if ($is_count) {
            $final_number = $final_number . $suf;
        } elseif ($format) {
            $decimal_point = ',';
            if (is_object($this->language)) {
                $decimal_point = $this->language->get('decimal_point');
            }

            $final_number = number_format($final_number, (int)$decimal_place, $decimal_point, $thousand_point);

            if (isset($symbol_left)) {
                $final_number = $symbol_left . $final_number;
            }

            $final_number .= $suf;

            if (isset($symbol_right)) {
                $final_number .= $symbol_right;
            }

            $final_number = trim($final_number);
        }

        return $final_number;
    }


    public function my_currency_format($number, $precision = 2)
    {
        $pos = strrpos($number, '.');
        if ($pos !== false) {
            $number = substr($number, 0, $pos + 1 + $precision);
        }

        return $number;
    }

    public function _get_default_attrs()
    {
        $default_attrs = array();
        if (version_compare(Mobileassistant_helper::getCartVersion(), '3.0', '>=')) {
            $this->load->model('extension/mobileassistant/helper');
        } else {
            $this->load->model('mobileassistant/helper');
        }


        $this->load->model('localisation/language');
        $language = $this->model_localisation_language->getLanguage((int)$this->config->get('config_language_id'));

        $default_attrs['text_missing'] = 'Missing Orders';
        if (file_exists('./admin/language/' . $language['directory'] . '/sale/order.php')) {
            include './admin/language/' . $language['directory'] . '/sale/order.php';

            if (isset($_['text_missing'])) {
                $default_attrs['text_missing'] = $_['text_missing'];
            }
        }

        return $default_attrs;
    }


    public function write_log($message)
    {
        $file = DIR_LOGS . '/mobileassistant.log';
        $handle = fopen($file, 'a+');
        fwrite($handle, date('Y-m-d G:i:s') . ' - ' . print_r($message, true) . "\n");
        fclose($handle);
    }
}

class Modelmobileassistanthelper extends BaseModelMobileassistantHelper
{

}

class ModelExtensionMobileassistantHelper extends BaseModelMobileassistantHelper
{

}
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
    public function nice_count($n)
    {
        return $this->nice_price($n, '', true);
    }

    public function nice_price($n, $code, $is_count = false)
    {
        $n = (float)$n;

        $final_number = str_replace(' ', '', trim($n));
        $suf          = '';

        if ($n > 1000000000000000) {
            $final_number = round($n / 1000000000000000, 2);
            $suf          = 'P';

        } elseif ($n > 1000000000000) {
            $final_number = round($n / 1000000000000, 2);
            $suf          = 'T';

        } elseif ($n > 1000000000) {
            $final_number = round($n / 1000000000, 2);
            $suf          = 'G';

        } elseif ($n > 1000000) {
            $final_number = round($n / 1000000, 2);
            $suf          = 'M';

        } elseif ($n > 10000 && $is_count) {
            $final_number = number_format($n, 0, '', ' ');
        }

        if ($is_count) {
            $final_number = (int)$final_number . $suf;
        } else {
            $symbolLeft   = $this->currency->getSymbolLeft($code);
            $symbolRight  = $this->currency->getSymbolRight($code);
            $final_number = $this->currency->format($final_number, $code, '', false) . $suf;
            if (!empty($symbolLeft)) {
                $final_number = $symbolLeft . $final_number;
            }

            if (!empty($symbolRight)) {
                if (!empty($suf)) {
                    $final_number .= ' ';
                }
                $final_number .= $symbolRight;
            }
        }

        return $final_number;
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
        $file   = DIR_LOGS . '/mobileassistant.log';
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
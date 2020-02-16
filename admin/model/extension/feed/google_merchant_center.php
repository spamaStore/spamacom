<?php
class ModelExtensionFeedGoogleMerchantCenter extends Model {
	public function getBaseCategory() {
        if ($this->hasTable(DB_PREFIX."feed_manager_taxonomy") == 0) {
            return array();
        }
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "feed_manager_taxonomy` WHERE `name` NOT LIKE '%>%' ORDER BY `name` ASC");
		return $query->rows;
	}

	public function getTaxonomyCategory($category_id) {
        if ($this->hasTable(DB_PREFIX."feed_manager_taxonomy") == 0) {
            return array();
        }
		$taxonomyID="";
		if ($category_id!=""){
			$query = $this->db->query("SELECT taxonomy_id FROM `" . DB_PREFIX . "feed_manager_category` WHERE `category_id` LIKE '".$category_id."'");
			if ($query->row) {
				$taxonomyID = $query->row['taxonomy_id'];
			}
		}
        $base_taxonomy = $this->config->get('feed_google_merchant_center_base_taxonomy');
        $base_taxonomy_name = array();
        if (!empty($base_taxonomy)) {
            $query = $this->db->query("SELECT `name` FROM `".DB_PREFIX."feed_manager_taxonomy` WHERE `taxonomy_id` LIKE '".implode("' OR `taxonomy_id` LIKE '",$base_taxonomy)."';");
            foreach ($query->rows as $value) {
                $base_taxonomy_name[]=$value['name'];
            }
        }
        $query = $this->db->query("SELECT taxonomy_id, name, IF(taxonomy_id = '".$taxonomyID."', 1, 0) as status FROM ".DB_PREFIX."feed_manager_taxonomy WHERE ".(empty($base_taxonomy_name) ? "" : "(name LIKE '".implode("%' OR name LIKE '",$base_taxonomy_name)."%') AND ")."taxonomy_id NOT LIKE '0' ORDER BY name ASC;");
		return $query->rows;
	}

	public function saveSetting($data_base) {
        if ($this->hasTable(DB_PREFIX."feed_manager_taxonomy") == 0) {
            return;
        }
		$this->db->query("UPDATE `" . DB_PREFIX . "feed_manager_taxonomy` SET status = '0';");
		if (isset($data_base['google_merchant_base'])){
			foreach($data_base['google_merchant_base'] as $base) {
				$this->db->query("UPDATE `" . DB_PREFIX . "feed_manager_taxonomy` SET status = '1' WHERE `taxonomy_id` LIKE '".$base."';");
			}
		}
	}

	public function saveCategory($taxonomy_id,$category_id) {
        if ($this->hasTable(DB_PREFIX."feed_manager_category") == 0) {
            return;
        }
		$this->db->query("INSERT INTO `" . DB_PREFIX . "feed_manager_category` SET taxonomy_id = '".$taxonomy_id."', category_id = '".$category_id."' ON DUPLICATE KEY UPDATE taxonomy_id = '".$taxonomy_id."'");
	}

	public function removeCategory($category_id) {
        if ($this->hasTable(DB_PREFIX."feed_manager_category") == 0) {
            return;
        }
		$this->db->query("DELETE FROM `" . DB_PREFIX . "feed_manager_category` WHERE category_id LIKE '".$category_id."';");
	}

	public function saveProduct($product_id,$gender,$age_group,$color) {
        if ($this->hasTable(DB_PREFIX."feed_manager_product") == 0) {
            return;
        }
        $color = trim(str_replace("\t", "", $color));
		$this->db->query("INSERT INTO `" . DB_PREFIX . "feed_manager_product` SET product_id = '".$product_id."', gender = '".$gender."', age_group = '".$age_group."', color = '".$color."' ON DUPLICATE KEY UPDATE gender = '".$gender."', age_group = '".$age_group."', color = '".$color."'");
	}

	public function removeProduct($product_id) {
        if ($this->hasTable(DB_PREFIX."feed_manager_product") == 0) {
            return array();
        }
		$this->db->query("DELETE FROM `" . DB_PREFIX . "feed_manager_product` WHERE product_id LIKE '".$product_id."';");
	}

	public function getColorAgeGender($product_id) {
        if ($this->hasTable(DB_PREFIX."feed_manager_product") == 0) {
            $merchant_center['color'] = '';
            $merchant_center['age_group'] = 'adult';
            $merchant_center['gender'] = 'unisex';
            return $merchant_center;
        }
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "feed_manager_product` WHERE `product_id` LIKE '".$product_id."';");
		$merchant_center = array();

		if ($query->row){
			$merchant_center['color'] = $query->row['color'];
			$merchant_center['age_group'] = $query->row['age_group'];
			$merchant_center['gender'] = $query->row['gender'];
		} else {
			$merchant_center['color'] = '';
			$merchant_center['age_group'] = 'adult';
			$merchant_center['gender'] = 'unisex';
		}
		return $merchant_center;
	}

	public function getOptionID() {
		$query=$this->db->query("SELECT od.option_id AS option_id, od.name AS name
            FROM `".DB_PREFIX."option_description` AS od
            INNER JOIN `".DB_PREFIX."option_value_description` AS ovd
            ON ovd.option_id = od.option_id
            WHERE od.`language_id` LIKE '".(int)$this->config->get('config_language_id')."'
            GROUP BY ovd.option_id;");
		return $query->rows;
	}

	public function getAttributes() {
		$query=$this->db->query("SELECT attribute_id,name FROM `" . DB_PREFIX . "attribute_description` WHERE `language_id` LIKE '".(int)$this->config->get('config_language_id')."';");
		return $query->rows;
	}

    public function getLanguages() {
        $languages = array();
        $query = $this->db->query("SELECT `language_id`, `name`, `code` FROM `".DB_PREFIX."language` WHERE `status` LIKE '1' ORDER BY `language_id`;");
        foreach ($query->rows as $value) {
            $code = $value['code'];
            $languages[$code]=$value['name'].' ('.$code.')'.($code == $this->config->get('config_language') ? ' - Default' : '');
        }
		return $languages;
    }

    public function getCurrencies() {
        $currencies = array();
        $query = $this->db->query("SELECT `currency_id`, `title`, `code` FROM `".DB_PREFIX."currency` WHERE `status` LIKE '1' ORDER BY `currency_id`;");
        foreach ($query->rows as $value) {
            $code = $value['code'];
            $currencies[$code]=$value['title'].' ('.$code.')'.($code == $this->config->get('config_currency') ? ' - Default' : '');
        }
		return $currencies;
    }

    public function hasTable($tableName) {
        $query=$this->db->query("SHOW tables LIKE '".$tableName."';");
        return count($query->rows);
    }

    public function getSettingData($prefix, $name, $default_value = null)
    {
        $name = $prefix.$name;
        if (isset($this->request->post[$name])) {
            return $this->request->post[$name];
        } elseif ($this->config->get($name)!='') {
            return $this->config->get($name);
        }
        return $default_value;
    }

	public function createInputSetting($prefix, $name, $default_value)
	{
		$id = 'input-'.str_replace('_', '-', $prefix.$name);
		return '<div class="form-group">
			<label class="col-sm-2 control-label" for="'.$id.'"><span data-toggle="tooltip" title="'.$this->language->get('help_'.$name).'">'.$this->language->get('entry_'.$name).'</span></label>
			<div class="col-sm-10">
				<input type="text" name="'.$prefix.$name.'" value="'.$this->getSettingData($prefix, $name, $default_value).'" placeholder="'.$this->language->get('entry_'.$name).'" id="'.$id.'" class="form-control" />
			</div>
		</div>';
	}

	public function createTextareaSetting($prefix, $name, $default_value)
	{
		$id = 'input-'.str_replace('_', '-', $prefix.$name);
		return '<div class="form-group">
			<label class="col-sm-2 control-label" for="'.$id.'"><span data-toggle="tooltip" data-html="true" title="'.$this->language->get('help_'.$name).'">'.$this->language->get('entry_'.$name).'</span></label>
			<div class="col-sm-10">
				<textarea name="'.$prefix.$name.'" rows="5" placeholder="'.$this->language->get('entry_'.$name).'" id="'.$id.'" class="form-control">'.$this->getSettingData($prefix, $name, $default_value).'</textarea>
			</div>
		</div>';
	}

	public function createNumberSetting($prefix, $name, $default_value, $step = 1)
	{
		$id = 'input-'.str_replace('_', '-', $prefix.$name);
		return '<div class="form-group">
			<label class="col-sm-2 control-label" for="'.$id.'"><span data-toggle="tooltip" title="'.$this->language->get('help_'.$name).'">'.$this->language->get('entry_'.$name).'</span></label>
			<div class="col-sm-2">
				<input type="number" step="'.$step.'" name="'.$prefix.$name.'" value="'.$this->getSettingData($prefix, $name, $default_value).'" placeholder="'.$this->language->get('entry_'.$name).'" id="'.$id.'" class="form-control" />
			</div>
		</div>';
	}

	public function createCheckboxSetting($prefix, $name, $options, $default_value)
	{
		$id = 'input-'.str_replace('_', '-', $prefix.$name);
		$selected = $this->getSettingData($prefix, $name, $default_value);
		$html = '<div class="form-group">
			<label class="col-sm-2 control-label" for="'.$id.'"><span data-toggle="tooltip" data-html="true" title="'.$this->language->get('help_'.$name).'">'.$this->language->get('entry_'.$name).'</span></label>
			<div class="col-sm-10">
				<select name="'.$prefix.$name.'" id="'.$id.'" class="form-control">';
		foreach ($options as $key => $value) {
				$html .= '<option '.($selected == $key ? 'selected="selected" ' : ' ').'value="'.$key.'">'.$value.'</option>';
		}
		$html .='</select>
			</div>
		</div>';
		return $html;
	}

	public function createSettingsTitle($prefix, $name)
	{
		$html = '<legend>'.$this->language->get('text_'.$name).'</legend>';
		if ($this->language->get('help_'.$name) != '') {
				$html .= '<div class="alert alert-info"><i class="fa fa-info-circle"></i>'.$this->language->get('help_'.$name).'
					<button type="button" class="close" data-dismiss="alert">&times;</button>
				</div>';
		}
		return $html;
	}

	public function createMultiCheckboxSetting($prefix, $name, $options, $default_value = array())
	{
		$id = 'input-'.str_replace('_', '-', $prefix.$name);
        $selected = $this->getSettingData($prefix, $name, $default_value);
		$html =	'<div class="form-group">
				<label class="col-sm-2 control-label" for="'.$id.'"><span data-toggle="tooltip" data-html="true" title="'.$this->language->get('help_'.$name).'">'.$this->language->get('entry_'.$name).'</span></label>
				<div class="col-sm-10">
					<div class="well well-sm" style="height: 150px; overflow: auto;">';
					foreach ($options as $key => $value) {
						$html .= '<div class="checkbox switching"><label style="display: block;"><input type="checkbox" name="'.$prefix.$name.'[]" value="'.$key.'"'.(in_array($key , $selected) ? ' checked="checked"' : '' ).' />'.$value.'</label></div>';
					}
					$html .='</div>
				</div>
			</div>';
		return $html;
	}

    public function getImageSizes($store_id, $min_size = 500)
    {
        $query=$this->db->query("SELECT * FROM
    (SELECT SUBSTRING_INDEX(SUBSTR(`key`,LOCATE('image',`key`) + 6), '_', 1) AS `name`, SUBSTR(`key`, 1, LOCATE('image',`key`)-2) AS `theme`, GROUP_CONCAT(`value` ORDER BY `key` DESC SEPARATOR 'x') AS `wh`
    FROM `".DB_PREFIX."setting`
    WHERE `key` LIKE '%image%' AND (`key` LIKE '%height%' || `key` LIKE '%width%') AND `store_id` LIKE ".(int)$store_id." AND `value` >= ".(int)$min_size." GROUP BY `name` ORDER BY `name`
    ) AS `image_sizes`
    WHERE ROUND((LENGTH(`wh`)-LENGTH( REPLACE (`wh`, 'x', '')))/LENGTH('x')) LIKE 1;");
        return $query->rows;
    }
}
?>

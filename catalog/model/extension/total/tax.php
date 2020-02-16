<?php
class ModelExtensionTotalTax extends Model {
	public function getTotal($total) {
	    
	    	$taxtitle='VAT';
				if ($this->language->get('code')=='ar') { 
$taxtitle='الضريبة المضافة';
} 

		foreach ($total['taxes'] as $key => $value) {
			if ($value > 0) {
				$total['totals'][] = array(
					'code'       => 'tax',
				//	'title'      => $this->tax->getRateName($key) ."12",
					'title'      => $taxtitle,
					'value'      => $value,
					'sort_order' => $this->config->get('total_tax_sort_order')
				);

				$total['total'] += $value;
			}
		}
	}
}
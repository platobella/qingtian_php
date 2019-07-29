<?php


define('EXCEL_ROOT', dirname(__FILE__));

class Excel
{
	protected function column_str($key)
	{
		$array = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ', 'BA', 'BB', 'BC', 'BD', 'BE', 'BF', 'BG', 'BH', 'BI', 'BJ', 'BK', 'BL', 'BM', 'BN', 'BO', 'BP', 'BQ', 'BR', 'BS', 'BT', 'BU', 'BV', 'BW', 'BX', 'BY', 'BZ', 'CA', 'CB', 'CC', 'CD', 'CE', 'CF', 'CG', 'CH', 'CI', 'CJ', 'CK', 'CL', 'CM', 'CN', 'CO', 'CP', 'CQ', 'CR', 'CS', 'CT', 'CU', 'CV', 'CW', 'CX', 'CY', 'CZ', 'DA', 'DB', 'DC', 'DD', 'DE', 'DF', 'DG', 'DH', 'DI', 'DJ', 'DK', 'DL', 'DM', 'DN', 'DO', 'DP', 'DQ', 'DR', 'DS', 'DT', 'DU', 'DV', 'DW', 'DX', 'DY', 'DZ', 'EA', 'EB', 'EC', 'ED', 'EE', 'EF', 'EG', 'EH', 'EI', 'EJ', 'EK', 'EL', 'EM', 'EN', 'EO', 'EP', 'EQ', 'ER', 'ES', 'ET', 'EU', 'EV', 'EW', 'EX', 'EY', 'EZ');
		return $array[$key];
	}

	protected function column($key, $columnnum = 1)
	{
		return $this->column_str($key) . $columnnum;
	}

	/**
     * 导出Excel
     * @param type $list
     * @param type $params
     */
	public function export($list, $params = array())
	{
		if (PHP_SAPI == 'cli') {
			exit('This example should only be run from a Web Browser');
		}


		require_once EXCEL_ROOT . '/phpexcel/PHPExcel.php';
		$excel = new \PHPExcel();
		$excel->getProperties()->setCreator('人人商城')->setLastModifiedBy('人人商城')->setTitle('Office 2007 XLSX Test Document')->setSubject('Office 2007 XLSX Test Document')->setDescription('Test document for Office 2007 XLSX, generated using PHP classes.')->setKeywords('office 2007 openxml php')->setCategory('report file');
		$sheet = $excel->setActiveSheetIndex(0);
		$rownum = 1;

		foreach ($params['columns'] as $key => $column ) {
			$sheet->setCellValue($this->column($key, $rownum), $column['title']);

			if (!(empty($column['width']))) {
				$sheet->getColumnDimension($this->column_str($key))->setWidth($column['width']);
			}

		}

		++$rownum;
		$len = count($params['columns']);

		foreach ($list as $row ) {
			$i = 0;

			while ($i < $len) {
				$value = ((isset($row[$params['columns'][$i]['field']]) ? $row[$params['columns'][$i]['field']] : ''));
				$sheet->setCellValue($this->column($i, $rownum), $value);
				++$i;
			}

			++$rownum;
		}

		$excel->getActiveSheet()->setTitle($params['title']);
		$filename = urlencode($params['title'] . '-' . date('Y-m-d H:i', time()));
		ob_end_clean();
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"');
		header('Cache-Control: max-age=0');
		$writer = \PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
		$this->SaveViaTempFile($writer);
		exit();
	}

	/**
     * @param $objWriter PHPExcel_Writer_IWriter
     */
	public function SaveViaTempFile($objWriter)
	{
		$filePath = '' . rand(0, getrandmax()) . rand(0, getrandmax()) . '.tmp';
		$objWriter->save($filePath);
		readfile($filePath);
		unlink($filePath);
	}

	/**
     * 生成模板文件Excel
     * @param type $list
     * @param type $params
     */
	public function temp($title, $columns = array())
	{
		if (PHP_SAPI == 'cli') {
			exit('This example should only be run from a Web Browser');
		}


		require_once EXCEL_ROOT . '/phpexcel/PHPExcel.php';
		$excel = new \PHPExcel();
		$excel->getProperties()->setCreator('人人商城')->setLastModifiedBy('人人商城')->setTitle('Office 2007 XLSX Test Document')->setSubject('Office 2007 XLSX Test Document')->setDescription('Test document for Office 2007 XLSX, generated using PHP classes.')->setKeywords('office 2007 openxml php')->setCategory('report file');
		$sheet = $excel->setActiveSheetIndex(0);
		$rownum = 1;

		foreach ($columns as $key => $column ) {
			$sheet->setCellValue($this->column($key, $rownum), $column['title']);

			if (!(empty($column['width']))) {
				$sheet->getColumnDimension($this->column_str($key))->setWidth($column['width']);
			}

		}

		++$rownum;
		$len = count($columns);
		$k = 1;

		while ($k <= 5000) {
			$i = 0;

			while ($i < $len) {
				$sheet->setCellValue($this->column($i, $rownum), '');
				++$i;
			}

			++$rownum;
			++$k;
		}

		$excel->getActiveSheet()->setTitle($title);
		$filename = urlencode($title);
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
		header('Cache-Control: max-age=0');
		$writer = PHPExcel_IOFactory::createWriter($excel, 'Excel5');
		$writer->save('php://output');
		exit();
	}

}


?>
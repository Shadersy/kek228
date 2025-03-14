<?php


namespace App\Service;


use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Font;

class ExcelService
{
    public function generateExcelReport(Forms $form, $data, $fileName) {
        $from = $form->getData()['created_from']->format('Y-m-d');
        $to = $form->getData()['created_to']->format('Y-m-d');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle("Отчёт");

        $sheet = $this->setHeaderForTable(
            $sheet,
            $from,
            $to
        );

        $startRow =  7;
        foreach($data as $ticket) {
            $currentRow = $startRow++;
            $sheet->setCellValueByColumnAndRow(5, $currentRow, $ticket->getId());
            $sheet->setCellValueByColumnAndRow(6, $currentRow, $ticket->getStatus());
            switch ( $ticket->getImportance()) {
                case  1  : $sheet->setCellValueByColumnAndRow(7, $currentRow, 'Низкий');
                break;
                case  2  : $sheet->setCellValueByColumnAndRow(7, $currentRow, 'Средний');
                    break;
                case  3  : $sheet->setCellValueByColumnAndRow(7, $currentRow, 'Высокий');
                    break;
            }

            $sheet->setCellValueByColumnAndRow(8, $currentRow, $ticket->getSender()->getLogin());
            $sheet->setCellValueByColumnAndRow(9, $currentRow, $ticket->getCreatedOn());
            $sheet->setCellValueByColumnAndRow(10, $currentRow,  $ticket->getDeadline() ?? 'Не указан');

        }

        $writer = new Xlsx($spreadsheet);
        $temp_file = tempnam(sys_get_temp_dir(), $fileName);

        $writer->save($temp_file);

        return $temp_file;
    }

    private function setHeaderForTable(
        $sheet,
        $from,
        $to
    )
    {
        $sheet->setCellValueByColumnAndRow(1, 1, 'Текущая дата:');
        $this->makeBoldText($sheet, 1, 1);
        $sheet->setCellValueByColumnAndRow(1, 2, 'Период:');
        $this->makeBoldText($sheet, 1, 2);
        $currentDate = new \DateTime();
        $currentDate =  $currentDate->format('Y-m-d');
        $sheet->setCellValueByColumnAndRow(2, 1, $currentDate);
        $sheet->setCellValueByColumnAndRow(2, 2, $from . '-' . $to);

        $sheet->setCellValueByColumnAndRow(5, 6, 'Номер заявки');
        $this->makeBoldText($sheet, 5, 6);
        $sheet->setCellValueByColumnAndRow(6, 6, 'Статус');
        $this->makeBoldText($sheet, 6, 6);
        $sheet->setCellValueByColumnAndRow(7, 6, 'Приоритет');
        $this->makeBoldText($sheet, 7, 6);
        $sheet->setCellValueByColumnAndRow(8, 6, 'Автор');
        $this->makeBoldText($sheet, 8, 6);
        $sheet->setCellValueByColumnAndRow(9, 6, 'Дата создания');
        $this->makeBoldText($sheet, 9, 6);
        $sheet->setCellValueByColumnAndRow(10, 6, 'Срок');
        $this->makeBoldText($sheet, 10, 6);

        return $sheet;
    }

    private function makeBoldText($objPHPExcel, $column, $row)
    {
        $cell = $objPHPExcel->getCellByColumnAndRow($column, $row)->getCoordinate();
        $objPHPExcel->getStyle($cell)->getFont()->setBold(true);
    }
}
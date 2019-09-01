<?php
require('./fpdf/fpdf.php');

    class PDF_MySQL_Table extends FPDF
    {
        protected $ProcessingTable=false;
        protected $aCols;
        protected $TableX;
        protected $HeaderColor;
        protected $RowColors;
        protected $ColorIndex;

        // permissible width of each column
        protected $colMaxWidths;


        function Header()
        {
            // Print the table header if necessary
            if($this->ProcessingTable)
                $this->TableHeader();
        }

        function TableHeader()
        {
            $this->SetFont('Arial', 'B', 8);
            $this->SetX($this->TableX);
            $fill = !empty($this->HeaderColor);
            $this->setTextColor(255, 255, 255);

            if($fill) {
                $this->SetFillColor($this->HeaderColor[0], $this->HeaderColor[1], $this->HeaderColor[2]);                
            }

            foreach($this->aCols as $col) {
                $this->Cell($col['w'], 6, $col['c'], 1, 0, 'C', $fill);
            }

            $this->Ln();
        }

        function Row($data, $idx)
        {
            $this->setTextColor(0, 0, 0);
            $this->SetX($this->TableX);
            $ci = $this->ColorIndex;
            $fill =! empty($this->RowColors[$ci]);

            if($fill) {
                $this->SetFillColor($this->RowColors[$ci][0], $this->RowColors[$ci][1], $this->RowColors[$ci][2]);
            }

            // stores the max height of the row. Default is 5
            $maxRowHeight = 0;

            $i = 0;
            foreach($this->aCols as $col) {
                if($this->GetStringWidth($data[$col['f']]) <= $this->colMaxWidths[$i]) {
                    $maxRowHeight = 1;
                }

                else {
                    $maxRowHeight =-1;
                    break;
                }

                $i++;
            }

            $piece = array();

            if($maxRowHeight == -1) {

                $colNo = 0;
                foreach($this->aCols as $col) {

                    $myStr = $data[$col['f']];
                    $pieces = array();
                    $leftWidthTemp = $this->colMaxWidths[$colNo];
                    $presStr = "";

                    for($currChar = 0; $currChar < strlen($myStr); $currChar++) {

                        if($this->GetStringWidth($myStr[$currChar]) < ($leftWidthTemp - 1.5)) {
                            $presStr = $presStr . $myStr[$currChar];
                            $leftWidthTemp -= ($this->GetStringWidth($myStr[$currChar]));
                        }

                        else {
                            array_push($pieces, $presStr);
                            $presStr = "";
                            $leftWidthTemp = $this->colMaxWidths[$colNo];
                            $currChar--;
                        }

                    }

                    if(strlen($presStr) > 0) {
                        array_push($pieces, $presStr);
                        $presStr = "";
                    }

                    array_push($piece, $pieces);
                    $colNo++;
                }

                $maxRowHeight = 1;
                foreach($piece as $pie) {
                    $maxRowHeight = max($maxRowHeight, count($pie));
                }

                $presCol = 0;
                foreach($this->aCols as $col) {

                    $xx = $this->getX();
                    $yy = $this->getY();

                    $this->Cell($col['w'], $maxRowHeight * 5, '', 1, 0, $col['a'], $fill);

                    $nameSplit = $piece[$presCol];

                    $ii = 0;
                    foreach($nameSplit as $dataText) {
                        $this->setXY($xx, $yy + $ii);
                        $this->Cell($col['w'], 5, $dataText, 0, 0, $col['a'], $fill);
                        $ii+=5;
                    }

                    $this->setXY($xx + $col['w'], $yy);

                    $presCol++;
                }
                $this->setY($yy + $maxRowHeight *5);

            }

            else {
                foreach($this->aCols as $col) {
                    $this->Cell($col['w'], 5, $data[$col['f']], 1, 0, $col['a'], $fill);
                }
                $this->Ln();
            }

            $this->ColorIndex = 1 - $ci;
        }


        function CalcWidth($pageTotalWidth, $resInit) {

            $noOfColumns = count($this->aCols);

            // permissible width of each column
            $this->colMaxWidths = array_fill(0, $noOfColumns, 0);
            // permissible length of each column
            //$this->colMaxLength = array_fill(0, $noOfColumns, 0);

            // add the padding over here
            while($row = mysqli_fetch_array($resInit)) {
                $colI = 0;
                foreach($this->aCols as $col) {
                    if($this->colMaxWidths[$colI] < ($this->GetStringWidth($row[$col['f']]) + 2)) {
                        //$this->colMaxLength[$colI] = strlen($row[$col['f']]);
                        $this->colMaxWidths[$colI] = $this->GetStringWidth($row[$col['f']]) + 2;
                    }
                    $colI++;
                }
            }

            // max possible length row present in the table
            $maxColLength = 0;

            // max possible length row present in the table
            $totalWidthRequired = 0;

            for($i = 0; $i < $noOfColumns; $i++) {
                $totalWidthRequired += $this->colMaxWidths[$i];
            }

            // if max no of chars required <= no of chars possible
            if($totalWidthRequired <= $pageTotalWidth) {

                $leftoverPerColumn = max(0, ($pageTotalWidth - $totalWidthRequired) / $noOfColumns);

                $i = 0;
                foreach($this->aCols as $i => $col) {
                    $this->colMaxWidths[$i] = $this->colMaxWidths[$i] + $leftoverPerColumn;
                    $this->aCols[$i]['w'] = $this->colMaxWidths[$i];
                    $i++;
                }

            }

            // else divide the widths in proportion
            else {
                for($i = 0; $i < $noOfColumns; $i++) {
                    $this->aCols[$i]['w'] = ($this->colMaxWidths[$i] * $pageTotalWidth) / $totalWidthRequired;
                    $this->colMaxWidths[$i] = $this->aCols[$i]['w'];
                }
            }

            $this->TableX = $this->lMargin;
        }

        function AddCol($field = -1, $width = -1, $caption = '', $align =' L')
        {
            // Add a column to the table
            if($field == -1)
                $field = count($this->aCols);
            $this->aCols[] = array('f' => $field, 'c' => $caption, 'w' => $width, 'a' => $align);
        }

        function Table($link, $query, $prop = array())
        {
            // Execute query : Error message to be changed
            $res = mysqli_query($link, $query) or die('Error: ' . mysqli_error($link) . "<br>Sorry Query: $query" . "<br>");

            // Add all columns if none was specified
            if(count($this->aCols) == 0)
            {
                $nb = mysqli_num_fields($res);
                for ($i = 0; $i < $nb; $i++)
                    $this->AddCol();
            }

            // Retrieve column names when not specified
            foreach($this->aCols as $i => $col)
            {
                if($col['c'] == '')
                {
                    if(is_string($col['f']))
                        $this->aCols[$i]['c'] = ucfirst($col['f']);
                    else
                        $this->aCols[$i]['c'] = ucfirst(mysqli_fetch_field_direct($res, $col['f'])->name);
                }
            }

            // Handle properties
            if(!isset($prop['width']))
                $prop['width'] = 0;

            // default val of page width = 276.99
            if($prop['width'] == 0) {
                $prop['width'] = $this->w - $this->lMargin - $this->rMargin;
            }

            if(!isset($prop['align']))
                $prop['align'] = 'C';

            if(!isset($prop['padding']))
                $prop['padding'] = $this->cMargin;

            $cMargin = $this->cMargin;

            $this->cMargin = $prop['padding'];

            if(!isset($prop['HeaderColor']))
                $prop['HeaderColor'] = array();

            $this->HeaderColor = $prop['HeaderColor'];

            if(!isset($prop['color1']))
                $prop['color1'] = array();

            if(!isset($prop['color2']))
                $prop['color2'] = array();

            $this->RowColors = array($prop['color1'], $prop['color2']);

            $this->SetFont('Arial', '', 8);

            // for computing dynamic column widths
            $resForWidthCalc = mysqli_query($link, $query) or die('Error: '.mysqli_error($link)."<br>Sorry Query: $query");
            $this->CalcWidth($prop['width'], $resForWidthCalc);

            // Print header
            $this->TableHeader();

            $this->SetFont('Arial', '', 8);

            $this->ColorIndex = 0;
            $this->ProcessingTable = true;

            $idx = 0;
            while($row = mysqli_fetch_array($res)) {
                $this->Row($row, $idx);
                ++$idx;
            }

            $this->ProcessingTable = false;
            $this->cMargin = $cMargin;
            $this->aCols = array();
        }
    }

?>
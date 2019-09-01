<!DOCTYPE html>
<html>

    <head>
        <title>Sample Table</title>
    </head>

    <body>

        <?php
            require('mysql_table.php');

            ob_start();

            class PDF extends PDF_MySQL_Table
            {
                function Header()
                {
                    $this->SetFont('Arial','',16);
                    $this->setTextColor(34,139,34);
                    $this->Cell(0, 6, 'Details (Page No: ' . $this->PageNo() . ')', 0, 1, 'C');
                    $this->Ln(10);
                    parent::Header();
                }
            }

            $link = mysqli_connect('127.0.0.1', 'root', '', 'test');

            $pdf = new PDF('L');

            // 1st table
            $pdf->AddPage();

            $prop = array(
                'HeaderColor'=>array(34,139,34),
                'color1'=>array(255, 255, 255),
                'color2'=>array(230, 230, 230),
            );

            $pdf->Table($link, 'SELECT * FROM students_mini', $prop);

            // 2nd table
            $pdf->AddPage();

            $prop = array(
                'HeaderColor'=>array(34,139,34),
                //'color1'=>array(255, 255, 255),
                //'color2'=>array(230, 230, 230),
            );

            $pdf->Table($link,'SELECT id, name, gName, bcs, ccs, col, cdc7, cwp, uri, ima, aab, cdc10, mpm1, mpm2, name, name, gName, mpm3 FROM students_long', $prop);

            // 3rd table
            $pdf->AddPage();

            $prop = array(
                'HeaderColor'=>array(34,139,34),
                //'color1'=>array(255, 255, 255),
                //'color2'=>array(230, 230, 230),
            );

            $pdf->Table($link,'SELECT id, id, id, id, id, id, id, id, id, id, id, id, id, id, id, id, id, id, id, id, id, id, id, id, id, id, id, id, id, id, id, id, id, id, id, id, id, id, id, id, id, id, id, id, id, id, id, id, id, id, id, id, id, id, id, id, id, id, id, id, id, id, id, id, id, id FROM students_long', $prop);


            $pdf->Output('I');

            ob_end_flush();
        ?>

    </body>

</html>